<?php

namespace App\Services;

/**
 * Exceção customizada para erros da Evolution API
 */
class EvolutionApiException extends \Exception
{
    private int $httpStatusCode;

    public function __construct(string $message = "", int $httpStatusCode = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}

/**
 * Serviço para interagir com a Evolution API
 * Gerencia criação de instâncias, QR codes, status, etc.
 */
class EvolutionApiService
{
    private string $apiUrl;
    private string $apiKey;
    private ?string $token;

    /**
     * Escreve log em arquivo whatsapp_evolution_api.log
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

    public function __construct(?string $apiUrl = null, ?string $apiKey = null, ?string $token = null)
    {
        // Se não fornecidos, buscar do config
        if ($apiUrl === null || $apiKey === null) {
            $configFile = __DIR__ . '/../Config/config.php';
            $config = file_exists($configFile) ? require $configFile : [];
            $whatsappConfig = $config['whatsapp'] ?? [];
            
            $this->apiUrl = rtrim($apiUrl ?? $whatsappConfig['api_url'] ?? '', '/');
            $this->apiKey = $apiKey ?? $whatsappConfig['api_key'] ?? '';
            $this->token = $token ?? $whatsappConfig['token'] ?? null;
        } else {
            $this->apiUrl = rtrim($apiUrl, '/');
            $this->apiKey = $apiKey;
            $this->token = $token;
        }
    }

    /**
     * Cria uma nova instância na Evolution API
     * 
     * @param string $instanceName Nome da instância
     * @param array $options Opções adicionais (token, number, webhook, etc.)
     */
    public function criarInstancia(string $instanceName, array $options = []): array
    {
        $startTime = microtime(true);
        $url = "{$this->apiUrl}/instance/create";
        
        // Conforme documentação Evolution API v2, o payload não inclui 'token'
        // A API Key é enviada apenas no header 'apikey'
        $payload = [
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ];
        
        // Adicionar webhook se fornecido (formato da documentação)
        if (isset($options['webhook']) && !empty($options['webhook'])) {
            if (is_array($options['webhook'])) {
                $payload['webhook'] = $options['webhook'];
            } else {
                // Se for string, converter para formato da API
                $payload['webhook'] = [
                    'url' => $options['webhook'],
                    'enabled' => true,
                    'events' => [
                        'MESSAGES_UPSERT',
                        'MESSAGES_UPDATE',
                        'MESSAGES_DELETE',
                        'SEND_MESSAGE',
                        'CONTACTS_SET',
                        'CONTACTS_UPSERT',
                        'CONTACTS_UPDATE',
                        'PRESENCE_UPDATE',
                        'CHATS_SET',
                        'CHATS_UPSERT',
                        'CHATS_UPDATE',
                        'CHATS_DELETE',
                        'GROUPS_UPSERT',
                        'GROUPS_UPDATE',
                        'GROUP_PARTICIPANTS_UPDATE',
                        'CONNECTION_UPDATE',
                        'CALL',
                        'NEW_JWT_TOKEN'
                    ]
                ];
            }
        }
        
        // Se foi fornecido um número, adicionar ao payload
        if (isset($options['number']) && !empty($options['number'])) {
            $payload['number'] = $options['number'];
        }
        
        // Mesclar outras opções (exceto as que já tratamos)
        $otherOptions = array_diff_key($options, ['token' => '', 'webhook' => '', 'number' => '']);
        $payload = array_merge($payload, $otherOptions);

        // Log da tentativa de criação
        $this->writeLog([
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'INICIADO',
            'operation' => 'Criar Instância',
            'instance_name' => $instanceName,
            'api_url' => $this->apiUrl,
            'payload' => $payload
        ]);

        try {
            $response = $this->fazerRequisicao('POST', $url, $payload);
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000, 2) . 'ms';

            // Log de sucesso
            $this->writeLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'SUCESSO',
                'operation' => 'Criar Instância',
                'instance_name' => $instanceName,
                'api_url' => $this->apiUrl,
                'http_code' => 200,
                'tempo_resposta' => $tempoResposta,
                'api_response' => $response,
                'payload_enviado' => $payload
            ]);

            return $response;
        } catch (EvolutionApiException $e) {
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000, 2) . 'ms';
            $httpCode = $e->getHttpStatusCode();

            // Log de erro
            $this->writeLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'ERRO',
                'operation' => 'Criar Instância',
                'instance_name' => $instanceName,
                'api_url' => $this->apiUrl,
                'http_code' => $httpCode,
                'tempo_resposta' => $tempoResposta,
                'erro' => $e->getMessage(),
                'erro_tipo' => 'EvolutionApiException',
                'payload_enviado' => $payload
            ]);

            throw $e;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000, 2) . 'ms';

            // Log de erro genérico
            $this->writeLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'ERRO',
                'operation' => 'Criar Instância',
                'instance_name' => $instanceName,
                'api_url' => $this->apiUrl,
                'tempo_resposta' => $tempoResposta,
                'erro' => $e->getMessage(),
                'erro_tipo' => get_class($e),
                'erro_arquivo' => $e->getFile(),
                'erro_linha' => $e->getLine(),
                'payload_enviado' => $payload
            ]);

            throw $e;
        }
    }

    /**
     * Obtém o QR code de uma instância
     * Se a instância não existir, tenta criá-la automaticamente
     * 
     * @param string $instanceName Nome da instância
     * @param bool $criarSeNaoExistir Se true, tenta criar a instância se não existir
     * @param string|null $number Número de telefone (opcional) para gerar QR code específico
     */
    public function obterQrcode(string $instanceName, bool $criarSeNaoExistir = true, ?string $number = null): array
    {
        // Tentar obter QR code diretamente primeiro, sem verificar existência
        // Isso evita problemas de verificação prévia que podem falhar incorretamente
        
        // Primeiro, tentar obter QR code via /instance/connect
        try {
            $connectUrl = "{$this->apiUrl}/instance/connect/{$instanceName}";
            
            // Se foi fornecido um número, adicionar como query parameter
            if ($number !== null && !empty($number)) {
                $connectUrl .= "?number=" . urlencode($number);
            }
            
            $connectResponse = $this->fazerRequisicao('GET', $connectUrl);
            
            // Log da resposta para debug
            $this->writeLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'INFO',
                'operation' => 'Connect Response',
                'instance_name' => $instanceName,
                'api_url' => $connectUrl,
                'response_keys' => array_keys($connectResponse),
                'has_base64' => isset($connectResponse['base64']),
                'has_qrcode' => isset($connectResponse['qrcode'])
            ]);
            
            // Verificar diferentes formatos de resposta
            if (isset($connectResponse['qrcode'])) {
                // Formato: { "qrcode": { "base64": "..." } }
                if (is_array($connectResponse['qrcode']) && isset($connectResponse['qrcode']['base64'])) {
                    return [
                        'qrcode' => $connectResponse['qrcode']['base64'],
                        'base64' => $connectResponse['qrcode']['base64']
                    ];
                }
                // Formato: { "qrcode": "data:image/png;base64,..." }
                if (is_string($connectResponse['qrcode'])) {
                    // Extrair base64 se vier com prefixo data:image
                    if (strpos($connectResponse['qrcode'], 'data:image') === 0) {
                        $base64 = explode(',', $connectResponse['qrcode'])[1] ?? $connectResponse['qrcode'];
                    } else {
                        $base64 = $connectResponse['qrcode'];
                    }
                    return [
                        'qrcode' => $base64,
                        'base64' => $base64
                    ];
                }
            }
            
            // Verificar se retornou base64 diretamente (formato da documentação)
            // A resposta pode ter: base64, code, pairingCode, count
            if (isset($connectResponse['base64'])) {
                $base64Value = $connectResponse['base64'];
                // Se vier com prefixo data:image, extrair só o base64
                // Pode vir como "data:image/png;base64,iVBORw0KG..." ou apenas "data:image/png;base64,"
                if (strpos($base64Value, 'data:image') !== false) {
                    // Se contém o prefixo, extrair tudo após a vírgula
                    $parts = explode(',', $base64Value, 2);
                    $base64Value = isset($parts[1]) ? $parts[1] : '';
                    // Se ainda estiver vazio ou contiver apenas o prefixo, tentar remover de outra forma
                    if (empty($base64Value) || strpos($base64Value, 'data:image') !== false) {
                        $base64Value = preg_replace('/^data:image\/[^;]+;base64,/', '', $base64Value);
                    }
                }
                // Garantir que não está vazio
                if (!empty($base64Value)) {
                    return [
                        'qrcode' => $base64Value,
                        'base64' => $base64Value,
                        'code' => $connectResponse['code'] ?? null,
                        'pairingCode' => $connectResponse['pairingCode'] ?? null,
                        'count' => $connectResponse['count'] ?? null
                    ];
                }
            }
            
            // Se não retornou base64 mas retornou code, a instância pode estar conectada
            if (isset($connectResponse['code']) && empty($connectResponse['base64'])) {
                // Verificar status para confirmar se está conectado
                try {
                    $statusInfo = $this->verificarStatus($instanceName);
                    $state = strtoupper($statusInfo['instance']['state'] ?? $statusInfo['state'] ?? '');
                    if ($state === 'OPEN' || $state === 'CONNECTED') {
                        return [
                            'connected' => true,
                            'state' => $state,
                            'qrcode' => null
                        ];
                    }
                } catch (\Exception $e) {
                    // Continuar
                }
            }
            
            // Se não retornou QR code mas a requisição foi bem-sucedida (200), 
            // pode ser que a instância esteja conectada ou em outro estado
            // Verificar status antes de lançar erro
            try {
                $statusInfo = $this->verificarStatus($instanceName);
                $state = strtoupper($statusInfo['instance']['state'] ?? $statusInfo['state'] ?? '');
                if ($state === 'OPEN' || $state === 'CONNECTED') {
                    return [
                        'connected' => true,
                        'state' => $state,
                        'qrcode' => null
                    ];
                }
            } catch (\Exception $e) {
                // Se não conseguir verificar status, continuar
            }
        } catch (\Exception $e) {
            // Se falhar, continuar para outras tentativas
            $httpCode = 0;
            if ($e instanceof EvolutionApiException) {
                $httpCode = $e->getHttpStatusCode();
            }
            
            // Se for 404, a instância pode não existir
            if ($httpCode === 404 && $criarSeNaoExistir) {
                // Tentar criar a instância
                try {
                    error_log("Instância '{$instanceName}' não encontrada. Tentando criar automaticamente...");
                    
                    // Preparar opções para criação (incluindo número se fornecido)
                    $createOptions = [];
                    if ($number !== null && !empty($number)) {
                        $createOptions['number'] = $number;
                    }
                    
                    $createResponse = $this->criarInstancia($instanceName, $createOptions);
                    
                    // Verificar se a criação retornou QR code diretamente
                    if (isset($createResponse['qrcode'])) {
                        if (is_array($createResponse['qrcode']) && isset($createResponse['qrcode']['base64'])) {
                            return [
                                'qrcode' => $createResponse['qrcode']['base64'],
                                'base64' => $createResponse['qrcode']['base64']
                            ];
                        }
                        if (is_string($createResponse['qrcode'])) {
                            if (strpos($createResponse['qrcode'], 'data:image') === 0) {
                                $base64 = explode(',', $createResponse['qrcode'])[1] ?? $createResponse['qrcode'];
                            } else {
                                $base64 = $createResponse['qrcode'];
                            }
                            return [
                                'qrcode' => $base64,
                                'base64' => $base64
                            ];
                        }
                    }
                    
                    if (isset($createResponse['base64'])) {
                        return [
                            'qrcode' => $createResponse['base64'],
                            'base64' => $createResponse['base64']
                        ];
                    }
                    
                    // Se criou mas não retornou QR code, tentar obter novamente
                    return $this->obterQrcode($instanceName, false, $number);
                } catch (\Exception $createError) {
                    error_log("Erro ao criar instância automaticamente: " . $createError->getMessage());
                    // Continuar para tentar fetchQrCode
                }
            } else {
                error_log("Erro ao conectar instância para obter QR code: " . $e->getMessage());
            }
        }
        
        // Se não conseguiu via connect, lançar erro
        // Conforme documentação, o QR code é obtido apenas via /instance/connect
        throw new \Exception("A instância '{$instanceName}' não foi encontrada na Evolution API. Verifique se o nome da instância está correto e se a instância foi criada.");
    }

    /**
     * Verifica o status de uma instância
     */
    public function verificarStatus(string $instanceName): array
    {
        // Buscar status de conexão diretamente conforme documentação
        $url = "{$this->apiUrl}/instance/connectionState/{$instanceName}";
        $response = $this->fazerRequisicao('GET', $url);
        
        // Se não retornou dados, buscar informações da instância via fetchInstances
        if (empty($response)) {
            $url = "{$this->apiUrl}/instance/fetchInstances?instanceName=" . urlencode($instanceName);
            $instances = $this->fazerRequisicao('GET', $url);
            
            // A API retorna um array, buscar a instância específica
            if (is_array($instances) && !empty($instances)) {
                foreach ($instances as $instanceData) {
                    if (isset($instanceData['instance']['instanceName']) && 
                        $instanceData['instance']['instanceName'] === $instanceName) {
                        return $instanceData;
                    }
                }
                // Se encontrou instâncias mas não a específica, retornar a primeira
                return $instances[0];
            }
        }
        
        return $response;
    }

    /**
     * Desconecta uma instância
     */
    public function desconectarInstancia(string $instanceName): array
    {
        $url = "{$this->apiUrl}/instance/logout/{$instanceName}";
        return $this->fazerRequisicao('DELETE', $url);
    }

    /**
     * Reinicia uma instância
     */
    public function reiniciarInstancia(string $instanceName): array
    {
        $url = "{$this->apiUrl}/instance/restart/{$instanceName}";
        return $this->fazerRequisicao('PUT', $url);
    }

    /**
     * Deleta uma instância
     */
    public function deletarInstancia(string $instanceName): array
    {
        $url = "{$this->apiUrl}/instance/delete/{$instanceName}";
        return $this->fazerRequisicao('DELETE', $url);
    }

    /**
     * Obtém informações de uma instância
     * Conforme documentação: GET /instance/fetchInstances?instanceName={instanceName}
     */
    public function obterInfoInstancia(string $instanceName): array
    {
        $url = "{$this->apiUrl}/instance/fetchInstances?instanceName=" . urlencode($instanceName);
        $instances = $this->fazerRequisicao('GET', $url);
        
        // A API retorna um array, buscar a instância específica
        if (is_array($instances) && !empty($instances)) {
            foreach ($instances as $instanceData) {
                if (isset($instanceData['instance']['instanceName']) && 
                    $instanceData['instance']['instanceName'] === $instanceName) {
                    return $instanceData;
                }
            }
            // Se encontrou instâncias mas não a específica, retornar a primeira
            return $instances[0];
        }
        
        return [];
    }

    /**
     * Envia mensagem de texto via Evolution API
     * 
     * @param string $instanceName Nome da instância
     * @param string $number Número do destinatário (formato: 5511999998888@c.us)
     * @param string $message Texto da mensagem
     * @return array Resposta da API
     */
    public function sendMessage(string $instanceName, string $number, string $message): array
    {
        $url = "{$this->apiUrl}/message/sendText/{$instanceName}";
        
        $payload = [
            'number' => $number,
            'text' => $message
        ];

        $this->writeLog([
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'INICIADO',
            'operation' => 'Enviar Mensagem',
            'instance_name' => $instanceName,
            'number' => $number,
            'message_length' => strlen($message)
        ]);

        try {
            $response = $this->fazerRequisicao('POST', $url, $payload);
            
            $this->writeLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'SUCESSO',
                'operation' => 'Enviar Mensagem',
                'instance_name' => $instanceName,
                'number' => $number,
                'api_response' => $response
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->writeLog([
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'ERRO',
                'operation' => 'Enviar Mensagem',
                'instance_name' => $instanceName,
                'number' => $number,
                'erro' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Faz uma requisição HTTP para a Evolution API
     */
    private function fazerRequisicao(string $method, string $url, array $payload = []): array
    {
        $ch = curl_init($url);
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->apiKey
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($payload)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new \Exception("Erro cURL: {$curlError}", 0);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = $responseData['message'] ?? $responseData['error'] ?? "HTTP {$httpCode}";
            
            // Para erros específicos, melhorar a mensagem
            if ($httpCode === 403) {
                $errorMsg = "Acesso negado (Forbidden). Verifique se a API Key está correta e tem permissão para esta operação.";
            } elseif ($httpCode === 401) {
                $errorMsg = "Não autorizado. Verifique se a API Key está correta.";
            } elseif ($httpCode === 404) {
                $errorMsg = "Recurso não encontrado.";
            }
            
            throw new EvolutionApiException($errorMsg, $httpCode);
        }
        
        return $responseData ?? [];
    }
}

