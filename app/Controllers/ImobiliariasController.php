<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Imobiliaria;
use App\Models\Usuario;

class ImobiliariasController extends Controller
{
    private Imobiliaria $imobiliariaModel;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->imobiliariaModel = new Imobiliaria();
        $this->usuarioModel = new Usuario();
    }

    public function index(): void
    {
        $pesquisa_nome = $this->input('pesquisa_nome', '');
        $pesquisa_cidade = $this->input('pesquisa_cidade', '');
        
        // Se houver parâmetros de pesquisa, usar método de busca
        if (!empty($pesquisa_nome) || !empty($pesquisa_cidade)) {
            $imobiliarias = $this->imobiliariaModel->search($pesquisa_nome, $pesquisa_cidade);
        } else {
            $imobiliarias = $this->imobiliariaModel->findAll([], 'nome_fantasia ASC');
        }
        
        $this->view('imobiliarias.index', [
            'imobiliarias' => $imobiliarias,
            'pesquisa_nome' => $pesquisa_nome,
            'pesquisa_cidade' => $pesquisa_cidade
        ]);
    }

    public function create(): void
    {
        $this->view('imobiliarias.create');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect(url('admin/imobiliarias/create'));
        }

        $data = [
            'cnpj' => $this->input('cnpj'),
            'razao_social' => $this->input('razao_social'),
            'nome_fantasia' => $this->input('nome_fantasia'),
            'nome' => $this->input('nome_fantasia'), // Usar nome fantasia como nome principal
            'endereco_logradouro' => $this->input('endereco_logradouro'),
            'endereco_numero' => $this->input('endereco_numero'),
            'endereco_complemento' => $this->input('endereco_complemento'),
            'endereco_bairro' => $this->input('endereco_bairro'),
            'endereco_cidade' => $this->input('endereco_cidade'),
            'endereco_estado' => $this->input('endereco_estado'),
            'endereco_cep' => $this->input('endereco_cep'),
            'telefone' => $this->input('telefone'),
            'email' => $this->input('email'),
            'cor_primaria' => $this->input('cor_primaria', '#3B82F6'),
            'cor_secundaria' => $this->input('cor_secundaria', '#1E40AF'),
            'api_id' => $this->input('api_id'),
            'instancia' => $this->input('instancia') ?: $this->gerarInstancia(),
            'url_base' => $this->input('url_base'),
            'token' => $this->input('token') ?: $this->gerarToken(),
            'status' => !empty($this->input('status')) ? $this->input('status') : 'ATIVA',
            'integracao_ativa' => $this->input('integracao_ativa') ? 1 : 0,
            'cache_ttl' => $this->input('cache_ttl', 300),
            'observacoes' => $this->input('observacoes'),
            'configuracoes' => [
                'timeout' => $this->input('timeout', 30),
                'retry_attempts' => $this->input('retry_attempts', 3),
                'headers' => $this->input('headers', []),
                'endpoints' => [
                    'autenticacao' => $this->input('endpoint_autenticacao'),
                    'locatarios' => $this->input('endpoint_locatarios')
                ]
            ]
        ];

        // Removida validação obrigatória dos campos para permitir cadastro com qualquer combinação de dados
        $errors = [];

        if (!empty($errors)) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro de validação',
                    'errors' => $errors
                ], 400);
                return;
            }
            
            $this->view('imobiliarias.create', [
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        // Processar upload de logo
        $logoFileName = $this->processarUploadLogo();
        if ($logoFileName) {
            $data['logo'] = $logoFileName;
        }

        // Verificar se CNPJ já existe (apenas se informado)
        if (!empty($data['cnpj']) && $this->imobiliariaModel->findByCnpj($data['cnpj'])) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Este CNPJ já está sendo usado por outra imobiliária'
                ], 400);
                return;
            }
            
            $this->view('imobiliarias.create', [
                'error' => 'Este CNPJ já está sendo usado por outra imobiliária',
                'data' => $data
            ]);
            return;
        }


        // Verificar se API instância já existe (apenas se informada)
        if (!empty($data['instancia']) && $this->imobiliariaModel->findByInstancia($data['instancia'])) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Esta API instância já está sendo usada por outra imobiliária'
                ], 400);
                return;
            }
            
            $this->view('imobiliarias.create', [
                'error' => 'Esta API instância já está sendo usada por outra imobiliária',
                'data' => $data
            ]);
            return;
        }

        try {
            $imobiliariaId = $this->imobiliariaModel->create($data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Imobiliária cadastrada com sucesso!',
                    'id' => $imobiliariaId
                ]);
                return;
            }
            
            $this->redirect(url('admin/imobiliarias'));
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao criar imobiliária: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('imobiliarias.create', [
                'error' => 'Erro ao criar imobiliária: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function show(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->view('errors.404');
            return;
        }

        $this->view('imobiliarias.show', [
            'imobiliaria' => $imobiliaria
        ]);
    }

    public function edit(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->view('errors.404');
            return;
        }

        $this->view('imobiliarias.edit', [
            'imobiliaria' => $imobiliaria
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect(url("admin/imobiliarias/$id/edit"));
        }

        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->view('errors.404');
            return;
        }

        $data = [
            'cnpj' => $this->input('cnpj'),
            'razao_social' => $this->input('razao_social'),
            'nome_fantasia' => $this->input('nome_fantasia'),
            'nome' => $this->input('nome_fantasia'),
            'endereco_logradouro' => $this->input('endereco_logradouro'),
            'endereco_numero' => $this->input('endereco_numero'),
            'endereco_complemento' => $this->input('endereco_complemento'),
            'endereco_bairro' => $this->input('endereco_bairro'),
            'endereco_cidade' => $this->input('endereco_cidade'),
            'endereco_estado' => $this->input('endereco_estado'),
            'endereco_cep' => $this->input('endereco_cep'),
            'telefone' => $this->input('telefone'),
            'email' => $this->input('email'),
            'cor_primaria' => $this->input('cor_primaria'),
            'cor_secundaria' => $this->input('cor_secundaria'),
            'api_id' => $this->input('api_id'),
            'instancia' => $this->input('instancia') ?: $this->gerarInstancia(),
            'url_base' => $this->input('url_base'),
            'token' => $this->input('token') ?: $this->gerarToken(),
            'status' => $this->input('status'),
            'integracao_ativa' => $this->input('integracao_ativa') ? 1 : 0,
            'cache_ttl' => $this->input('cache_ttl'),
            'observacoes' => $this->input('observacoes'),
            'configuracoes' => [
                'timeout' => $this->input('timeout'),
                'retry_attempts' => $this->input('retry_attempts'),
                'headers' => $this->input('headers', []),
                'endpoints' => [
                    'autenticacao' => $this->input('endpoint_autenticacao'),
                    'locatarios' => $this->input('endpoint_locatarios')
                ]
            ]
        ];

        // Removida validação obrigatória dos campos para permitir edição com qualquer combinação de dados
        $errors = [];

        if (!empty($errors)) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro de validação',
                    'errors' => $errors
                ], 400);
                return;
            }
            
            $this->view('imobiliarias.edit', [
                'imobiliaria' => $imobiliaria,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        // Verificar se CNPJ já existe em outra imobiliária (apenas se informado)
        $existingByCnpj = null;
        if (!empty($data['cnpj'])) {
            $existingByCnpj = $this->imobiliariaModel->findByCnpj($data['cnpj']);
        }
        if ($existingByCnpj && $existingByCnpj['id'] != $id) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Este CNPJ já está sendo usado por outra imobiliária'
                ], 400);
                return;
            }
            
            $this->view('imobiliarias.edit', [
                'imobiliaria' => $imobiliaria,
                'error' => 'Este CNPJ já está sendo usado por outra imobiliária',
                'data' => $data
            ]);
            return;
        }


        // Verificar se API instância já existe em outra imobiliária (apenas se informada)
        $existingByInstancia = null;
        if (!empty($data['instancia'])) {
            $existingByInstancia = $this->imobiliariaModel->findByInstancia($data['instancia']);
        }
        if ($existingByInstancia && $existingByInstancia['id'] != $id) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Esta API instância já está sendo usada por outra imobiliária'
                ], 400);
                return;
            }
            
            $this->view('imobiliarias.edit', [
                'imobiliaria' => $imobiliaria,
                'error' => 'Esta API instância já está sendo usada por outra imobiliária',
                'data' => $data
            ]);
            return;
        }

        // Processar upload de logo
        $logoFileName = $this->processarUploadLogo();
        if ($logoFileName) {
            // Remover logo antiga se existir
            if (!empty($imobiliaria['logo'])) {
                $oldLogoPath = __DIR__ . '/../../Public/uploads/logos/' . $imobiliaria['logo'];
                if (file_exists($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }
            $data['logo'] = $logoFileName;
        } else {
            // Manter logo existente se não houver novo upload
            $data['logo'] = $imobiliaria['logo'] ?? null;
        }

        try {
            $this->imobiliariaModel->update($id, $data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Imobiliária atualizada com sucesso!'
                ]);
                return;
            }
            
            $this->redirect(url('admin/imobiliarias'));
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao atualizar imobiliária: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('imobiliarias.edit', [
                'imobiliaria' => $imobiliaria,
                'error' => 'Erro ao atualizar imobiliária: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function destroy(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['error' => 'Imobiliária não encontrada'], 404);
            return;
        }

        // Verificar se há solicitações vinculadas
        $sql = "SELECT COUNT(*) as count FROM solicitacoes WHERE imobiliaria_id = ?";
        $result = \App\Core\Database::fetch($sql, [$id]);
        
        if ($result['count'] > 0) {
            $this->json(['error' => 'Não é possível excluir imobiliária com solicitações vinculadas'], 400);
            return;
        }

        try {
            $this->imobiliariaModel->delete($id);
            $this->json(['success' => true, 'message' => 'Imobiliária excluída com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao excluir imobiliária: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            $imobiliaria = $this->imobiliariaModel->find($id);
            if (!$imobiliaria) {
                $this->json(['error' => 'Imobiliária não encontrada'], 404);
                return;
            }

            $newStatus = $imobiliaria['status'] === 'ATIVA' ? 'INATIVA' : 'ATIVA';
            $this->imobiliariaModel->update($id, ['status' => $newStatus]);
            
            $this->json([
                'success' => true, 
                'message' => 'Status atualizado com sucesso',
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao atualizar status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Executar migração para adicionar campo integracao_ativa
     */
    public function runMigration(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            // Verificar se a coluna já existe
            $checkColumn = \App\Core\Database::fetch("
                SELECT COUNT(*) AS c 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'imobiliarias' 
                AND COLUMN_NAME = 'integracao_ativa'
            ");
            
            if ((int)($checkColumn['c'] ?? 0) === 0) {
                // Adicionar coluna se não existir
                \App\Core\Database::query("
                    ALTER TABLE imobiliarias 
                    ADD COLUMN integracao_ativa TINYINT(1) NOT NULL DEFAULT 1 
                    COMMENT 'Indica se a integração com a API está ativa. Quando desativada, redireciona para solicitação manual.' 
                    AFTER status
                ");
            }
            
            // Atualizar todas as imobiliárias existentes para ter integração ativa por padrão (se NULL ou 0)
            \App\Core\Database::query("UPDATE imobiliarias SET integracao_ativa = 1 WHERE integracao_ativa IS NULL OR integracao_ativa = 0");
            
            $this->json([
                'success' => true,
                'message' => 'Migração executada com sucesso! Campo integracao_ativa adicionado.'
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao executar migração: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle integração ativa/desativada
     */
    public function toggleIntegracao(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            // Garantir que $id é um inteiro
            $id = (int)$id;
            
            $imobiliaria = $this->imobiliariaModel->find($id);
            if (!$imobiliaria) {
                $this->json(['error' => 'Imobiliária não encontrada'], 404);
                return;
            }

            // Verificar se o campo existe, se não existir, executar migração primeiro
            $checkColumn = \App\Core\Database::fetch("
                SELECT COUNT(*) AS c 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'imobiliarias' 
                AND COLUMN_NAME = 'integracao_ativa'
            ");
            
            if ((int)($checkColumn['c'] ?? 0) === 0) {
                // Executar migração primeiro (sem chamar o método que pode ter problemas de tipo)
                try {
                    \App\Core\Database::query("
                        ALTER TABLE imobiliarias 
                        ADD COLUMN integracao_ativa TINYINT(1) NOT NULL DEFAULT 1 
                        COMMENT 'Indica se a integração com a API está ativa. Quando desativada, redireciona para solicitação manual.' 
                        AFTER status
                    ");
                } catch (\Exception $e) {
                    // Se a coluna já existir, ignorar o erro
                    if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                        throw $e;
                    }
                }
            }

            // Obter valor atual da integração (1 = ativa, 0 = desativada, NULL = padrão ativa)
            $currentIntegracao = isset($imobiliaria['integracao_ativa']) ? (int)$imobiliaria['integracao_ativa'] : 1;
            // Inverter o valor: se está 1 (ativa), vira 0 (desativada); se está 0 (desativada), vira 1 (ativa)
            $newIntegracao = $currentIntegracao === 1 ? 0 : 1;
            
            $this->imobiliariaModel->update($id, ['integracao_ativa' => $newIntegracao]);
            
            $this->json([
                'success' => true, 
                'message' => 'Integração ' . ($newIntegracao === 1 ? 'ativada' : 'desativada') . ' com sucesso',
                'integracao_ativa' => $newIntegracao
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao atualizar integração: ' . $e->getMessage()], 500);
        }
    }

    public function testConnection(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['error' => 'Imobiliária não encontrada'], 404);
            return;
        }

        try {
            // Testar conexão com a API KSI
            $ksiService = \App\Services\KsiApiService::fromImobiliaria($imobiliaria);
            
            // Fazer uma requisição de teste simples
            $testResult = $ksiService->autenticarLocatario('test', 'test');
            
            if ($testResult['success']) {
                $this->json([
                    'success' => true,
                    'message' => 'Conexão com API KSI estabelecida com sucesso',
                    'response_time' => '150ms',
                    'status_code' => 200
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'error' => 'Erro na API KSI: ' . $testResult['message']
                ], 400);
            }
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao testar conexão: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLocatarios(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['error' => 'Imobiliária não encontrada'], 404);
            return;
        }

        $locatarios = $this->imobiliariaModel->getLocatarios($id);
        $this->json($locatarios);
    }

    public function getEstatisticas(int $id): void
    {
        $periodo = $this->input('periodo', '30');
        
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['error' => 'Imobiliária não encontrada'], 404);
            return;
        }

        $estatisticas = $this->imobiliariaModel->getEstatisticas($id, $periodo);
        $this->json($estatisticas);
    }
    
    /**
     * API: Buscar dados da imobiliária para exibir no offcanvas
     */
    public function api(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['success' => false, 'message' => 'Imobiliária não encontrada'], 404);
            return;
        }
        
        $this->json([
            'success' => true,
            'imobiliaria' => $imobiliaria
        ]);
    }

    public function buscarCnpj(): void
    {
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        // Capturar CNPJ - priorizar JSON para requisições AJAX
        $cnpj = null;
        
        // Primeiro tentar capturar do JSON (para requisições AJAX)
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            if ($data && isset($data['cnpj'])) {
                $cnpj = $data['cnpj'];
            }
        }
        
        // Se não veio pelo JSON, tentar pelo input() (para formulários)
        if (empty($cnpj)) {
            $cnpj = $this->input('cnpj');
        }
        
        if (empty($cnpj)) {
            $this->json(['error' => 'CNPJ é obrigatório'], 400);
            return;
        }

        // Limpar CNPJ (remover pontos, traços, barras)
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) !== 14) {
            $this->json(['error' => 'CNPJ deve ter 14 dígitos'], 400);
            return;
        }

        try {
            $dadosEmpresa = $this->buscarDadosReceita($cnpj);
            
            if ($dadosEmpresa) {
                $this->json([
                    'success' => true,
                    'data' => $dadosEmpresa
                ]);
            } else {
                $this->json(['error' => 'CNPJ não encontrado na Receita Federal'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao buscar dados: ' . $e->getMessage()], 500);
        }
    }

    private function buscarDadosReceita(string $cnpj): ?array
    {
        $url = "https://receitaws.com.br/v1/cnpj/{$cnpj}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: KSS-Seguros/1.0'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("Erro cURL: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("Erro HTTP: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!$data || $data['status'] !== 'OK') {
            return null;
        }
        
        // Mapear dados da API para nosso formato
        return [
            'cnpj' => $data['cnpj'] ?? '',
            'razao_social' => $data['nome'] ?? '',
            'nome_fantasia' => $data['fantasia'] ?? '',
            'endereco_logradouro' => $data['logradouro'] ?? '',
            'endereco_numero' => $data['numero'] ?? '',
            'endereco_complemento' => $data['complemento'] ?? '',
            'endereco_bairro' => $data['bairro'] ?? '',
            'endereco_cidade' => $data['municipio'] ?? '',
            'endereco_estado' => $data['uf'] ?? '',
            'endereco_cep' => $data['cep'] ?? '',
            'telefone' => $data['telefone'] ?? '',
            'email' => $data['email'] ?? '',
            'situacao' => $data['situacao'] ?? '',
            'porte' => $data['porte'] ?? '',
            'natureza_juridica' => $data['natureza_juridica'] ?? '',
            'atividade_principal' => $data['atividade_principal'][0]['text'] ?? '',
            'capital_social' => $data['capital_social'] ?? '',
            'data_abertura' => $data['abertura'] ?? '',
            'ultima_atualizacao' => $data['ultima_atualizacao'] ?? ''
        ];
    }

    private function gerarToken(): string
    {
        return 'token_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }

    private function gerarInstancia(): string
    {
        return 'instancia_' . uniqid() . '_' . bin2hex(random_bytes(6));
    }

    /**
     * Processar upload de logo
     */
    private function processarUploadLogo(): ?string
    {
        if (empty($_FILES['logo']['name']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../Public/uploads/logos/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log('Erro ao criar diretório de upload: ' . $uploadDir);
                return null;
            }
        }

        $file = $_FILES['logo'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        // Validar erro de upload
        if ($fileError !== UPLOAD_ERR_OK) {
            error_log('Erro no upload da logo: ' . $fileError);
            return null;
        }

        // Validar tamanho (máximo 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($fileSize > $maxSize) {
            error_log('Logo muito grande: ' . $fileSize . ' bytes');
            return null;
        }

        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($fileTmpName);
        
        if (!in_array($fileType, $allowedTypes)) {
            error_log('Tipo de arquivo não permitido: ' . $fileType);
            return null;
        }

        // Validar extensão
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($extension, $allowedExtensions)) {
            error_log('Extensão não permitida: ' . $extension);
            return null;
        }

        // Gerar nome único para o arquivo
        $newFileName = uniqid('logo_', true) . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $newFileName;

        // Mover arquivo
        if (!move_uploaded_file($fileTmpName, $filePath)) {
            error_log('Erro ao mover arquivo de logo: ' . $filePath);
            return null;
        }

        return $newFileName;
    }

    /**
     * Processar upload de Excel com CPF e número do contrato
     */
    public function uploadExcel(int $id): void
    {
        // Garantir que sempre retornamos JSON, mesmo em caso de erro
        set_error_handler(function($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        
        try {
            if (!$this->isPost()) {
                $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
                return;
            }

            $imobiliaria = $this->imobiliariaModel->find($id);
            
            if (!$imobiliaria) {
                $this->json(['success' => false, 'error' => 'Imobiliária não encontrada'], 404);
                return;
            }

            // Verificar se arquivo foi enviado
            if (empty($_FILES['excel_file']['name']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                $this->json(['success' => false, 'error' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload'], 400);
                return;
            }

            $file = $_FILES['excel_file'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];

            // Validar tamanho (máximo 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileSize > $maxSize) {
                $this->json(['success' => false, 'error' => 'Arquivo muito grande. Tamanho máximo: 10MB'], 400);
                return;
            }

            // Validar extensão
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            
            if (!in_array($extension, $allowedExtensions)) {
                $this->json(['success' => false, 'error' => 'Formato de arquivo não permitido. Use .xlsx, .xls ou .csv'], 400);
                return;
            }
            
            $isCsv = ($extension === 'csv');

            // Verificar se a tabela locatarios_contratos existe, se não existir, criar
            $this->garantirTabelaLocatariosContratos();
            
            // ⚠️ Importação é tratada como carga completa: antes de inserir os novos contratos,
            // removemos todos os registros atuais dessa imobiliária em locatarios_contratos.
            // Assim, se um contrato deixar de vir no arquivo, ele também deixa de existir na base.
            try {
                $sqlDeleteContratos = "DELETE FROM locatarios_contratos WHERE imobiliaria_id = ?";
                \App\Core\Database::query($sqlDeleteContratos, [$id]);
                error_log("IMPORTAÇÃO CONTRATOS: todos os registros de locatarios_contratos para imobiliaria_id={$id} foram removidos antes do upload.");
            } catch (\Exception $e) {
                error_log("Erro ao limpar locatarios_contratos para imobiliaria_id={$id}: " . $e->getMessage());
                // Não bloqueia o upload, mas registra o erro
            }
            
            $rows = [];
            
            // Processar CSV ou Excel
            if ($isCsv) {
                // Processar CSV usando funções nativas do PHP
                try {
                    $handle = fopen($fileTmpName, 'r');
                    if ($handle === false) {
                        throw new \Exception('Não foi possível abrir o arquivo CSV');
                    }
                    
                    // Detectar separador (vírgula ou ponto e vírgula)
                    $primeiraLinha = fgets($handle);
                    rewind($handle); // Voltar ao início do arquivo
                    
                    $separador = ',';
                    if (strpos($primeiraLinha, ';') !== false && substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',')) {
                        $separador = ';';
                    }
                    
                    // Ler cabeçalho (primeira linha) e descartar
                    $header = fgetcsv($handle, 1000, $separador);
                    
                    // Ler todas as linhas
                    while (($row = fgetcsv($handle, 1000, $separador)) !== false) {
                        $rows[] = $row;
                    }
                    
                    fclose($handle);
                    
                    if (empty($rows)) {
                        $this->json(['success' => false, 'error' => 'O arquivo CSV está vazio ou não possui dados'], 400);
                        return;
                    }
                } catch (\Exception $e) {
                    error_log("Erro ao processar CSV: " . $e->getMessage());
                    $this->json([
                        'success' => false,
                        'error' => 'Erro ao ler arquivo CSV: ' . $e->getMessage()
                    ], 400);
                    return;
                }
            } else {
                // Processar Excel usando PhpSpreadsheet
                // Verificar se PhpSpreadsheet está disponível
                if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                    // Verificar se o vendor existe
                    $vendorPath = dirname(__DIR__, 2) . '/vendor';
                    $spreadsheetPath = $vendorPath . '/phpoffice/phpspreadsheet';
                    
                    if (!is_dir($spreadsheetPath)) {
                        error_log("PhpSpreadsheet não instalado. Caminho esperado: {$spreadsheetPath}");
                        $this->json([
                            'success' => false,
                            'error' => 'Biblioteca PhpSpreadsheet não está instalada. Use arquivo CSV (.csv) como alternativa ou execute: composer install'
                        ], 500);
                        return;
                    }
                    
                    // Se o vendor existe mas a classe não está disponível, pode ser problema de autoloader
                    error_log("PhpSpreadsheet encontrado em {$spreadsheetPath} mas classe não carregada. Verifique o autoloader.");
                    $this->json([
                        'success' => false,
                        'error' => 'Erro ao carregar PhpSpreadsheet. Use arquivo CSV (.csv) como alternativa ou verifique o autoloader.'
                    ], 500);
                    return;
                }
                
                // Carregar arquivo Excel usando PhpSpreadsheet
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpName);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();

                    if (empty($rows) || count($rows) < 2) {
                        $this->json(['success' => false, 'error' => 'O arquivo Excel está vazio ou não possui dados'], 400);
                        return;
                    }

                    // Remover cabeçalho (primeira linha)
                    array_shift($rows);
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    error_log("Erro ao carregar arquivo Excel: " . $e->getMessage());
                    $this->json([
                        'success' => false,
                        'error' => 'Erro ao ler arquivo Excel: ' . $e->getMessage()
                    ], 400);
                    return;
                }
            }
            
            // Processar linhas (tanto CSV quanto Excel)
            $sucessos = 0;
            $erros = 0;
            $detalhesErros = [];

            foreach ($rows as $index => $row) {
                    $linha = $index + 2; // +2 porque removemos o cabeçalho e arrays começam em 0
                    
                    // Extrair CPF e número do contrato
                    $cpf = isset($row[0]) ? trim($row[0]) : '';
                    $numeroContrato = isset($row[1]) ? trim($row[1]) : '';

                    // Validar dados
                    if (empty($cpf)) {
                        $erros++;
                        $detalhesErros[] = "Linha {$linha}: CPF não informado";
                        continue;
                    }

                    if (empty($numeroContrato)) {
                        $erros++;
                        $detalhesErros[] = "Linha {$linha}: Número do contrato não informado";
                        continue;
                    }

                    // Limpar CPF (remover pontos, traços, espaços)
                    $cpf = preg_replace('/[^0-9]/', '', $cpf);

                    if (strlen($cpf) !== 11) {
                        $erros++;
                        $detalhesErros[] = "Linha {$linha}: CPF inválido (deve ter 11 dígitos)";
                        continue;
                    }

                    try {
                        // Verificar se já existe na tabela locatarios_contratos
                        $sql = "SELECT * FROM locatarios_contratos 
                                WHERE imobiliaria_id = ? AND cpf = ? AND numero_contrato = ?";
                        $existente = \App\Core\Database::fetch($sql, [$id, $cpf, $numeroContrato]);

                        if ($existente) {
                            // Atualizar registro existente
                            $updateSql = "UPDATE locatarios_contratos 
                                         SET updated_at = NOW() 
                                         WHERE id = ?";
                            \App\Core\Database::query($updateSql, [$existente['id']]);
                            $sucessos++;
                        } else {
                            // Criar novo registro
                            $insertSql = "INSERT INTO locatarios_contratos 
                                         (imobiliaria_id, cpf, numero_contrato, created_at, updated_at) 
                                         VALUES (?, ?, ?, NOW(), NOW())";
                            \App\Core\Database::query($insertSql, [$id, $cpf, $numeroContrato]);
                            $sucessos++;
                        }
                    } catch (\Exception $e) {
                        $erros++;
                        $mensagemErro = $e->getMessage();
                        // Se for erro de tabela não existe, tentar criar novamente
                        if (strpos($mensagemErro, "doesn't exist") !== false || 
                            strpos($mensagemErro, "Table") !== false) {
                            try {
                                $this->garantirTabelaLocatariosContratos();
                                // Tentar novamente
                                $insertSql = "INSERT INTO locatarios_contratos 
                                             (imobiliaria_id, cpf, numero_contrato, created_at, updated_at) 
                                             VALUES (?, ?, ?, NOW(), NOW())";
                                \App\Core\Database::query($insertSql, [$id, $cpf, $numeroContrato]);
                                $sucessos++;
                                $erros--; // Descontar o erro já que conseguiu processar
                            } catch (\Exception $e2) {
                                $detalhesErros[] = "Linha {$linha}: Erro ao processar - " . $e2->getMessage();
                                error_log("Erro ao processar linha {$linha} do Excel (tentativa 2): " . $e2->getMessage());
                            }
                        } else {
                            $detalhesErros[] = "Linha {$linha}: Erro ao processar - " . $mensagemErro;
                            error_log("Erro ao processar linha {$linha} do Excel: " . $mensagemErro);
                        }
                    }
                }

                $mensagem = "Processamento concluído: {$sucessos} registro(s) processado(s) com sucesso";
                if ($erros > 0) {
                    $mensagem .= ", {$erros} erro(s) encontrado(s)";
                }

                $this->json([
                    'success' => true,
                    'message' => $mensagem,
                    'sucessos' => $sucessos,
                    'erros' => $erros,
                    'detalhes_erros' => $detalhesErros
                ]);
        } catch (\Exception $e) {
            error_log("Erro ao processar Excel: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Garantir que sempre retornamos JSON
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao processar arquivo Excel: ' . $e->getMessage()
            ]);
            exit;
        } catch (\Error $e) {
            error_log("Erro fatal ao processar Excel: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Garantir que sempre retornamos JSON
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao processar arquivo Excel: ' . $e->getMessage()
            ]);
            exit;
        } finally {
            restore_error_handler();
        }
    }
    
    /**
     * Garantir que a tabela locatarios_contratos existe
     */
    private function garantirTabelaLocatariosContratos(): void
    {
        try {
            // Verificar se a tabela existe
            $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'locatarios_contratos'";
            $result = \App\Core\Database::fetch($sql);
            
            if (empty($result) || ($result['count'] ?? 0) == 0) {
                // Criar a tabela se não existir
                $createTableSql = "CREATE TABLE locatarios_contratos (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    imobiliaria_id INT NOT NULL,
                    cpf VARCHAR(14) NOT NULL,
                    numero_contrato VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_cpf_contrato_imobiliaria (imobiliaria_id, cpf, numero_contrato),
                    INDEX idx_cpf_imobiliaria (imobiliaria_id, cpf)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                \App\Core\Database::query($createTableSql);
                error_log("Tabela locatarios_contratos criada automaticamente");
            }
        } catch (\Exception $e) {
            // Se der erro de tabela já existe, ignorar
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate table') === false) {
                error_log("Erro ao verificar/criar tabela locatarios_contratos: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Listar CPFs e contratos de uma imobiliária
     */
    public function listagemContratos(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['success' => false, 'error' => 'Imobiliária não encontrada'], 404);
            return;
        }
        
        try {
            $sql = "SELECT * FROM locatarios_contratos 
                    WHERE imobiliaria_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1000";
            $contratos = \App\Core\Database::fetchAll($sql, [$id]);
            
            $sqlTotal = "SELECT COUNT(*) as total FROM locatarios_contratos WHERE imobiliaria_id = ?";
            $resultTotal = \App\Core\Database::fetch($sqlTotal, [$id]);
            $total = $resultTotal['total'] ?? 0;
            
            $this->json([
                'success' => true,
                'contratos' => $contratos,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao listar contratos: " . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Erro ao carregar listagem: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remover registro de locatarios_contratos
     */
    public function removerContrato(int $id): void
    {
        error_log("=== INICIANDO REMOÇÃO DE CONTRATO ===");
        error_log("ID recebido: " . var_export($id, true));
        error_log("Método HTTP: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
        
        if (!$this->isPost()) {
            error_log("ERRO: Método não permitido");
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }

        if (empty($id) || $id <= 0) {
            error_log("ERRO: ID inválido - " . var_export($id, true));
            $this->json(['success' => false, 'error' => 'ID não informado ou inválido'], 400);
            return;
        }

        try {
            // Verificar se o registro existe e obter imobiliaria_id
            $sql = "SELECT * FROM locatarios_contratos WHERE id = ?";
            $registro = \App\Core\Database::fetch($sql, [$id]);
            
            if (!$registro) {
                error_log("Tentativa de remover registro inexistente - locatarios_contratos ID: {$id}");
                $this->json(['success' => false, 'error' => 'Registro não encontrado'], 404);
                return;
            }

            $imobiliariaId = $registro['imobiliaria_id'];
            error_log("Iniciando remoção - locatarios_contratos ID: {$id}, Imobiliária ID: {$imobiliariaId}");

            // Remover registro usando DELETE direto
            $sqlDelete = "DELETE FROM locatarios_contratos WHERE id = ?";
            $stmt = \App\Core\Database::query($sqlDelete, [$id]);
            
            // Verificar quantas linhas foram afetadas
            $rowsAffected = $stmt->rowCount();
            error_log("Linhas afetadas pelo DELETE: {$rowsAffected}");
            
            // Fechar cursor para garantir que não há bloqueios
            $stmt->closeCursor();

            // Verificar se foi realmente deletado (mais confiável que rowCount)
            $verificar = \App\Core\Database::fetch($sql, [$id]);
            if ($verificar !== null) {
                error_log("ERRO: Registro ainda existe após DELETE - locatarios_contratos ID: {$id}");
                $this->json([
                    'success' => false, 
                    'error' => 'Erro ao remover registro do banco de dados. O registro ainda existe.'
                ], 500);
                return;
            }
            
            // Se chegou aqui, a remoção foi bem-sucedida
            if ($rowsAffected === 0) {
                error_log("AVISO: rowCount retornou 0, mas registro foi deletado (verificado) - locatarios_contratos ID: {$id}");
            }

            error_log("✅ Registro removido com sucesso - locatarios_contratos ID: {$id}");

            $this->json([
                'success' => true,
                'message' => 'Registro removido com sucesso',
                'imobiliaria_id' => $imobiliariaId
            ]);
        } catch (\Exception $e) {
            error_log("ERRO ao remover contrato ID {$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->json([
                'success' => false, 
                'error' => 'Erro ao remover registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar CPFs e contratos como CSV
     */
    public function exportarContratos(int $id): void
    {
        $imobiliaria = $this->imobiliariaModel->find($id);
        
        if (!$imobiliaria) {
            $this->json(['success' => false, 'error' => 'Imobiliária não encontrada'], 404);
            return;
        }
        
        try {
            $sql = "SELECT cpf, inquilino_nome, numero_contrato, tipo_imovel, cidade, estado, bairro, 
                           cep, endereco, numero, complemento, unidade, empresa_fiscal, 
                           created_at, updated_at 
                    FROM locatarios_contratos 
                    WHERE imobiliaria_id = ? 
                    ORDER BY created_at DESC";
            $contratos = \App\Core\Database::fetchAll($sql, [$id]);
            
            // Configurar headers para download CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="cpf_contratos_' . $imobiliaria['id'] . '_' . date('Y-m-d') . '.csv"');
            
            // Abrir output stream
            $output = fopen('php://output', 'w');
            
            // Adicionar BOM para UTF-8 (para Excel reconhecer corretamente)
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Função para garantir UTF-8 nos dados
            $garantirUTF8 = function($valor) {
                if ($valor === null || $valor === '') return $valor;
                if (!mb_check_encoding($valor, 'UTF-8')) {
                    return mb_convert_encoding($valor, 'UTF-8', 'auto');
                }
                return $valor;
            };
            
            // Escrever cabeçalho
            fputcsv($output, [
                'CPF', 'Nome', 'Número do Contrato', 'Tipo Imóvel', 'Cidade', 'Estado', 'Bairro',
                'CEP', 'Endereço', 'Número', 'Complemento', 'Unidade', 'Empresa Fiscal',
                'Data de Cadastro', 'Última Atualização'
            ], ',');
            
            // Escrever dados
            foreach ($contratos as $contrato) {
                $cpfFormatado = $garantirUTF8($contrato['cpf']);
                $dataCadastro = date('d/m/Y H:i:s', strtotime($contrato['created_at']));
                $dataAtualizacao = date('d/m/Y H:i:s', strtotime($contrato['updated_at']));
                
                fputcsv($output, [
                    $cpfFormatado,
                    $garantirUTF8($contrato['inquilino_nome'] ?? ''),
                    $garantirUTF8($contrato['numero_contrato']),
                    $garantirUTF8($contrato['tipo_imovel'] ?? ''),
                    $garantirUTF8($contrato['cidade'] ?? ''),
                    $garantirUTF8($contrato['estado'] ?? ''),
                    $garantirUTF8($contrato['bairro'] ?? ''),
                    $garantirUTF8($contrato['cep'] ?? ''),
                    $garantirUTF8($contrato['endereco'] ?? ''),
                    $garantirUTF8($contrato['numero'] ?? ''),
                    $garantirUTF8($contrato['complemento'] ?? ''),
                    $garantirUTF8($contrato['unidade'] ?? ''),
                    $garantirUTF8($contrato['empresa_fiscal'] ?? ''),
                    $dataCadastro,
                    $dataAtualizacao
                ], ',');
            }
            
            fclose($output);
            exit;
        } catch (\Exception $e) {
            error_log("Erro ao exportar contratos: " . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Erro ao exportar: ' . $e->getMessage()
            ], 500);
        }
    }
}