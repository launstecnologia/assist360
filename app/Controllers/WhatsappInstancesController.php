<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;

class WhatsappInstancesController extends Controller
{
    private WhatsappInstance $model;

    public function __construct()
    {
        $this->requireAuth();
        $this->model = new WhatsappInstance();
    }

    /**
     * Gera um token UUID no formato: 1B977680-9AE4-449B-B844-FB01C0074B16
     * 
     * @return string Token UUID
     */
    private function gerarTokenUUID(): string
    {
        // Gerar UUID v4 (formato: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx)
        $data = random_bytes(16);
        
        // Definir versão (4) e variante
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Versão 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variante 10
        
        // Converter para string hexadecimal e formatar como UUID
        $hex = bin2hex($data);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    /**
     * Escreve log em arquivo whatsapp_evolution_api.log
     * 
     * @param array $data Dados do log
     * @return void
     */
    private function writeInstanceLog(array $data): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        
        // Criar diretório se não existir
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/whatsapp_evolution_api.log';
        
        // Formatar linha de log
        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $status = $data['status'] ?? 'INFO';
        $operation = $data['operation'] ?? 'N/A';
        $instanceName = $data['instance_name'] ?? 'N/A';
        
        // Montar linha de log estruturada
        $logLine = sprintf(
            "[%s] [%s] Operação:%s | Instância:%s",
            $timestamp,
            strtoupper($status),
            $operation,
            $instanceName
        );
        
        // Adicionar informações adicionais
        if (isset($data['instance_id'])) {
            $logLine .= " | ID:" . $data['instance_id'];
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
        
        if (isset($data['api_url'])) {
            $logLine .= " | API:" . $data['api_url'];
        }
        
        $logLine .= PHP_EOL;
        
        // Adicionar detalhes completos em formato JSON
        $logLine .= "  DETALHES: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $logLine .= str_repeat('-', 100) . PHP_EOL;
        
        // Escrever no arquivo
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Lista todas as instâncias
     */
    public function index(): void
    {
        $instances = $this->model->findAll([], 'created_at DESC');
        
        // Verificar status de cada instância na Evolution API e atualizar no banco
        foreach ($instances as &$instance) {
            try {
                $evolutionService = new EvolutionApiService(
                    $instance['api_url'],
                    $instance['api_key'],
                    $instance['token']
                );
                
                // Buscar informações completas da instância para sincronizar dados
                try {
                    $instanceInfo = $evolutionService->obterInfoInstancia($instance['instance_name']);
                    
                    // A API retorna apikey, não token. O token UUID é apenas para identificação interna.
                    // Se necessário, podemos atualizar outros dados da instância aqui
                    if (!empty($instanceInfo)) {
                        // Log para debug
                        $this->writeInstanceLog([
                            'status' => 'INFO',
                            'operation' => 'Informações da instância obtidas',
                            'instance_name' => $instance['instance_name'],
                            'has_instance_data' => isset($instanceInfo['instance'])
                        ]);
                    }
                } catch (\Exception $e) {
                    // Se não conseguir buscar informações, continuar com verificação de status
                    error_log("Aviso: Não foi possível buscar informações da instância {$instance['instance_name']}: " . $e->getMessage());
                }
                
                $statusInfo = $evolutionService->verificarStatus($instance['instance_name']);
                // A resposta pode ter 'instance.state' ou 'state' diretamente
                $state = strtoupper($statusInfo['instance']['state'] ?? $statusInfo['state'] ?? $statusInfo['status'] ?? '');
                
                // Atualizar status no banco se diferente
                if ($state === 'OPEN' || $state === 'CONNECTED') {
                    if ($instance['status'] !== 'CONECTADO') {
                        $this->model->atualizarStatus($instance['id'], 'CONECTADO');
                        $instance['status'] = 'CONECTADO';
                    }
                } elseif ($state === 'CLOSE' || $state === 'DISCONNECTED') {
                    if ($instance['status'] !== 'DESCONECTADO') {
                        $this->model->atualizarStatus($instance['id'], 'DESCONECTADO');
                        $instance['status'] = 'DESCONECTADO';
                    }
                }
            } catch (\Exception $e) {
                // Se der erro ao verificar, apenas logar e continuar
                error_log("Erro ao verificar status da instância {$instance['instance_name']}: " . $e->getMessage());
            }
        }
        unset($instance); // Liberar referência
        
        $this->view('whatsapp.instances', [
            'pageTitle' => 'Instâncias WhatsApp',
            'currentPage' => 'whatsapp-instances',
            'user' => $_SESSION['user'] ?? null,
            'instances' => $instances
        ]);
    }

    /**
     * Exibe formulário para criar nova instância
     */
    public function create(): void
    {
        $config = require __DIR__ . '/../Config/config.php';
        $whatsappConfig = $config['whatsapp'] ?? [];
        
        $this->view('whatsapp.instances-create', [
            'pageTitle' => 'Nova Instância WhatsApp',
            'currentPage' => 'whatsapp-instances',
            'user' => $_SESSION['user'] ?? null,
            'apiUrl' => $whatsappConfig['api_url'] ?? '',
            'apiKey' => 'E4C35BD2041F-42FB-AD3D-E39810A5E374', // API Key fixa
        ]);
    }

    /**
     * Cria uma nova instância
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect(url('admin/whatsapp-instances'));
            return;
        }

        $config = require __DIR__ . '/../Config/config.php';
        
        $nome = trim($this->input('nome'));
        $instanceName = trim($this->input('instance_name'));
        $apiUrl = trim($this->input('api_url'));
        $apiKey = 'E4C35BD2041F-42FB-AD3D-E39810A5E374'; // API Key fixa registrada
        $token = trim($this->input('token', ''));
        $numeroWhatsapp = trim($this->input('numero_whatsapp', ''));
        
        // Se o token não foi fornecido, gerar automaticamente um UUID
        if (empty($token)) {
            $token = $this->gerarTokenUUID();
        }

        $errors = [];
        if (empty($nome)) $errors[] = 'Nome é obrigatório';
        if (empty($instanceName)) $errors[] = 'Nome da instância é obrigatório';
        if (empty($apiUrl)) $errors[] = 'URL da API é obrigatória';
        if (empty($apiKey)) $errors[] = 'API Key é obrigatória';

        if (!empty($errors)) {
            $_SESSION['flash_message'] = implode('<br>', $errors);
            $_SESSION['flash_type'] = 'error';
            $this->redirect(url('admin/whatsapp-instances/create'));
            return;
        }

        try {
            // Log inicial da tentativa de criação
            $this->writeInstanceLog([
                'status' => 'INICIADO',
                'operation' => 'Criar Instância (Controller)',
                'instance_name' => $instanceName,
                'nome' => $nome,
                'api_url' => $apiUrl,
                'has_token' => !empty($token),
                'token_gerado' => $token, // Log do token gerado
                'numero_whatsapp' => $numeroWhatsapp ?: null
            ]);

            // Verificar se a instância já existe no banco
            $instanciaExistente = $this->model->findByInstanceName($instanceName);
            if ($instanciaExistente) {
                $this->writeInstanceLog([
                    'status' => 'AVISO',
                    'operation' => 'Instância já existe no banco',
                    'instance_name' => $instanceName,
                    'instance_id_existente' => $instanciaExistente['id']
                ]);
                throw new \Exception("Uma instância com o nome '{$instanceName}' já existe no sistema.");
            }

            // Criar instância na Evolution API PRIMEIRO
            $evolutionService = new EvolutionApiService($apiUrl, $apiKey, $token);
            
            // URL do webhook fixa
            $webhookUrl = 'https://kss.launs.com.br/webhook/whatsapp';
            
            // Log para debug
            error_log("🔗 Webhook URL: $webhookUrl");
            
            // Log da configuração do webhook
            $this->writeInstanceLog([
                'status' => 'INFO',
                'operation' => 'Configurando Webhook',
                'instance_name' => $instanceName,
                'webhook_url' => $webhookUrl
            ]);
            
            // Preparar opções para criação (incluindo número e webhook)
            // Conforme documentação Evolution API v2, o payload não inclui 'token'
            // A API Key é enviada apenas no header 'apikey'
            $createOptions = [
                'webhook' => $webhookUrl // Configurar webhook automaticamente
            ];
            if (!empty($numeroWhatsapp)) {
                $createOptions['number'] = $numeroWhatsapp;
            }
            
            $result = $evolutionService->criarInstancia($instanceName, $createOptions);

            // Log da resposta recebida
            $this->writeInstanceLog([
                'status' => 'INFO',
                'operation' => 'Resposta da API',
                'instance_name' => $instanceName,
                'api_response' => $result
            ]);

            // Verificar se a criação foi bem-sucedida
            // A API pode retornar erro mesmo com HTTP 200, então verificamos a resposta
            if (isset($result['error']) && $result['error']) {
                $errorMsg = $result['message'] ?? $result['error'] ?? 'Erro desconhecido ao criar instância';
                throw new \Exception($errorMsg);
            }

            // Verificar se a instância foi criada
            // Conforme documentação Evolution API v2, a resposta tem:
            // { "instance": { "instanceName": "...", "instanceId": "...", "status": "..." }, "hash": {...}, "webhook": {...} }
            $instanceCreated = false;
            
            // Formato da documentação: { "instance": { "instanceName": "...", "status": "close" ou "connecting" } }
            if (isset($result['instance'])) {
                $instanceData = $result['instance'];
                if (isset($instanceData['instanceName']) && $instanceData['instanceName'] === $instanceName) {
                    $instanceCreated = true;
                    // O status pode ser "close", "connecting", "open", etc.
                }
            }
            
            // Formato alternativo: { "status": "SUCCESS" ou "created" }
            if (!$instanceCreated && isset($result['status'])) {
                $status = strtolower($result['status']);
                if ($status === 'success' || $status === 'created' || $status === 'ok') {
                    $instanceCreated = true;
                }
            }
            
            // Formato alternativo: { "instanceName": "..." } diretamente
            if (!$instanceCreated && isset($result['instanceName']) && $result['instanceName'] === $instanceName) {
                $instanceCreated = true;
            }

            if (!$instanceCreated) {
                $errorMsg = 'A instância não foi criada na Evolution API. Verifique se a API Key tem permissão para criar instâncias. Resposta da API: ' . json_encode($result);
                
                $this->writeInstanceLog([
                    'status' => 'ERRO',
                    'operation' => 'Validação de Criação',
                    'instance_name' => $instanceName,
                    'erro' => $errorMsg,
                    'api_response' => $result
                ]);
                
                error_log("Resposta da API ao criar instância: " . json_encode($result));
                throw new \Exception($errorMsg);
            }

            // Log antes de salvar no banco
            $this->writeInstanceLog([
                'status' => 'INFO',
                'operation' => 'Validação OK - Salvando no Banco',
                'instance_name' => $instanceName
            ]);

            // O token UUID gerado é usado apenas para identificação interna
            // A API retorna 'apikey' no hash ou na instância, mas não é um token separado
            $tokenParaSalvar = $token; // Usar o UUID gerado como padrão

            // SÓ AGORA salvar no banco, após confirmar que foi criada na API
            $instanceId = $this->model->create([
                'nome' => $nome,
                'instance_name' => $instanceName,
                'api_url' => $apiUrl,
                'api_key' => $apiKey,
                'token' => $tokenParaSalvar ?: null,
                'numero_whatsapp' => $numeroWhatsapp ?: null,
                'status' => 'CONECTANDO',
                'is_ativo' => 1,
                'is_padrao' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Extrair QR code da resposta de criação (se disponível)
            // Conforme documentação, a resposta pode ter 'qrcode' com 'base64', 'code', 'pairingCode', 'count'
            $qrcode = null;
            
            // Função auxiliar para limpar prefixo data:image
            $limparPrefixDataImage = function($value) {
                if (empty($value)) return null;
                // Remover qualquer prefixo data:image
                if (strpos($value, 'data:image') !== false) {
                    $parts = explode(',', $value, 2);
                    $value = isset($parts[1]) ? $parts[1] : '';
                    // Se ainda contém o prefixo, usar regex para remover
                    if (empty($value) || strpos($value, 'data:image') !== false) {
                        $value = preg_replace('/^data:image\/[^;]+;base64,?/', '', $value);
                    }
                }
                return trim($value);
            };
            
            // Verificar se retornou qrcode na resposta (formato da documentação)
            if (isset($result['qrcode'])) {
                if (is_array($result['qrcode']) && isset($result['qrcode']['base64'])) {
                    $qrcode = $limparPrefixDataImage($result['qrcode']['base64']);
                } elseif (is_string($result['qrcode'])) {
                    $qrcode = $limparPrefixDataImage($result['qrcode']);
                }
            }
            // Se não encontrou em qrcode, verificar em base64 diretamente
            elseif (isset($result['base64'])) {
                $qrcode = $limparPrefixDataImage($result['base64']);
            }

            // Se não veio na resposta, tentar obter separadamente
            if (!$qrcode) {
                try {
                    // Passar o número se foi fornecido
                    $qrcodeData = $evolutionService->obterQrcode(
                        $instanceName, 
                        false, // false = não criar se não existir
                        $numeroWhatsapp ?: null // passar número se disponível
                    );
                    $qrcode = $qrcodeData['qrcode'] ?? $qrcodeData['base64'] ?? null;
                    
                    // Limpar prefixo data:image se houver
                    if ($qrcode && strpos($qrcode, 'data:image') !== false) {
                        $parts = explode(',', $qrcode, 2);
                        $qrcode = isset($parts[1]) ? $parts[1] : '';
                        if (empty($qrcode) || strpos($qrcode, 'data:image') !== false) {
                            $qrcode = preg_replace('/^data:image\/[^;]+;base64,?/', '', $qrcode);
                        }
                        $qrcode = trim($qrcode);
                    }
                } catch (\Exception $e) {
                    // Se não conseguir obter QR code, logar mas não falhar a criação
                    $this->writeInstanceLog([
                        'status' => 'AVISO',
                        'operation' => 'QR Code não obtido',
                        'instance_name' => $instanceName,
                        'erro' => $e->getMessage()
                    ]);
                    error_log("Aviso: Não foi possível obter QR code após criar instância: " . $e->getMessage());
                }
            }

            // Salvar QR code se obtido
            if ($qrcode) {
                $this->model->atualizarQrcode($instanceId, $qrcode);
            }

            // Log de sucesso final
            $this->writeInstanceLog([
                'status' => 'SUCESSO',
                'operation' => 'Instância Criada Completamente',
                'instance_name' => $instanceName,
                'instance_id' => $instanceId,
                'has_qrcode' => !empty($qrcode)
            ]);

            $_SESSION['flash_message'] = 'Instância criada com sucesso! Escaneie o QR code para conectar.';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/whatsapp-instances/' . $instanceId . '/qrcode'));

        } catch (\Exception $e) {
            // Log de erro final
            $this->writeInstanceLog([
                'status' => 'ERRO',
                'operation' => 'Erro ao Criar Instância',
                'instance_name' => $instanceName,
                'erro' => $e->getMessage(),
                'erro_tipo' => get_class($e),
                'erro_arquivo' => $e->getFile(),
                'erro_linha' => $e->getLine()
            ]);

            error_log("Erro ao criar instância: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Erro ao criar instância: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect(url('admin/whatsapp-instances/create'));
        }
    }

    /**
     * Exibe QR code para conectar instância
     */
    public function qrcode(int $id): void
    {
        $instance = $this->model->find($id);
        
        if (!$instance) {
            $_SESSION['flash_message'] = 'Instância não encontrada';
            $_SESSION['flash_type'] = 'error';
            $this->redirect(url('admin/whatsapp-instances'));
            return;
        }

        // Sempre verificar o status na Evolution API e atualizar no banco
        try {
            $evolutionService = new EvolutionApiService(
                $instance['api_url'],
                $instance['api_key'],
                $instance['token']
            );
            
            // Buscar informações completas da instância para sincronizar dados
            try {
                $instanceInfo = $evolutionService->obterInfoInstancia($instance['instance_name']);
                
                // A API retorna apikey, não token. O token UUID é apenas para identificação interna.
                // Se necessário, podemos atualizar outros dados da instância aqui
                if (!empty($instanceInfo)) {
                    // Log para debug
                    $this->writeInstanceLog([
                        'status' => 'INFO',
                        'operation' => 'Informações da instância obtidas',
                        'instance_name' => $instance['instance_name'],
                        'has_instance_data' => isset($instanceInfo['instance'])
                    ]);
                }
            } catch (\Exception $e) {
                // Se não conseguir buscar informações, continuar com verificação de status
                error_log("Aviso: Não foi possível buscar informações da instância {$instance['instance_name']}: " . $e->getMessage());
            }
            
            // Primeiro, verificar o status de conexão
            $statusInfo = $evolutionService->verificarStatus($instance['instance_name']);
            // A resposta pode ter 'instance.state' ou 'state' diretamente
            $state = strtoupper($statusInfo['instance']['state'] ?? $statusInfo['state'] ?? $statusInfo['status'] ?? '');
            
            // Se estiver conectado, atualizar status no banco
            if ($state === 'OPEN' || $state === 'CONNECTED') {
                $this->model->atualizarStatus($id, 'CONECTADO');
                $instance['status'] = 'CONECTADO';
                $instance['qrcode'] = null; // Limpar QR code quando conectado
            } 
            // Se não estiver conectado e não tiver QR code, tentar obter
            elseif (empty($instance['qrcode']) || $instance['status'] === 'DESCONECTADO') {
                $numero = $instance['numero_whatsapp'] ?? null;
                $qrcodeData = $evolutionService->obterQrcode($instance['instance_name'], false, $numero);
                
                // Verificar se já está conectado (pode ter conectado durante a obtenção do QR code)
                if (isset($qrcodeData['connected']) && $qrcodeData['connected']) {
                    $this->model->atualizarStatus($id, 'CONECTADO');
                    $instance['status'] = 'CONECTADO';
                    $instance['qrcode'] = null;
                } else {
                    // Extrair QR code dos diferentes formatos possíveis
                    $qrcode = $qrcodeData['qrcode'] ?? $qrcodeData['base64'] ?? null;
                    
                    // Limpar prefixo data:image se houver
                    if ($qrcode && strpos($qrcode, 'data:image') !== false) {
                        $parts = explode(',', $qrcode, 2);
                        $qrcode = isset($parts[1]) ? $parts[1] : '';
                        if (empty($qrcode) || strpos($qrcode, 'data:image') !== false) {
                            $qrcode = preg_replace('/^data:image\/[^;]+;base64,?/', '', $qrcode);
                        }
                        $qrcode = trim($qrcode);
                    }
                    
                    if ($qrcode) {
                        $this->model->atualizarQrcode($id, $qrcode);
                        $this->model->atualizarStatus($id, 'CONECTANDO');
                        $instance['qrcode'] = $qrcode;
                        $instance['status'] = 'CONECTANDO';
                    } else {
                        // Se não retornou QR code, pode ser que a instância não exista ou esteja em estado inválido
                        error_log("QR code não retornado pela API. Resposta: " . json_encode($qrcodeData));
                        $_SESSION['flash_message'] = 'QR code não disponível. A instância pode não existir na Evolution API ou estar em estado inválido.';
                        $_SESSION['flash_type'] = 'warning';
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Erro ao verificar status ou obter QR code: " . $e->getMessage());
            
            // Verificar se é erro de instância não encontrada
            $errorMsg = strtolower($e->getMessage());
            if (strpos($errorMsg, 'não encontrada') !== false || 
                strpos($errorMsg, 'not found') !== false ||
                strpos($errorMsg, '404') !== false) {
                $_SESSION['flash_message'] = "A instância '{$instance['instance_name']}' não foi encontrada na Evolution API. Verifique se o nome da instância está correto e se a instância foi criada.";
            } else {
                $_SESSION['flash_message'] = 'Erro ao verificar status: ' . $e->getMessage();
            }
            $_SESSION['flash_type'] = 'error';
        }

        $this->view('whatsapp.instances-qrcode', [
            'pageTitle' => 'Conectar Instância WhatsApp',
            'currentPage' => 'whatsapp-instances',
            'user' => $_SESSION['user'] ?? null,
            'instance' => $instance
        ]);
    }

    /**
     * Atualiza QR code via AJAX
     */
    public function atualizarQrcode(int $id): void
    {
        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        try {
            $evolutionService = new EvolutionApiService(
                $instance['api_url'],
                $instance['api_key'],
                $instance['token']
            );
            
            // Primeiro, verificar o status da instância
            try {
                $statusInfo = $evolutionService->verificarStatus($instance['instance_name']);
                $state = strtoupper($statusInfo['instance']['state'] ?? $statusInfo['state'] ?? $statusInfo['status'] ?? '');
                
                // Se já está conectado, não precisa de QR code
                if ($state === 'OPEN' || $state === 'CONNECTED') {
                    $this->model->atualizarStatus($id, 'CONECTADO');
                    $this->model->atualizarQrcode($id, null); // Limpar QR code
                    $this->json([
                        'success' => true,
                        'connected' => true,
                        'message' => 'Instância já está conectada'
                    ]);
                    return;
                }
            } catch (\Exception $e) {
                // Se não conseguir verificar status, continuar para obter QR code
                error_log("Aviso: Não foi possível verificar status antes de obter QR code: " . $e->getMessage());
            }
            
            // Obter QR code (permitir criar se não existir, pois pode ter sido deletada)
            // Passar o número se disponível na instância
            $numero = $instance['numero_whatsapp'] ?? null;
            $qrcodeData = $evolutionService->obterQrcode($instance['instance_name'], true, $numero); // true = criar se não existir
            
            // Verificar se já está conectado na resposta
            if (isset($qrcodeData['connected']) && $qrcodeData['connected']) {
                $this->model->atualizarStatus($id, 'CONECTADO');
                $this->model->atualizarQrcode($id, null); // Limpar QR code
                $this->json([
                    'success' => true,
                    'connected' => true,
                    'message' => 'Instância já está conectada'
                ]);
                return;
            }
            
            // Extrair QR code de diferentes formatos possíveis
            $qrcode = null;
            
            // Formato 1: qrcode direto
            if (isset($qrcodeData['qrcode'])) {
                $qrcode = $qrcodeData['qrcode'];
            }
            // Formato 2: base64 direto
            elseif (isset($qrcodeData['base64'])) {
                $qrcode = $qrcodeData['base64'];
            }
            
            // Limpar prefixo data:image se houver (pode vir em diferentes formatos)
            if ($qrcode) {
                // Remover qualquer prefixo data:image (com ou sem vírgula)
                if (strpos($qrcode, 'data:image') !== false) {
                    // Se contém o prefixo, extrair tudo após a vírgula
                    $parts = explode(',', $qrcode, 2);
                    $qrcode = isset($parts[1]) ? $parts[1] : '';
                    // Se ainda contém o prefixo, usar regex para remover
                    if (empty($qrcode) || strpos($qrcode, 'data:image') !== false) {
                        $qrcode = preg_replace('/^data:image\/[^;]+;base64,?/', '', $qrcode);
                    }
                }
                // Remover espaços em branco e quebras de linha
                $qrcode = trim($qrcode);
            }
            
            if ($qrcode) {
                // Salvar QR code no banco
                $this->model->atualizarQrcode($id, $qrcode);
                $this->model->atualizarStatus($id, 'CONECTANDO');
                
                $this->json([
                    'success' => true,
                    'qrcode' => $qrcode,
                    'message' => 'QR Code atualizado com sucesso'
                ]);
            } else {
                // Se não retornou QR code, pode ser que a instância não exista ou esteja em estado inválido
                $this->json([
                    'success' => false,
                    'error' => 'QR Code não disponível. A instância pode não existir na Evolution API ou estar em estado inválido.',
                    'debug' => $qrcodeData
                ], 400);
            }
            
        } catch (\Exception $e) {
            error_log("Erro ao atualizar QR code: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Mensagem de erro mais amigável
            $errorMsg = $e->getMessage();
            
            // Verificar se é erro de instância não encontrada
            $errorMsgLower = strtolower($errorMsg);
            if (strpos($errorMsgLower, 'não encontrada') !== false || 
                strpos($errorMsgLower, 'not found') !== false ||
                strpos($errorMsgLower, '404') !== false) {
                $errorMsg = "A instância '{$instance['instance_name']}' não foi encontrada na Evolution API. Verifique se o nome da instância está correto e se a instância foi criada.";
            }
            
            $this->json([
                'success' => false,
                'error' => $errorMsg,
                'instance_name' => $instance['instance_name'] ?? null
            ], 500);
        }
    }

    /**
     * Verifica status da instância (AJAX)
     */
    public function verificarStatus(int $id): void
    {
        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        try {
            $evolutionService = new EvolutionApiService(
                $instance['api_url'],
                $instance['api_key'],
                $instance['token']
            );
            
            $statusData = $evolutionService->verificarStatus($instance['instance_name']);
            
            // Atualizar status no banco
            $status = 'DESCONECTADO';
            $numeroWhatsapp = null;
            
            // Interpretar resposta da Evolution API
            // A resposta pode ter 'instance.state' ou 'state' diretamente
            $state = strtoupper($statusData['instance']['state'] ?? $statusData['state'] ?? $statusData['status'] ?? '');
            
            if ($state === 'OPEN' || $state === 'CONNECTED') {
                $status = 'CONECTADO';
                $numeroWhatsapp = $statusData['instance']['owner'] ?? $statusData['owner'] ?? $statusData['phone'] ?? null;
            } elseif ($state === 'CLOSE' || $state === 'DISCONNECTED') {
                $status = 'DESCONECTADO';
            } elseif ($state === 'CONNECTING' || $state === 'QRCODE') {
                $status = 'CONECTANDO';
            }
            
            $this->model->atualizarStatus($id, $status, $numeroWhatsapp);
            
            // Buscar instância atualizada
            $instance = $this->model->find($id);
            
            $this->json([
                'success' => true,
                'status' => $instance['status'],
                'numero_whatsapp' => $instance['numero_whatsapp'],
                'qrcode' => $instance['qrcode']
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Define instância como padrão
     */
    public function setPadrao(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        if ($instance['status'] !== 'CONECTADO') {
            $this->json(['error' => 'A instância deve estar conectada para ser definida como padrão'], 400);
            return;
        }

        $this->model->setPadrao($id);
        
        $this->json(['success' => true, 'message' => 'Instância definida como padrão']);
    }

    /**
     * Reinicia uma instância
     */
    public function reiniciar(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        try {
            $evolutionService = new EvolutionApiService(
                $instance['api_url'],
                $instance['api_key'],
                $instance['token']
            );
            
            $evolutionService->reiniciarInstancia($instance['instance_name']);
            $this->model->atualizarStatus($id, 'CONECTANDO');
            
            $this->json(['success' => true, 'message' => 'Instância reiniciada com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Faz logout de uma instância
     */
    public function logout(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        try {
            $evolutionService = new EvolutionApiService(
                $instance['api_url'],
                $instance['api_key'],
                $instance['token']
            );
            
            $evolutionService->desconectarInstancia($instance['instance_name']);
            $this->model->atualizarStatus($id, 'DESCONECTADO');
            
            $this->json(['success' => true, 'message' => 'Logout realizado com sucesso']);
            
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Desconecta uma instância
     */
    public function desconectar(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        try {
            $evolutionService = new EvolutionApiService(
                $instance['api_url'],
                $instance['api_key'],
                $instance['token']
            );
            
            $evolutionService->desconectarInstancia($instance['instance_name']);
            $this->model->atualizarStatus($id, 'DESCONECTADO');
            
            $this->json(['success' => true, 'message' => 'Instância desconectada']);
            
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Deleta uma instância
     */
    public function destroy(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $instance = $this->model->find($id);
        
        if (!$instance) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        try {
            // Deletar da Evolution API (pode falhar se a instância não existir, mas continuamos)
            try {
                $evolutionService = new EvolutionApiService(
                    $instance['api_url'],
                    $instance['api_key'],
                    $instance['token']
                );
                
                $evolutionService->deletarInstancia($instance['instance_name']);
            } catch (\Exception $e) {
                // Logar mas continuar com a exclusão do banco mesmo se falhar na API
                error_log("Aviso: Não foi possível deletar instância da Evolution API: " . $e->getMessage());
            }
            
            // Deletar do banco
            $this->model->delete($id);
            
            $this->json([
                'success' => true,
                'message' => 'Instância deletada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro ao deletar instância: " . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

