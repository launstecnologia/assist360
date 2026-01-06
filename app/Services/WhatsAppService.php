<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ScheduleConfirmationToken;

/**
 * Serviço de WhatsApp - Integração com Evolution API (Envio Direto/Síncrono)
 * 
 * Este serviço gerencia o envio de mensagens WhatsApp através da Evolution API.
 * Modo: ENVIO DIRETO (síncrono) - mensagens enviadas imediatamente
 * 
 * Suporta:
 * - Envio direto de mensagens
 * - Templates customizáveis do banco de dados
 * - Substituição de variáveis dinâmicas
 * - Tokens de confirmação com expiração de 48h
 * - Formatação automática de números WhatsApp
 */
class WhatsAppService
{
    private string $apiUrl;
    private string $instance;
    private string $apiKey;
    private bool $enabled;
    
    /**
     * Construtor - Carrega configurações do WhatsApp
     * Prioriza instância do banco de dados, depois configuração do arquivo
     */
    public function __construct()
    {
        // Tentar buscar instância padrão do banco de dados
        try {
            $instanceModel = new \App\Models\WhatsappInstance();
            $instancePadrao = $instanceModel->getPadrao();

            if ($instancePadrao && (int)($instancePadrao['is_ativo'] ?? 0) === 1) {
                // Usar instância marcada como padrão
                $this->apiUrl = rtrim($instancePadrao['api_url'], '/');
                $this->instance = $instancePadrao['instance_name'];
                $this->apiKey = $instancePadrao['api_key'];
                $this->token = $instancePadrao['token'] ?? null;
                $this->enabled = true;

                if (($instancePadrao['status'] ?? '') !== 'CONECTADO') {
                    error_log(sprintf(
                        'WhatsApp: Instância padrão "%s" está com status "%s". Tentando enviar assim mesmo.',
                        $this->instance,
                        $instancePadrao['status'] ?? 'desconhecido'
                    ));
                }

                return;
            }
        } catch (\Exception $e) {
            error_log('WhatsApp: Erro ao buscar instância do banco: ' . $e->getMessage());
        }
        
        // Fallback: usar configuração do arquivo
        $configFile = __DIR__ . '/../Config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $whatsappConfig = $config['whatsapp'] ?? [];
        
        $envEnabled = (function_exists('env') ? env('WHATSAPP_ENABLED', 'false') : (getenv('WHATSAPP_ENABLED') ?: 'false'));
        $this->enabled = $whatsappConfig['enabled'] ?? ($envEnabled === 'true');
        $envApiUrl = (function_exists('env') ? env('WHATSAPP_API_URL', '') : (getenv('WHATSAPP_API_URL') ?: ''));
        $this->apiUrl = rtrim($whatsappConfig['api_url'] ?? $envApiUrl, '/');
        $envInstance = (function_exists('env') ? env('WHATSAPP_INSTANCE', '') : (getenv('WHATSAPP_INSTANCE') ?: ''));
        $this->instance = $whatsappConfig['instance'] ?? $envInstance;
        $envApiKey = (function_exists('env') ? env('WHATSAPP_API_KEY', '') : (getenv('WHATSAPP_API_KEY') ?: ''));
        $this->apiKey = $whatsappConfig['api_key'] ?? $envApiKey;
        $envToken = (function_exists('env') ? env('WHATSAPP_TOKEN', '') : (getenv('WHATSAPP_TOKEN') ?: ''));
        $this->token = $whatsappConfig['token'] ?? $envToken;
        
        // Validar configurações
        if ($this->enabled && (empty($this->apiUrl) || empty($this->instance) || empty($this->apiKey))) {
            error_log('WhatsApp: Configurações incompletas. Verifique WHATSAPP_API_URL, WHATSAPP_INSTANCE e WHATSAPP_API_KEY.');
            error_log('WhatsApp: Configurações atuais - URL: ' . ($this->apiUrl ?: 'VAZIA') . ', Instância: ' . ($this->instance ?: 'VAZIA') . ', API Key: ' . (!empty($this->apiKey) ? 'CONFIGURADO' : 'VAZIA'));
            $this->enabled = false;
        }
    }
    
    private ?string $token = null;
    
    /**
     * Envia mensagem WhatsApp de forma síncrona/direta
     * 
     * @param int $solicitacaoId ID da solicitação
     * @param string $messageType Tipo de mensagem (ex: 'Nova Solicitação', 'Horário Confirmado')
     * @param array $extraData Dados adicionais para substituição de variáveis
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function sendMessage(int $solicitacaoId, string $messageType, array $extraData = []): array
    {
        $logData = [
            'solicitacao_id' => $solicitacaoId,
            'message_type' => $messageType,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'iniciado'
        ];
        
        if (!$this->enabled) {
            $logData['status'] = 'erro';
            $logData['erro'] = 'WhatsApp está desabilitado ou configurações incompletas';
            $logData['configuracoes'] = [
                'enabled' => $this->enabled,
                'api_url' => $this->apiUrl ?: 'VAZIA',
                'instance' => $this->instance ?: 'VAZIA',
                'api_key' => !empty($this->apiKey) ? 'CONFIGURADO' : 'VAZIA'
            ];
            $this->writeLog($logData);
            
            return [
                'success' => false,
                'message' => 'WhatsApp está desabilitado ou configurações incompletas. Verifique WHATSAPP_API_URL, WHATSAPP_INSTANCE e WHATSAPP_API_KEY.',
                'data' => null
            ];
        }
        
        try {
            // Buscar dados da solicitação
            $solicitacao = $this->getSolicitacaoDetalhes($solicitacaoId);
            
            if (!$solicitacao) {
                $logData['status'] = 'erro';
                $logData['erro'] = 'Solicitação não encontrada';
                $this->writeLog($logData);
                throw new \Exception('Solicitação não encontrada');
            }
            
            $logData['protocolo'] = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacaoId);
            $logData['cliente_nome'] = $solicitacao['cliente_nome'] ?? 'N/A';
            
            // Buscar template
            $template = $this->getTemplate($messageType);
            
            if (!$template) {
                $logData['status'] = 'erro';
                $logData['erro'] = "Template não encontrado para o tipo: {$messageType}";
                $this->writeLog($logData);
                throw new \Exception("Template não encontrado para o tipo: {$messageType}");
            }
            
            $logData['template_id'] = $template['id'] ?? null;
            
            // Criar token se necessário (para mensagens de confirmação/sugestão de horário)
            $token = $this->createTokenIfNeeded($solicitacaoId, $messageType, $solicitacao, $extraData);
            
            // Adicionar message_type aos extraData para uso em prepareVariables
            $extraData['message_type'] = $messageType;
            
            // Preparar variáveis (incluindo links com token)
            $variables = $this->prepareVariables($solicitacao, $extraData, $token);
            
            // Log de debug para verificar variáveis
            if ($messageType === 'Horário Sugerido') {
                error_log("DEBUG WhatsAppService [ID:{$solicitacaoId}] - data_agendamento: " . ($variables['data_agendamento'] ?? 'VAZIO'));
                error_log("DEBUG WhatsAppService [ID:{$solicitacaoId}] - horario_agendamento: " . ($variables['horario_agendamento'] ?? 'VAZIO'));
                error_log("DEBUG WhatsAppService [ID:{$solicitacaoId}] - extraData recebido: " . json_encode($extraData));
            }
            
            // Substituir variáveis no template
            $message = $this->replaceVariables($template['corpo'], $variables);
            
            // Formatar número WhatsApp
            $telefone = $solicitacao['cliente_telefone'] ?? '';
            
            if (empty($telefone)) {
                $logData['status'] = 'erro';
                $logData['erro'] = 'Telefone do cliente não encontrado';
                $this->writeLog($logData);
                throw new \Exception('Telefone do cliente não encontrado');
            }
            
            $whatsappNumber = $this->formatWhatsAppNumber($telefone);
            $logData['telefone_original'] = $telefone;
            $logData['telefone_formatado'] = $whatsappNumber;
            $logData['mensagem_tamanho'] = strlen($message);
            $logData['mensagem'] = $message; // ✅ Salvar mensagem completa no log
            
            // Enviar para Evolution API
            $apiResponse = $this->sendToEvolutionAPI($whatsappNumber, $message, $logData);
            
            // Log de sucesso
            $logData['status'] = 'sucesso';
            $logData['api_response'] = $apiResponse;
            $logData['http_code'] = $apiResponse['http_code'] ?? null;
            $this->writeLog($logData);
            
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'data' => $apiResponse
            ];
            
        } catch (\Exception $e) {
            $logData['status'] = 'erro';
            $logData['erro'] = $e->getMessage();
            $logData['erro_tipo'] = get_class($e);
            $logData['erro_arquivo'] = $e->getFile();
            $logData['erro_linha'] = $e->getLine();
            $this->writeLog($logData);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Gera token de confirmação para agendamento
     * 
     * @param int $solicitacaoId ID da solicitação
     * @param string $protocol Protocolo da solicitação
     * @param string|null $scheduledDate Data sugerida
     * @param string|null $scheduledTime Horário sugerido
     * @return string Token gerado
     */
    public function generateConfirmationToken(
        int $solicitacaoId, 
        string $protocol, 
        ?string $scheduledDate = null, 
        ?string $scheduledTime = null
    ): string {
        try {
            $token = bin2hex(random_bytes(32)); // 64 caracteres
            $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
            
            $sql = "
                INSERT INTO schedule_confirmation_tokens 
                (token, solicitacao_id, protocol, scheduled_date, scheduled_time, expires_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            Database::execute($sql, [
                $token,
                $solicitacaoId,
                $protocol,
                $scheduledDate,
                $scheduledTime,
                $expiresAt
            ]);
            
            return $token;
            
        } catch (\Exception $e) {
            error_log('Erro ao gerar token de confirmação: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Busca detalhes completos da solicitação com relacionamentos
     * 
     * @param int $id ID da solicitação
     * @return array|null Dados da solicitação
     */
    private function getSolicitacaoDetalhes(int $id): ?array
    {
        $sql = "
            SELECT 
                s.*,
                COALESCE(l.nome, s.locatario_nome) as cliente_nome,
                l.cpf as cliente_cpf,
                COALESCE(l.telefone, s.locatario_telefone) as cliente_telefone,
                COALESCE(l.email, s.locatario_email) as cliente_email,
                s.imovel_endereco as cliente_endereco,
                s.imovel_numero as cliente_numero,
                s.imovel_complemento as cliente_complemento,
                s.imovel_bairro as cliente_bairro,
                s.imovel_cidade as cliente_cidade,
                s.imovel_estado as cliente_estado,
                s.imovel_cep as cliente_cep,
                s.numero_contrato as contrato_numero,
                s.descricao_problema,
                i.nome as imobiliaria_nome,
                i.telefone as imobiliaria_telefone,
                i.instancia as imobiliaria_instancia,
                st.nome as status_nome,
                c.nome as categoria_nome
            FROM solicitacoes s
            LEFT JOIN locatarios l ON s.locatario_id = l.id
            LEFT JOIN imobiliarias i ON s.imobiliaria_id = i.id
            LEFT JOIN status st ON s.status_id = st.id
            LEFT JOIN categorias c ON s.categoria_id = c.id
            WHERE s.id = ?
        ";
        
        return Database::fetch($sql, [$id]);
    }
    
    /**
     * Busca detalhes de solicitação manual
     */
    private function getSolicitacaoManualDetalhes(int $id): ?array
    {
        $sql = "
            SELECT 
                sm.*,
                sm.nome_completo as cliente_nome,
                sm.nome_completo as locatario_nome,
                sm.cpf as cliente_cpf,
                sm.whatsapp as cliente_telefone,
                sm.endereco as cliente_endereco,
                sm.numero as cliente_numero,
                sm.complemento as cliente_complemento,
                sm.bairro as cliente_bairro,
                sm.cidade as cliente_cidade,
                sm.estado as cliente_estado,
                sm.cep as cliente_cep,
                sm.numero_contrato as contrato_numero,
                sm.descricao_problema,
                i.nome as imobiliaria_nome,
                i.telefone as imobiliaria_telefone,
                i.instancia as imobiliaria_instancia,
                st.nome as status_nome,
                c.nome as categoria_nome,
                sc.nome as subcategoria_nome,
                CONCAT('KSS', sm.id) as numero_solicitacao
            FROM solicitacoes_manuais sm
            LEFT JOIN imobiliarias i ON sm.imobiliaria_id = i.id
            LEFT JOIN status st ON sm.status_id = st.id
            LEFT JOIN categorias c ON sm.categoria_id = c.id
            LEFT JOIN subcategorias sc ON sm.subcategoria_id = sc.id
            WHERE sm.id = ?
        ";
        
        return Database::fetch($sql, [$id]);
    }
    
    /**
     * Envia mensagem WhatsApp para solicitação manual
     * 
     * @param int $solicitacaoManualId ID da solicitação manual
     * @param string $messageType Tipo de mensagem (ex: 'Nova Solicitação')
     * @param array $extraData Dados adicionais para substituição de variáveis
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function sendMessageManual(int $solicitacaoManualId, string $messageType, array $extraData = []): array
    {
        $logData = [
            'solicitacao_manual_id' => $solicitacaoManualId,
            'message_type' => $messageType,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'iniciado'
        ];
        
        if (!$this->enabled) {
            $logData['status'] = 'erro';
            $logData['erro'] = 'WhatsApp está desabilitado ou configurações incompletas';
            $this->writeLog($logData);
            
            return [
                'success' => false,
                'message' => 'WhatsApp está desabilitado ou configurações incompletas',
                'data' => null
            ];
        }
        
        try {
            // Buscar dados da solicitação manual
            $solicitacao = $this->getSolicitacaoManualDetalhes($solicitacaoManualId);
            
            if (!$solicitacao) {
                $logData['status'] = 'erro';
                $logData['erro'] = 'Solicitação manual não encontrada';
                $this->writeLog($logData);
                throw new \Exception('Solicitação manual não encontrada');
            }
            
            $logData['protocolo'] = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacaoManualId);
            $logData['cliente_nome'] = $solicitacao['cliente_nome'] ?? 'N/A';
            
            // Buscar template
            $template = $this->getTemplate($messageType);
            
            if (!$template) {
                $logData['status'] = 'erro';
                $logData['erro'] = "Template não encontrado para o tipo: {$messageType}";
                $this->writeLog($logData);
                throw new \Exception("Template não encontrado para o tipo: {$messageType}");
            }
            
            $logData['template_id'] = $template['id'] ?? null;
            
            // Adicionar message_type aos extraData
            $extraData['message_type'] = $messageType;
            
            // Preparar variáveis (adaptado para solicitação manual)
            $variables = $this->prepareVariablesManual($solicitacao, $extraData);
            
            // Substituir variáveis no template
            $message = $this->replaceVariables($template['corpo'], $variables);
            
            // Formatar número WhatsApp
            $telefone = $solicitacao['cliente_telefone'] ?? '';
            
            if (empty($telefone)) {
                $logData['status'] = 'erro';
                $logData['erro'] = 'Telefone do cliente não encontrado';
                $this->writeLog($logData);
                throw new \Exception('Telefone do cliente não encontrado');
            }
            
            $whatsappNumber = $this->formatWhatsAppNumber($telefone);
            $logData['telefone_original'] = $telefone;
            $logData['telefone_formatado'] = $whatsappNumber;
            $logData['mensagem_tamanho'] = strlen($message);
            $logData['mensagem'] = $message;
            
            // Enviar para Evolution API
            $apiResponse = $this->sendToEvolutionAPI($whatsappNumber, $message, $logData);
            
            // Log de sucesso
            $logData['status'] = 'sucesso';
            $logData['api_response'] = $apiResponse;
            $logData['http_code'] = $apiResponse['http_code'] ?? null;
            $this->writeLog($logData);
            
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'data' => $apiResponse
            ];
            
        } catch (\Exception $e) {
            $logData['status'] = 'erro';
            $logData['erro'] = $e->getMessage();
            $logData['erro_tipo'] = get_class($e);
            $logData['erro_arquivo'] = $e->getFile();
            $logData['erro_linha'] = $e->getLine();
            $this->writeLog($logData);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Prepara variáveis para mensagem de solicitação manual
     */
    private function prepareVariablesManual(array $solicitacao, array $extraData = []): array
    {
        // Obter URL base
        $baseUrl = $this->getLinksBaseUrl();
        
        // Formatar endereço completo
        $enderecoCompleto = $this->formatEnderecoManual($solicitacao);
        
        // Buscar nome do serviço (subcategoria ou categoria)
        $servicoTipo = $solicitacao['subcategoria_nome'] ?? $solicitacao['categoria_nome'] ?? 'Assistência';
        
        // Gerar link de rastreamento (se houver token)
        $linkRastreamento = '';
        if (!empty($solicitacao['token_acesso'])) {
            $instancia = $solicitacao['imobiliaria_instancia'] ?? '';
            if ($instancia) {
                $linkRastreamento = $this->cleanUrl($baseUrl . '/' . $instancia . '/solicitacao-manual/' . $solicitacao['token_acesso']);
            }
        }
        
        $variables = [
            // Dados básicos
            'protocol' => $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']),
            'protocolo_seguradora' => $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']),
            'contrato_numero' => $solicitacao['contrato_numero'] ?? '',
            
            // Cliente
            'cliente_nome' => $solicitacao['cliente_nome'] ?? '',
            'cliente_cpf' => $solicitacao['cliente_cpf'] ?? '',
            'cliente_telefone' => $solicitacao['cliente_telefone'] ?? '',
            
            // Imobiliária
            'imobiliaria_nome' => $solicitacao['imobiliaria_nome'] ?? '',
            
            // Endereço
            'endereco_completo' => $enderecoCompleto,
            
            // Serviço
            'servico_tipo' => $servicoTipo,
            'categoria_nome' => $solicitacao['categoria_nome'] ?? '',
            'subcategoria_nome' => $solicitacao['subcategoria_nome'] ?? '',
            
            // Descrição
            'descricao_problema' => $solicitacao['descricao_problema'] ?? '',
            
            // Links
            'link_rastreamento' => $linkRastreamento,
        ];
        
        // Mesclar com dados extras
        $variables = array_merge($variables, $extraData);
        
        return $variables;
    }
    
    /**
     * Formata endereço completo para solicitação manual
     */
    private function formatEnderecoManual(array $solicitacao): string
    {
        $parts = [];
        
        if (!empty($solicitacao['cliente_endereco'])) {
            $parts[] = $solicitacao['cliente_endereco'];
        }
        
        if (!empty($solicitacao['cliente_numero'])) {
            $parts[] = 'nº ' . $solicitacao['cliente_numero'];
        }
        
        if (!empty($solicitacao['cliente_complemento'])) {
            $parts[] = $solicitacao['cliente_complemento'];
        }
        
        if (!empty($solicitacao['cliente_bairro'])) {
            $parts[] = $solicitacao['cliente_bairro'];
        }
        
        if (!empty($solicitacao['cliente_cidade']) && !empty($solicitacao['cliente_estado'])) {
            $parts[] = $solicitacao['cliente_cidade'] . '/' . $solicitacao['cliente_estado'];
        }
        
        if (!empty($solicitacao['cliente_cep'])) {
            $parts[] = 'CEP: ' . $solicitacao['cliente_cep'];
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Busca template ativo por tipo de mensagem
     * 
     * @param string $messageType Tipo de mensagem
     * @return array|null Template encontrado
     */
    private function getTemplate(string $messageType): ?array
    {
        $sql = "
            SELECT * FROM whatsapp_templates 
            WHERE tipo = ? 
            AND ativo = 1 
            ORDER BY padrao DESC, created_at DESC 
            LIMIT 1
        ";
        
        return Database::fetch($sql, [$messageType]);
    }
    
    /**
     * Cria token de confirmação se necessário para o tipo de mensagem
     * 
     * @param int $solicitacaoId ID da solicitação
     * @param string $messageType Tipo de mensagem
     * @param array $solicitacao Dados da solicitação
     * @param array $extraData Dados extras
     * @return string|null Token gerado ou null
     */
    private function createTokenIfNeeded(int $solicitacaoId, string $messageType, array $solicitacao, array $extraData): ?string
    {
        // Tipos de mensagem que precisam de token
        $tokenTypes = ['Horário Confirmado', 'Horário Sugerido', 'Confirmação de Serviço', 'lembrete_peca'];
        
        if (!in_array($messageType, $tokenTypes)) {
            return null;
        }
        
        $protocol = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']);
        $scheduledDate = null;
        $scheduledTime = null;
        $actionType = 'confirm';
        
        // Determinar data e horário agendados
        if (isset($extraData['data_agendamento']) && !empty($extraData['data_agendamento'])) {
            // Para 'Horário Confirmado' e 'Horário Sugerido'
            $scheduledDate = $extraData['data_agendamento'];
            
            // Converter formato se necessário (d/m/Y -> Y-m-d)
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $scheduledDate)) {
                $scheduledDate = \DateTime::createFromFormat('d/m/Y', $scheduledDate)->format('Y-m-d');
            }
        }
        
        if (isset($extraData['horario_agendamento']) && !empty($extraData['horario_agendamento'])) {
            $scheduledTime = $extraData['horario_agendamento'];
        }
        
        // Determinar tipo de ação baseado no tipo de mensagem
        // ✅ "Horário Sugerido" = VGT enviou novos horários para locatário aceitar
        // O locatário deve CONFIRMAR (aceitar) o horário, não reagendar
        if ($messageType === 'Horário Sugerido') {
            $actionType = 'confirm'; // Link de confirmação para o locatário aceitar o horário
        } elseif ($messageType === 'Confirmação de Serviço') {
            $actionType = 'service_status';
        } elseif ($messageType === 'lembrete_peca') {
            $actionType = 'compra_peca';
        }
        
        // Criar token
        $tokenModel = new ScheduleConfirmationToken();
        $token = $tokenModel->createToken($solicitacaoId, $protocol, $scheduledDate, $scheduledTime, $actionType);
        
        return $token;
    }
    
    /**
     * Prepara todas as variáveis disponíveis para substituição
     * 
     * @param array $solicitacao Dados da solicitação
     * @param array $extraData Dados adicionais
     * @param string|null $token Token de confirmação (se aplicável)
     * @return array Mapa de variáveis
     */
    private function prepareVariables(array $solicitacao, array $extraData = [], ?string $token = null): array
    {
        // ✅ Usar URL base configurada especificamente para links WhatsApp
        $baseUrl = $this->getLinksBaseUrl();
        
        $variables = [
            // Cliente
            'cliente_nome' => $solicitacao['cliente_nome'] ?? 'Cliente',
            'cliente_cpf' => $solicitacao['cliente_cpf'] ?? '',
            'cliente_telefone' => $solicitacao['cliente_telefone'] ?? '',
            'cliente_email' => $solicitacao['cliente_email'] ?? '',
            
            // Solicitação
            'protocol' => $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']),
            'contrato_numero' => $solicitacao['contrato_numero'] ?? $solicitacao['numero_contrato'] ?? '',
            'protocolo_seguradora' => $solicitacao['protocolo_seguradora'] ?? '',
            'descricao_problema' => $solicitacao['descricao_problema'] ?? $solicitacao['descricao'] ?? '',
            'servico_tipo' => $solicitacao['categoria_nome'] ?? 'Serviço',
            
            // Status
            'status_atual' => $extraData['status_atual'] ?? $solicitacao['status_nome'] ?? '',
            
            // Endereço
            'endereco_completo' => $this->formatEndereco($solicitacao),
            'endereco_rua' => $solicitacao['cliente_endereco'] ?? '',
            'endereco_numero' => $solicitacao['cliente_numero'] ?? '',
            'endereco_bairro' => $solicitacao['cliente_bairro'] ?? '',
            'endereco_cidade' => $solicitacao['cliente_cidade'] ?? '',
            'endereco_estado' => $solicitacao['cliente_estado'] ?? '',
            'endereco_cep' => $solicitacao['cliente_cep'] ?? '',
            
            // Imobiliária
            'imobiliaria_nome' => $solicitacao['imobiliaria_nome'] ?? '',
            
            // Agendamento
            'data_agendamento' => $extraData['data_agendamento'] ?? 
                ($solicitacao['data_agendamento'] ? date('d/m/Y', strtotime($solicitacao['data_agendamento'])) : ''),
            'horario_agendamento' => $extraData['horario_agendamento'] ?? '',
            'horario_servico' => $extraData['horario_servico'] ?? '',
            'periodo_chegada' => $extraData['periodo_chegada'] ?? '',
            
            // Prestador (se disponível)
            'prestador_nome' => $extraData['prestador_nome'] ?? '',
            'prestador_telefone' => $extraData['prestador_telefone'] ?? '',
            
            // Lembrete de peça
            'data_limite' => $extraData['data_limite'] ?? 
                ($solicitacao['data_limite_peca'] ? date('d/m/Y', strtotime($solicitacao['data_limite_peca'])) : ''),
            'dias_restantes' => $extraData['dias_restantes'] ?? '',
            
            // Links (sempre absolutos, sem barras invertidas)
            // Link de rastreamento: /{instancia}/solicitacoes/{id}
            'link_rastreamento' => $this->getRastreamentoLink($baseUrl, $solicitacao),
            'link_confirmacao' => $token ? $this->cleanUrl($baseUrl . '/confirmacao-horario?token=' . $token) : '',
            'link_cancelamento' => $token ? $this->cleanUrl($baseUrl . '/cancelamento-horario?token=' . $token) : '',
            'link_reagendamento' => $token ? $this->cleanUrl($baseUrl . '/reagendamento-horario?token=' . $token) : '',
            // Link de status sempre usa token público permanente (não expira, acesso sem login)
            'link_status' => $this->getStatusPublicLink($baseUrl, $solicitacao),
            // Link de cancelamento de solicitação (permanente, não expira)
            'link_cancelamento_solicitacao' => $this->getCancelamentoSolicitacaoLink($baseUrl, $solicitacao),
            // Link de ações do serviço (pré-serviço)
            'link_acoes_servico' => $extraData['link_acoes_servico'] ?? '',
            // Link de compra de peça (para lembrete_peca)
            'link_compra_peca' => $token && ($extraData['message_type'] ?? '') === 'lembrete_peca' 
                ? $this->cleanUrl($baseUrl . '/compra-peca?token=' . $token) 
                : ($extraData['link_compra_peca'] ?? ''),
        ];
        
        // Adicionar variável observacao se presente no extraData
        if (isset($extraData['observacao'])) {
            $variables['observacao'] = $extraData['observacao'];
        }
        
        // Mesclar com dados extras (sobrescreve se existir)
        $variables = array_merge($variables, $extraData);
        
        return $variables;
    }
    
    /**
     * Substitui todas as variáveis {{variavel}} no template
     * 
     * @param string $template Texto do template
     * @param array $variables Mapa de variáveis
     * @return string Texto com variáveis substituídas
     */
    private function replaceVariables(string $template, array $variables): string
    {
        $text = $template;
        
        // Primeiro, substituir variáveis de link removendo barras invertidas extras do template
        foreach ($variables as $key => $value) {
            if (strpos($key, 'link_') === 0 && !empty($value)) {
                $placeholder = '{{' . $key . '}}';
                
                // Padrões com barras invertidas: \{{link_*}}, \\{{link_*}}, {{link_*}}\, {{link_*}}\\, etc.
                $patterns = [
                    // Remover barras invertidas antes e depois do placeholder
                    '/\\\\+\\{\\{' . preg_quote($key, '/') . '\\}\\}\\\\*/' => $value,
                    '/\\\\+\\{\\{' . preg_quote($key, '/') . '\\}\\}/' => $value,
                    '/\\{\\{' . preg_quote($key, '/') . '\\}\\}\\\\*/' => $value,
                    // Remover URL base duplicada se o template já tiver (ex: http://localhost/kss/{{link_*}})
                    '/https?:\/\/[^\/\s]+(?:\/[^\/\s]*)?\/?\\{\\{' . preg_quote($key, '/') . '\\}\\}/' => $value,
                    // Remover qualquer URL antes do placeholder seguida de URL completa
                    '/https?:\/\/[^\s]+\\{\\{' . preg_quote($key, '/') . '\\}\\}/' => $value,
                ];
                
                foreach ($patterns as $pattern => $replacement) {
                    $text = preg_replace($pattern, $replacement, $text);
                }
                
                // Substituir placeholder simples (caso não tenha sido substituído)
                $text = str_replace($placeholder, $value, $text);
            }
        }
        
        // Depois, substituir outras variáveis normalmente
        foreach ($variables as $key => $value) {
            if (strpos($key, 'link_') !== 0) {
                $placeholder = '{{' . $key . '}}';
                $text = str_replace($placeholder, $value, $text);
            }
        }
        
        // Remover variáveis não preenchidas
        $text = preg_replace('/\{\{[^}]+\}\}/', '', $text);
        
        // Limpeza final: remover TODAS as barras invertidas antes de URLs
        $text = preg_replace('/\\\\+(http[s]?:\/\/[^\s]+)/', '$1', $text);
        // Remover barras invertidas antes de barras normais em URLs
        $text = preg_replace('/\\\\+(\/)/', '$1', $text);
        // Remover múltiplas barras invertidas consecutivas
        $text = preg_replace('/\\\\{2,}/', '', $text);
        
        // Remover URLs duplicadas de forma mais agressiva
        // Padrão especial: http://localhost/http:/localhost/... (URL malformada com : duplicado)
        $text = preg_replace('/(https?:\/\/[^\/\s]+)\/(https?:\/\/?[^\s]+)/', '$2', $text);
        // Padrão 1: http://localhost/http://localhost/kss/... (URL completa duplicada)
        $text = preg_replace('/(https?:\/\/[^\/\s]+)(https?:\/\/[^\s]+)/', '$2', $text);
        // Padrão 2: http://localhost/kss/http://localhost/kss/... (URL com path duplicada)
        $text = preg_replace('/(https?:\/\/[^\/\s]+\/[^\/\s]*\/?)(https?:\/\/[^\s]+)/', '$2', $text);
        // Padrão 3: http:/localhost/... (corrigir protocolo malformado sem //)
        $text = preg_replace('/http:\/localhost/', 'http://localhost', $text);
        $text = preg_replace('/https:\/localhost/', 'https://localhost', $text);
        // Padrão 4: http:/... (protocolo malformado geral)
        $text = preg_replace('/(https?:\/\/)(https?:\/\/)/', '$1', $text);
        
        return $text;
    }
    
    /**
     * Limpa URL removendo barras invertidas e espaços
     * 
     * @param string $url URL a limpar
     * @return string URL limpa
     */
    private function cleanUrl(string $url): string
    {
        // Remover todas as barras invertidas
        $url = str_replace('\\', '', $url);
        // Remover espaços
        $url = trim($url);
        
        // ✅ CORRIGIR PRIMEIRO: Garantir que o protocolo sempre tenha :// (duas barras)
        // Corrigir https:/ ou http:/ para https:// ou http://
        $url = preg_replace('/^(https?):\/([^\/])/', '$1://$2', $url);
        
        // Remover URLs duplicadas ANTES de processar barras
        // Padrão especial: http://localhost/http:/localhost/... (URL malformada com : duplicado)
        $url = preg_replace('/(https?:\/\/[^\/\s]+)\/(https?:\/\/?[^\s]+)/', '$2', $url);
        // Padrão: http://localhost/http://localhost/... (URL completa duplicada)
        $url = preg_replace('/(https?:\/\/[^\/\s]+)(https?:\/\/[^\s]+)/', '$2', $url);
        // Padrão: http:/localhost/... (corrigir protocolo malformado sem //)
        $url = preg_replace('/http:\/localhost/', 'http://localhost', $url);
        $url = preg_replace('/https:\/localhost/', 'https://localhost', $url);
        
        // ✅ CORRIGIR NOVAMENTE: Garantir que o protocolo sempre tenha :// após outras operações
        $url = preg_replace('/^(https?):\/([^\/])/', '$1://$2', $url);
        
        // Garantir que não tem barras duplicadas (mas preservar :// do protocolo)
        // Substituir múltiplas barras, mas não as duas barras do protocolo
        $url = preg_replace('/(?<!:)\/\/+/', '/', $url);
        
        // Remover barra final
        $url = rtrim($url, '/');
        
        // Garantir que tem protocolo correto
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://localhost' . ($url ? '/' . ltrim($url, '/') : '');
        }
        
        return $url;
    }
    
    /**
     * Formata endereço completo
     * 
     * @param array $solicitacao Dados da solicitação
     * @return string Endereço formatado
     */
    private function formatEndereco(array $solicitacao): string
    {
        $parts = [];
        
        if (!empty($solicitacao['cliente_endereco'])) {
            $parts[] = $solicitacao['cliente_endereco'];
        }
        
        if (!empty($solicitacao['cliente_numero'])) {
            $parts[] = 'nº ' . $solicitacao['cliente_numero'];
        }
        
        if (!empty($solicitacao['cliente_complemento'])) {
            $parts[] = $solicitacao['cliente_complemento'];
        }
        
        if (!empty($solicitacao['cliente_bairro'])) {
            $parts[] = $solicitacao['cliente_bairro'];
        }
        
        $cidadeEstado = [];
        if (!empty($solicitacao['cliente_cidade'])) {
            $cidadeEstado[] = $solicitacao['cliente_cidade'];
        }
        if (!empty($solicitacao['cliente_estado'])) {
            $cidadeEstado[] = $solicitacao['cliente_estado'];
        }
        if (!empty($cidadeEstado)) {
            $parts[] = implode('/', $cidadeEstado);
        }
        
        if (!empty($solicitacao['cliente_cep'])) {
            $parts[] = 'CEP: ' . $solicitacao['cliente_cep'];
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Gera o link de rastreamento da solicitação com a instância correta
     * Usa URL encurtada se disponível
     * 
     * @param string $baseUrl URL base
     * @param array $solicitacao Dados da solicitação
     * @return string Link de rastreamento
     */
    private function getRastreamentoLink(string $baseUrl, array $solicitacao): string
    {
        $solicitacaoId = $solicitacao['id'] ?? 0;
        
        // Verificar se a solicitação tem token_acesso (criada via solicitação manual)
        // Se tiver, usar link público com token (sem precisar de login)
        $tokenAcesso = null;
        try {
            $sqlToken = "SELECT token_acesso FROM solicitacoes WHERE id = ? LIMIT 1";
            $result = Database::fetch($sqlToken, [$solicitacaoId]);
            $tokenAcesso = $result['token_acesso'] ?? null;
        } catch (\Exception $e) {
            // Se a coluna não existir, continuar com o fluxo normal
        }
        
        // Se tiver token_acesso, usar link público com token
        if (!empty($tokenAcesso)) {
            return $this->cleanUrl($baseUrl . '/rastreamento/' . $tokenAcesso);
        }
        
        // Caso contrário, usar link normal com instância (requer login)
        $instancia = $solicitacao['imobiliaria_instancia'] ?? '';
        error_log("DEBUG getRastreamentoLink - instancia do array: " . ($instancia ?: 'VAZIA'));
        
        if (empty($instancia)) {
            // Fallback: tentar buscar a instância da imobiliária
            if (!empty($solicitacao['imobiliaria_id'])) {
                error_log("DEBUG getRastreamentoLink - Buscando instância no banco para imobiliaria_id: " . $solicitacao['imobiliaria_id']);
                $sql = "SELECT instancia FROM imobiliarias WHERE id = ? LIMIT 1";
                $imobiliaria = Database::fetch($sql, [$solicitacao['imobiliaria_id']]);
                $instancia = $imobiliaria['instancia'] ?? '';
                error_log("DEBUG getRastreamentoLink - instancia encontrada no banco: " . ($instancia ?: 'VAZIA'));
            } else {
                error_log("DEBUG getRastreamentoLink - imobiliaria_id não encontrado no array da solicitação");
            }
        }
        
        // Se ainda não tiver instância, usar um fallback genérico
        if (empty($instancia)) {
            error_log("DEBUG getRastreamentoLink - ⚠️ Instância não encontrada, usando fallback 'demo'");
            $instancia = 'demo'; // Fallback padrão
        }
        
        // IMPORTANTE: Formato correto: /{instancia}/solicitacoes/{id}
        // Garantir que o ID da solicitação está presente
        if (empty($solicitacaoId) || $solicitacaoId <= 0) {
            error_log("ERRO getRastreamentoLink: ID da solicitação inválido: {$solicitacaoId}");
            // Fallback: retornar apenas a instância se não houver ID válido
            return $this->cleanUrl($baseUrl . '/' . $instancia);
        }
        
        // Formato correto: /{instancia}/solicitacoes/{id}
        $urlOriginal = $baseUrl . '/' . $instancia . '/solicitacoes/' . $solicitacaoId;
        
        // Log de debug para verificar o link gerado
        error_log("DEBUG getRastreamentoLink - baseUrl: {$baseUrl}, instancia: {$instancia}, solicitacaoId: {$solicitacaoId}, urlOriginal: {$urlOriginal}");
        
        // IMPORTANTE: Usar link direto ao invés de URL encurtada para mensagens WhatsApp
        // A URL encurtada pode ser usada em outros contextos, mas para WhatsApp enviamos o link completo
        $linkFinal = $this->cleanUrl($urlOriginal);
        error_log("DEBUG getRastreamentoLink - Link final (direto): {$linkFinal}");
        return $linkFinal;
    }

    /**
     * Gera o link público de status da solicitação com token permanente
     * Este link não expira e permite acesso sem login
     * 
     * @param string $baseUrl URL base
     * @param array $solicitacao Dados da solicitação
     * @return string Link público de status
     */
    private function getStatusPublicLink(string $baseUrl, array $solicitacao): string
    {
        $solicitacaoId = $solicitacao['id'] ?? 0;
        
        if (empty($solicitacaoId)) {
            return '';
        }
        
        // Gerar token público permanente
        $solicitacaoModel = new \App\Models\Solicitacao();
        $tokenPublico = $solicitacaoModel->gerarTokenPublico($solicitacaoId);
        
        // Formato: /status-servico?token={token_publico}
        $link = $baseUrl . '/status-servico?token=' . $tokenPublico;
        
        return $this->cleanUrl($link);
    }

    /**
     * Gera o link de cancelamento de solicitação com token permanente
     * Este link não expira e permite cancelar a solicitação sem login
     * 
     * @param string $baseUrl URL base
     * @param array $solicitacao Dados da solicitação
     * @return string Link de cancelamento de solicitação
     */
    private function getCancelamentoSolicitacaoLink(string $baseUrl, array $solicitacao): string
    {
        $solicitacaoId = $solicitacao['id'] ?? 0;
        
        if (empty($solicitacaoId)) {
            return '';
        }
        
        // Gerar token de cancelamento permanente
        $solicitacaoModel = new \App\Models\Solicitacao();
        $tokenCancelamento = $solicitacaoModel->gerarTokenCancelamento($solicitacaoId);
        
        // Formato: /cancelar-solicitacao?token={token_cancelamento}
        $link = $baseUrl . '/cancelar-solicitacao?token=' . $tokenCancelamento;
        
        return $this->cleanUrl($link);
    }
    
    /**
     * Obtém a URL base para links enviados nas mensagens WhatsApp
     * Prioridade: Banco de dados > config.php > app.url > APP_URL > localhost
     * 
     * @return string URL base (sem barra final)
     */
    private function getLinksBaseUrl(): string
    {
        // Prioridade 1: Buscar do banco de dados (configurações)
        try {
            $configuracaoModel = new \App\Models\Configuracao();
            $urlBase = $configuracaoModel->getValor('whatsapp_links_base_url');
            
            if (!empty($urlBase)) {
                $linksBaseUrl = $urlBase;
            } else {
                $linksBaseUrl = null;
            }
        } catch (\Exception $e) {
            // Se houver erro ao buscar do banco, continuar com fallbacks
            error_log('WhatsApp: Erro ao buscar URL base do banco: ' . $e->getMessage());
            $linksBaseUrl = null;
        }
        
        // Prioridade 2: Configuração do arquivo config.php
        if (!$linksBaseUrl) {
            $configFile = __DIR__ . '/../Config/config.php';
            $config = file_exists($configFile) ? require $configFile : [];
            
            $whatsappConfig = $config['whatsapp'] ?? [];
            $linksBaseUrl = $whatsappConfig['links_base_url'] ?? null;
        }
        
        // Prioridade 3: app.url do config.php
        if (!$linksBaseUrl) {
            $configFile = __DIR__ . '/../Config/config.php';
            $config = file_exists($configFile) ? require $configFile : [];
            $linksBaseUrl = $config['app']['url'] ?? null;
        }
        
        // Prioridade 4: Variável de ambiente
        if (!$linksBaseUrl) {
            $linksBaseUrl = (function_exists('env') ? env('APP_URL', 'http://localhost') : (getenv('APP_URL') ?: 'http://localhost'));
        }
        
        // Limpar: remover barras invertidas, espaços, e barras finais
        $linksBaseUrl = str_replace('\\', '', $linksBaseUrl); // Remover todas as barras invertidas
        $linksBaseUrl = trim($linksBaseUrl); // Remover espaços
        
        // Remover URLs duplicadas se houver (caso raro mas possível)
        $linksBaseUrl = preg_replace('/(https?:\/\/[^\/\s]+)(https?:\/\/[^\s]+)/', '$2', $linksBaseUrl);
        
        $linksBaseUrl = rtrim($linksBaseUrl, '/'); // Remover barra final
        
        // Garantir que não está vazio e tem protocolo
        if (empty($linksBaseUrl) || !preg_match('/^https?:\/\//', $linksBaseUrl)) {
            $linksBaseUrl = 'http://localhost';
        }
        
        return $linksBaseUrl;
    }
    
    /**
     * Formata número de telefone para formato WhatsApp
     * 
     * @param string $phone Número de telefone
     * @return string Número formatado (ex: 5511999998888@c.us)
     */
    private function formatWhatsAppNumber(string $phone): string
    {
        // Remove tudo que não é número
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adiciona DDI 55 se não tiver
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }
        
        // Formato Evolution API: 5511999998888@c.us
        return $phone . '@c.us';
    }
    
    /**
     * Envia mensagem para Evolution API
     * 
     * @param string $number Número formatado
     * @param string $message Texto da mensagem
     * @param array &$logData Dados de log (referência para atualizar)
     * @return array Resposta da API
     * @throws \Exception Se falhar ao enviar
     */
    private function sendToEvolutionAPI(string $number, string $message, array &$logData): array
    {
        $url = "{$this->apiUrl}/message/sendText/{$this->instance}";
        
        $payload = [
            'number' => $number,
            'text' => $message
        ];
        
        $logData['api_url'] = $url;
        $logData['api_instance'] = $this->instance;
        
        $ch = curl_init($url);
        
        // Headers de autenticação (Evolution API usa 2 níveis)
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->apiKey  // API Key global
        ];
        
        // Adicionar token da instância se disponível
        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        $logData['tempo_resposta'] = round(($endTime - $startTime) * 1000, 2) . 'ms';
        $logData['http_code'] = $httpCode;
        $logData['curl_error'] = $curlError ?: null;
        $logData['curl_errno'] = $curlErrno ?: null;
        
        if ($curlError) {
            $logData['status'] = 'erro';
            $logData['erro'] = 'Erro cURL: ' . $curlError;
            $logData['api_response_raw'] = $response;
            throw new \Exception('Erro cURL: ' . $curlError);
        }
        
        $responseData = json_decode($response, true);
        $logData['api_response_raw'] = $response;
        
        if ($httpCode !== 200 && $httpCode !== 201) {
            $logData['status'] = 'erro';
            $logData['erro'] = "Evolution API retornou código {$httpCode}";
            $logData['api_response'] = $responseData;
            throw new \Exception("Evolution API retornou código {$httpCode}: {$response}");
        }
        
        $logData['api_response'] = $responseData;
        
        return array_merge($responseData ?? [], ['http_code' => $httpCode]);
    }
    
    /**
     * Escreve log em arquivo .log
     * 
     * @param array $data Dados do log
     * @return void
     */
    private function writeLog(array $data): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        
        // Criar diretório se não existir
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/whatsapp_evolution_api.log';
        
        // Formatar linha de log
        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $status = $data['status'] ?? 'unknown';
        $solicitacaoId = $data['solicitacao_id'] ?? 'N/A';
        $protocolo = $data['protocolo'] ?? 'N/A';
        $messageType = $data['message_type'] ?? 'N/A';
        
        // Montar linha de log estruturada
        $logLine = sprintf(
            "[%s] [%s] ID:%s | Protocolo:%s | Tipo:%s",
            $timestamp,
            strtoupper($status),
            $solicitacaoId,
            $protocolo,
            $messageType
        );
        
        // Adicionar informações adicionais
        if (isset($data['cliente_nome'])) {
            $logLine .= " | Cliente:" . $data['cliente_nome'];
        }
        
        if (isset($data['telefone_formatado'])) {
            $logLine .= " | Telefone:" . $data['telefone_formatado'];
        }
        
        if (isset($data['http_code'])) {
            $logLine .= " | HTTP:" . $data['http_code'];
        }
        
        if (isset($data['tempo_resposta'])) {
            $logLine .= " | Tempo:" . $data['tempo_resposta'];
        }
        
        if (isset($data['erro'])) {
            $logLine .= " | ERRO:" . $data['erro'];
        }
        
        if (isset($data['api_response']) && is_array($data['api_response'])) {
            $responseSummary = [];
            if (isset($data['api_response']['key']) && is_array($data['api_response']['key'])) {
                $keyInfo = [];
                if (isset($data['api_response']['key']['id'])) {
                    $keyInfo[] = "id:" . substr($data['api_response']['key']['id'], 0, 20);
                }
                if (isset($data['api_response']['key']['remoteJid'])) {
                    $keyInfo[] = "jid:" . $data['api_response']['key']['remoteJid'];
                }
                if (!empty($keyInfo)) {
                    $responseSummary[] = "key:" . implode('|', $keyInfo);
                }
            }
            if (isset($data['api_response']['status'])) {
                $responseSummary[] = "status:" . $data['api_response']['status'];
            }
            if (isset($data['api_response']['message']) && is_array($data['api_response']['message'])) {
                if (isset($data['api_response']['message']['conversation'])) {
                    $msgPreview = substr($data['api_response']['message']['conversation'], 0, 50);
                    $responseSummary[] = "msg:" . $msgPreview . "...";
                }
            }
            if (!empty($responseSummary)) {
                $logLine .= " | API:" . implode(', ', $responseSummary);
            }
        }
        
        $logLine .= PHP_EOL;
        
        // Adicionar detalhes completos em formato JSON (para debug)
        $logLine .= "  DETALHES: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $logLine .= str_repeat('-', 100) . PHP_EOL;
        
        // Escrever no arquivo
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

