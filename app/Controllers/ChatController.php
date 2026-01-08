<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SolicitacaoMensagem;
use App\Models\Solicitacao;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;

class ChatController extends Controller
{
    private SolicitacaoMensagem $mensagemModel;
    private Solicitacao $solicitacaoModel;
    private WhatsappInstance $whatsappInstanceModel;

    public function __construct()
    {
        $this->mensagemModel = new SolicitacaoMensagem();
        $this->solicitacaoModel = new Solicitacao();
        $this->whatsappInstanceModel = new WhatsappInstance();
    }

    /**
     * Busca mensagens de uma solicitação
     * GET /admin/chat/{solicitacao_id}/mensagens
     */
    public function getMensagens(int $solicitacaoId): void
    {
        $this->requireAdmin();

        // Buscar solicitação com SQL direto para garantir que os campos de chat sejam retornados
        $sql = "
            SELECT id, chat_whatsapp_instance_id, chat_atendimento_ativo, 
                   chat_atendimento_iniciado_em, chat_atendimento_encerrado_em
            FROM solicitacoes 
            WHERE id = ?
        ";
        $solicitacao = \App\Core\Database::fetch($sql, [$solicitacaoId]);
        
        if (!$solicitacao) {
            $this->json([
                'success' => false,
                'message' => 'Solicitação não encontrada'
            ], 404);
            return;
        }

        $mensagens = $this->mensagemModel->getBySolicitacao($solicitacaoId);
        
        // Contar mensagens não lidas ANTES de marcar como lidas
        $naoLidas = $this->mensagemModel->countNaoLidas($solicitacaoId);
        
        // Marcar mensagens recebidas como lidas apenas quando o chat é aberto
        // Isso garante que o contador apareça até que o chat seja visualizado
        $this->mensagemModel->marcarComoLidas($solicitacaoId);
        
        // Debug: Log dos valores
        error_log("🔍 getMensagens - Solicitação: $solicitacaoId");
        error_log("   instanceId: " . ($solicitacao['chat_whatsapp_instance_id'] ?? 'null'));
        error_log("   atendimentoAtivo: " . ($solicitacao['chat_atendimento_ativo'] ?? 'null'));
        error_log("   mensagensNaoLidas: $naoLidas");

        $this->json([
            'success' => true,
            'mensagens' => $mensagens,
            'mensagens_nao_lidas_antes' => $naoLidas,
            'solicitacao' => [
                'id' => $solicitacao['id'],
                'protocol' => '',
                'cliente_nome' => '',
                'cliente_telefone' => '',
                'cliente_whatsapp' => '',
                'chat_whatsapp_instance_id' => $solicitacao['chat_whatsapp_instance_id'] ?? null,
                'chat_atendimento_ativo' => (bool)($solicitacao['chat_atendimento_ativo'] ?? false)
            ]
        ]);
    }

    /**
     * Envia mensagem via WhatsApp
     * POST /admin/chat/{solicitacao_id}/enviar
     */
    public function enviarMensagem(int $solicitacaoId): void
    {
        $this->requireAdmin();

        $solicitacao = $this->solicitacaoModel->find($solicitacaoId);
        if (!$solicitacao) {
            $this->json([
                'success' => false,
                'message' => 'Solicitação não encontrada'
            ], 404);
            return;
        }

        $mensagem = $_POST['mensagem'] ?? '';
        $instanceId = $_POST['whatsapp_instance_id'] ?? null;

        if (empty($mensagem)) {
            $this->json([
                'success' => false,
                'message' => 'Mensagem não pode estar vazia'
            ], 400);
            return;
        }

        // Verificar se já existe uma instância definida para esta solicitação
        $instanciaJaDefinida = !empty($solicitacao['chat_whatsapp_instance_id']);
        $atendimentoAtivo = (bool)($solicitacao['chat_atendimento_ativo'] ?? false);
        
        // Se já existe instância definida e atendimento ativo, usar essa instância
        if ($instanciaJaDefinida && $atendimentoAtivo) {
            $instanceId = $solicitacao['chat_whatsapp_instance_id'];
        } elseif ($instanciaJaDefinida && !$atendimentoAtivo && $instanceId) {
            // Se a instância está definida mas o atendimento foi encerrado E uma nova instância foi fornecida
            // Reiniciar atendimento com a nova instância (ou mesma se for a mesma)
            $this->iniciarAtendimento($solicitacaoId, $instanceId);
        } elseif ($instanciaJaDefinida && !$atendimentoAtivo && !$instanceId) {
            // Se a instância está definida mas o atendimento foi encerrado E nenhuma instância foi fornecida
            $this->json([
                'success' => false,
                'message' => 'O atendimento foi encerrado. Selecione uma instância para iniciar um novo atendimento.'
            ], 400);
            return;
        } elseif (!$instanciaJaDefinida && $instanceId) {
            // Primeira vez selecionando instância - iniciar atendimento
            $this->iniciarAtendimento($solicitacaoId, $instanceId);
        } elseif (!$instanciaJaDefinida && !$instanceId) {
            $this->json([
                'success' => false,
                'message' => 'Selecione uma instância WhatsApp para iniciar o atendimento'
            ], 400);
            return;
        }

        // Buscar instância WhatsApp
        $whatsappInstance = $this->whatsappInstanceModel->find($instanceId);
        
        if (!$whatsappInstance || $whatsappInstance['status'] !== 'CONECTADO') {
            $this->json([
                'success' => false,
                'message' => 'Instância WhatsApp não disponível ou desconectada'
            ], 400);
            return;
        }
        
        // Verificar se a instância está sendo usada em outra solicitação ativa
        if ($this->instanciaEmUso($instanceId, $solicitacaoId)) {
            $this->json([
                'success' => false,
                'message' => 'Esta instância está sendo usada em outro atendimento ativo'
            ], 400);
            return;
        }

        // Número fixo para envio de mensagens: 16997360690
        $numeroDestinatario = '16997360690';

        // Formatar número para Evolution API
        $numeroFormatado = $this->formatWhatsAppNumber($numeroDestinatario);

        // Criar registro de mensagem (status: ENVIANDO)
        $mensagemData = [
            'solicitacao_id' => $solicitacaoId,
            'whatsapp_instance_id' => $whatsappInstance['id'],
            'instance_name' => $whatsappInstance['instance_name'],
            'numero_remetente' => $whatsappInstance['numero_whatsapp'] ?? 'ADMIN',
            'numero_destinatario' => $numeroDestinatario,
            'mensagem' => $mensagem,
            'tipo' => 'ENVIADA',
            'status' => 'ENVIANDO',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $mensagemId = $this->mensagemModel->create($mensagemData);

        // Enviar via Evolution API
        try {
            $evolutionService = new EvolutionApiService(
                $whatsappInstance['api_url'],
                $whatsappInstance['api_key'],
                $whatsappInstance['token']
            );

            $response = $evolutionService->sendMessage(
                $whatsappInstance['instance_name'],
                $numeroFormatado,
                $mensagem
            );

            // Atualizar mensagem com sucesso
            $updateData = [
                'status' => 'ENVIADA',
                'message_id' => $response['key']['id'] ?? null,
                'metadata' => json_encode($response),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->mensagemModel->update($mensagemId, $updateData);
            
            // Atualizar updated_at da solicitação para que o card vá para o topo do Kanban
            $this->solicitacaoModel->update($solicitacaoId, []);

            $this->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'data' => [
                    'id' => $mensagemId,
                    'message_id' => $response['key']['id'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            // Atualizar mensagem com erro
            $this->mensagemModel->update($mensagemId, [
                'status' => 'ERRO',
                'erro' => $e->getMessage(),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Atualizar updated_at da solicitação mesmo em caso de erro (houve tentativa de interação)
            $this->solicitacaoModel->update($solicitacaoId, []);

            $this->json([
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook para receber mensagens da Evolution API
     * POST /webhook/whatsapp
     */
    public function webhook(): void
    {
        // Ler o body completo antes de qualquer processamento
        $rawBody = file_get_contents('php://input');
        
        // Log do webhook recebido
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'headers' => getallheaders() ?? [],
            'body_raw' => $rawBody,
            'body_length' => strlen($rawBody)
        ];
        
        // Salvar em log exclusivo de webhook
        $this->writeWebhookLog('WEBHOOK RECEBIDO', $logData);
        
        // Também logar no error_log padrão
        error_log('🔔 WEBHOOK RECEBIDO: ' . json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Log importante: verificar se o método é POST
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->writeWebhookLog('AVISO: MÉTODO NÃO É POST', ['method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN']);
            error_log("⚠️ AVISO: Webhook recebido com método diferente de POST: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
        }

        $payload = json_decode($rawBody, true);

        if (!$payload) {
            $jsonError = json_last_error_msg();
            $this->writeWebhookLog('ERRO JSON', [
                'error' => $jsonError,
                'body_preview' => substr($rawBody, 0, 500),
                'body_length' => strlen($rawBody)
            ]);
            error_log("❌ Erro ao decodificar JSON: $jsonError | Body: " . substr($rawBody, 0, 500));
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload', 'json_error' => $jsonError]);
            return;
        }

        // Log do payload decodificado
        $this->writeWebhookLog('PAYLOAD DECODIFICADO', ['payload' => $payload]);
        error_log('📦 PAYLOAD DECODIFICADO: ' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Evolution API pode enviar eventos de diferentes formas
        // Verificar diferentes estruturas possíveis
        $event = $payload['event'] ?? $payload['type'] ?? null;
        $this->writeWebhookLog('EVENTO DETECTADO', ['event' => $event ?? 'NENHUM']);
        error_log("🔍 Evento detectado: " . ($event ?? 'NENHUM'));

        // Processar diferentes tipos de eventos
        // Verificar se é evento de mensagem (pode vir como messages.upsert, MESSAGES_UPSERT, ou com data.messages)
        $isMessageEvent = false;
        $messageEventType = null;
        
        if ($event === 'messages.upsert' || $event === 'MESSAGES_UPSERT' || isset($payload['data']['messages'])) {
            $isMessageEvent = true;
            $messageEventType = 'messages.upsert';
        } elseif ($event === 'messages.update' || $event === 'MESSAGES_UPDATE' || isset($payload['data']['key'])) {
            $isMessageEvent = true;
            $messageEventType = 'messages.update';
        }
        
        if ($isMessageEvent) {
            $this->writeWebhookLog('PROCESSANDO MENSAGEM', [
                'event' => $messageEventType, 
                'event_original' => $event,
                'has_data_messages' => isset($payload['data']['messages']),
                'has_data_key' => isset($payload['data']['key']),
                'payload_keys' => array_keys($payload)
            ]);
            error_log("✅ Processando mensagem recebida - Evento: $messageEventType (original: $event)");
            
            if ($messageEventType === 'messages.upsert') {
                $this->processarMensagemRecebida($payload);
            } else {
                $this->processarAtualizacaoMensagem($payload);
            }
        } else {
            // Eventos que não são mensagens (contacts.update, connection.update, etc) são ignorados intencionalmente
            $this->writeWebhookLog('EVENTO IGNORADO (NÃO É MENSAGEM)', [
                'event' => $event, 
                'tipo' => 'Evento de sistema (contato, conexão, etc) - não requer processamento',
                'payload_keys' => array_keys($payload),
                'payload' => $payload
            ]);
            error_log("ℹ️ Evento ignorado (não é mensagem): $event - Este tipo de evento não requer processamento");
        }

        // Sempre retornar 200 para Evolution API
        $this->writeWebhookLog('RESPOSTA ENVIADA', ['status' => 200, 'response' => ['success' => true]]);
        http_response_code(200);
        echo json_encode(['success' => true]);
    }
    
    /**
     * Escreve log exclusivo para webhook
     */
    private function writeWebhookLog(string $tipo, array $data): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        
        // Criar diretório se não existir
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/webhook_whatsapp.log';
        
        // Formatar linha de log
        $timestamp = date('Y-m-d H:i:s');
        $logLine = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            strtoupper($tipo),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        
        $logLine .= str_repeat('-', 100) . "\n";
        
        // Escrever no arquivo
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Processa mensagem recebida do webhook
     */
    private function processarMensagemRecebida(array $payload): void
    {
        try {
            $this->writeWebhookLog('INICIANDO PROCESSAMENTO MENSAGEM', [
                'payload_structure' => [
                    'has_data' => isset($payload['data']),
                    'has_data_messages' => isset($payload['data']['messages']),
                    'has_messages' => isset($payload['messages']),
                    'data_keys' => isset($payload['data']) ? array_keys($payload['data']) : [],
                    'payload_keys' => array_keys($payload)
                ]
            ]);
            
            // Tentar diferentes estruturas de payload
            $messages = $payload['data']['messages'] ?? $payload['messages'] ?? $payload['data'] ?? [];
            
            // Se data é um array direto, pode ser que as mensagens estejam em data
            if (isset($payload['data']) && is_array($payload['data']) && !isset($payload['data']['messages'])) {
                // Verificar se data é uma mensagem única
                if (isset($payload['data']['key']) || isset($payload['data']['message'])) {
                    $messages = [$payload['data']];
                }
            }
            
            if (empty($messages)) {
                $this->writeWebhookLog('ERRO: NENHUMA MENSAGEM ENCONTRADA', ['payload' => $payload]);
                error_log("⚠️ Nenhuma mensagem encontrada no payload");
                error_log("📋 Estrutura do payload: " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return;
            }

            error_log("📨 Processando " . count($messages) . " mensagem(ns)");
            
            foreach ($messages as $message) {
                // Ignorar mensagens enviadas por nós
                $fromMe = $message['key']['fromMe'] ?? $message['fromMe'] ?? false;
                if ($fromMe === true) {
                    error_log("⏭️ Ignorando mensagem enviada por nós");
                    continue;
                }

                // Extrair número do remetente de diferentes formatos
                $numeroRemetente = $message['key']['remoteJid'] ?? 
                                 $message['remoteJid'] ?? 
                                 $message['from'] ?? 
                                 $message['key']['participant'] ?? '';
                
                // Extrair texto da mensagem de diferentes formatos
                $mensagemTexto = $message['message']['conversation'] ?? 
                                $message['message']['extendedTextMessage']['text'] ??
                                $message['message']['text'] ??
                                $message['body'] ??
                                '';

                error_log("📝 Mensagem: $mensagemTexto | De: $numeroRemetente");

                if (empty($mensagemTexto) && empty($message['message']['imageMessage']) && empty($message['message']['videoMessage'])) {
                    error_log("⚠️ Mensagem vazia ou sem texto (pode ser mídia)");
                    // Continuar mesmo assim para processar mídias se necessário
                }

                if (empty($numeroRemetente)) {
                    error_log("❌ Número do remetente vazio");
                    continue;
                }

                // Remover @c.us, @s.whatsapp.net, etc do número
                $numeroLimpo = preg_replace('/@[^.]+\.whatsapp\.net$/', '', $numeroRemetente);
                $numeroLimpo = str_replace('@c.us', '', $numeroLimpo);
                $numeroLimpo = str_replace('@s.whatsapp.net', '', $numeroLimpo);
                
                // Formatar número brasileiro
                $numeroFormatado = $this->formatarNumeroBrasil($numeroLimpo);
                error_log("📞 Número formatado: $numeroFormatado (original: $numeroRemetente)");

                // IMPORTANTE: Verificar se a mensagem é do número fixo 16997360690
                // Se for, buscar a solicitação que tem atendimento ativo com essa instância
                $numeroFixo = '16997360690';
                $numeroFixoFormatado = $this->formatarNumeroBrasil($numeroFixo);
                
                if ($numeroFormatado === $numeroFixoFormatado || $numeroLimpo === $numeroFixo) {
                    error_log("✅ Mensagem recebida do número fixo: $numeroFixo");
                    
                    // Buscar instância pelo instance_name do webhook
                    $instanceName = $payload['instance'] ?? $payload['instanceName'] ?? $payload['data']['instance'] ?? null;
                    error_log("🔍 Instance name do payload: " . ($instanceName ?? 'NÃO ENCONTRADO'));
                    
                    if ($instanceName) {
                        $whatsappInstance = $this->whatsappInstanceModel->findByInstanceName($instanceName);
                        
                        if ($whatsappInstance) {
                            // Buscar solicitação com atendimento ativo para esta instância
                            $sql = "
                                SELECT id 
                                FROM solicitacoes 
                                WHERE chat_whatsapp_instance_id = ? 
                                AND chat_atendimento_ativo = 1 
                                ORDER BY chat_atendimento_iniciado_em DESC 
                                LIMIT 1
                            ";
                            $solicitacao = \App\Core\Database::fetch($sql, [$whatsappInstance['id']]);
                            
                            if (!$solicitacao) {
                                error_log("⚠️ Nenhuma solicitação com atendimento ativo encontrada para instância: {$whatsappInstance['instance_name']}");
                                continue;
                            }
                            
                            error_log("✅ Solicitação encontrada: {$solicitacao['id']}");
                        } else {
                            error_log("❌ Instância não encontrada: $instanceName");
                            continue;
                        }
                    } else {
                        error_log("❌ Instance name não encontrado no payload");
                        continue;
                    }
                } else {
                    // Buscar solicitação pelo número do cliente (comportamento original)
                    $solicitacao = $this->solicitacaoModel->findByWhatsApp($numeroFormatado);

                    if (!$solicitacao) {
                        error_log("⚠️ Solicitação não encontrada para número: $numeroFormatado");
                        continue;
                    }
                    
                    // Buscar instância pelo instance_name do webhook
                    $instanceName = $payload['instance'] ?? $payload['instanceName'] ?? $payload['data']['instance'] ?? null;
                    $whatsappInstance = null;
                    if ($instanceName) {
                        $whatsappInstance = $this->whatsappInstanceModel->findByInstanceName($instanceName);
                    }
                }

                // Criar registro de mensagem recebida
                $mensagemData = [
                    'solicitacao_id' => $solicitacao['id'],
                    'whatsapp_instance_id' => $whatsappInstance['id'] ?? null,
                    'instance_name' => $instanceName,
                    'numero_remetente' => $numeroFormatado,
                    'numero_destinatario' => $whatsappInstance['numero_whatsapp'] ?? 'ADMIN',
                    'mensagem' => $mensagemTexto ?: '[Mídia ou mensagem sem texto]',
                    'tipo' => 'RECEBIDA',
                    'status' => 'ENTREGUE',
                    'is_lida' => 0, // Mensagem recebida começa como não lida
                    'message_id' => $message['key']['id'] ?? $message['id'] ?? null,
                    'metadata' => json_encode($message, JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $mensagemId = $this->mensagemModel->create($mensagemData);
                error_log("✅ Mensagem salva com ID: $mensagemId");
                
                // Atualizar updated_at da solicitação para que o card vá para o topo do Kanban
                if (isset($solicitacao['id'])) {
                    $this->solicitacaoModel->update($solicitacao['id'], []);
                }
            }
        } catch (\Exception $e) {
            error_log("❌ Erro ao processar mensagem recebida: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Processa atualização de status de mensagem (entregue, lida, etc)
     */
    private function processarAtualizacaoMensagem(array $payload): void
    {
        try {
            $updates = $payload['data']['update'] ?? [];
            
            foreach ($updates as $update) {
                $messageId = $update['key']['id'] ?? null;
                $status = $update['update']['status'] ?? null;

                if (!$messageId || !$status) {
                    continue;
                }

                // Buscar mensagem pelo message_id
                $sql = "SELECT * FROM solicitacao_mensagens WHERE message_id = ? LIMIT 1";
                $mensagem = \App\Core\Database::fetch($sql, [$messageId]);

                if ($mensagem) {
                    $novoStatus = $mensagem['status'];
                    if ($status === 'DELIVERY_ACK') {
                        $novoStatus = 'ENTREGUE';
                    } elseif ($status === 'READ') {
                        $novoStatus = 'LIDA';
                    }

                    if ($novoStatus !== $mensagem['status']) {
                        $this->mensagemModel->update($mensagem['id'], [
                            'status' => $novoStatus,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Erro ao processar atualização de mensagem: " . $e->getMessage());
        }
    }

    /**
     * Formata número para formato Evolution API
     */
    private function formatWhatsAppNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }
        return $phone . '@c.us';
    }

    /**
     * Formata número do Brasil para formato legível
     */
    private function formatarNumeroBrasil(string $numero): string
    {
        $numero = preg_replace('/[^0-9]/', '', $numero);
        // Remover DDI 55 se presente
        if (substr($numero, 0, 2) === '55') {
            $numero = substr($numero, 2);
        }
        return $numero;
    }

    /**
     * Busca instâncias WhatsApp disponíveis
     * GET /admin/chat/instancias
     */
    public function getInstancias(): void
    {
        $this->requireAdmin();
        
        $solicitacaoId = $_GET['solicitacao_id'] ?? null;

        $instancias = $this->whatsappInstanceModel->getAtivas();
        
        // Filtrar instâncias que estão em uso em outras solicitações ativas
        $instanciasDisponiveis = [];
        foreach ($instancias as $instancia) {
            $emUso = $this->instanciaEmUso($instancia['id'], $solicitacaoId);
            $instancia['disponivel'] = !$emUso;
            $instancia['em_uso_em_outro_atendimento'] = $emUso;
            $instanciasDisponiveis[] = $instancia;
        }

        $this->json([
            'success' => true,
            'instancias' => $instanciasDisponiveis
        ]);
    }
    
    /**
     * Verifica se uma instância está em uso em outra solicitação ativa
     */
    private function instanciaEmUso(int $instanceId, ?int $excluirSolicitacaoId = null): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM solicitacoes
            WHERE chat_whatsapp_instance_id = ?
              AND chat_atendimento_ativo = 1
        ";
        
        $params = [$instanceId];
        
        if ($excluirSolicitacaoId) {
            $sql .= " AND id != ?";
            $params[] = $excluirSolicitacaoId;
        }
        
        $result = \App\Core\Database::fetch($sql, $params);
        return ($result['total'] ?? 0) > 0;
    }
    
    /**
     * Inicia atendimento vinculando instância à solicitação
     */
    private function iniciarAtendimento(int $solicitacaoId, int $instanceId): void
    {
        // Verificar se a instância está disponível
        if ($this->instanciaEmUso($instanceId, $solicitacaoId)) {
            throw new \Exception('Esta instância está sendo usada em outro atendimento ativo');
        }
        
        // Atualizar solicitação usando SQL direto para garantir que os campos sejam salvos
        $sql = "
            UPDATE solicitacoes 
            SET chat_whatsapp_instance_id = ?,
                chat_atendimento_ativo = 1,
                chat_atendimento_iniciado_em = ?,
                updated_at = ?
            WHERE id = ?
        ";
        
        $params = [
            $instanceId,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            $solicitacaoId
        ];
        
        \App\Core\Database::query($sql, $params);
        
        error_log("✅ Atendimento iniciado - Solicitação: $solicitacaoId, Instância: $instanceId");
    }
    
    /**
     * Encerra atendimento e libera instância
     * POST /admin/chat/{solicitacao_id}/encerrar
     */
    public function encerrarAtendimento(int $solicitacaoId): void
    {
        $this->requireAdmin();

        $solicitacao = $this->solicitacaoModel->find($solicitacaoId);
        if (!$solicitacao) {
            $this->json([
                'success' => false,
                'message' => 'Solicitação não encontrada'
            ], 404);
            return;
        }

        // Encerrar atendimento usando SQL direto
        $sql = "
            UPDATE solicitacoes 
            SET chat_atendimento_ativo = 0,
                chat_atendimento_encerrado_em = ?,
                updated_at = ?
            WHERE id = ?
        ";
        
        \App\Core\Database::query($sql, [
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            $solicitacaoId
        ]);
        
        error_log("✅ Atendimento encerrado - Solicitação: $solicitacaoId");

        $this->json([
            'success' => true,
            'message' => 'Atendimento encerrado com sucesso. A instância foi liberada.'
        ]);
    }
    
    /**
     * Retorna contagem de mensagens não lidas para múltiplas solicitações
     * GET /admin/chat/mensagens-nao-lidas?solicitacao_ids=1,2,3
     */
    public function getMensagensNaoLidas(): void
    {
        $this->requireAdmin();
        
        $solicitacaoIds = $this->input('solicitacao_ids', '');
        
        if (empty($solicitacaoIds)) {
            $this->json([
                'success' => true,
                'contagens' => []
            ]);
            return;
        }
        
        // Converter string de IDs separados por vírgula em array
        $ids = array_map('intval', explode(',', $solicitacaoIds));
        $ids = array_filter($ids, fn($id) => $id > 0);
        
        if (empty($ids)) {
            $this->json([
                'success' => true,
                'contagens' => []
            ]);
            return;
        }
        
        // Buscar contagens de mensagens não lidas
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "
            SELECT 
                solicitacao_id,
                COUNT(*) as nao_lidas
            FROM solicitacao_mensagens
            WHERE solicitacao_id IN ($placeholders)
              AND tipo = 'RECEBIDA'
              AND (is_lida = 0 OR is_lida IS NULL)
            GROUP BY solicitacao_id
        ";
        
        $resultados = \App\Core\Database::fetchAll($sql, $ids);
        
        // Converter para formato chave-valor
        $contagens = [];
        foreach ($resultados as $resultado) {
            $contagens[$resultado['solicitacao_id']] = (int)$resultado['nao_lidas'];
        }
        
        $this->json([
            'success' => true,
            'contagens' => $contagens
        ]);
    }
    
    /**
     * Retorna status do WhatsApp (instância ativa) para múltiplas solicitações
     * GET /admin/chat/whatsapp-status?solicitacao_ids=1,2,3
     */
    public function getWhatsAppStatus(): void
    {
        $this->requireAdmin();
        
        $solicitacaoIds = $this->input('solicitacao_ids', '');
        
        if (empty($solicitacaoIds)) {
            $this->json([
                'success' => true,
                'status' => []
            ]);
            return;
        }
        
        // Converter string de IDs separados por vírgula em array
        $ids = array_map('intval', explode(',', $solicitacaoIds));
        $ids = array_filter($ids, fn($id) => $id > 0);
        
        if (empty($ids)) {
            $this->json([
                'success' => true,
                'status' => []
            ]);
            return;
        }
        
        // Buscar status do WhatsApp para cada solicitação
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "
            SELECT 
                s.id as solicitacao_id,
                s.chat_whatsapp_instance_id,
                s.chat_atendimento_ativo,
                wi.nome as whatsapp_instance_nome,
                wi.status as whatsapp_instance_status
            FROM solicitacoes s
            LEFT JOIN whatsapp_instances wi ON s.chat_whatsapp_instance_id = wi.id
            WHERE s.id IN ($placeholders)
        ";
        
        $resultados = \App\Core\Database::fetchAll($sql, $ids);
        
        // Converter para formato chave-valor
        $status = [];
        foreach ($resultados as $resultado) {
            $status[$resultado['solicitacao_id']] = [
                'has_instance' => !empty($resultado['chat_whatsapp_instance_id']),
                'is_active' => !empty($resultado['chat_atendimento_ativo']),
                'instance_name' => $resultado['whatsapp_instance_nome'] ?? null,
                'instance_status' => $resultado['whatsapp_instance_status'] ?? null
            ];
        }
        
        $this->json([
            'success' => true,
            'status' => $status
        ]);
    }
}

