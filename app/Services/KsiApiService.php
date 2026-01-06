<?php

namespace App\Services;

use App\Core\Database;

class KsiApiService
{
    private string $url;
    private string $id;
    private string $token;
    
    public function __construct(string $url, string $id, string $token)
    {
        $this->url = rtrim($url, '/');
        $this->id = $id;
        $this->token = $token;
    }
    
    /**
     * Autenticar locatário na API KSI
     */
    public function autenticarLocatario(string $cpf, string $senha): array
    {
        $endpoint = $this->url . '/kurole_include/api/webservice/escopos/';
        
        // Parâmetros da URL (obrigatórios)
        $urlParams = [
            'ws_destino' => 'CLIENTES_AUTENTICACOES',
            'id' => $this->id,
            'token' => $this->token
        ];
        
        // Parâmetros do body
        $bodyParams = [
            'ksi_cli_usuario' => $cpf,
            'ksi_cli_senha' => $senha
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $urlParams, $bodyParams);
        
        // Debug da resposta
        if (defined('DEBUG') && DEBUG) {
            error_log('KSI API Response: ' . json_encode($response));
        }
        
        // A API retorna um array com um objeto
        if (is_array($response) && isset($response[0])) {
            $apiResponse = $response[0];
            
            if (isset($apiResponse['sucesso']) && $apiResponse['sucesso'] == '1' && !empty($apiResponse['dados'])) {
                $cliente = $apiResponse['dados'][0];
                
                // Verificar se o cliente foi autenticado com sucesso
                if (isset($cliente['cliente_autenticado']) && $cliente['cliente_autenticado'] == '1') {
                    return [
                        'success' => true,
                        'cliente' => $cliente,
                        'message' => 'Login realizado com sucesso'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cliente não autenticado'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => $apiResponse['msg'] ?? 'Erro na autenticação'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Resposta inválida da API'
            ];
        }
    }
    
    /**
     * Buscar dados do imóvel do locatário
     */
    public function buscarImovelLocatario(string $idCliente): array
    {
        $endpoint = $this->url . '/kurole_include/api/webservice/escopos/';
        
        // Parâmetros da URL (obrigatórios)
        $urlParams = [
            'ws_destino' => 'IMO_CTR_LOCATARIOS',
            'id' => $this->id,
            'token' => $this->token,
            'id_cliente' => $idCliente
        ];
        
        $response = $this->makeRequest('GET', $endpoint, $urlParams);
        
        // Debug da resposta
        if (defined('DEBUG') && DEBUG) {
            error_log('KSI API Imóveis Response: ' . json_encode($response));
        }
        
        // A API retorna um array com um objeto
        if (is_array($response) && isset($response[0])) {
            $apiResponse = $response[0];
            
            if (isset($apiResponse['sucesso']) && $apiResponse['sucesso'] == '1' && !empty($apiResponse['dados'])) {
                // Mapear os campos da API para nomes mais amigáveis
                $imoveis = [];
                foreach ($apiResponse['dados'] as $imovel) {
                    $imoveis[] = [
                        'codigo' => $imovel['ImoCod'] ?? '',
                        'endereco' => $imovel['ImoEnd'] ?? '',
                        'numero' => $imovel['ImoEndNum'] ?? '',
                        'complemento' => $imovel['ImoEndCompl'] ?? '',
                        'bairro' => $imovel['ImoBaiNom'] ?? '',
                        'cidade' => $imovel['ImoCidNom'] ?? '',
                        'uf' => $imovel['ImoUF'] ?? '',
                        'cep' => $imovel['ImoEndCep'] ?? '',
                        'tipo' => $imovel['ImoTipo'] ?? '',
                        'unidade' => $imovel['ImoEndUnid'] ?? '',
                        'contratos' => $imovel['Ctr'] ?? []
                    ];
                }
                
                return [
                    'success' => true,
                    'imoveis' => $imoveis,
                    'message' => 'Dados do imóvel carregados com sucesso'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $apiResponse['msg'] ?? 'Erro ao buscar dados do imóvel'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Resposta inválida da API'
            ];
        }
    }
    
    /**
     * Fazer requisição HTTP para a API
     */
    private function makeRequest(string $method, string $url, array $urlParams = [], array $bodyParams = []): array
    {
        // Debug: mostrar como a requisição está sendo montada
        error_log('=== KSI API REQUEST DEBUG ===');
        error_log('Method: ' . $method);
        error_log('URL: ' . $url);
        error_log('URL Params: ' . json_encode($urlParams));
        error_log('Body Params: ' . json_encode($bodyParams));
        error_log('Token: ' . $this->token);
        error_log('API ID: ' . $this->id);
        
        $ch = curl_init();
        
        // Montar URL com parâmetros
        $fullUrl = $url;
        if (!empty($urlParams)) {
            $fullUrl .= '?' . http_build_query($urlParams);
        }
        
        error_log('Full URL: ' . $fullUrl);
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];
        
        error_log('Headers: ' . json_encode($headers));
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($method === 'POST' && !empty($bodyParams)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bodyParams));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Debug: mostrar resposta da API
        error_log('HTTP Code: ' . $httpCode);
        error_log('Response: ' . $response);
        error_log('CURL Error: ' . $error);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'sucesso' => '0',
                'msg' => 'Erro de conexão: ' . $error,
                'dados' => []
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'sucesso' => '0',
                'msg' => 'Erro HTTP: ' . $httpCode,
                'dados' => []
            ];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'sucesso' => '0',
                'msg' => 'Erro ao decodificar JSON: ' . json_last_error_msg(),
                'dados' => []
            ];
        }
        
        return $data ?: [
            'sucesso' => '0',
            'msg' => 'Resposta vazia da API',
            'dados' => []
        ];
    }
    
    /**
     * Criar instância do serviço a partir de dados da imobiliária
     */
    public static function fromImobiliaria(array $imobiliaria): self
    {
        return new self(
            $imobiliaria['url_base'],
            $imobiliaria['api_id'],
            $imobiliaria['token']
        );
    }
    
    /**
     * Buscar imobiliária por instância
     */
    public static function getImobiliariaByInstancia(string $instancia): ?array
    {
        $sql = "SELECT * FROM imobiliarias WHERE instancia = ? AND status = 'ATIVA'";
        return Database::fetch($sql, [$instancia]);
    }
}
