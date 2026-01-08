<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\KsiApiService;
use App\Models\Solicitacao;
use App\Models\Locatario;

class LocatarioController extends Controller
{
    private Solicitacao $solicitacaoModel;
    private Locatario $locatarioModel;
    
    public function __construct()
    {
        $this->solicitacaoModel = new Solicitacao();
        $this->locatarioModel = new Locatario();
    }
    
    /**
     * Login do locatário
     */
    public function login(string $instancia = ''): void
    {
        if ($this->isPost()) {
            $this->processarLogin();
            return;
        }
        
        // Se a instância não foi passada como parâmetro, extrair da URL
        if (empty($instancia)) {
            $instancia = $this->getInstanciaFromUrl();
        }
        
        $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
        
        if (!$imobiliaria) {
            // Exibir tela de imobiliária não encontrada
            $this->view('locatario.imobiliaria-nao-encontrada', [
                'instancia' => $instancia
            ]);
            return;
        }
        
        // Verificar se a integração está ativa
        $integracaoAtiva = isset($imobiliaria['integracao_ativa']) ? (bool)$imobiliaria['integracao_ativa'] : true;
        if (!$integracaoAtiva) {
            // Redirecionar para solicitação manual se integração estiver desativada
            $this->redirect(url($instancia . '/solicitacao-manual'));
            return;
        }
        
        $this->view('locatario.login', [
            'imobiliaria' => $imobiliaria,
            'instancia' => $instancia
        ]);
    }
    
    /**
     * Redirect com parâmetros de query
     */
    private function redirectWithParams(string $url, array $params = []): void
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $this->redirect($url);
    }
    
    /**
     * Processar login do locatário
     */
    private function processarLogin(): void
    {
        $cpf = $this->input('cpf');
        $senha = $this->input('senha');
        $instancia = $this->input('instancia');
        
        if (empty($cpf) || empty($senha) || empty($instancia)) {
            $this->redirectWithParams(url($instancia), [
                'error' => 'Todos os campos são obrigatórios'
            ]);
            return;
        }
        
        // Buscar dados da imobiliária
        $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
        
        if (!$imobiliaria) {
            $this->redirectWithParams(url($instancia), [
                'error' => 'Imobiliária não encontrada'
            ]);
            return;
        }
        
        // Criar serviço da API KSI
        $ksiApi = KsiApiService::fromImobiliaria($imobiliaria);
        
        // Autenticar na API
        $resultado = $ksiApi->autenticarLocatario($cpf, $senha);
        
        if ($resultado['success']) {
            $cliente = $resultado['cliente'];
            
            // Buscar dados do imóvel
            $imovelResult = $ksiApi->buscarImovelLocatario($cliente['id_cliente']);
            
            // Validar regras de acesso (Bolsão e Data do Contrato)
            $validacaoAcesso = \App\Services\ValidacaoAcessoService::validarRegrasAcesso(
                $cpf, 
                $imobiliaria['id'], 
                $imovelResult['success'] ? $imovelResult['imoveis'] : []
            );
            
            // Salvar dados na sessão
            $_SESSION['locatario'] = [
                'id' => $cliente['id_cliente'],
                'nome' => $cliente['nome'],
                'cpf' => $cpf,
                'email' => $cliente['email'] ?? null,
                'telefone' => $cliente['telefone'] ?? null,
                'whatsapp' => $cliente['whatsapp'] ?? $cliente['telefone'] ?? null,
                'imobiliaria_id' => $imobiliaria['id'],
                'imobiliaria_nome' => $imobiliaria['nome'],
                'instancia' => $instancia,
                'imoveis' => $imovelResult['success'] ? $imovelResult['imoveis'] : [],
                'login_time' => time(),
                'acesso_permitido' => $validacaoAcesso['permitido'],
                'motivo_bloqueio' => $validacaoAcesso['motivo_bloqueio'] ?? null,
                'validacao_bolsao' => $validacaoAcesso['bolsao'] ?? false
            ];
            
            $this->redirect(url($instancia . '/dashboard'));
        } else {
            $this->redirectWithParams(url($instancia), [
                'error' => $resultado['message']
            ]);
        }
    }
    
    /**
     * Dashboard do locatário
     */
    public function dashboard(string $instancia = ''): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        // Revalidar acesso (Regra 1: Bolsão, Regra 2: Data do contrato - 45 dias)
        $cpf = $locatario['cpf'] ?? '';
        $imobiliariaId = $locatario['imobiliaria_id'] ?? 0;
        $imoveis = $locatario['imoveis'] ?? [];
        
        if (!empty($cpf) && !empty($imobiliariaId)) {
            $validacaoAcesso = \App\Services\ValidacaoAcessoService::validarRegrasAcesso(
                $cpf,
                $imobiliariaId,
                $imoveis
            );
            
            // Atualizar sessão com nova validação
            $_SESSION['locatario']['acesso_permitido'] = $validacaoAcesso['permitido'];
            $_SESSION['locatario']['motivo_bloqueio'] = $validacaoAcesso['motivo_bloqueio'] ?? null;
            $_SESSION['locatario']['validacao_bolsao'] = $validacaoAcesso['bolsao'] ?? false;
            
            $locatario['acesso_permitido'] = $validacaoAcesso['permitido'];
            $locatario['motivo_bloqueio'] = $validacaoAcesso['motivo_bloqueio'] ?? null;
            $locatario['validacao_bolsao'] = $validacaoAcesso['bolsao'] ?? false;
        }
        
        // Sempre buscar dados atualizados do banco para garantir consistência
        $locatarioBanco = null;
        
        // Tentar buscar por CPF primeiro
        if (!empty($locatario['cpf'])) {
            $cpfLimpo = str_replace(['.', '-'], '', $locatario['cpf']);
            $locatarioBanco = $this->locatarioModel->findByCpfAndImobiliaria($cpfLimpo, $locatario['imobiliaria_id']);
        }
        
        // Se não encontrou por CPF, tentar por ksi_cliente_id
        if (!$locatarioBanco && !empty($locatario['id']) && !empty($locatario['imobiliaria_id'])) {
            $locatarioBanco = $this->locatarioModel->findByKsiIdAndImobiliaria($locatario['id'], $locatario['imobiliaria_id']);
        }
        
        // Se encontrou dados no banco, atualizar com os dados mais recentes
            if ($locatarioBanco) {
            // Atualizar dados do locatário com dados do banco (usar dados do banco se existirem)
            $locatario['nome'] = $locatarioBanco['nome'] ?? $locatario['nome'];
            $locatario['whatsapp'] = $locatarioBanco['whatsapp'] ?? $locatario['whatsapp'] ?? '';
            $locatario['telefone'] = $locatarioBanco['telefone'] ?? $locatario['telefone'] ?? '';
            $locatario['email'] = $locatarioBanco['email'] ?? $locatario['email'] ?? '';
                
            // Atualizar sessão com dados atualizados do banco
            $_SESSION['locatario']['nome'] = $locatario['nome'];
                $_SESSION['locatario']['whatsapp'] = $locatario['whatsapp'];
                $_SESSION['locatario']['telefone'] = $locatario['telefone'];
                $_SESSION['locatario']['email'] = $locatario['email'];
        }
        
        // Buscar solicitações do locatário
        $solicitacoes = $this->solicitacaoModel->getByLocatario($locatario['id']);
        
        // Estatísticas
        $stats = [
            'total' => count($solicitacoes),
            'agendadas' => count(array_filter($solicitacoes, fn($s) => $s['status_nome'] === 'Serviço Agendado')),
            'concluidas' => count(array_filter($solicitacoes, fn($s) => $s['status_nome'] === 'Concluído (NCP)'))
        ];
        
        $this->view('locatario.dashboard', [
            'locatario' => $locatario,
            'solicitacoes' => $solicitacoes,
            'stats' => $stats
        ]);
    }
    
    /**
     * Lista de solicitações do locatário
     */
    public function solicitacoes(string $instancia = ''): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        // Revalidar acesso (Regra 1: Bolsão, Regra 2: Data do contrato - 45 dias)
        $cpf = $locatario['cpf'] ?? '';
        $imobiliariaId = $locatario['imobiliaria_id'] ?? 0;
        $imoveis = $locatario['imoveis'] ?? [];
        
        if (!empty($cpf) && !empty($imobiliariaId)) {
            $validacaoAcesso = \App\Services\ValidacaoAcessoService::validarRegrasAcesso(
                $cpf,
                $imobiliariaId,
                $imoveis
            );
            
            // Atualizar sessão com nova validação
            $_SESSION['locatario']['acesso_permitido'] = $validacaoAcesso['permitido'];
            $_SESSION['locatario']['motivo_bloqueio'] = $validacaoAcesso['motivo_bloqueio'] ?? null;
            $_SESSION['locatario']['validacao_bolsao'] = $validacaoAcesso['bolsao'] ?? false;
            
            $locatario['acesso_permitido'] = $validacaoAcesso['permitido'];
            $locatario['motivo_bloqueio'] = $validacaoAcesso['motivo_bloqueio'] ?? null;
            $locatario['validacao_bolsao'] = $validacaoAcesso['bolsao'] ?? false;
        }
        
        $solicitacoes = $this->solicitacaoModel->getByLocatario($locatario['id']);
        
        $this->view('locatario.solicitacoes', [
            'locatario' => $locatario,
            'solicitacoes' => $solicitacoes
        ]);
    }
    
    /**
     * Perfil do locatário
     */
    public function perfil(string $instancia = ''): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        // Sempre buscar dados atualizados do banco
        $locatarioBanco = null;
        
        // Tentar buscar por CPF primeiro
        if (!empty($locatario['cpf'])) {
            $cpfLimpo = str_replace(['.', '-'], '', $locatario['cpf']);
            $locatarioBanco = $this->locatarioModel->findByCpfAndImobiliaria($cpfLimpo, $locatario['imobiliaria_id']);
        }
        
        // Se não encontrou por CPF, tentar por ksi_cliente_id
        if (!$locatarioBanco && !empty($locatario['id']) && !empty($locatario['imobiliaria_id'])) {
            $locatarioBanco = $this->locatarioModel->findByKsiIdAndImobiliaria($locatario['id'], $locatario['imobiliaria_id']);
        }
        
        if ($locatarioBanco) {
            // Atualizar dados do locatário com dados do banco (priorizar dados do banco)
            $locatario['nome'] = $locatarioBanco['nome'] ?? $locatario['nome'];
            $locatario['whatsapp'] = $locatarioBanco['whatsapp'] ?? $locatario['whatsapp'] ?? '';
            $locatario['telefone'] = $locatarioBanco['telefone'] ?? $locatario['telefone'] ?? '';
            $locatario['email'] = $locatarioBanco['email'] ?? $locatario['email'] ?? '';
            
            // Atualizar sessão com dados atualizados do banco
            $_SESSION['locatario']['nome'] = $locatario['nome'];
            $_SESSION['locatario']['whatsapp'] = $locatario['whatsapp'];
            $_SESSION['locatario']['telefone'] = $locatario['telefone'];
            $_SESSION['locatario']['email'] = $locatario['email'];
        }
        
        $this->view('locatario.perfil', [
            'locatario' => $locatario
        ]);
    }
    
    /**
     * Nova solicitação
     */
    /**
     * Endpoint para verificar limite de solicitações ao selecionar categoria
     * GET /{instancia}/verificar-limite-categoria?categoria_id=X&numero_contrato=Y
     */
    public function verificarLimiteCategoria(string $instancia = ''): void
    {
        $this->requireLocatarioAuth();
        
        $categoriaId = $this->input('categoria_id');
        $numeroContrato = $this->input('numero_contrato', '');
        
        if (empty($categoriaId)) {
            $this->json([
                'success' => false,
                'message' => 'Categoria não informada'
            ], 400);
            return;
        }
        
        if (empty($numeroContrato)) {
            // Se não houver contrato, permitir (sem limite)
            $this->json([
                'success' => true,
                'permitido' => true,
                'limite' => null,
                'total_atual' => 0,
                'mensagem' => 'Sem contrato informado'
            ]);
            return;
        }
        
        try {
            $categoriaModel = new \App\Models\Categoria();
            $verificacao = $categoriaModel->verificarLimiteSolicitacoes((int)$categoriaId, $numeroContrato);
            
            $this->json([
                'success' => true,
                'permitido' => $verificacao['permitido'],
                'limite' => $verificacao['limite'],
                'total_atual' => $verificacao['total_atual'],
                'mensagem' => $verificacao['mensagem']
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao verificar limite de categoria: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Erro ao verificar limite'
            ], 500);
        }
    }

    public function novaSolicitacao(string $instancia = ''): void
    {
        // LOG CRÍTICO: Verificar se método é chamado
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - novaSolicitacao() chamado - Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
        
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        // Verificar se o acesso está permitido (Regra 1: Bolsão, Regra 2: Data do contrato)
        $acessoPermitido = $locatario['acesso_permitido'] ?? true;
        if (!$acessoPermitido) {
            $motivoBloqueio = $locatario['motivo_bloqueio'] ?? 'Acesso bloqueado';
            $this->redirect(url($locatario['instancia'] . '/dashboard?error=' . urlencode($motivoBloqueio)));
            return;
        }
        
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Passou requireLocatarioAuth\n", FILE_APPEND);
        
        if ($this->isPost()) {
            // Processar envio do formulário da etapa 1
            file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - É POST! Processando etapa 1\n", FILE_APPEND);
            file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
            
            file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - ANTES de chamar salvarDadosEtapa\n", FILE_APPEND);
            error_log("CRÍTICO: ANTES de chamar salvarDadosEtapa");
            
            $this->salvarDadosEtapa(1);
            
            file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - DEPOIS de salvarDadosEtapa\n", FILE_APPEND);
            error_log("CRÍTICO: DEPOIS de salvarDadosEtapa");
            return;
        }
        
        // Limpar dados da sessão apenas quando é GET (começar nova solicitação)
        unset($_SESSION['nova_solicitacao']);
        
        $locatario = $_SESSION['locatario'];
        
        // IMPORTANTE: Recarregar imóveis da API se estiverem vazios
        if (empty($locatario['imoveis'])) {
            error_log("DEBUG: Imóveis vazios, recarregando da API...");
            
            // Buscar imobiliária
            $imobiliaria = KsiApiService::getImobiliariaByInstancia($locatario['instancia']);
            
            if ($imobiliaria) {
                // Criar serviço da API
                $ksiApi = KsiApiService::fromImobiliaria($imobiliaria);
                
                // Buscar imóveis do locatário
                $imovelResult = $ksiApi->buscarImovelLocatario($locatario['id']);
                
                if ($imovelResult['success']) {
                    $locatario['imoveis'] = $imovelResult['imoveis'];
                    $_SESSION['locatario']['imoveis'] = $imovelResult['imoveis'];
                    error_log("DEBUG: Imóveis recarregados: " . count($imovelResult['imoveis']));
                } else {
                    error_log("DEBUG: Erro ao recarregar imóveis: " . $imovelResult['message']);
                }
            }
        } else {
            error_log("DEBUG: Imóveis já carregados na sessão: " . count($locatario['imoveis']));
        }
        
        // Buscar categorias e subcategorias
        $categoriaModel = new \App\Models\Categoria();
        $subcategoriaModel = new \App\Models\Subcategoria();
        
        // ✅ Na etapa 1, mostrar todas as categorias em hierarquia (ainda não há seleção de finalidade)
        $categorias = $categoriaModel->getHierarquicas();
        $subcategorias = $subcategoriaModel->getAtivas();
        
        // Organizar subcategorias por categoria (incluindo categorias filhas)
        foreach ($categorias as $key => $categoria) {
            // Organizar subcategorias para a categoria pai
            $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                return $sub['categoria_id'] == $categoria['id'];
            }));
            
            // Organizar subcategorias para cada categoria filha
            if (!empty($categoria['filhas'])) {
                foreach ($categoria['filhas'] as $filhaKey => $categoriaFilha) {
                    $categorias[$key]['filhas'][$filhaKey]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoriaFilha) {
                        return $sub['categoria_id'] == $categoriaFilha['id'];
                    }));
                }
            }
        }
        
        $this->view('locatario.nova-solicitacao', [
            'locatario' => $locatario,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'etapa' => 1, // Sempre começa na etapa 1
            'nova_solicitacao' => $_SESSION['nova_solicitacao'] ?? []
        ]);
    }
    
    /**
     * Processar etapa específica do fluxo de nova solicitação
     */
    public function processarEtapa(string $instancia, int $etapa): void
    {
        $this->requireLocatarioAuth();
        
        // Se não há dados na sessão e não é etapa 1, redirecionar para etapa 1
        if (!isset($_SESSION['nova_solicitacao']) && $etapa > 1) {
            $this->redirect(url($instancia . '/nova-solicitacao'));
            return;
        }
        
        if ($this->isPost()) {
            $this->salvarDadosEtapa($etapa);
            return;
        }
        
        // GET: Exibir a view da etapa correspondente
        $locatario = $_SESSION['locatario'];
        $novaSolicitacao = $_SESSION['nova_solicitacao'] ?? [];
        
        // Preparar dados específicos para cada etapa
        $data = [
            'locatario' => $locatario,
            'etapa' => $etapa,
            'nova_solicitacao' => $novaSolicitacao
        ];
        
        // Adicionar dados extras conforme necessário para cada etapa
        switch ($etapa) {
            case 2:
                // Buscar categorias e subcategorias
                $categoriaModel = new \App\Models\Categoria();
                $subcategoriaModel = new \App\Models\Subcategoria();
                
                // Filtrar categorias baseado na finalidade da locação selecionada
                $finalidadeLocacao = $novaSolicitacao['finalidade_locacao'] ?? 'RESIDENCIAL';
                
                // ✅ Usar getHierarquicas() para organizar categorias em hierarquia pai-filha
                // Se for RESIDENCIAL, mostrar categorias com tipo_imovel = 'RESIDENCIAL' ou 'AMBOS'
                // Se for COMERCIAL, mostrar categorias com tipo_imovel = 'COMERCIAL' ou 'AMBOS'
                if ($finalidadeLocacao === 'RESIDENCIAL') {
                    $categorias = $categoriaModel->getHierarquicas('RESIDENCIAL');
                } elseif ($finalidadeLocacao === 'COMERCIAL') {
                    $categorias = $categoriaModel->getHierarquicas('COMERCIAL');
                } else {
                    // Fallback: mostrar todas se não houver seleção
                    $categorias = $categoriaModel->getHierarquicas();
                }
                
                $subcategorias = $subcategoriaModel->getAtivas();
                
                // Organizar subcategorias por categoria (incluindo categorias filhas)
                foreach ($categorias as $key => $categoria) {
                    // Organizar subcategorias para a categoria pai
                    $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                        return $sub['categoria_id'] == $categoria['id'];
                    }));
                    
                    // Organizar subcategorias para cada categoria filha
                    if (!empty($categoria['filhas'])) {
                        foreach ($categoria['filhas'] as $filhaKey => $categoriaFilha) {
                            $categorias[$key]['filhas'][$filhaKey]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoriaFilha) {
                                return $sub['categoria_id'] == $categoriaFilha['id'];
                            }));
                        }
                    }
                }
                
                $data['categorias'] = $categorias;
                $data['subcategorias'] = $subcategorias;
                $data['finalidade_locacao'] = $finalidadeLocacao; // Passar para a view
                break;
            case 3:
                // Fotos já estão em $novaSolicitacao
                break;
            case 4:
                // Horários
                break;
            case 5:
                // Resumo final
                break;
        }
        
        $this->view('locatario.nova-solicitacao', $data);
    }
    
    /**
     * Salvar dados da etapa atual
     */
    private function salvarDadosEtapa(int $etapa): void
    {
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - salvarDadosEtapa($etapa) iniciado\n", FILE_APPEND);
        
        // Inicializar sessão de nova solicitação se não existir
        if (!isset($_SESSION['nova_solicitacao'])) {
            $_SESSION['nova_solicitacao'] = [];
        }
        
        // Salvar dados da etapa atual
        switch ($etapa) {
            case 1:
                file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Dentro do case 1\n", FILE_APPEND);
                
                // Validar campos obrigatórios da etapa 1
                $enderecoSelecionado = $this->input('endereco_selecionado');
                $finalidadeLocacao = $this->input('finalidade_locacao');
                $tipoImovel = $this->input('tipo_imovel');
                
                file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Valores: endereco=$enderecoSelecionado, finalidade=$finalidadeLocacao, tipo=$tipoImovel\n", FILE_APPEND);
                
                // Validar campos obrigatórios
                if ($enderecoSelecionado === null || $finalidadeLocacao === null) {
                    $instancia = $this->getInstanciaFromUrl();
                    file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - ERRO: Campos obrigatórios faltando! Redirecionando...\n", FILE_APPEND);
                    $this->redirect(url($instancia . '/nova-solicitacao?error=campos_obrigatorios'));
                    return;
                }
                
                // Se for COMERCIAL, definir tipo_imovel como COMERCIAL se não foi enviado
                if ($finalidadeLocacao === 'COMERCIAL' && ($tipoImovel === null || $tipoImovel === '')) {
                    $tipoImovel = 'COMERCIAL';
                }
                
                // Se for RESIDENCIAL, tipo_imovel é obrigatório (CASA ou APARTAMENTO)
                if ($finalidadeLocacao === 'RESIDENCIAL' && ($tipoImovel === null || $tipoImovel === '')) {
                    $instancia = $this->getInstanciaFromUrl();
                    file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - ERRO: Tipo de imóvel obrigatório para Residencial! Redirecionando...\n", FILE_APPEND);
                    $this->redirect(url($instancia . '/nova-solicitacao?error=campos_obrigatorios'));
                    return;
                }
                
                file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Salvando na sessão...\n", FILE_APPEND);
                
                $_SESSION['nova_solicitacao']['endereco_selecionado'] = $enderecoSelecionado;
                $_SESSION['nova_solicitacao']['finalidade_locacao'] = $finalidadeLocacao;
                $_SESSION['nova_solicitacao']['tipo_imovel'] = $tipoImovel;
                
                file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Salvo! Sessão: " . print_r($_SESSION['nova_solicitacao'], true) . "\n", FILE_APPEND);
                break;
                
            case 2:
                // Verificar se é seleção múltipla de subcategorias
                $subcategoriasSelecionadas = $this->input('subcategorias_selecionadas', []);
                
                if (!empty($subcategoriasSelecionadas) && is_array($subcategoriasSelecionadas)) {
                    // Seleção múltipla de subcategorias - buscar categoria_id de cada subcategoria
                    $categoriasSelecionadas = [];
                    $subcategoriaModel = new \App\Models\Subcategoria();
                    
                    foreach ($subcategoriasSelecionadas as $subcategoriaId) {
                        $subcategoria = $subcategoriaModel->find((int)$subcategoriaId);
                        
                        if (!$subcategoria || empty($subcategoria['categoria_id'])) {
                            continue; // Pular se subcategoria não encontrada
                        }
                        
                        $categoriasSelecionadas[] = [
                            'categoria_id' => (int)$subcategoria['categoria_id'],
                            'subcategoria_id' => (int)$subcategoriaId
                        ];
                    }
                    
                    if (empty($categoriasSelecionadas)) {
                        $instancia = $this->getInstanciaFromUrl();
                        $this->redirect(url($instancia . '/nova-solicitacao/etapa/2?error=' . urlencode('Selecione pelo menos uma subcategoria')));
                        return;
                    }
                    
                    // Limitar a 3 seleções
                    if (count($categoriasSelecionadas) > 3) {
                        $categoriasSelecionadas = array_slice($categoriasSelecionadas, 0, 3);
                    }
                    
                    // Salvar primeira seleção como principal (para compatibilidade)
                    $_SESSION['nova_solicitacao']['categoria_id'] = $categoriasSelecionadas[0]['categoria_id'];
                    $_SESSION['nova_solicitacao']['subcategoria_id'] = $categoriasSelecionadas[0]['subcategoria_id'];
                    $_SESSION['nova_solicitacao']['categorias_selecionadas'] = $categoriasSelecionadas;
                    $_SESSION['nova_solicitacao']['subcategorias_selecionadas'] = array_column($categoriasSelecionadas, 'subcategoria_id');
                } else {
                    // Seleção única (comportamento padrão - compatibilidade)
                    $categoriaId = $this->input('categoria_id');
                    $subcategoriaId = $this->input('subcategoria_id');
                    
                    if (empty($categoriaId) || empty($subcategoriaId)) {
                        $instancia = $this->getInstanciaFromUrl();
                        $this->redirect(url($instancia . '/nova-solicitacao/etapa/2?error=' . urlencode('Selecione a categoria e o tipo de serviço para continuar')));
                        return;
                    }
                    
                    $_SESSION['nova_solicitacao']['categoria_id'] = $categoriaId;
                    $_SESSION['nova_solicitacao']['subcategoria_id'] = $subcategoriaId;
                    $_SESSION['nova_solicitacao']['categorias_selecionadas'] = [
                        [
                            'categoria_id' => (int)$categoriaId,
                            'subcategoria_id' => (int)$subcategoriaId
                        ]
                    ];
                    $_SESSION['nova_solicitacao']['subcategorias_selecionadas'] = [(int)$subcategoriaId];
                }
                break;
                
            case 3:
                $_SESSION['nova_solicitacao']['local_manutencao'] = $this->input('local_manutencao');
                $_SESSION['nova_solicitacao']['descricao_problema'] = $this->input('descricao_problema');
                
                // Processar upload de fotos se houver
                error_log("🔍 Etapa 3 - Verificando upload de fotos");
                error_log("🔍 \$_FILES: " . print_r($_FILES, true));
                
                if (!empty($_FILES['fotos']['name'][0])) {
                    error_log("✅ Fotos detectadas no upload");
                    $fotosSalvas = $this->processarUploadFotos();
                    error_log("✅ Fotos processadas: " . print_r($fotosSalvas, true));
                    $_SESSION['nova_solicitacao']['fotos'] = $fotosSalvas;
                } else {
                    error_log("⚠️ Nenhuma foto detectada no upload");
                    error_log("⚠️ \$_FILES['fotos']: " . print_r($_FILES['fotos'] ?? 'NÃO DEFINIDO', true));
                }
                break;
                
            case 4:
                // Verificar se é emergencial
                $isEmergencial = $this->input('is_emergencial', 0);
                
                // Calcular se está fora do horário comercial usando configurações
                $configuracaoModel = new \App\Models\Configuracao();
                $isForaHorario = $configuracaoModel->isForaHorarioComercial() ? 1 : 0;
                
                $_SESSION['nova_solicitacao']['is_emergencial'] = $isEmergencial;
                $_SESSION['nova_solicitacao']['is_fora_horario'] = $isForaHorario;
                
                if ($isEmergencial) {
                    // Emergencial: verificar tipo de atendimento escolhido
                    $tipoAtendimentoEmergencial = $this->input('tipo_atendimento_emergencial', '120_minutos');
                    $_SESSION['nova_solicitacao']['tipo_atendimento_emergencial'] = $tipoAtendimentoEmergencial;
                    
                    if ($tipoAtendimentoEmergencial === '120_minutos') {
                        // Atendimento em 120 minutos: não precisa de horários
                        $_SESSION['nova_solicitacao']['horarios_preferenciais'] = [];
                    } else if ($tipoAtendimentoEmergencial === 'agendar') {
                        // Agendar: receber horários enviados pelo JavaScript
                        $horariosRaw = $this->input('horarios_opcoes');
                        $horarios = [];
                        
                        if (!empty($horariosRaw)) {
                            // Se for string JSON, decodificar
                            $horarios = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
                        }
                        
                        // Log para debug
                        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Horários emergenciais recebidos: " . print_r($horariosRaw, true) . "\n", FILE_APPEND);
                        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Horários emergenciais processados: " . print_r($horarios, true) . "\n", FILE_APPEND);
                        
                        // Salvar horários formatados na sessão
                        $_SESSION['nova_solicitacao']['horarios_preferenciais'] = $horarios;
                    }
                } else {
                    // Normal: receber horários enviados pelo JavaScript
                    $horariosRaw = $this->input('horarios_opcoes');
                    $horarios = [];
                    
                    if (!empty($horariosRaw)) {
                        // Se for string JSON, decodificar
                        $horarios = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
                    }
                    
                    // Log para debug
                    file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Horários recebidos: " . print_r($horariosRaw, true) . "\n", FILE_APPEND);
                    file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Horários processados: " . print_r($horarios, true) . "\n", FILE_APPEND);
                    
                    // Salvar horários formatados na sessão
                    $_SESSION['nova_solicitacao']['horarios_preferenciais'] = $horarios;
                }
                break;
                
            case 5:
                // Verificar se todos os dados necessários estão presentes antes de finalizar
                $dados = $_SESSION['nova_solicitacao'] ?? [];
                $camposObrigatorios = ['endereco_selecionado', 'categoria_id', 'subcategoria_id', 'descricao_problema'];
                $camposFaltando = [];
                
                foreach ($camposObrigatorios as $campo) {
                    if (!isset($dados[$campo]) || $dados[$campo] === '' || $dados[$campo] === null) {
                        $camposFaltando[] = $campo;
                    }
                }
                
                // Se faltar algum campo, não finalizar e redirecionar para etapa apropriada
                if (!empty($camposFaltando)) {
                    $instancia = $this->getInstanciaFromUrl();
                    $etapaRedirecionar = 1;
                    
                    if (in_array('categoria_id', $camposFaltando) || in_array('subcategoria_id', $camposFaltando)) {
                        $etapaRedirecionar = 2;
                    } elseif (in_array('descricao_problema', $camposFaltando)) {
                        $etapaRedirecionar = 3;
                    }
                    
                    $this->redirect(url($instancia . '/nova-solicitacao/etapa/' . $etapaRedirecionar . '?error=' . urlencode("Por favor, complete todas as etapas antes de finalizar")));
                    return;
                }
                
                $termoAceite = $this->input('termo_aceite');
                $lgpdAceite = $this->input('lgpd_aceite');
                
                if (!$termoAceite || !$lgpdAceite) {
                    $this->redirect(url($instancia . '/nova-solicitacao/etapa/4?error=' . urlencode('É necessário aceitar os termos e a LGPD para continuar')));
                    return;
                }
                
                $_SESSION['nova_solicitacao']['termo_aceite'] = $termoAceite;
                $_SESSION['nova_solicitacao']['lgpd_aceite'] = $lgpdAceite;
                $this->finalizarSolicitacao();
                return;
        }
        
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Salvou etapa $etapa, preparando redirect\n", FILE_APPEND);
        
        $_SESSION['nova_solicitacao']['etapa'] = $etapa;
        
        $instancia = $this->getInstanciaFromUrl();
        $proximaEtapa = $etapa + 1;
        
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Instancia retornada: '$instancia'\n", FILE_APPEND);
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - URL construída: " . ($instancia . '/nova-solicitacao/etapa/' . $proximaEtapa) . "\n", FILE_APPEND);
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Redirecionando para etapa $proximaEtapa da instancia $instancia\n", FILE_APPEND);
        
        if ($proximaEtapa <= 5) {
            $this->redirect(url($instancia . '/nova-solicitacao/etapa/' . $proximaEtapa));
        } else {
            $this->redirect(url($instancia . '/nova-solicitacao'));
        }
    }
    
    /**
     * Processar upload de fotos
     */
    private function processarUploadFotos(): array
    {
        $fotosSalvas = [];
        $uploadDir = __DIR__ . '/../../Public/uploads/solicitacoes/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            error_log("📁 Diretório criado: {$uploadDir}");
        }
        
        error_log("📸 Processando " . count($_FILES['fotos']['name']) . " arquivo(s)");
        
        foreach ($_FILES['fotos']['name'] as $key => $name) {
            $error = $_FILES['fotos']['error'][$key];
            error_log("📸 Arquivo {$key}: {$name}, Erro: {$error}");
            
            if ($error === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['fotos']['tmp_name'][$key];
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                // Validar extensão
                $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $extensoesPermitidas)) {
                    error_log("❌ Extensão não permitida: {$extension}");
                    continue;
                }
                
                // Validar tamanho (10MB)
                $tamanho = $_FILES['fotos']['size'][$key];
                $tamanhoMaximo = 10 * 1024 * 1024; // 10MB
                if ($tamanho > $tamanhoMaximo) {
                    error_log("❌ Arquivo muito grande: " . number_format($tamanho / 1024 / 1024, 2) . " MB");
                    continue;
                }
                
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $filePath)) {
                    $fotosSalvas[] = $fileName;
                    error_log("✅ Foto salva: {$fileName} em {$filePath}");
                } else {
                    error_log("❌ Erro ao mover arquivo: {$tmpName} para {$filePath}");
                }
            } else {
                $mensagensErro = [
                    UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'Upload parcial',
                    UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
                    UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
                    UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo',
                    UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
                ];
                error_log("❌ Erro no upload: " . ($mensagensErro[$error] ?? "Erro desconhecido ({$error})"));
            }
        }
        
        error_log("📸 Total de fotos salvas: " . count($fotosSalvas));
        return $fotosSalvas;
    }
    
    /**
     * Finalizar solicitação com todos os dados coletados
     */
    private function finalizarSolicitacao(): void
    {
        $dados = $_SESSION['nova_solicitacao'] ?? [];
        $locatario = $_SESSION['locatario'];
        
        // DEBUG: Ver o que tem na sessão
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - finalizarSolicitacao() iniciado\n", FILE_APPEND);
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Dados na sessão: " . print_r($dados, true) . "\n", FILE_APPEND);
        
        // Validar dados obrigatórios (usar !isset para permitir valor "0")
        // Verificar apenas os campos que devem estar presentes na etapa final
        $required = ['endereco_selecionado', 'categoria_id', 'subcategoria_id', 'descricao_problema'];
        $camposFaltando = [];
        
        foreach ($required as $field) {
            if (!isset($dados[$field]) || $dados[$field] === '' || $dados[$field] === null) {
                $camposFaltando[] = $field;
                file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Campo faltando: $field (valor: " . var_export($dados[$field] ?? 'UNDEFINED', true) . ")\n", FILE_APPEND);
            }
        }
        
        // Se houver campos faltando, redirecionar para a etapa apropriada
        if (!empty($camposFaltando)) {
            $instancia = $locatario['instancia'];
            
            // Determinar para qual etapa redirecionar baseado nos campos faltando
            $etapaRedirecionar = 1; // Padrão: etapa 1
            
            if (in_array('categoria_id', $camposFaltando) || in_array('subcategoria_id', $camposFaltando)) {
                $etapaRedirecionar = 2; // Etapa de seleção de serviço
            } elseif (in_array('descricao_problema', $camposFaltando)) {
                $etapaRedirecionar = 3; // Etapa de descrição
            } elseif (in_array('endereco_selecionado', $camposFaltando)) {
                $etapaRedirecionar = 1; // Etapa de endereço
            }
            
            // Redirecionar para a etapa apropriada
            if ($etapaRedirecionar == 1) {
                $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode("Por favor, complete todas as etapas antes de finalizar")));
            } else {
                $this->redirect(url($instancia . '/nova-solicitacao/etapa/' . $etapaRedirecionar . '?error=' . urlencode("Por favor, complete todas as etapas antes de finalizar")));
            }
            return;
        }
        
        file_put_contents('C:\xampp\htdocs\debug_kss.log', date('H:i:s') . " - Validação OK, criando solicitação...\n", FILE_APPEND);
        
        // Preparar dados para criação da solicitação
        $imovel = $locatario['imoveis'][$dados['endereco_selecionado']];
        
        // Buscar número do contrato do imóvel
        $numeroContrato = null;
        if (!empty($imovel['contratos'])) {
            foreach ($imovel['contratos'] as $contrato) {
                if (isset($contrato['CtrTipo']) && $contrato['CtrTipo'] == 'PRINCIPAL') {
                    $numeroContrato = ($contrato['CtrCod'] ?? '') . '-' . ($contrato['CtrDV'] ?? '');
                    break;
                }
            }
            // Se não encontrou principal, pegar o primeiro
            if (!$numeroContrato && !empty($imovel['contratos'][0])) {
                $contrato = $imovel['contratos'][0];
                $numeroContrato = ($contrato['CtrCod'] ?? '') . '-' . ($contrato['CtrDV'] ?? '');
            }
        }
        
        // Buscar status inicial (geralmente "Nova Solicitação" ou similar)
        $statusModel = new \App\Models\Status();
        $statusInicial = $statusModel->findByNome('Nova Solicitação') 
                      ?? $statusModel->findByNome('Nova') 
                      ?? $statusModel->findByNome('NOVA')
                      ?? ['id' => 1];
        
        // Preparar horários para salvar (converter array para JSON)
        $horarios = $dados['horarios_preferenciais'] ?? [];
        $horariosJson = !empty($horarios) ? json_encode($horarios) : null;
        
        // Verificar se é emergencial e fora do horário comercial
        $isEmergencial = !empty($dados['is_emergencial']);
        $isForaHorario = !empty($dados['is_fora_horario']);
        $isEmergencialForaHorario = $isEmergencial && $isForaHorario;
        
        // Se for emergencial, definir prioridade como ALTA
        $prioridade = $isEmergencial ? 'ALTA' : 'NORMAL';
        
        // Verificar regras de acesso usando ValidacaoAcessoService
        // Isso valida Regra 1 (Bolsão) e Regra 2 (Data do contrato)
        $cpfLimpo = preg_replace('/[^0-9]/', '', $locatario['cpf'] ?? '');
        $validacaoAcesso = [
            'permitido' => true,
            'bolsao' => false,
            'regra_2_passa' => false
        ];
        
        if (!empty($cpfLimpo) && !empty($locatario['imobiliaria_id']) && !empty($locatario['imoveis'])) {
            $validacaoAcesso = \App\Services\ValidacaoAcessoService::validarRegrasAcesso(
                $locatario['cpf'],
                $locatario['imobiliaria_id'],
                $locatario['imoveis'] ?? []
            );
        } else {
            // Se não temos dados completos, verificar apenas bolsão
            if (!empty($cpfLimpo) && !empty($locatario['imobiliaria_id'])) {
                $sql = "SELECT * FROM locatarios_contratos 
                        WHERE imobiliaria_id = ? 
                        AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
                $cpfEncontradoNaListagem = \App\Core\Database::fetch($sql, [$locatario['imobiliaria_id'], $cpfLimpo]);
                if ($cpfEncontradoNaListagem) {
                    $validacaoAcesso['bolsao'] = true;
                }
            }
        }
        
        // ✅ Verificar se CPF está no bolsão (precisa verificar antes de validacao_utilizacao)
        $cpfEncontradoNaListagem = false;
        if (!empty($cpfLimpo) && !empty($locatario['imobiliaria_id'])) {
            $sql = "SELECT * FROM locatarios_contratos 
                    WHERE imobiliaria_id = ? 
                    AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
            $cpfEncontradoNaListagem = \App\Core\Database::fetch($sql, [$locatario['imobiliaria_id'], $cpfLimpo]) !== null;
            error_log("DEBUG [finalizarSolicitacao] - CPF: {$cpfLimpo}, Imobiliaria: {$locatario['imobiliaria_id']}, CPF no bolsão: " . ($cpfEncontradoNaListagem ? 'SIM' : 'NÃO'));
        } else {
            error_log("DEBUG [finalizarSolicitacao] - CPF ou imobiliaria_id vazios - CPF: " . ($cpfLimpo ?? 'NULL') . ", Imobiliaria: " . ($locatario['imobiliaria_id'] ?? 'NULL'));
        }
        
        // ✅ VERIFICAR VALIDAÇÃO DE UTILIZAÇÃO (limite por CPF) APENAS se CPF estiver no bolsão
        // IMPORTANTE: Esta verificação só deve bloquear se realmente houver um limite definido e excedido
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $validacaoUtilizacao = null;
        $deveRedirecionarPorUtilizacao = false;
        
        if ($cpfEncontradoNaListagem && !empty($cpfLimpo) && !empty($locatario['imobiliaria_id']) && !empty($dados['categoria_id'])) {
            $validacaoUtilizacao = $solicitacaoManualModel->calcularValidacaoUtilizacao(
                $cpfLimpo,
                $locatario['imobiliaria_id'],
                (int)$dados['categoria_id']
            );
            error_log("DEBUG [finalizarSolicitacao] - Validação Utilização calculada: " . ($validacaoUtilizacao === null ? 'NULL' : $validacaoUtilizacao) . " (0=excedido, 1=aprovado)");
            
            // Só redirecionar se validacao_utilizacao for explicitamente 0 (excedido)
            // Se for null, significa que não há limite ou não foi possível calcular - permitir criação normal
            if ($validacaoUtilizacao === 0) {
                $deveRedirecionarPorUtilizacao = true;
                error_log("DEBUG [finalizarSolicitacao] - ⚠️ LIMITE DE UTILIZAÇÃO EXCEDIDO - CPF no bolsão mas excedeu limite");
            } else {
                error_log("DEBUG [finalizarSolicitacao] - ✅ Limite de utilização OK ou sem limite - validacao_utilizacao: " . ($validacaoUtilizacao ?? 'NULL'));
            }
        } else {
            error_log("DEBUG [finalizarSolicitacao] - Validação Utilização NÃO calculada - CPF no bolsão: " . ($cpfEncontradoNaListagem ? 'SIM' : 'NÃO') . ", categoria_id: " . ($dados['categoria_id'] ?? 'NULL'));
        }
        
        // ✅ Se CPF está no bolsão E validacao_utilizacao = 0 (excedido), redirecionar para solicitação manual
        // ✅ Se CPF NÃO está no bolsão OU validacao_utilizacao não é 0, criar solicitação normal
        if ($deveRedirecionarPorUtilizacao) {
            error_log("DEBUG [finalizarSolicitacao] - ⚠️ REDIRECIONANDO PARA MANUAL: CPF no bolsão mas excedeu limite de utilização");
            $instancia = $locatario['instancia'];
            
            // Salvar dados na sessão de solicitação manual
            $_SESSION['solicitacao_manual'] = [
                'nome_completo' => $locatario['nome'],
                'cpf' => $locatario['cpf'],
                'whatsapp' => $locatario['whatsapp'] ?? $locatario['telefone'] ?? '',
                'tipo_imovel' => $dados['tipo_imovel'] ?? 'RESIDENCIAL',
                'cep' => $imovel['cep'] ?? '',
                'endereco' => $imovel['endereco'] ?? '',
                'numero' => $imovel['numero'] ?? '',
                'complemento' => $imovel['complemento'] ?? '',
                'bairro' => $imovel['bairro'] ?? '',
                'cidade' => $imovel['cidade'] ?? '',
                'estado' => $imovel['uf'] ?? '',
                'categoria_id' => $dados['categoria_id'],
                'subcategoria_id' => $dados['subcategoria_id'],
                'numero_contrato' => $numeroContrato,
                'local_manutencao' => $dados['local_manutencao'] ?? null,
                'descricao_problema' => $dados['descricao_problema'],
                'horarios_preferenciais' => $horarios,
                'fotos' => $dados['fotos'] ?? [],
                'termos_aceitos' => 1
            ];
            
            // Redirecionar para solicitação manual
            $this->redirect(url($instancia . '/solicitacao-manual?info=' . urlencode('Limite de solicitações excedido. Sua solicitação será analisada pela equipe.')));
            return;
        }
        
        error_log("DEBUG [finalizarSolicitacao] - ✅ Continuando criação normal - CPF no bolsão: " . ($cpfEncontradoNaListagem ? 'SIM' : 'NÃO') . ", validacao_utilizacao: " . ($validacaoUtilizacao ?? 'NULL') . ", deveRedirecionarPorUtilizacao: " . ($deveRedirecionarPorUtilizacao ? 'SIM' : 'NÃO'));
        
        // Definir tipo de qualificação baseado nas regras de acesso
        // IMPORTANTE: Não redirecionar para solicitação manual por limite de categoria
        // O limite é apenas informativo, a solicitação deve ser criada normalmente
        $tipoQualificacao = null;
        
        // Definir tipo baseado nas regras de acesso
        if ($validacaoAcesso['bolsao']) {
            // Regra 1 passou: CPF no bolsão
            $tipoQualificacao = 'BOLSAO';
        } elseif ($validacaoAcesso['regra_2_passa'] ?? false) {
            // Regra 2 passou: CPF não no bolsão mas contrato válido (< 45 dias)
            $tipoQualificacao = 'REGRA_2';
        } else {
            // Não passou em nenhuma regra - deixa NULL para admin definir
            $tipoQualificacao = null;
        }
        
        // Verificar limite por contrato apenas para adicionar observação (não bloqueia criação)
        // IMPORTANTE: Esta verificação é apenas informativa - NUNCA bloqueia a criação da solicitação
        $observacoesExcedeuLimite = '';
        if (!empty($numeroContrato) && !empty($dados['categoria_id'])) {
            error_log("DEBUG [finalizarSolicitacao] - Verificando limite por contrato (apenas informativo) - Contrato: {$numeroContrato}, Categoria: {$dados['categoria_id']}");
            $categoriaModel = new \App\Models\Categoria();
            $verificacaoLimite = $categoriaModel->verificarLimiteSolicitacoes($dados['categoria_id'], $numeroContrato);
            
            error_log("DEBUG [finalizarSolicitacao] - Resultado verificação limite: " . json_encode($verificacaoLimite));
            
            // Se excedeu limite, apenas adicionar observação (não bloquear)
            if (!$verificacaoLimite['permitido'] && $verificacaoLimite['limite'] !== null && $verificacaoLimite['limite'] > 0) {
                $observacoesExcedeuLimite = "\n⚡ EXCEDEU_LIMITE_QUANTIDADE: Limite de solicitações da categoria excedido (Total: {$verificacaoLimite['total_atual']}/{$verificacaoLimite['limite']})";
                error_log("DEBUG [finalizarSolicitacao] - ⚠️ LIMITE POR CONTRATO EXCEDIDO (apenas observação): Total: {$verificacaoLimite['total_atual']}/{$verificacaoLimite['limite']}");
            } else {
                error_log("DEBUG [finalizarSolicitacao] - ✅ Limite OK ou sem limite definido - Permitido: " . ($verificacaoLimite['permitido'] ? 'SIM' : 'NÃO') . ", Limite: " . ($verificacaoLimite['limite'] ?? 'NULL'));
            }
        } else {
            error_log("DEBUG [finalizarSolicitacao] - Sem verificação de limite por contrato - numeroContrato: " . ($numeroContrato ?? 'NULL') . ", categoria_id: " . ($dados['categoria_id'] ?? 'NULL') . ", CPF no bolsão: " . ($cpfEncontradoNaListagem ? 'SIM' : 'NÃO'));
        }
        
        // Montar observações com múltiplas subcategorias, quando existir seleção múltipla
        // NOTA: local_manutencao não deve ser incluído em observacoes, ele tem seu próprio campo no banco
        $observacoes = "Finalidade: " . ($dados['finalidade_locacao'] ?? 'RESIDENCIAL') . "\nTipo: " . ($dados['tipo_imovel'] ?? 'CASA');
        
        // Verificar se há múltiplas subcategorias selecionadas
        $categoriasSelecionadas = $dados['categorias_selecionadas'] ?? [];
        $temMultiplasCategorias = !empty($categoriasSelecionadas) && is_array($categoriasSelecionadas) && count($categoriasSelecionadas) > 1;
        
        if ($temMultiplasCategorias) {
            $subcategoriaModel = new \App\Models\Subcategoria();
            $nomesSubcategorias = [];
            
            foreach ($categoriasSelecionadas as $cat) {
                if (!empty($cat['subcategoria_id'])) {
                    $subcategoria = $subcategoriaModel->find((int)$cat['subcategoria_id']);
                    if ($subcategoria && !empty($subcategoria['nome'])) {
                        $nomesSubcategorias[] = $subcategoria['nome'];
                    }
                }
            }
            
            if (count($nomesSubcategorias) > 1) {
                // Mesmo formato usado para solicitações manuais, para reaproveitar o helper getSubcategorias
                $observacoes .= "\n\nServiços solicitados (" . count($nomesSubcategorias) . "):\n";
                foreach ($nomesSubcategorias as $index => $nome) {
                    $observacoes .= ($index + 1) . ". " . $nome . "\n";
                }
                
                // Armazenar IDs das subcategorias em JSON para referência futura
                $subcategoriasIds = array_column($categoriasSelecionadas, 'subcategoria_id');
                $observacoes .= "\n[SUBCATEGORIAS_IDS: " . json_encode($subcategoriasIds) . "]";
            }
        }
        
        // Adicionar observação de limite excedido se houver
        if (isset($observacoesExcedeuLimite)) {
            $observacoes .= $observacoesExcedeuLimite;
        }
        
        $data = [
            // IDs e relacionamentos
            'locatario_id' => $locatario['codigo_locatario'] ?? $locatario['id'],
            'locatario_nome' => $locatario['nome'],
            'locatario_cpf' => $cpfLimpo, // CPF do locatário
            'locatario_telefone' => $locatario['whatsapp'] ?? $locatario['telefone'] ?? '',
            'locatario_email' => $locatario['email'] ?? '',
            'imobiliaria_id' => $locatario['imobiliaria_id'],
            'categoria_id' => $dados['categoria_id'],
            'subcategoria_id' => $dados['subcategoria_id'],
            'status_id' => $statusInicial['id'],
            
            // Descrição
            'descricao_problema' => $dados['descricao_problema'],
            'local_manutencao' => $dados['local_manutencao'] ?? null,
            'tipo_imovel' => $dados['tipo_imovel'] ?? 'RESIDENCIAL',
            'prioridade' => $prioridade,
            'is_emergencial_fora_horario' => $isEmergencialForaHorario ? 1 : 0,
            // Observações agora podem conter a lista de serviços e SUBCATEGORIAS_IDS, quando houver múltiplas
            'observacoes' => $observacoes,
            
            // Validação Bolsão: 1 = CPF validado na listagem da imobiliária
            'validacao_bolsao' => $cpfEncontradoNaListagem ? 1 : 0,
            
            // Horários preferenciais
            'horarios_opcoes' => $horariosJson,
            'datas_opcoes' => $horariosJson, // ✅ Preservar também em datas_opcoes para manter dados originais do locatário
            
            // Número do contrato
            'numero_contrato' => $numeroContrato,
            
            // Dados do imóvel (com prefixo imovel_)
            'imovel_endereco' => $imovel['endereco'] ?? '',
            'imovel_numero' => $imovel['numero'] ?? '',
            'imovel_complemento' => $imovel['complemento'] ?? '',
            'imovel_bairro' => $imovel['bairro'] ?? '',
            'imovel_cidade' => $imovel['cidade'] ?? '',
            'imovel_estado' => $imovel['uf'] ?? '',
            'imovel_cep' => $imovel['cep'] ?? ''
        ];
        
        // Adicionar tipo_qualificacao apenas se a coluna existir
        if ($this->solicitacaoModel->colunaExisteBanco('tipo_qualificacao')) {
            $data['tipo_qualificacao'] = $tipoQualificacao;
            
            // ✅ Se for NAO_QUALIFICADA, atualizar status para "Não qualificado" para aparecer na coluna correta do kanban
            if ($tipoQualificacao === 'NAO_QUALIFICADA') {
                $statusNaoQualificado = $statusModel->findByNome('Não qualificado');
                if (!$statusNaoQualificado) {
                    $statusNaoQualificado = $statusModel->findByNome('Não Qualificados');
                }
                if ($statusNaoQualificado && !empty($statusNaoQualificado['id'])) {
                    $data['status_id'] = (int)$statusNaoQualificado['id'];
                    error_log("DEBUG [finalizarSolicitacao] - Atualizando status para 'Não qualificado' (ID: {$data['status_id']}) porque tipo_qualificacao = NAO_QUALIFICADA");
                }
            }
        }
        
        // Criar solicitação
        try {
        $solicitacaoId = $this->solicitacaoModel->create($data);
            
            if (!$solicitacaoId || $solicitacaoId <= 0) {
                error_log("ERRO [finalizarSolicitacao] - create() retornou ID inválido: " . $solicitacaoId);
                error_log("Dados tentados: " . json_encode($data));
                $instancia = $locatario['instancia'];
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                
                if ($isAjax) {
                    $this->json([
                        'success' => false,
                        'message' => 'Erro ao criar solicitação. ID inválido retornado.'
                    ]);
                } else {
                    $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode('Erro ao criar solicitação. Tente novamente.')));
                }
                return;
            }
            
            error_log("✅ [finalizarSolicitacao] - Solicitação criada com sucesso. ID: {$solicitacaoId}");
        } catch (\Exception $e) {
            error_log("ERRO [finalizarSolicitacao] ao criar solicitação: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Dados tentados: " . json_encode($data));
            
            $instancia = $locatario['instancia'];
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Erro ao criar solicitação: ' . $e->getMessage()
                ]);
            } else {
                $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode('Erro ao criar solicitação: ' . $e->getMessage())));
            }
            return;
        }
        
        // Garantir que tipo_qualificacao foi salvo corretamente
        if ($solicitacaoId && $this->solicitacaoModel->colunaExisteBanco('tipo_qualificacao') && isset($tipoQualificacao) && !empty($tipoQualificacao)) {
            // Verificar se precisa atualizar (pode não ter sido salvo se coluna não existia)
            $solicitacaoCriada = $this->solicitacaoModel->find($solicitacaoId);
            if ($solicitacaoCriada && empty($solicitacaoCriada['tipo_qualificacao'])) {
                \App\Core\Database::query("UPDATE solicitacoes SET tipo_qualificacao = ? WHERE id = ?", [$tipoQualificacao, $solicitacaoId]);
                error_log("DEBUG [processarNovaSolicitacaoInterna] - Atualizado tipo_qualificacao para {$tipoQualificacao} na solicitação #{$solicitacaoId}");
            }
        }
        
        $instancia = $locatario['instancia'];
        if ($solicitacaoId) {
            // Gerar token de cancelamento permanente (não expira)
            $tokenCancelamento = $this->solicitacaoModel->gerarTokenCancelamento($solicitacaoId);
            error_log("✅ Token de cancelamento gerado para solicitação #{$solicitacaoId}: {$tokenCancelamento}");
            
            // Salvar fotos na tabela fotos se houver
            error_log("🔍 finalizarSolicitacao - Verificando fotos na sessão");
            error_log("🔍 finalizarSolicitacao - dados['fotos']: " . print_r($dados['fotos'] ?? 'NÃO DEFINIDO', true));
            
            if (!empty($dados['fotos']) && is_array($dados['fotos'])) {
                error_log("✅ finalizarSolicitacao - Encontradas " . count($dados['fotos']) . " foto(s) para salvar");
                foreach ($dados['fotos'] as $fotoNome) {
                    $urlArquivo = 'Public/uploads/solicitacoes/' . $fotoNome;
                    $sqlFoto = "INSERT INTO fotos (solicitacao_id, nome_arquivo, url_arquivo, created_at) 
                                VALUES (?, ?, ?, NOW())";
                    try {
                        \App\Core\Database::query($sqlFoto, [$solicitacaoId, $fotoNome, $urlArquivo]);
                        error_log("✅ Foto salva: {$fotoNome} para solicitação #{$solicitacaoId}");
                    } catch (\Exception $e) {
                        error_log("❌ Erro ao salvar foto {$fotoNome}: " . $e->getMessage());
                    }
                }
            } else {
                error_log("⚠️ finalizarSolicitacao - Nenhuma foto encontrada ou não é array");
            }
            
            // Enviar notificação WhatsApp
            try {
                // Verificar se é não qualificada para enviar mensagem específica
                $solicitacaoCriada = $this->solicitacaoModel->find($solicitacaoId);
                if ($solicitacaoCriada && !empty($solicitacaoCriada['tipo_qualificacao']) && 
                    $solicitacaoCriada['tipo_qualificacao'] === 'NAO_QUALIFICADA') {
                    $observacao = $solicitacaoCriada['observacao_qualificacao'] ?? 'Não se enquadra nos critérios estabelecidos.';
                    $this->enviarNotificacaoWhatsApp($solicitacaoId, 'Não Qualificado', [
                        'observacao' => $observacao
                    ]);
                } else {
                    $this->enviarNotificacaoWhatsApp($solicitacaoId, 'Nova Solicitação');
                }
            } catch (\Exception $e) {
                // Log do erro mas não bloquear o fluxo
                error_log('Erro ao enviar WhatsApp no LocatarioController [ID:' . $solicitacaoId . ']: ' . $e->getMessage());
            }
            
            // Limpar dados da sessão
            unset($_SESSION['nova_solicitacao']);
            
            // Verificar se é requisição AJAX
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                // Retornar JSON para requisições AJAX
                $this->json([
                    'success' => true,
                    'solicitacao_id' => $solicitacaoId,
                    'message' => 'Solicitação criada com sucesso!',
                    'redirect' => $isEmergencialForaHorario 
                        ? url($instancia . '/solicitacao-emergencial/' . $solicitacaoId)
                        : url($instancia . '/dashboard?success=' . urlencode('Solicitação criada com sucesso! ID: #' . $solicitacaoId))
                ]);
                return;
            }
            
            // Se for emergencial e fora do horário comercial, mostrar tela com telefone
            if ($isEmergencialForaHorario) {
                $this->redirect(url($instancia . '/solicitacao-emergencial/' . $solicitacaoId));
            } else {
                // Redirecionar para a tela de sucesso
                $this->redirect(url($instancia . '/solicitacao-sucesso/' . $solicitacaoId));
            }
        } else {
            // Verificar se é requisição AJAX
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Erro ao criar solicitação. Tente novamente.'
                ]);
                return;
            }
            
            $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode('Erro ao criar solicitação. Tente novamente.')));
        }
    }
    
    /**
     * Ver detalhes de uma solicitação
     */
    public function showSolicitacao(string $instancia, int $id): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        $solicitacao = $this->solicitacaoModel->getDetalhes($id);
        
        if (!$solicitacao || $solicitacao['locatario_id'] !== $locatario['id']) {
            $this->view('errors.404', [
                'message' => 'Solicitação não encontrada'
            ]);
            return;
        }
        
        // Buscar fotos
        try {
            $fotos = $this->solicitacaoModel->getFotos($id);
        } catch (\Exception $e) {
            $fotos = [];
        }
        
        // Buscar histórico de status (linha do tempo)
        try {
            $historicoStatus = $this->solicitacaoModel->getHistoricoStatus($id);
        } catch (\Exception $e) {
            $historicoStatus = [];
        }
        
        // Buscar histórico de WhatsApp (se método existir)
        $whatsappHistorico = [];
        if (method_exists($this->solicitacaoModel, 'getWhatsAppHistorico')) {
            try {
                $whatsappHistorico = $this->solicitacaoModel->getWhatsAppHistorico($id);
            } catch (\Exception $e) {
                $whatsappHistorico = [];
            }
        }
        
        $this->view('locatario.show-solicitacao', [
            'locatario' => $locatario,
            'solicitacao' => $solicitacao,
            'fotos' => $fotos,
            'historicoStatus' => $historicoStatus,
            'whatsappHistorico' => $whatsappHistorico
        ]);
    }
    
    /**
     * Atualizar observações da solicitação (para locatário autenticado)
     */
    public function atualizarObservacoes(string $instancia, int $id): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        $solicitacao = $this->solicitacaoModel->find($id);
        
        // Verificar se a solicitação pertence ao locatário
        if (!$solicitacao || $solicitacao['locatario_id'] !== $locatario['id']) {
            $this->json([
                'success' => false,
                'message' => 'Solicitação não encontrada ou não autorizada'
            ], 404);
            return;
        }
        
        $observacoes = $this->input('observacoes', '');
        
        // Atualizar observações
        $this->solicitacaoModel->update($id, [
            'observacoes' => $observacoes,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Registrar no histórico
        try {
            $this->solicitacaoModel->updateStatus($id, $solicitacao['status_id'], null, 'Observações atualizadas pelo Locatário.');
        } catch (\Exception $e) {
            error_log('Erro ao registrar histórico de atualização de observações [ID:' . $id . ']: ' . $e->getMessage());
        }
        
        $this->json([
            'success' => true,
            'message' => 'Observações salvas com sucesso'
        ]);
    }
    
    /**
     * Processar nova solicitação
     */
    private function processarNovaSolicitacao(): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        // Processar horários (máximo 3)
        $horariosRaw = $this->input('horarios_opcoes', '[]');
        $horariosArray = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
        
        if (!is_array($horariosArray)) {
            $horariosArray = [];
        }
        
        // Limitar a 3 horários máximo
        if (count($horariosArray) > 3) {
            $horariosArray = array_slice($horariosArray, 0, 3);
        }
        
        // Validar que há pelo menos 1 horário
        if (empty($horariosArray)) {
            $instancia = $this->getInstanciaFromUrl();
            $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode('É necessário selecionar pelo menos 1 horário (máximo 3)')));
            return;
        }
        
        $horarios = json_encode($horariosArray);
        
        // Preparar CPF limpo para usar nos dados e na validação
        $cpfLimpo = preg_replace('/[^0-9]/', '', $locatario['cpf'] ?? '');
        
        $data = [
            'imobiliaria_id' => $locatario['imobiliaria_id'],
            'categoria_id' => $this->input('categoria_id'),
            'subcategoria_id' => $this->input('subcategoria_id'),
            'locatario_id' => $locatario['id'],
            'locatario_nome' => $locatario['nome'],
            'locatario_cpf' => $cpfLimpo, // CPF do locatário
            'locatario_telefone' => $this->input('telefone'),
            'locatario_email' => $this->input('email'),
            'imovel_endereco' => $this->input('endereco'),
            'imovel_numero' => $this->input('numero'),
            'imovel_complemento' => $this->input('complemento'),
            'imovel_bairro' => $this->input('bairro'),
            'imovel_cidade' => $this->input('cidade'),
            'imovel_estado' => $this->input('estado'),
            'imovel_cep' => $this->input('cep'),
            'descricao_problema' => $this->input('descricao'),
            'tipo_atendimento' => $this->input('tipo_atendimento', 'RESIDENCIAL'),
            'horarios_opcoes' => $horarios,
            'datas_opcoes' => $horarios, // ✅ Preservar também em datas_opcoes para manter dados originais do locatário
            'prioridade' => $this->input('prioridade', 'NORMAL')
        ];
        
        // Verificar se o CPF está na listagem da imobiliária (Bolsão)
        $cpfEncontradoNaListagem = false;
        if (!empty($cpfLimpo) && !empty($locatario['imobiliaria_id'])) {
            $sql = "SELECT * FROM locatarios_contratos 
                    WHERE imobiliaria_id = ? 
                    AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
            $resultado = \App\Core\Database::fetch($sql, [$locatario['imobiliaria_id'], $cpfLimpo]);
            $cpfEncontradoNaListagem = $resultado !== null;
            
            error_log("DEBUG [validacao_bolsao - processarNovaSolicitacao] - CPF: {$cpfLimpo}, imobiliaria_id: {$locatario['imobiliaria_id']}, encontrado: " . ($cpfEncontradoNaListagem ? 'SIM' : 'NÃO'));
            if ($cpfEncontradoNaListagem) {
                error_log("DEBUG [validacao_bolsao] - CPF encontrado na tabela: " . ($resultado['cpf'] ?? 'N/A'));
            }
        } else {
            error_log("DEBUG [validacao_bolsao - processarNovaSolicitacao] - CPF vazio ou imobiliaria_id não informado");
        }
        $data['validacao_bolsao'] = $cpfEncontradoNaListagem ? 1 : 0;
        error_log("DEBUG [validacao_bolsao - processarNovaSolicitacao] - validacao_bolsao será salvo como: " . $data['validacao_bolsao']);
        
        // Validar dados obrigatórios
        $required = ['categoria_id', 'subcategoria_id', 'descricao_problema'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $instancia = $this->getInstanciaFromUrl();
                $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode('Todos os campos obrigatórios devem ser preenchidos')));
                return;
            }
        }
        
        // Definir condição inicial: "Aguardando Resposta do Prestador"
        $condicaoModel = new \App\Models\Condicao();
        $condicaoAguardando = $condicaoModel->findByNome('Aguardando Resposta do Prestador');
        if ($condicaoAguardando) {
            $data['condicao_id'] = $condicaoAguardando['id'];
        }
        
        // Definir status inicial: "Nova Solicitação" ou "Buscando Prestador"
        $statusModel = new \App\Models\Status();
        $statusNova = $statusModel->findByNome('Nova Solicitação');
        if (!$statusNova) {
            $statusNova = $statusModel->findByNome('Buscando Prestador');
        }
        if ($statusNova) {
            $data['status_id'] = $statusNova['id'];
        }
        
        // Criar solicitação
        $solicitacaoId = $this->solicitacaoModel->create($data);
        
        // Garantir que tipo_qualificacao foi salvo corretamente (se foi definido)
        // Como processarNovaSolicitacao não define tipo_qualificacao, não precisa verificar aqui
        
        $instancia = $this->getInstanciaFromUrl();
        if ($solicitacaoId) {
            $this->redirect(url($instancia . '/solicitacoes?success=' . urlencode('Solicitação criada com sucesso!')));
        } else {
            $this->redirect(url($instancia . '/nova-solicitacao?error=' . urlencode('Erro ao criar solicitação. Tente novamente.')));
        }
    }
    
    /**
     * Atualizar perfil do locatário
     */
    public function atualizarPerfil(string $instancia = ''): void
    {
        try {
            $this->requireLocatarioAuth();
            
            if (!$this->isPost()) {
                $this->json([
                    'success' => false,
                    'message' => 'Método não permitido'
                ]);
                return;
            }
            
            $locatario = $_SESSION['locatario'];
            
            // Receber dados do formulário
            $nome = trim($this->input('nome'));
            $email = trim($this->input('email'));
            $whatsapp = trim($this->input('whatsapp'));
            
            // Validar dados
            if (empty($nome)) {
                $this->json([
                    'success' => false,
                    'message' => 'O nome é obrigatório'
                ]);
                return;
            }
            
            // Validar email se fornecido
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->json([
                    'success' => false,
                    'message' => 'E-mail inválido'
                ]);
                return;
            }
            
            // Validar WhatsApp se fornecido
            if (!empty($whatsapp)) {
                $whatsappLimpo = preg_replace('/\D/', '', $whatsapp);
                if (strlen($whatsappLimpo) < 10 || strlen($whatsappLimpo) > 11) {
                    $this->json([
                        'success' => false,
                        'message' => 'WhatsApp inválido. Use o formato (XX) XXXXX-XXXX'
                    ]);
                    return;
                }
            }
            
            // Buscar locatário no banco - tentar por CPF primeiro, depois por ksi_cliente_id
            $cpfLimpo = str_replace(['.', '-'], '', $locatario['cpf']);
            $locatarioBanco = null;
            
            // Tentar buscar por CPF primeiro
            if (!empty($cpfLimpo)) {
            $locatarioBanco = $this->locatarioModel->findByCpfAndImobiliaria($cpfLimpo, $locatario['imobiliaria_id']);
            }
            
            // Se não encontrou por CPF, tentar por ksi_cliente_id
            if (!$locatarioBanco && !empty($locatario['id'])) {
                $locatarioBanco = $this->locatarioModel->findByKsiIdAndImobiliaria($locatario['id'], $locatario['imobiliaria_id']);
            }
            
            // Preparar dados para atualização
            $dados = [
                'nome' => $nome,
                'email' => $email,
                'whatsapp' => $whatsapp,
                'telefone' => $whatsapp // Usar whatsapp como telefone também
            ];
            
            // Se o locatário existe no banco, atualizar
            if ($locatarioBanco) {
                $sucesso = $this->locatarioModel->updateDadosPessoais($locatarioBanco['id'], $dados);
            } else {
                // Se não existe, criar novo registro
                $dados['cpf'] = $cpfLimpo;
                $dados['imobiliaria_id'] = $locatario['imobiliaria_id'];
                $dados['ksi_cliente_id'] = $locatario['id'];
                $dados['status'] = 'ATIVO';
                
                $sucesso = $this->locatarioModel->create($dados);
            }
            
            if ($sucesso) {
                // Atualizar dados na sessão
                $_SESSION['locatario']['nome'] = $nome;
                $_SESSION['locatario']['email'] = $email;
                $_SESSION['locatario']['whatsapp'] = $whatsapp;
                $_SESSION['locatario']['telefone'] = $whatsapp;
                
                $this->json([
                    'success' => true,
                    'message' => 'Perfil atualizado com sucesso!'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar perfil. Tente novamente.'
                ]);
            }
        } catch (\Exception $e) {
            error_log('Erro ao atualizar perfil: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $this->json([
                'success' => false,
                'message' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Logout do locatário
     */
    public function logout(string $instancia = ''): void
    {
        // Tentar pegar a instância da sessão antes de limpar
        if (empty($instancia) && isset($_SESSION['locatario']['instancia'])) {
            $instancia = $_SESSION['locatario']['instancia'];
        }
        
        // Se ainda estiver vazio, tentar da URL
        if (empty($instancia)) {
            $instancia = $this->getInstanciaFromUrl();
        }
        
        // Se ainda estiver vazio, usar 'demo' como padrão
        if (empty($instancia)) {
            $instancia = 'demo';
        }
        
        // Log para debug
        error_log('Logout iniciado - Instância: ' . $instancia);
        error_log('Sessão antes do logout: ' . json_encode($_SESSION));
        
        // Limpar completamente a sessão do locatário
        if (isset($_SESSION['locatario'])) {
            unset($_SESSION['locatario']);
        }
        if (isset($_SESSION['locatario_id'])) {
            unset($_SESSION['locatario_id']);
        }
        if (isset($_SESSION['user_id'])) {
            unset($_SESSION['user_id']);
        }
        if (isset($_SESSION['instancia'])) {
            unset($_SESSION['instancia']);
        }
        if (isset($_SESSION['imobiliaria_id'])) {
            unset($_SESSION['imobiliaria_id']);
        }
        if (isset($_SESSION['cliente_data'])) {
            unset($_SESSION['cliente_data']);
        }
        
        // Limpar todas as variáveis de sessão relacionadas
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'locatario') !== false || strpos($key, 'imobiliaria') !== false) {
                unset($_SESSION[$key]);
            }
        }
        
        // Garantir que a sessão foi limpa
        $_SESSION['locatario'] = null;
        
        // Regenerar ID da sessão para segurança
        session_regenerate_id(true);
        
        // Log para debug
        error_log('Sessão após logout: ' . json_encode($_SESSION));
        error_log('Redirecionando para: /' . $instancia);
        
        $this->redirect('/' . $instancia);
    }
    
    /**
     * Registrar no histórico que a ação foi feita pelo LOCATÁRIO
     */
    private function registrarHistoricoLocatario(int $solicitacaoId, int $statusId, string $observacao): void
    {
        try {
            $sql = "INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                    VALUES (?, ?, NULL, ?, NOW())";
            \App\Core\Database::query($sql, [$solicitacaoId, $statusId, $observacao]);
        } catch (\Exception $e) {
            error_log("Erro ao registrar histórico do locatário: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar se locatário está autenticado
     */
    private function requireLocatarioAuth(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (!isset($_SESSION['locatario'])) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Sessão expirada. Por favor, faça login novamente.',
                    'redirect' => true
                ], 401);
            }
            
            $instancia = $this->getInstanciaFromUrl();
            $this->redirect(url($instancia));
        }
        
        // Verificar se sessão não expirou (24 horas)
        if (time() - $_SESSION['locatario']['login_time'] > 86400) {
            unset($_SESSION['locatario']);
            
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Sessão expirada. Por favor, faça login novamente.',
                    'redirect' => true
                ], 401);
            }
            
            $instancia = $this->getInstanciaFromUrl();
            $this->redirect(url($instancia));
        }
    }
    
    /**
     * Extrair instância da URL
     */
    private function getInstanciaFromUrl(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        // Para a nova estrutura: /{instancia} ou /{instancia}/dashboard, etc.
        // A instância é sempre o primeiro segmento após o base path
        if (!empty($segments)) {
            // Remover o base path se existir
            $basePath = trim(FOLDER, '/');
            if (!empty($basePath) && $segments[0] === $basePath) {
                array_shift($segments);
            }
            
            // O primeiro segmento restante é a instância
            if (!empty($segments[0])) {
                return $segments[0];
            }
        }
        
        return '';
    }
    
    // ============================================================
    // SOLICITAÇÃO MANUAL (SEM AUTENTICAÇÃO)
    // ============================================================
    
    /**
     * Solicitação Manual - Fluxo para usuários não logados
     */
    public function solicitacaoManual(string $instancia = ''): void
    {
        if ($this->isPost()) {
            $this->processarSolicitacaoManual(1);
            return;
        }
        
        // Extrair instância da URL se não foi passada
        if (empty($instancia)) {
            $instancia = $this->getInstanciaFromUrl();
        }
        
        // Buscar imobiliária
        $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
        
        if (!$imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Imobiliária não encontrada'
            ]);
            return;
        }
        
        // Limpar dados da sessão ao começar nova solicitação
        unset($_SESSION['solicitacao_manual']);
        
        // Buscar categorias para as próximas etapas
        $categoriaModel = new \App\Models\Categoria();
        $subcategoriaModel = new \App\Models\Subcategoria();
        // ✅ Usar getHierarquicas() para organizar categorias em hierarquia pai-filha
        $categorias = $categoriaModel->getHierarquicas();
        $subcategorias = $subcategoriaModel->getAtivas();
        
        // Organizar subcategorias por categoria (incluindo categorias filhas)
        foreach ($categorias as $key => $categoria) {
            // Organizar subcategorias para a categoria pai
            $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                return $sub['categoria_id'] == $categoria['id'];
            }));
            
            // Organizar subcategorias para cada categoria filha
            if (!empty($categoria['filhas'])) {
                foreach ($categoria['filhas'] as $filhaKey => $categoriaFilha) {
                    $categorias[$key]['filhas'][$filhaKey]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoriaFilha) {
                        return $sub['categoria_id'] == $categoriaFilha['id'];
                    }));
                }
            }
        }
        
        $this->view('locatario.solicitacao-manual', [
            'imobiliaria' => $imobiliaria,
            'instancia' => $instancia,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'etapa' => 1,
            'dados' => $_SESSION['solicitacao_manual'] ?? []
        ]);
    }
    
    /**
     * Processar etapa específica da solicitação manual
     */
    public function solicitacaoManualEtapa(string $instancia, int $etapa): void
    {
        // GET: exibir a etapa
        if (!$this->isPost()) {
            // Se não há dados na sessão e não é etapa 1, redirecionar
            if (!isset($_SESSION['solicitacao_manual']) && $etapa > 1) {
                $this->redirect(url($instancia . '/solicitacao-manual'));
                return;
            }
            
            // Buscar imobiliária
            $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
            
            if (!$imobiliaria) {
                $this->view('errors.404', ['message' => 'Imobiliária não encontrada']);
                return;
            }
            
            // Buscar categorias e subcategorias
            $categoriaModel = new \App\Models\Categoria();
            $subcategoriaModel = new \App\Models\Subcategoria();
            
            // Filtrar categorias baseado na finalidade da locação selecionada (se estiver na etapa 2 ou superior)
            $dados = $_SESSION['solicitacao_manual'] ?? [];
            $tipoImovel = $dados['tipo_imovel'] ?? 'RESIDENCIAL';
            
            // ✅ Usar getHierarquicas() para organizar categorias em hierarquia pai-filha
            // Se estiver na etapa 2 ou superior, filtrar categorias
            if ($etapa >= 2 && !empty($tipoImovel)) {
                if ($tipoImovel === 'RESIDENCIAL') {
                    $categorias = $categoriaModel->getHierarquicas('RESIDENCIAL');
                } elseif ($tipoImovel === 'COMERCIAL') {
                    $categorias = $categoriaModel->getHierarquicas('COMERCIAL');
                } else {
                    $categorias = $categoriaModel->getHierarquicas();
                }
            } else {
                // Na etapa 1, mostrar todas em hierarquia
                $categorias = $categoriaModel->getHierarquicas();
            }
            
            $subcategorias = $subcategoriaModel->getAtivas();
            
            // Organizar subcategorias por categoria (incluindo categorias filhas)
            foreach ($categorias as $key => $categoria) {
                // Organizar subcategorias para a categoria pai
                $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                    return $sub['categoria_id'] == $categoria['id'];
                }));
                
                // Organizar subcategorias para cada categoria filha
                if (!empty($categoria['filhas'])) {
                    foreach ($categoria['filhas'] as $filhaKey => $categoriaFilha) {
                        $categorias[$key]['filhas'][$filhaKey]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoriaFilha) {
                            return $sub['categoria_id'] == $categoriaFilha['id'];
                        }));
                    }
                }
            }
            
            // Verificar se deve pular etapa 4 (se for emergencial e escolheu 120_minutos)
            if ($etapa == 4) {
                $isEmergencial = false;
                $tipoAtendimentoEmergencial = $dados['tipo_atendimento_emergencial'] ?? '';
                
                if (!empty($dados['subcategoria_id'])) {
                    $subcategoriaModel = new \App\Models\Subcategoria();
                    $subcategoria = $subcategoriaModel->find($dados['subcategoria_id']);
                    if ($subcategoria && !empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) {
                        $isEmergencial = true;
                    }
                }
                
                // Se for emergencial e escolheu 120_minutos, pular para confirmação
                if ($isEmergencial && $tipoAtendimentoEmergencial === '120_minutos') {
                    $this->redirect(url($instancia . '/solicitacao-manual/etapa/5'));
                    return;
                }
            }
            
            $this->view('locatario.solicitacao-manual', [
                'imobiliaria' => $imobiliaria,
                'instancia' => $instancia,
                'categorias' => $categorias,
                'subcategorias' => $subcategorias,
                'etapa' => $etapa,
                'dados' => $dados
            ]);
            return;
        }
        
        // POST: processar dados da etapa
        $this->processarSolicitacaoManual($etapa);
    }
    
    /**
     * Processar dados de cada etapa da solicitação manual
     */
    private function processarSolicitacaoManual(int $etapa, ?string $token = null): void
    {
        $instancia = $this->getInstanciaFromUrl();
        $modoToken = !empty($token);
        
        // Função helper para gerar URL baseada no modo
        $urlBase = function($path) use ($instancia, $token, $modoToken) {
            if ($modoToken) {
                return url('solicitacao-manual/' . $token . $path);
            }
            return url($instancia . '/solicitacao-manual' . $path);
        };
        
        // Inicializar sessão se não existir
        if (!isset($_SESSION['solicitacao_manual'])) {
            $_SESSION['solicitacao_manual'] = [];
        }
        
        switch ($etapa) {
            case 1: // Dados e Endereço
                $nome = trim($this->input('nome_completo'));
                $cpf = trim($this->input('cpf'));
                $whatsapp = trim($this->input('whatsapp'));
                $tipoImovel = $this->input('tipo_imovel');
                $subtipoImovel = $this->input('subtipo_imovel');
                $cep = trim($this->input('cep'));
                $endereco = trim($this->input('endereco'));
                $numero = trim($this->input('numero'));
                $complemento = trim($this->input('complemento'));
                $bairro = trim($this->input('bairro'));
                $cidade = trim($this->input('cidade'));
                $estado = trim($this->input('estado'));
                $numeroContrato = trim($this->input('numero_contrato'));

                // Verificar se CPF está no bolsão (locatarios_contratos) para tratar fluxo especial
                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                $validacaoBolsao = 0;
                $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
                if (!empty($cpfLimpo) && !empty($imobiliaria['id'])) {
                    // Usar mesma lógica de comparação que outros lugares (remove pontos, traços e espaços)
                    $sql = "SELECT id FROM locatarios_contratos 
                            WHERE imobiliaria_id = ? 
                            AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ? 
                            LIMIT 1";
                    $cpfEncontrado = \App\Core\Database::fetch($sql, [$imobiliaria['id'], $cpfLimpo]);
                    $validacaoBolsao = $cpfEncontrado ? 1 : 0;
                    error_log("DEBUG [validacao_bolsao - etapa1] - CPF: {$cpfLimpo}, imobiliaria_id: {$imobiliaria['id']}, encontrado: " . ($cpfEncontrado ? 'SIM' : 'NAO'));
                }

                // Campos realmente obrigatórios para prosseguir
                // OBS:
                // - Se NÃO for bolsão (validacao_bolsao != 1): exigir todos os dados de endereço e nome
                // - Se for bolsão (validacao_bolsao == 1): muitos dados já vêm da base locatarios_contratos,
                //   então não vamos barrar por falta de endereço/nome aqui, apenas garantir CPF e WhatsApp.
                $camposIncompletos = (empty($nome) || empty($cep) || empty($endereco) || empty($numero) ||
                    empty($bairro) || empty($cidade) || empty($estado));

                if (empty($cpf) || empty($whatsapp) || ($validacaoBolsao != 1 && $camposIncompletos)) {
                    // Debug: Logar todos os campos recebidos quando a validação falhar
                    error_log('[SOLICITACAO_MANUAL_ETAPA1] Validação falhou - Campos recebidos: ' . json_encode([
                        'validacao_bolsao' => $validacaoBolsao,
                        'nome' => $nome ?: '(vazio)',
                        'cpf' => $cpf ?: '(vazio)',
                        'whatsapp' => $whatsapp ?: '(vazio)',
                        'tipo_imovel' => $tipoImovel ?: '(vazio)',
                        'subtipo_imovel' => $subtipoImovel ?: '(vazio)',
                        'cep' => $cep ?: '(vazio)',
                        'endereco' => $endereco ?: '(vazio)',
                        'numero' => $numero ?: '(vazio)',
                        'complemento' => $complemento ?: '(vazio)',
                        'bairro' => $bairro ?: '(vazio)',
                        'cidade' => $cidade ?: '(vazio)',
                        'estado' => $estado ?: '(vazio)',
                        'numero_contrato' => $numeroContrato ?: '(vazio)',
                        'POST_completo' => $_POST
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    $this->redirect($urlBase('?error=' . urlencode('Preencha todos os dados obrigatórios antes de continuar')));
                    return;
                }

                // Se tipo de imóvel não vier (edge case de alguns browsers), assumir RESIDENCIAL
                if (empty($tipoImovel)) {
                    $tipoImovel = 'RESIDENCIAL';
                }

                // Validar subtipo de imóvel quando for RESIDENCIAL
                if ($tipoImovel === 'RESIDENCIAL' && empty($subtipoImovel)) {
                    $this->redirect($urlBase('?error=' . urlencode('Selecione o tipo de imóvel (Casa ou Apartamento) antes de continuar')));
                    return;
                }

                $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                if (!$solicitacaoManualModel->validarCPF($cpf)) {
                    $this->redirect($urlBase('?error=' . urlencode('CPF/CNPJ inválido')));
                    return;
                }

                $whatsappLimpo = preg_replace('/\D/', '', $whatsapp);
                if (strlen($whatsappLimpo) < 10 || strlen($whatsappLimpo) > 11) {
                    $this->redirect($urlBase('?error=' . urlencode('WhatsApp inválido')));
                    return;
                }
                
                // Salvar dados na sessão
                $_SESSION['solicitacao_manual']['nome_completo'] = $nome;
                $_SESSION['solicitacao_manual']['cpf'] = $cpf;
                $_SESSION['solicitacao_manual']['whatsapp'] = $whatsapp;
                $_SESSION['solicitacao_manual']['tipo_imovel'] = $tipoImovel;
                $_SESSION['solicitacao_manual']['subtipo_imovel'] = $subtipoImovel;
                $_SESSION['solicitacao_manual']['cep'] = $cep;
                $_SESSION['solicitacao_manual']['endereco'] = $endereco;
                $_SESSION['solicitacao_manual']['numero'] = $numero;
                $_SESSION['solicitacao_manual']['complemento'] = $complemento;
                $_SESSION['solicitacao_manual']['bairro'] = $bairro;
                $_SESSION['solicitacao_manual']['cidade'] = $cidade;
                $_SESSION['solicitacao_manual']['estado'] = $estado;
                $_SESSION['solicitacao_manual']['numero_contrato'] = $numeroContrato;
                $_SESSION['solicitacao_manual']['validacao_bolsao'] = $validacaoBolsao;
                break;

            case 2: // Serviço
                // Verificar se é seleção múltipla de subcategorias (Manutenção e Prevenção)
                $subcategoriasSelecionadas = $this->input('subcategorias_selecionadas', []);
                
                if (!empty($subcategoriasSelecionadas) && is_array($subcategoriasSelecionadas)) {
                    // Seleção múltipla de subcategorias - buscar categoria_id de cada subcategoria
                    $categoriasSelecionadas = [];
                    $subcategoriaModel = new \App\Models\Subcategoria();
                    
                    foreach ($subcategoriasSelecionadas as $subcategoriaId) {
                        $subcategoria = $subcategoriaModel->find((int)$subcategoriaId);
                        
                        if (!$subcategoria || empty($subcategoria['categoria_id'])) {
                            continue; // Pular se subcategoria não encontrada
                        }
                        
                        $categoriasSelecionadas[] = [
                            'categoria_id' => (int)$subcategoria['categoria_id'],
                            'subcategoria_id' => (int)$subcategoriaId
                        ];
                    }
                    
                    if (empty($categoriasSelecionadas)) {
                        $this->redirect($urlBase('/etapa/2?error=' . urlencode('Selecione pelo menos uma subcategoria')));
                        return;
                    }
                    
                    // Limitar a 3 seleções
                    if (count($categoriasSelecionadas) > 3) {
                        $categoriasSelecionadas = array_slice($categoriasSelecionadas, 0, 3);
                    }
                    
                    // Salvar primeira seleção como principal (para compatibilidade)
                    $_SESSION['solicitacao_manual']['categoria_id'] = $categoriasSelecionadas[0]['categoria_id'];
                    $_SESSION['solicitacao_manual']['subcategoria_id'] = $categoriasSelecionadas[0]['subcategoria_id'];
                    $_SESSION['solicitacao_manual']['categorias_selecionadas'] = $categoriasSelecionadas;
                } else {
                    // Verificar se é seleção múltipla antiga (categorias_filhas) - compatibilidade
                    $categoriasFilhas = $this->input('categorias_filhas', []);
                    
                    if (!empty($categoriasFilhas) && is_array($categoriasFilhas)) {
                        // Seleção múltipla antiga - validar que cada categoria filha tem subcategoria
                        $categoriasSelecionadas = [];
                        $todasValidas = true;
                        
                        foreach ($categoriasFilhas as $categoriaFilhaId) {
                            $subcategoriaId = $this->input('subcategoria_id_' . $categoriaFilhaId);
                            
                            if (empty($subcategoriaId)) {
                                $todasValidas = false;
                                break;
                            }
                            
                            $categoriasSelecionadas[] = [
                                'categoria_id' => (int)$categoriaFilhaId,
                                'subcategoria_id' => (int)$subcategoriaId
                            ];
                        }
                        
                        if (!$todasValidas || empty($categoriasSelecionadas)) {
                            $this->redirect($urlBase('/etapa/2?error=' . urlencode('Selecione o tipo de serviço para cada categoria escolhida')));
                            return;
                        }
                        
                        // Limitar a 3 seleções
                        if (count($categoriasSelecionadas) > 3) {
                            $categoriasSelecionadas = array_slice($categoriasSelecionadas, 0, 3);
                        }
                        
                        // Salvar primeira seleção como principal (para compatibilidade)
                        $_SESSION['solicitacao_manual']['categoria_id'] = $categoriasSelecionadas[0]['categoria_id'];
                        $_SESSION['solicitacao_manual']['subcategoria_id'] = $categoriasSelecionadas[0]['subcategoria_id'];
                        $_SESSION['solicitacao_manual']['categorias_selecionadas'] = $categoriasSelecionadas;
                    } else {
                        // Seleção única (comportamento padrão)
                        $categoriaId = $this->input('categoria_id');
                        $subcategoriaId = $this->input('subcategoria_id');

                        if (empty($categoriaId) || empty($subcategoriaId)) {
                            $this->redirect($urlBase('/etapa/2?error=' . urlencode('Selecione a categoria e o tipo de serviço para continuar')));
                            return;
                        }

                        $_SESSION['solicitacao_manual']['categoria_id'] = $categoriaId;
                        $_SESSION['solicitacao_manual']['subcategoria_id'] = $subcategoriaId;
                        $_SESSION['solicitacao_manual']['categorias_selecionadas'] = [[
                            'categoria_id' => (int)$categoriaId,
                            'subcategoria_id' => (int)$subcategoriaId
                        ]];
                    }
                }
                break;

            case 3: // Descrição + Fotos
                $localManutencao = trim($this->input('local_manutencao'));
                $descricaoProblema = trim($this->input('descricao_problema'));

                if (empty($descricaoProblema)) {
                    $this->redirect($urlBase('/etapa/3?error=' . urlencode('Descreva o problema para continuar')));
                    return;
                }

                // Upload de fotos (agora nesta etapa)
                $fotos = [];
                if (!empty($_FILES['fotos']['name'][0])) {
                    $fotos = $this->processarUploadFotos();
                }

                $_SESSION['solicitacao_manual']['local_manutencao'] = $localManutencao;
                $_SESSION['solicitacao_manual']['descricao_problema'] = $descricaoProblema;
                $_SESSION['solicitacao_manual']['fotos'] = $fotos;
                break;

            case 4: // Agendamento ou Atendimento Emergencial
                // Verificar se é emergencial E se CPF está validado pelo bolsão
                $isEmergencial = false;
                $cpfValidadoBolsao = !empty($_SESSION['solicitacao_manual']['validacao_bolsao']) && $_SESSION['solicitacao_manual']['validacao_bolsao'] == 1;
                
                if (!empty($_SESSION['solicitacao_manual']['subcategoria_id'])) {
                    $subcategoriaModel = new \App\Models\Subcategoria();
                    $subcategoria = $subcategoriaModel->find($_SESSION['solicitacao_manual']['subcategoria_id']);
                    
                    // Só considera emergencial se subcategoria for emergencial E CPF validado no bolsão
                    if ($subcategoria && !empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true) && $cpfValidadoBolsao) {
                        $isEmergencial = true;
                        $_SESSION['solicitacao_manual']['is_emergencial'] = 1;
                        
                        // Processar tipo de atendimento emergencial
                        $tipoAtendimentoEmergencial = $this->input('tipo_atendimento_emergencial', '120_minutos');
                        $_SESSION['solicitacao_manual']['tipo_atendimento_emergencial'] = $tipoAtendimentoEmergencial;
                        
                        // Se escolheu 120_minutos, não precisa de horários - ir direto para confirmação
                        if ($tipoAtendimentoEmergencial === '120_minutos') {
                            $_SESSION['solicitacao_manual']['horarios_preferenciais'] = [];
                            $this->redirect($urlBase('/etapa/5'));
                            return;
                        }
                        
                        // Se escolheu agendar, processar horários normalmente abaixo
                    } else if ($subcategoria && !empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) {
                        // Subcategoria é emergencial mas CPF não validado - ainda marca como emergencial para fins de registro
                        $_SESSION['solicitacao_manual']['is_emergencial'] = 1;
                    }
                }
                
                // Processar horários preferenciais (normal ou emergencial agendado)
                $horariosRaw = $this->input('horarios_opcoes');
                $horarios = [];
                
                if (!empty($horariosRaw)) {
                    $horarios = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
                }
                
                // Validar que pelo menos 1 horário foi selecionado (só se não for emergencial 120_minutos)
                if (empty($horarios)) {
                    $this->redirect($urlBase('/etapa/4?error=' . urlencode('Selecione pelo menos um horário preferencial')));
                    return;
                }
                
                $_SESSION['solicitacao_manual']['horarios_preferenciais'] = $horarios;
                break;
                
            case 5: // Confirmação
                $termosAceitos = $this->input('termo_aceite');
                $lgpdAceite = $this->input('lgpd_aceite');
                
                if (!$termosAceitos || !$lgpdAceite) {
                    $this->redirect($urlBase('/etapa/5?error=' . urlencode('É necessário aceitar os termos e a LGPD para continuar')));
                    return;
                }
                
                $_SESSION['solicitacao_manual']['termos_aceitos'] = true;
                $_SESSION['solicitacao_manual']['lgpd_aceite'] = $lgpdAceite;
                
                // Finalizar e salvar (passar o token se estiver em modo token)
                $this->finalizarSolicitacaoManual($token);
                return;
        }
        
        // Salvar etapa atual
        $_SESSION['solicitacao_manual']['etapa'] = $etapa;
        
        // Redirecionar para próxima etapa
        $proximaEtapa = $etapa + 1;
        if ($proximaEtapa <= 5) {
            $this->redirect($urlBase('/etapa/' . $proximaEtapa));
        }
    }
    
    /**
     * Salvar dados temporários da etapa atual (sem validação rigorosa)
     * Usado quando o usuário clica em "Voltar" para preservar os dados
     */
    public function salvarDadosTemporariosManual(): void
    {
        $this->salvarDadosTemporariosManualInterno(null);
    }
    
    /**
     * Salvar dados temporários da etapa atual com token (sem validação rigorosa)
     * Usado quando o usuário clica em "Voltar" para preservar os dados
     */
    public function salvarDadosTemporariosManualComToken(string $token): void
    {
        // Validar token
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $solicitacaoManual = $solicitacaoManualModel->findByToken($token);
        
        if (!$solicitacaoManual) {
            $this->json(['success' => false, 'message' => 'Token inválido']);
            return;
        }
        
        $this->salvarDadosTemporariosManualInterno($token);
    }
    
    /**
     * Método interno para salvar dados temporários (com ou sem token)
     */
    private function salvarDadosTemporariosManualInterno(?string $token = null): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        // Se estiver em modo token, validar sessão
        if ($token) {
            if (empty($_SESSION['solicitacao_manual_token']) || $_SESSION['solicitacao_manual_token'] !== $token) {
                $this->json(['success' => false, 'message' => 'Sessão inválida']);
                return;
            }
        }
        
        // Inicializar sessão se não existir
        if (!isset($_SESSION['solicitacao_manual'])) {
            $_SESSION['solicitacao_manual'] = [];
        }
        
        $etapa = (int)$this->input('etapa', 1);
        
        // Log para debug
        error_log("DEBUG [salvarDadosTemporariosManual] - Etapa: {$etapa}, Dados recebidos: " . json_encode($_POST));
        
        // Salvar dados da etapa atual sem validação rigorosa
        switch ($etapa) {
            case 1: // Dados e Endereço
                // Salvar todos os campos, mesmo que vazios (para não perder dados já preenchidos)
                $nome = $this->input('nome_completo');
                if ($nome !== null) $_SESSION['solicitacao_manual']['nome_completo'] = trim($nome);
                
                $cpf = $this->input('cpf');
                if ($cpf !== null) $_SESSION['solicitacao_manual']['cpf'] = trim($cpf);
                
                $whatsapp = $this->input('whatsapp');
                if ($whatsapp !== null) $_SESSION['solicitacao_manual']['whatsapp'] = trim($whatsapp);
                
                $tipoImovel = $this->input('tipo_imovel');
                if ($tipoImovel !== null) $_SESSION['solicitacao_manual']['tipo_imovel'] = $tipoImovel;
                
                $subtipoImovel = $this->input('subtipo_imovel');
                if ($subtipoImovel !== null) $_SESSION['solicitacao_manual']['subtipo_imovel'] = $subtipoImovel;
                
                $cep = $this->input('cep');
                if ($cep !== null) $_SESSION['solicitacao_manual']['cep'] = trim($cep);
                
                $endereco = $this->input('endereco');
                if ($endereco !== null) $_SESSION['solicitacao_manual']['endereco'] = trim($endereco);
                
                $numero = $this->input('numero');
                if ($numero !== null) $_SESSION['solicitacao_manual']['numero'] = trim($numero);
                
                $complemento = $this->input('complemento');
                if ($complemento !== null) $_SESSION['solicitacao_manual']['complemento'] = trim($complemento);
                
                $bairro = $this->input('bairro');
                if ($bairro !== null) $_SESSION['solicitacao_manual']['bairro'] = trim($bairro);
                
                $cidade = $this->input('cidade');
                if ($cidade !== null) $_SESSION['solicitacao_manual']['cidade'] = trim($cidade);
                
                $estado = $this->input('estado');
                if ($estado !== null) $_SESSION['solicitacao_manual']['estado'] = trim($estado);
                
                $numeroContrato = $this->input('numero_contrato');
                if ($numeroContrato !== null) $_SESSION['solicitacao_manual']['numero_contrato'] = trim($numeroContrato);
                
                error_log("DEBUG [salvarDadosTemporariosManual] - Dados salvos na sessão: " . json_encode($_SESSION['solicitacao_manual']));
                break;
                
            case 2: // Serviço
                $categoriaId = $this->input('categoria_id');
                if ($categoriaId !== null) $_SESSION['solicitacao_manual']['categoria_id'] = $categoriaId;
                
                $subcategoriaId = $this->input('subcategoria_id');
                if ($subcategoriaId !== null) $_SESSION['solicitacao_manual']['subcategoria_id'] = $subcategoriaId;
                break;
                
            case 3: // Descrição + Fotos
                $localManutencao = $this->input('local_manutencao');
                if ($localManutencao !== null) $_SESSION['solicitacao_manual']['local_manutencao'] = trim($localManutencao);
                
                $descricaoProblema = $this->input('descricao_problema');
                if ($descricaoProblema !== null) $_SESSION['solicitacao_manual']['descricao_problema'] = trim($descricaoProblema);
                // Fotos não são salvas aqui (seriam perdidas), mas mantemos as que já existem
                break;
                
            case 4: // Agendamento ou Atendimento Emergencial
                if ($this->input('tipo_atendimento_emergencial')) {
                    $_SESSION['solicitacao_manual']['tipo_atendimento_emergencial'] = $this->input('tipo_atendimento_emergencial');
                }
                $horariosRaw = $this->input('horarios_opcoes');
                if (!empty($horariosRaw)) {
                    $horarios = is_string($horariosRaw) ? json_decode($horariosRaw, true) : $horariosRaw;
                    if (is_array($horarios) && !empty($horarios)) {
                        $_SESSION['solicitacao_manual']['horarios_preferenciais'] = $horarios;
                    }
                }
                break;
        }
        
        $this->json(['success' => true]);
    }
    
    /**
     * Finalizar e salvar solicitação manual no banco de dados
     */
    private function finalizarSolicitacaoManual(?string $token = null): void
    {
        $instancia = $this->getInstanciaFromUrl();
        $modoToken = !empty($token);
        
        // Função helper para gerar URL baseada no modo
        $urlBase = function($path) use ($instancia, $token, $modoToken) {
            if ($modoToken) {
                return url('solicitacao-manual/' . $token . $path);
            }
            return url($instancia . '/solicitacao-manual' . $path);
        };
        $dados = $_SESSION['solicitacao_manual'] ?? [];
        
        // Verificar se é emergencial e fora do horário comercial
        $isEmergencialManual = !empty($dados['is_emergencial']);
        $configuracaoModel = new \App\Models\Configuracao();
        $isForaHorarioManual = $configuracaoModel->isForaHorarioComercial();
        $isEmergencialForaHorarioManual = $isEmergencialManual && $isForaHorarioManual;
        
        // Verificar se há múltiplas categorias selecionadas (Manutenção e Prevenção)
        $categoriasSelecionadas = $dados['categorias_selecionadas'] ?? [];
        $temMultiplasCategorias = !empty($categoriasSelecionadas) && is_array($categoriasSelecionadas) && count($categoriasSelecionadas) > 1;
        
        // Validar que todos os dados necessários estão presentes
        $camposObrigatorios = ['nome_completo', 'cpf', 'whatsapp', 'tipo_imovel', 'cep', 
                               'endereco', 'numero', 'bairro', 'cidade', 'estado',
                               'descricao_problema', 'termos_aceitos'];
        
        // Se não tiver múltiplas categorias, validar categoria_id e subcategoria_id
        if (!$temMultiplasCategorias) {
            $camposObrigatorios[] = 'categoria_id';
            $camposObrigatorios[] = 'subcategoria_id';
        } else {
            // Se tiver múltiplas, validar que cada uma tem subcategoria
            foreach ($categoriasSelecionadas as $cat) {
                if (empty($cat['categoria_id']) || empty($cat['subcategoria_id'])) {
                    $this->redirect(url($instancia . '/solicitacao-manual?error=' . urlencode('Selecione o tipo de serviço para cada categoria escolhida.')));
                    return;
                }
            }
        }
        
        foreach ($camposObrigatorios as $campo) {
            if (!isset($dados[$campo]) || $dados[$campo] === '' || $dados[$campo] === null) {
                $this->redirect(url($instancia . '/solicitacao-manual?error=' . urlencode('Dados incompletos. Por favor, preencha todos os campos.')));
                return;
            }
        }
        
        // Buscar imobiliária
        $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
        
        if (!$imobiliaria) {
            $this->redirect($urlBase('?error=' . urlencode('Imobiliária não encontrada')));
            return;
        }
        
        // Limpar CPF (remover pontos, traços, espaços)
        $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
        
        error_log("DEBUG WhatsApp [finalizarSolicitacaoManual] - modoToken: " . ($modoToken ? 'true' : 'false') . ", token: " . ($token ?? 'vazio') . ", CPF limpo: {$cpfLimpo}, imobiliaria_id: {$imobiliaria['id']}");
        
        // Verificar se o CPF está na tabela locatarios_contratos para esta imobiliária
        // Comparar removendo formatação de ambos os lados para garantir que funcione
        $sql = "SELECT * FROM locatarios_contratos 
                WHERE imobiliaria_id = ? 
                AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
        $cpfEncontrado = \App\Core\Database::fetch($sql, [$imobiliaria['id'], $cpfLimpo]);
        
        error_log("DEBUG [validacao_bolsao] - CPF limpo: {$cpfLimpo}, imobiliaria_id: {$imobiliaria['id']}");
        error_log("DEBUG [validacao_bolsao] - CPF encontrado na listagem: " . ($cpfEncontrado ? 'SIM' : 'NÃO'));
        if ($cpfEncontrado) {
            error_log("DEBUG [validacao_bolsao] - CPF encontrado na tabela: " . ($cpfEncontrado['cpf'] ?? 'N/A'));
            error_log("DEBUG [numero_contrato] - numero_contrato em cpfEncontrado: " . ($cpfEncontrado['numero_contrato'] ?? 'NÃO ENCONTRADO'));
        } else {
            error_log("DEBUG [validacao_bolsao] - CPF {$cpfLimpo} NÃO encontrado na tabela locatarios_contratos para imobiliaria_id {$imobiliaria['id']}");
        }
        
        // Debug numero_contrato em $dados
        error_log("DEBUG [numero_contrato] - numero_contrato em \$dados: " . ($dados['numero_contrato'] ?? 'NÃO ENCONTRADO'));
        $numeroContratoFinal = $dados['numero_contrato'] ?? $cpfEncontrado['numero_contrato'] ?? null;
        error_log("DEBUG [numero_contrato] - numero_contrato final que será salvo: " . ($numeroContratoFinal ?? 'NULL'));
        
        // Verificar validação de utilização ANTES de decidir onde criar
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $categoriaIdParaValidacao = $dados['categoria_id'] ?? null;
        
        // Calcular validação de utilização (limite de CPF por categoria)
        $validacaoUtilizacao = null;
        if ($cpfEncontrado && $categoriaIdParaValidacao) {
            $validacaoUtilizacao = $solicitacaoManualModel->calcularValidacaoUtilizacao(
                $cpfLimpo,
                $imobiliaria['id'],
                (int)$categoriaIdParaValidacao
            );
            error_log("DEBUG [finalizarSolicitacaoManual] - Validação Utilização: " . ($validacaoUtilizacao === null ? 'NULL' : $validacaoUtilizacao) . " (0=excedido, 1=aprovado)");
        }
        
        // Se o CPF estiver na tabela E validacao_utilizacao = 1 (aprovado), criar como solicitação normal (vai para kanban)
        // Se não estiver na tabela OU validacao_utilizacao = 0 (excedido), criar como solicitação manual (vai para admin)
        if ($cpfEncontrado && $validacaoUtilizacao === 1) {
            // Preparar horários para salvar (converter array para JSON)
            $horarios = $dados['horarios_preferenciais'] ?? [];
            $horariosJson = !empty($horarios) ? json_encode($horarios) : null;
            
            // Se tiver múltiplas categorias, criar uma única solicitação com todas as subcategorias
            if ($temMultiplasCategorias) {
                $solicitacaoModel = new \App\Models\Solicitacao();
                $subcategoriaModel = new \App\Models\Subcategoria();
                $statusModel = new \App\Models\Status();
                $statusInicial = $statusModel->findByNome('Nova Solicitação') 
                              ?? $statusModel->findByNome('Nova') 
                              ?? $statusModel->findByNome('NOVA')
                              ?? ['id' => 1];
                
                // Buscar nomes de todas as subcategorias selecionadas
                $nomesSubcategorias = [];
                foreach ($categoriasSelecionadas as $cat) {
                    if (!empty($cat['subcategoria_id'])) {
                        $subcategoria = $subcategoriaModel->find($cat['subcategoria_id']);
                        if ($subcategoria && !empty($subcategoria['nome'])) {
                            $nomesSubcategorias[] = $subcategoria['nome'];
                        }
                    }
                }
                
                // Usar a primeira categoria/subcategoria como principal
                $primeiraCategoria = $categoriasSelecionadas[0];
                
                // Preparar observações com todas as subcategorias
                // NOTA: local_manutencao não deve ser incluído em observacoes, ele tem seu próprio campo no banco
                $observacoes = "Tipo: " . ($dados['tipo_imovel'] ?? 'RESIDENCIAL');
                if (count($nomesSubcategorias) > 1) {
                    $observacoes .= "\n\nServiços solicitados (" . count($nomesSubcategorias) . "):\n";
                    foreach ($nomesSubcategorias as $index => $nome) {
                        $observacoes .= ($index + 1) . ". " . $nome . "\n";
                    }
                    // Armazenar IDs das subcategorias em JSON para referência futura
                    $subcategoriasIds = array_column($categoriasSelecionadas, 'subcategoria_id');
                    $observacoes .= "\n[SUBCATEGORIAS_IDS: " . json_encode($subcategoriasIds) . "]";
                }
                
                $dadosSolicitacao = [
                    'imobiliaria_id' => $imobiliaria['id'],
                    'categoria_id' => $primeiraCategoria['categoria_id'],
                    'subcategoria_id' => $primeiraCategoria['subcategoria_id'], // Primeira como principal
                    'status_id' => $statusInicial['id'],
                    
                    // Dados do locatário
                    'locatario_id' => 0,
                    'locatario_nome' => $dados['nome_completo'],
                    'locatario_cpf' => $cpfLimpo,
                    'locatario_telefone' => $dados['whatsapp'],
                    'locatario_email' => null,
                    
                    // Dados do imóvel
                    'imovel_endereco' => $dados['endereco'],
                    'imovel_numero' => $dados['numero'],
                    'imovel_complemento' => $dados['complemento'] ?? null,
                    'imovel_bairro' => $dados['bairro'],
                    'imovel_cidade' => $dados['cidade'],
                    'imovel_estado' => $dados['estado'],
                    'imovel_cep' => $dados['cep'],
                    
                    // Descrição e detalhes
                    'descricao_problema' => $dados['descricao_problema'],
                    'local_manutencao' => $dados['local_manutencao'] ?? null,
                    'tipo_imovel' => $dados['tipo_imovel'] ?? 'RESIDENCIAL',
                    'observacoes' => $observacoes,
                    'prioridade' => 'NORMAL',
                    'numero_contrato' => $dados['numero_contrato'] ?? $cpfEncontrado['numero_contrato'] ?? null,
                    
                    'validacao_bolsao' => 1,
                    'tipo_qualificacao' => 'BOLSAO',
                    
                    'horarios_opcoes' => $horariosJson,
                    'datas_opcoes' => $horariosJson
                ];
                
                try {
                $solicitacaoId = $solicitacaoModel->create($dadosSolicitacao);
                    
                    if (!$solicitacaoId || $solicitacaoId <= 0) {
                        error_log("ERRO [finalizarSolicitacaoManual - múltiplas categorias] - create() retornou ID inválido: " . $solicitacaoId);
                        error_log("Dados tentados: " . json_encode($dadosSolicitacao));
                        $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação. Tente novamente.')));
                        return;
                    }
                } catch (\Exception $e) {
                    error_log("ERRO [finalizarSolicitacaoManual - múltiplas categorias] ao criar solicitação: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    error_log("Dados tentados: " . json_encode($dadosSolicitacao));
                    $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação: ' . $e->getMessage())));
                    return;
                }
                
                if ($solicitacaoId) {
                    // Gerar token de acesso
                    $tokenAcesso = bin2hex(random_bytes(32));
                    try {
                        $sqlToken = "UPDATE solicitacoes SET token_acesso = ? WHERE id = ?";
                        \App\Core\Database::query($sqlToken, [$tokenAcesso, $solicitacaoId]);
                    } catch (\Exception $e) {
                        error_log('Aviso: Coluna token_acesso não existe na tabela solicitacoes.');
                    }
                    
                    // Salvar fotos
                    if (!empty($dados['fotos']) && is_array($dados['fotos'])) {
                        foreach ($dados['fotos'] as $fotoNome) {
                            $urlArquivo = 'Public/uploads/solicitacoes/' . $fotoNome;
                            $sqlFoto = "INSERT INTO fotos (solicitacao_id, nome_arquivo, url_arquivo, created_at) 
                                        VALUES (?, ?, ?, NOW())";
                            try {
                                \App\Core\Database::query($sqlFoto, [$solicitacaoId, $fotoNome, $urlArquivo]);
                            } catch (\Exception $e) {
                                error_log("Erro ao salvar foto {$fotoNome}: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // Enviar WhatsApp
                    try {
                        $this->enviarNotificacaoWhatsApp($solicitacaoId, 'Nova Solicitação');
                    } catch (\Exception $e) {
                        error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
                    }
                    
                    // Limpar sessão
                    unset($_SESSION['solicitacao_manual']);
                    if ($modoToken) {
                        unset($_SESSION['solicitacao_manual_token']);
                    }
                    
                    // Verificar se é emergencial fora do horário
                    if ($isEmergencialForaHorarioManual) {
                        $this->redirect(url($instancia . '/solicitacao-manual-emergencial/' . $solicitacaoId));
                    } else {
                        $this->redirect(url($instancia . '/solicitacao-manual-sucesso/' . $solicitacaoId));
                    }
                    return;
                }
            }
            
            // Comportamento padrão: uma única solicitação
            $statusModel = new \App\Models\Status();
            $statusInicial = $statusModel->findByNome('Nova Solicitação') 
                          ?? $statusModel->findByNome('Nova') 
                          ?? $statusModel->findByNome('NOVA')
                          ?? ['id' => 1];
            
            // Montar observações com múltiplas subcategorias, quando existir seleção múltipla
            // Mesma lógica usada na solicitação manual para padronizar
            // NOTA: local_manutencao não deve ser incluído em observacoes, ele tem seu próprio campo no banco
            $observacoes = "Tipo: " . ($dados['tipo_imovel'] ?? 'RESIDENCIAL');
            
            // Verificar se há múltiplas subcategorias selecionadas (mesmo formato da solicitação manual)
            if ($temMultiplasCategorias && !empty($categoriasSelecionadas) && is_array($categoriasSelecionadas)) {
                $subcategoriaModel = new \App\Models\Subcategoria();
                $nomesSubcategorias = [];
                
                foreach ($categoriasSelecionadas as $cat) {
                    if (!empty($cat['subcategoria_id'])) {
                        $subcategoria = $subcategoriaModel->find((int)$cat['subcategoria_id']);
                        if ($subcategoria && !empty($subcategoria['nome'])) {
                            $nomesSubcategorias[] = $subcategoria['nome'];
                        }
                    }
                }
                
                if (count($nomesSubcategorias) > 1) {
                    // Mesmo formato usado para solicitações manuais, para reaproveitar o helper getSubcategorias
                    $observacoes .= "\n\nServiços solicitados (" . count($nomesSubcategorias) . "):\n";
                    foreach ($nomesSubcategorias as $index => $nome) {
                        $observacoes .= ($index + 1) . ". " . $nome . "\n";
                    }
                    
                    // Armazenar IDs das subcategorias em JSON para referência futura
                    $subcategoriasIds = array_column($categoriasSelecionadas, 'subcategoria_id');
                    $observacoes .= "\n[SUBCATEGORIAS_IDS: " . json_encode($subcategoriasIds) . "]";
                }
            }
            
            // Preparar dados para solicitação normal
            $dadosSolicitacao = [
                'imobiliaria_id' => $imobiliaria['id'],
                'categoria_id' => $dados['categoria_id'],
                'subcategoria_id' => $dados['subcategoria_id'],
                'status_id' => $statusInicial['id'],
                
                // Dados do locatário
                'locatario_id' => 0, // ID 0 indica que veio de solicitação manual
                'locatario_nome' => $dados['nome_completo'],
                'locatario_cpf' => $cpfLimpo,
                'locatario_telefone' => $dados['whatsapp'],
                'locatario_email' => null,
                
                // Dados do imóvel
                'imovel_endereco' => $dados['endereco'],
                'imovel_numero' => $dados['numero'],
                'imovel_complemento' => $dados['complemento'] ?? null,
                'imovel_bairro' => $dados['bairro'],
                'imovel_cidade' => $dados['cidade'],
                'imovel_estado' => $dados['estado'],
                'imovel_cep' => $dados['cep'],
                
                // Descrição e detalhes
                // Observações agora podem conter a lista de serviços e SUBCATEGORIAS_IDS, quando houver múltiplas
                'descricao_problema' => $dados['descricao_problema'],
                'local_manutencao' => $dados['local_manutencao'] ?? null,
                'tipo_imovel' => $dados['tipo_imovel'] ?? 'RESIDENCIAL',
                'observacoes' => $observacoes,
                'prioridade' => 'NORMAL',
                'numero_contrato' => $dados['numero_contrato'] ?? $cpfEncontrado['numero_contrato'] ?? null,
                
                // Validação Bolsão: 1 = CPF validado na listagem da imobiliária
                'validacao_bolsao' => 1, // Sempre 1 aqui porque só chega aqui se cpfEncontrado = true
                
                // Tipo de Qualificação: Se está no bolsão, vai direto pro kanban como BOLSAO
                'tipo_qualificacao' => 'BOLSAO',
                
                // Horários preferenciais
                'horarios_opcoes' => $horariosJson,
                'datas_opcoes' => $horariosJson
            ];
            
            // Gerar token de acesso para visualização pública (sem login)
            // Este token permite que a pessoa veja o status da solicitação sem precisar fazer login
            $tokenAcesso = bin2hex(random_bytes(32)); // 64 caracteres
            
            // Criar solicitação normal
            try {
            $solicitacaoModel = new \App\Models\Solicitacao();
            $solicitacaoId = $solicitacaoModel->create($dadosSolicitacao);
                
                if (!$solicitacaoId || $solicitacaoId <= 0) {
                    error_log("ERRO [finalizarSolicitacaoManual] - create() retornou ID inválido: " . $solicitacaoId);
                    error_log("Dados tentados: " . json_encode($dadosSolicitacao));
                    $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação. Tente novamente.')));
                    return;
                }
            
            // Garantir que tipo_qualificacao seja 'BOLSAO' se validacao_bolsao = 1 (pode não ter sido salvo se coluna não existia)
            if ($solicitacaoId && $solicitacaoModel->colunaExisteBanco('tipo_qualificacao')) {
                // Verificar se precisa atualizar
                $solicitacaoCriada = $solicitacaoModel->find($solicitacaoId);
                if ($solicitacaoCriada && isset($dadosSolicitacao['validacao_bolsao']) && $dadosSolicitacao['validacao_bolsao'] == 1) {
                    if (empty($solicitacaoCriada['tipo_qualificacao'])) {
                        \App\Core\Database::query("UPDATE solicitacoes SET tipo_qualificacao = 'BOLSAO' WHERE id = ?", [$solicitacaoId]);
                        error_log("DEBUG [finalizarSolicitacaoManual] - Atualizado tipo_qualificacao para BOLSAO na solicitação #{$solicitacaoId}");
                    }
                }
                }
            } catch (\Exception $e) {
                error_log("ERRO [finalizarSolicitacaoManual] ao criar solicitação: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                error_log("Dados tentados: " . json_encode($dadosSolicitacao));
                $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação: ' . $e->getMessage())));
                return;
            }
            
            // Salvar token de acesso na solicitação (se a coluna existir)
            if ($solicitacaoId) {
                try {
                    $sqlToken = "UPDATE solicitacoes SET token_acesso = ? WHERE id = ?";
                    \App\Core\Database::query($sqlToken, [$tokenAcesso, $solicitacaoId]);
                } catch (\Exception $e) {
                    // Se a coluna não existir, apenas logar o erro (não bloquear)
                    error_log('Aviso: Coluna token_acesso não existe na tabela solicitacoes. Execute o script SQL para adicionar.');
                }
                
                // Atualizar descricao_card com informação de validação
                if ($cpfEncontrado && !empty($dadosSolicitacao['validacao_bolsao']) && $dadosSolicitacao['validacao_bolsao'] == 1) {
                    try {
                        $descricaoCard = $dados['descricao_problema'] . "\nValidação: Bolsão";
                        $sqlDescCard = "UPDATE solicitacoes SET descricao_card = ? WHERE id = ?";
                        \App\Core\Database::query($sqlDescCard, [$descricaoCard, $solicitacaoId]);
                    } catch (\Exception $e) {
                        error_log('Aviso: Erro ao atualizar descricao_card: ' . $e->getMessage());
                    }
                }
            }
            
            if ($solicitacaoId) {
                // Salvar fotos na tabela fotos se houver
                if (!empty($dados['fotos']) && is_array($dados['fotos'])) {
                    foreach ($dados['fotos'] as $fotoNome) {
                        $urlArquivo = 'Public/uploads/solicitacoes/' . $fotoNome;
                        $sqlFoto = "INSERT INTO fotos (solicitacao_id, nome_arquivo, url_arquivo, created_at) 
                                    VALUES (?, ?, ?, NOW())";
                        try {
                            \App\Core\Database::query($sqlFoto, [$solicitacaoId, $fotoNome, $urlArquivo]);
                        } catch (\Exception $e) {
                            error_log("Erro ao salvar foto {$fotoNome} para solicitação {$solicitacaoId}: " . $e->getMessage());
                        }
                    }
                }
                
                // Enviar notificação WhatsApp se o CPF está registrado na tabela locatarios_contratos
                // Não precisa verificar token - se o CPF está registrado, envia WhatsApp
                error_log("DEBUG WhatsApp [Solicitação Manual] - CPF encontrado: " . ($cpfEncontrado ? 'SIM' : 'NÃO'));
                
                if ($cpfEncontrado) {
                    error_log("✅ DEBUG WhatsApp [Solicitação Manual] - CPF registrado encontrado! Enviando WhatsApp para solicitação ID: {$solicitacaoId}");
                    try {
                        $this->enviarNotificacaoWhatsApp($solicitacaoId, 'Nova Solicitação');
                        error_log("✅ DEBUG WhatsApp [Solicitação Manual] - Método enviarNotificacaoWhatsApp chamado com sucesso para solicitação ID: {$solicitacaoId}");
                    } catch (\Exception $e) {
                        error_log('❌ Erro ao enviar WhatsApp para solicitação normal [ID:' . $solicitacaoId . ']: ' . $e->getMessage());
                        error_log('❌ Stack trace: ' . $e->getTraceAsString());
                    }
                } else {
                    error_log("❌ DEBUG WhatsApp [Solicitação Manual] - CPF não encontrado na tabela locatarios_contratos. WhatsApp NÃO será enviado.");
                }
                
                // Limpar sessão
                unset($_SESSION['solicitacao_manual']);
                if ($modoToken) {
                    unset($_SESSION['solicitacao_manual_token']);
                }
                
                // Redirecionar com mensagem de sucesso
                if ($modoToken) {
                    $this->redirect(url($instancia . '?success=' . urlencode('Solicitação atualizada com sucesso! ID: #' . $solicitacaoId)));
                } else {
                    // Verificar se é emergencial fora do horário
                    if ($isEmergencialForaHorarioManual) {
                        $this->redirect(url($instancia . '/solicitacao-manual-emergencial/' . $solicitacaoId));
                    } else {
                        $this->redirect(url($instancia . '/solicitacao-manual-sucesso/' . $solicitacaoId));
                    }
                }
            } else {
                $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação. Tente novamente.')));
            }
        } else {
            // CPF não encontrado OU validacao_utilizacao = 0 (excedido) - criar como solicitação manual
            // Se estiver em modo token, atualizar a solicitação manual existente
            if ($modoToken && !empty($token)) {
                $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                $solicitacaoManual = $solicitacaoManualModel->findByToken($token);
                
                if ($solicitacaoManual) {
                    // Atualizar solicitação manual existente
                    $dadosParaAtualizar = [
                        'nome_completo' => $dados['nome_completo'],
                        'cpf' => $cpfLimpo,
                        'whatsapp' => $dados['whatsapp'],
                        'tipo_imovel' => $dados['tipo_imovel'],
                        'subtipo_imovel' => $dados['subtipo_imovel'] ?? null,
                        'cep' => $dados['cep'],
                        'endereco' => $dados['endereco'],
                        'numero' => $dados['numero'],
                        'complemento' => $dados['complemento'] ?? null,
                        'bairro' => $dados['bairro'],
                        'cidade' => $dados['cidade'],
                        'estado' => $dados['estado'],
                        'categoria_id' => $dados['categoria_id'],
                        'subcategoria_id' => $dados['subcategoria_id'],
                        'numero_contrato' => $dados['numero_contrato'] ?? null,
                        'local_manutencao' => $dados['local_manutencao'] ?? null,
                        'descricao_problema' => $dados['descricao_problema'],
                        'horarios_preferenciais' => $dados['horarios_preferenciais'] ?? [],
                        'fotos' => $dados['fotos'] ?? [],
                        'termos_aceitos' => $dados['termos_aceitos'],
                        'lgpd_aceite' => $dados['lgpd_aceite'] ?? null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $solicitacaoManualModel->update($solicitacaoManual['id'], $dadosParaAtualizar);
                    
                    // Limpar sessão
                    unset($_SESSION['solicitacao_manual']);
                    unset($_SESSION['solicitacao_manual_token']);
                    
                    $this->redirect(url($instancia . '?success=' . urlencode('Solicitação atualizada com sucesso! ID: #' . $solicitacaoManual['id'])));
                    return;
                }
            }
            
            // Criar como solicitação manual (vai para admin)
            // ===============================================
            // Aqui precisamos preservar TODAS as subcategorias selecionadas (quando houver múltiplas)
            // para que apareçam corretamente como "Tipos/Serviços" tanto na tela de Solicitação Manual
            // quanto depois que forem migradas para o Kanban.
            
            // Montar observações com múltiplas subcategorias, quando existir seleção múltipla
            // NOTA: local_manutencao não deve ser incluído em observacoes, ele tem seu próprio campo no banco
            $observacoesManual = "Tipo: " . ($dados['tipo_imovel'] ?? 'RESIDENCIAL');
            
            if ($temMultiplasCategorias && !empty($categoriasSelecionadas) && is_array($categoriasSelecionadas)) {
                $subcategoriaModel = new \App\Models\Subcategoria();
                $nomesSubcategorias = [];
                
                foreach ($categoriasSelecionadas as $cat) {
                    if (!empty($cat['subcategoria_id'])) {
                        $subcategoria = $subcategoriaModel->find((int)$cat['subcategoria_id']);
                        if ($subcategoria && !empty($subcategoria['nome'])) {
                            $nomesSubcategorias[] = $subcategoria['nome'];
                        }
                    }
                }
                
                if (count($nomesSubcategorias) > 1) {
                    // Mesmo formato usado para solicitações normais, para reaproveitar o helper getSubcategorias
                    $observacoesManual .= "\n\nServiços solicitados (" . count($nomesSubcategorias) . "):\n";
                    foreach ($nomesSubcategorias as $index => $nome) {
                        $observacoesManual .= ($index + 1) . ". " . $nome . "\n";
                    }
                    
                    // Armazenar IDs das subcategorias em JSON para referência futura
                    $subcategoriasIds = array_column($categoriasSelecionadas, 'subcategoria_id');
                    $observacoesManual .= "\n[SUBCATEGORIAS_IDS: " . json_encode($subcategoriasIds) . "]";
                }
            }
            
            $dadosParaSalvar = [
                'imobiliaria_id' => $imobiliaria['id'],
                'nome_completo' => $dados['nome_completo'],
                'cpf' => $cpfLimpo,
                'whatsapp' => $dados['whatsapp'],
                'tipo_imovel' => $dados['tipo_imovel'],
                'subtipo_imovel' => $dados['subtipo_imovel'] ?? null,
                'cep' => $dados['cep'],
                'endereco' => $dados['endereco'],
                'numero' => $dados['numero'],
                'complemento' => $dados['complemento'] ?? null,
                'bairro' => $dados['bairro'],
                'cidade' => $dados['cidade'],
                'estado' => $dados['estado'],
                'categoria_id' => $dados['categoria_id'],
                'subcategoria_id' => $dados['subcategoria_id'],
                'numero_contrato' => $dados['numero_contrato'] ?? null,
                'local_manutencao' => $dados['local_manutencao'] ?? null,
                'descricao_problema' => $dados['descricao_problema'],
                // Observações agora podem conter a lista de serviços e SUBCATEGORIAS_IDS, quando houver múltiplas
                'observacoes' => $observacoesManual,
                'validacao_bolsao' => $cpfEncontrado ? 1 : 0, // 1 se CPF está no bolsão, 0 se não está
                // Tipo de Qualificação: 
                // - Se validacao_utilizacao = 0 (excedido), marcar como NULL (aguarda admin decidir)
                // - Se CPF não está no bolsão, marcar como NULL (aguarda admin escolher CORTESIA ou NAO_QUALIFICADA)
                'tipo_qualificacao' => null,
                'horarios_preferenciais' => $dados['horarios_preferenciais'] ?? [],
                'fotos' => $dados['fotos'] ?? [],
                'termos_aceitos' => $dados['termos_aceitos'],
                'lgpd_aceite' => $dados['lgpd_aceite'] ?? null
            ];
            
            // Remover lógica de token na criação - não é mais necessária
            
            // Criar solicitação manual
                    $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                    
                    // Verificar quantidade de solicitações por CPF antes de criar
                    $verificacaoQuantidade = $solicitacaoManualModel->verificarQuantidadePorCPF(
                        $dadosParaSalvar['cpf'],
                        $imobiliaria['id'],
                        $dadosParaSalvar['categoria_id'] ?? null
                    );
                    
                    // Log da verificação
                    error_log("DEBUG [Solicitação Manual] - CPF: {$cpfLimpo}, Quantidade total: {$verificacaoQuantidade['quantidade_total']}, Últimos 12 meses: {$verificacaoQuantidade['quantidade_12_meses']}");
                    
                    // Verificar se a coluna observacoes existe antes de tentar salvar
                    $colunaObservacoesExiste = $solicitacaoManualModel->colunaExisteBanco('observacoes');
                    if (!$colunaObservacoesExiste && isset($dadosParaSalvar['observacoes'])) {
                        // Se a coluna não existir, remover observacoes dos dados para salvar
                        // As múltiplas subcategorias serão preservadas quando a solicitação for migrada
                        // através do campo descricao_problema ou outro campo disponível
                        unset($dadosParaSalvar['observacoes']);
                    }
                    
                    // Criar solicitação manual
                    try {
                    $id = $solicitacaoManualModel->create($dadosParaSalvar);
                        
                        if (!$id || $id <= 0) {
                            error_log("ERRO [finalizarSolicitacaoManual - solicitação manual] - create() retornou ID inválido: " . $id);
                            error_log("Dados tentados: " . json_encode($dadosParaSalvar));
                            $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação. Tente novamente.')));
                            return;
                        }
                    } catch (\Exception $e) {
                        error_log("ERRO [finalizarSolicitacaoManual - solicitação manual] ao criar: " . $e->getMessage());
                        error_log("Stack trace: " . $e->getTraceAsString());
                        error_log("Dados tentados: " . json_encode($dadosParaSalvar));
                        $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação: ' . $e->getMessage())));
                        return;
                    }
                    
                    // Nota: Contagem de CPF será atualizada apenas quando a solicitação for CONCLUÍDA
            
            if ($id) {
                // Enviar notificação WhatsApp se o CPF está registrado
                // Não precisa verificar token - se o CPF está registrado, envia WhatsApp
                if ($cpfEncontrado) {
                    error_log("✅ DEBUG WhatsApp [Solicitação Manual] - CPF registrado encontrado! Enviando WhatsApp para solicitação ID: {$id}");
                    try {
                        $whatsappService = new \App\Services\WhatsAppService();
                        $whatsappService->sendMessageManual($id, 'Nova Solicitação');
                    } catch (\Exception $e) {
                        error_log('❌ Erro ao enviar WhatsApp para solicitação manual [ID:' . $id . ']: ' . $e->getMessage());
                    }
                } else {
                    error_log("❌ DEBUG WhatsApp [Solicitação Manual] - CPF não encontrado. WhatsApp NÃO será enviado.");
                }
                
                // Limpar sessão
                unset($_SESSION['solicitacao_manual']);
                if ($modoToken) {
                    unset($_SESSION['solicitacao_manual_token']);
                }
                
                // Redirecionar - verificar se é emergencial fora do horário
                if ($isEmergencialForaHorarioManual) {
                    $this->redirect(url($instancia . '/solicitacao-manual-emergencial/' . $id));
                } else {
                    $this->redirect(url($instancia . '/solicitacao-manual-sucesso/' . $id));
                }
            } else {
                $this->redirect($urlBase('/etapa/5?error=' . urlencode('Erro ao salvar solicitação. Tente novamente.')));
            }
        }
    }
    
    /**
     * Solicitação Manual com Token - Acesso público sem login
     */
    public function solicitacaoManualComToken(string $token): void
    {
        // Validar token e buscar solicitação manual
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $solicitacaoManual = $solicitacaoManualModel->findByToken($token);
        
        if (!$solicitacaoManual) {
            $this->view('errors.404', [
                'message' => 'Link inválido ou expirado'
            ]);
            return;
        }
        
        // Buscar imobiliária
        $imobiliariaModel = new \App\Models\Imobiliaria();
        $imobiliaria = $imobiliariaModel->find($solicitacaoManual['imobiliaria_id']);
        
        if (!$imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Imobiliária não encontrada'
            ]);
            return;
        }
        
        // Carregar dados da solicitação manual na sessão (se ainda não estiver)
        if (empty($_SESSION['solicitacao_manual_token']) || $_SESSION['solicitacao_manual_token'] !== $token) {
            $_SESSION['solicitacao_manual_token'] = $token;
            $_SESSION['solicitacao_manual'] = [
                'nome_completo' => $solicitacaoManual['nome_completo'],
                'cpf' => $solicitacaoManual['cpf'],
                'whatsapp' => $solicitacaoManual['whatsapp'],
                'tipo_imovel' => $solicitacaoManual['tipo_imovel'],
                'subtipo_imovel' => $solicitacaoManual['subtipo_imovel'],
                'cep' => $solicitacaoManual['cep'],
                'endereco' => $solicitacaoManual['endereco'],
                'numero' => $solicitacaoManual['numero'],
                'complemento' => $solicitacaoManual['complemento'],
                'bairro' => $solicitacaoManual['bairro'],
                'cidade' => $solicitacaoManual['cidade'],
                'estado' => $solicitacaoManual['estado'],
                'numero_contrato' => $solicitacaoManual['numero_contrato'],
                'categoria_id' => $solicitacaoManual['categoria_id'],
                'subcategoria_id' => $solicitacaoManual['subcategoria_id'],
                'descricao_problema' => $solicitacaoManual['descricao_problema'],
                'horarios_preferenciais' => json_decode($solicitacaoManual['horarios_preferenciais'] ?? '[]', true),
                'fotos' => json_decode($solicitacaoManual['fotos'] ?? '[]', true),
                'termos_aceitos' => $solicitacaoManual['termos_aceitos']
            ];
        }
        
        // Buscar categorias
        $categoriaModel = new \App\Models\Categoria();
        $subcategoriaModel = new \App\Models\Subcategoria();
        $tipoImovel = $_SESSION['solicitacao_manual']['tipo_imovel'] ?? 'RESIDENCIAL';
        
        if ($tipoImovel === 'RESIDENCIAL') {
            $categorias = $categoriaModel->getHierarquicas('RESIDENCIAL');
        } elseif ($tipoImovel === 'COMERCIAL') {
            $categorias = $categoriaModel->getHierarquicas('COMERCIAL');
        } else {
            $categorias = $categoriaModel->getHierarquicas();
        }
        
        $subcategorias = $subcategoriaModel->getAtivas();
        
        // Organizar subcategorias por categoria
        foreach ($categorias as $key => $categoria) {
            $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                return $sub['categoria_id'] == $categoria['id'];
            }));
            
            if (!empty($categoria['filhas'])) {
                foreach ($categoria['filhas'] as $filhaKey => $categoriaFilha) {
                    $categorias[$key]['filhas'][$filhaKey]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoriaFilha) {
                        return $sub['categoria_id'] == $categoriaFilha['id'];
                    }));
                }
            }
        }
        
        $this->view('locatario.solicitacao-manual', [
            'imobiliaria' => $imobiliaria,
            'instancia' => $imobiliaria['instancia'],
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'etapa' => 1,
            'dados' => $_SESSION['solicitacao_manual'],
            'token' => $token,
            'modo_token' => true
        ]);
    }
    
    /**
     * Processar etapa específica da solicitação manual com token
     */
    public function solicitacaoManualComTokenEtapa(string $token, int $etapa): void
    {
        // Validar token
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $solicitacaoManual = $solicitacaoManualModel->findByToken($token);
        
        if (!$solicitacaoManual) {
            $this->view('errors.404', [
                'message' => 'Link inválido ou expirado'
            ]);
            return;
        }
        
        // Verificar se o token na sessão corresponde
        if (empty($_SESSION['solicitacao_manual_token']) || $_SESSION['solicitacao_manual_token'] !== $token) {
            $_SESSION['solicitacao_manual_token'] = $token;
        }
        
        // GET: exibir a etapa
        if (!$this->isPost()) {
            // Buscar imobiliária
            $imobiliariaModel = new \App\Models\Imobiliaria();
            $imobiliaria = $imobiliariaModel->find($solicitacaoManual['imobiliaria_id']);
            
            if (!$imobiliaria) {
                $this->view('errors.404', ['message' => 'Imobiliária não encontrada']);
                return;
            }
            
            // Buscar categorias
            $categoriaModel = new \App\Models\Categoria();
            $subcategoriaModel = new \App\Models\Subcategoria();
            $dados = $_SESSION['solicitacao_manual'] ?? [];
            $tipoImovel = $dados['tipo_imovel'] ?? 'RESIDENCIAL';
            
            if ($etapa >= 2 && !empty($tipoImovel)) {
                if ($tipoImovel === 'RESIDENCIAL') {
                    $categorias = $categoriaModel->getHierarquicas('RESIDENCIAL');
                } elseif ($tipoImovel === 'COMERCIAL') {
                    $categorias = $categoriaModel->getHierarquicas('COMERCIAL');
                } else {
                    $categorias = $categoriaModel->getHierarquicas();
                }
            } else {
                $categorias = $categoriaModel->getHierarquicas();
            }
            
            $subcategorias = $subcategoriaModel->getAtivas();
            
            // Organizar subcategorias
            foreach ($categorias as $key => $categoria) {
                $categorias[$key]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoria) {
                    return $sub['categoria_id'] == $categoria['id'];
                }));
                
                if (!empty($categoria['filhas'])) {
                    foreach ($categoria['filhas'] as $filhaKey => $categoriaFilha) {
                        $categorias[$key]['filhas'][$filhaKey]['subcategorias'] = array_values(array_filter($subcategorias, function($sub) use ($categoriaFilha) {
                            return $sub['categoria_id'] == $categoriaFilha['id'];
                        }));
                    }
                }
            }
            
            $this->view('locatario.solicitacao-manual', [
                'imobiliaria' => $imobiliaria,
                'instancia' => $imobiliaria['instancia'],
                'categorias' => $categorias,
                'subcategorias' => $subcategorias,
                'etapa' => $etapa,
                'dados' => $dados,
                'token' => $token,
                'modo_token' => true
            ]);
            return;
        }
        
        // POST: processar dados da etapa
        $this->processarSolicitacaoManual($etapa, $token);
    }
    
    /**
     * Buscar dados do locatário por CPF com token
     */
    public function buscarDadosPorCPFComToken(string $token): void
    {
        // Validar token
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $solicitacaoManual = $solicitacaoManualModel->findByToken($token);
        
        if (!$solicitacaoManual) {
            $this->json(['success' => false, 'error' => 'Token inválido'], 404);
            return;
        }
        
        // Usar o método existente, mas com a instância da solicitação manual
        $imobiliariaModel = new \App\Models\Imobiliaria();
        $imobiliaria = $imobiliariaModel->find($solicitacaoManual['imobiliaria_id']);
        
        if (!$imobiliaria) {
            $this->json(['success' => false, 'error' => 'Imobiliária não encontrada'], 404);
            return;
        }
        
        // Chamar o método existente com a instância
        $this->buscarDadosPorCPF($imobiliaria['instancia']);
    }
    
    /**
     * Buscar dados do locatário por CPF (para preenchimento automático)
     */
    public function buscarDadosPorCPF(string $instancia): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }

        $cpf = trim($this->input('cpf'));
        
        if (empty($cpf)) {
            $this->json(['success' => false, 'error' => 'CPF não informado'], 400);
            return;
        }

        // Buscar imobiliária
        $imobiliaria = KsiApiService::getImobiliariaByInstancia($instancia);
        
        if (!$imobiliaria) {
            $this->json(['success' => false, 'error' => 'Imobiliária não encontrada'], 404);
            return;
        }

        // Limpar CPF (remover pontos, traços, espaços)
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        // Validar CPF (11 dígitos) ou CNPJ (14 dígitos)
        if (strlen($cpfLimpo) !== 11 && strlen($cpfLimpo) !== 14) {
            $this->json(['success' => false, 'error' => 'CPF/CNPJ inválido'], 400);
            return;
        }

        // Buscar na tabela locatarios_contratos
        // Comparar removendo formatação de ambos os lados para garantir que funcione
        // Removido LIMIT 1 para buscar TODOS os endereços do CPF
        $sql = "SELECT * FROM locatarios_contratos 
                WHERE imobiliaria_id = ? 
                AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ? 
                ORDER BY id ASC";
        $todosEnderecos = \App\Core\Database::fetchAll($sql, [$imobiliaria['id'], $cpfLimpo]);

        if (!empty($todosEnderecos)) {
            // CPF encontrado = bolsão validado (validacao_bolsao = 1)
            
            // Função helper para formatar um endereço
            $formatarEndereco = function($endereco) {
                return [
                    'id' => $endereco['id'] ?? null,
                    'nome_completo' => $endereco['inquilino_nome'] ?? '',
                    'tipo_imovel' => $endereco['tipo_imovel'] ?? 'RESIDENCIAL',
                    'cep' => $endereco['cep'] ?? '',
                    'endereco' => $endereco['endereco'] ?? '',
                    'numero' => $endereco['numero'] ?? '',
                    'complemento' => $endereco['complemento'] ?? '',
                    'bairro' => $endereco['bairro'] ?? '',
                    'cidade' => $endereco['cidade'] ?? '',
                    'estado' => $endereco['estado'] ?? '',
                    'unidade' => $endereco['unidade'] ?? '',
                    'numero_contrato' => $endereco['numero_contrato'] ?? ''
                ];
            };
            
            // Se houver apenas 1 endereço, retornar no formato antigo (compatibilidade)
            if (count($todosEnderecos) === 1) {
                $dados = $formatarEndereco($todosEnderecos[0]);
            $this->json([
                'success' => true,
                    'validacao_bolsao' => 1,
                    'dados' => $dados,
                    'tem_multiplos_enderecos' => false
                ]);
            } else {
                // Se houver múltiplos endereços, retornar array
                $enderecosFormatados = array_map($formatarEndereco, $todosEnderecos);
                $this->json([
                    'success' => true,
                    'validacao_bolsao' => 1,
                    'tem_multiplos_enderecos' => true,
                    'enderecos' => $enderecosFormatados,
                    'total' => count($enderecosFormatados)
            ]);
            }
        } else {
            // CPF não encontrado = bolsão recusado (validacao_bolsao = 0)
            $this->json([
                'success' => false,
                'validacao_bolsao' => 0, // CPF não encontrado = recusado
                'error' => 'CPF não encontrado na base de dados'
            ], 404);
        }
    }
    
    /**
     * Exibir tela de emergência com telefone 0800
     */
    public function solicitacaoEmergencial(string $instancia, int $solicitacaoId): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        // Buscar solicitação
        $solicitacao = $this->solicitacaoModel->getDetalhes($solicitacaoId);
        
        if (!$solicitacao) {
            $this->redirect(url($instancia . '/dashboard?error=' . urlencode('Solicitação não encontrada')));
            return;
        }
        
        // Verificar se a solicitação pertence ao locatário
        $locatarioIdComparar = $locatario['codigo_locatario'] ?? $locatario['id'];
        if ($solicitacao['locatario_id'] != $locatarioIdComparar) {
            $this->redirect(url($instancia . '/dashboard?error=' . urlencode('Solicitação não encontrada')));
            return;
        }
        
        // Buscar telefone de emergência
        $telefoneEmergenciaModel = new \App\Models\TelefoneEmergencia();
        $telefoneEmergencia = $telefoneEmergenciaModel->getPrincipal();
        
        $this->view('locatario.solicitacao-emergencial', [
            'locatario' => $locatario,
            'solicitacao' => $solicitacao,
            'telefoneEmergencia' => $telefoneEmergencia,
            'instancia' => $instancia
        ]);
    }
    
    /**
     * Tela de sucesso após finalizar nova solicitação
     * GET /{instancia}/solicitacao-sucesso/{id}
     */
    public function solicitacaoSucesso(string $instancia, int $solicitacaoId): void
    {
        $this->requireLocatarioAuth();
        
        $locatario = $_SESSION['locatario'];
        
        $this->view('locatario.solicitacao-sucesso', [
            'locatario' => $locatario,
            'solicitacao_id' => $solicitacaoId,
            'instancia' => $instancia
        ]);
    }
    
    /**
     * Tela de sucesso após finalizar solicitação manual
     * GET /{instancia}/solicitacao-manual-sucesso/{id}
     */
    public function solicitacaoManualSucesso(string $instancia, int $solicitacaoId): void
    {
        $locatario = $_SESSION['locatario'] ?? ['instancia' => $instancia];
        
        $this->view('locatario.solicitacao-manual-sucesso', [
            'locatario' => $locatario,
            'solicitacao_id' => $solicitacaoId,
            'instancia' => $instancia
        ]);
    }
    
    /**
     * Tela de emergência após finalizar solicitação manual (emergencial fora do horário)
     * GET /{instancia}/solicitacao-manual-emergencial/{id}
     */
    public function solicitacaoManualEmergencial(string $instancia, int $solicitacaoId): void
    {
        $locatario = $_SESSION['locatario'] ?? ['instancia' => $instancia];
        
        // Buscar telefone de emergência
        $telefoneEmergenciaModel = new \App\Models\TelefoneEmergencia();
        $telefoneEmergencia = $telefoneEmergenciaModel->getPrincipal();
        
        $this->view('locatario.solicitacao-manual-emergencial', [
            'locatario' => $locatario,
            'solicitacao_id' => $solicitacaoId,
            'telefone_emergencia' => $telefoneEmergencia,
            'instancia' => $instancia
        ]);
    }
    
    /**
     * Executar ação na solicitação (concluir, cancelar, etc)
     * POST /{instancia}/solicitacoes/{id}/acao
     */
    public function executarAcao(string $instancia, int $id): void
    {
        $this->requireLocatarioAuth();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        
        $acao = $this->input('acao');
        $solicitacao = $this->solicitacaoModel->find($id);
        
        if (!$solicitacao) {
            $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
            return;
        }
        
        // Verificar se a solicitação pertence ao locatário logado
        $locatario = $_SESSION['locatario'];
        if ($solicitacao['locatario_id'] != $locatario['id']) {
            $this->json(['success' => false, 'message' => 'Você não tem permissão para executar esta ação'], 403);
            return;
        }
        
        try {
            $statusModel = new \App\Models\Status();
            $observacaoInput = $this->input('observacao', '');
            $valorReembolso = $this->input('valor_reembolso');
            
            // Processar upload de anexos
            $anexosSalvos = [];
            if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                $anexosSalvos = $this->processarUploadAnexos($id, $_FILES['anexos']);
            }
            
            $observacaoBase = $solicitacao['observacoes'] ?? '';
            $timestamp = date('d/m/Y H:i:s');
            
            switch ($acao) {
                case 'concluido':
                    $statusConcluido = $statusModel->findByNome('Concluído');
                    if (!$statusConcluido) {
                        $statusConcluido = $statusModel->findByNome('Concluido');
                    }
                    if ($statusConcluido) {
                        $observacaoFinal = $observacaoBase;
                        if (!empty($observacaoInput)) {
                            $observacaoFinal .= "\n\n[Concluído em {$timestamp}] Observação do locatário: {$observacaoInput}";
                        } else {
                            $observacaoFinal .= "\n\n[Concluído em {$timestamp}] Serviço concluído - confirmado pelo locatário";
                        }
                        if (!empty($anexosSalvos)) {
                            $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                        }
                        
                        $this->solicitacaoModel->update($id, [
                            'status_id' => $statusConcluido['id'],
                            'observacoes' => $observacaoFinal
                        ]);
                        
                        // Registrar no histórico que foi feito pelo LOCATÁRIO
                        $this->registrarHistoricoLocatario($id, $statusConcluido['id'], 'Solicitação marcada como concluída pelo locatário');
                        
                        // Atualizar contagem de CPF quando a solicitação é concluída
                        try {
                            $cpfLocatario = $solicitacao['locatario_cpf'] ?? null;
                            $imobiliariaId = $solicitacao['imobiliaria_id'] ?? null;
                            $categoriaId = $solicitacao['categoria_id'] ?? null;
                            
                            if ($cpfLocatario && $imobiliariaId) {
                                $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                                $solicitacaoManualModel->atualizarContagemCPF(
                                    preg_replace('/[^0-9]/', '', $cpfLocatario),
                                    $imobiliariaId,
                                    $categoriaId
                                );
                            }
                        } catch (\Exception $e) {
                            error_log("Erro ao atualizar contagem de CPF: " . $e->getMessage());
                        }
                        
                        $this->json(['success' => true, 'message' => 'Solicitação marcada como concluída com sucesso!']);
                    } else {
                        $this->json(['success' => false, 'message' => 'Status "Concluído" não encontrado no sistema']);
                    }
                    break;
                    
                case 'cancelado':
                    if (empty($observacaoInput)) {
                        $this->json(['success' => false, 'message' => 'Observação é obrigatória para cancelamento'], 400);
                        return;
                    }
                    
                    // Buscar status "Cancelado"
                    $statusCancelado = $statusModel->findByNome('Cancelado');
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelada');
                    }
                    
                    if (!$statusCancelado) {
                        $this->json(['success' => false, 'message' => 'Status "Cancelado" não encontrado no sistema']);
                        return;
                    }
                    
                    // Buscar categoria "Cancelado"
                    $categoriaModel = new \App\Models\Categoria();
                    $sqlCategoria = "SELECT * FROM categorias WHERE nome = 'Cancelado' AND status = 'ATIVA' LIMIT 1";
                    $categoriaCancelado = \App\Core\Database::fetch($sqlCategoria);
                    
                    // Se não encontrar, buscar qualquer categoria com "Cancelado" no nome
                    if (!$categoriaCancelado) {
                        $sqlCategoria = "SELECT * FROM categorias WHERE nome LIKE '%Cancelado%' AND status = 'ATIVA' LIMIT 1";
                        $categoriaCancelado = \App\Core\Database::fetch($sqlCategoria);
                    }
                    
                    // Buscar condição "Cancelado pelo Locatário"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicaoCancelado = $condicaoModel->findByNome('Cancelado pelo Locatário');
                    
                    // Se não encontrar, buscar qualquer condição com "Cancelado" no nome
                    if (!$condicaoCancelado) {
                        $sqlCondicao = "SELECT * FROM condicoes WHERE nome LIKE '%Cancelado%' AND status = 'ATIVO' LIMIT 1";
                        $condicaoCancelado = \App\Core\Database::fetch($sqlCondicao);
                    }
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Cancelado em {$timestamp}] Motivo: {$observacaoInput}";
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'status_id' => $statusCancelado['id'],
                        'observacoes' => $observacaoFinal,
                        'motivo_cancelamento' => $observacaoInput
                    ];
                    
                    // Adicionar categoria se encontrada
                    if ($categoriaCancelado) {
                        $updateData['categoria_id'] = $categoriaCancelado['id'];
                    }
                    
                    // Adicionar condição se encontrada
                    if ($condicaoCancelado) {
                        $updateData['condicao_id'] = $condicaoCancelado['id'];
                    }
                    
                    $this->solicitacaoModel->update($id, $updateData);
                    
                    // Registrar no histórico
                    $sqlHistorico = "
                        INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ";
                    \App\Core\Database::query($sqlHistorico, [
                        $id,
                        $statusCancelado['id'],
                        null,
                        'Solicitação cancelada pelo locatário. Motivo: ' . $observacaoInput
                    ]);
                    
                    $this->json(['success' => true, 'message' => 'Solicitação cancelada com sucesso!']);
                    break;
                    
                case 'servico_nao_realizado':
                    // Mesma lógica do TokenController: volta para "Nova Solicitação" com condição "Prestador não compareceu"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('Prestador não compareceu');
                    if (!$condicao) {
                        // Criar condição se não existir
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Prestador não compareceu',
                            'cor' => '#dc2626',
                            'icone' => 'fa-times-circle',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    $statusModel = new \App\Models\Status();
                    $statusNova = $statusModel->findByNome('Nova Solicitação');
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Serviço não realizado em {$timestamp}]";
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= " Observação: {$observacaoInput}";
                    } else {
                        $observacaoFinal .= " Prestador não compareceu no serviço agendado.";
                    }
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'observacoes' => $observacaoFinal
                    ];
                    
                    // Adicionar status e condição se encontrados
                    if ($statusNova) {
                        $updateData['status_id'] = $statusNova['id'];
                    }
                    if ($condicaoId) {
                        $updateData['condicao_id'] = $condicaoId;
                    }
                    
                    $this->solicitacaoModel->update($id, $updateData);
                    $this->json(['success' => true, 'message' => 'Informação registrada: serviço não realizado. Solicitação reaberta.']);
                    break;
                    
                case 'comprar_pecas':
                    // Mesma lógica do TokenController: status "Pendente Cliente", condição "Comprar peças", prazo de 10 dias
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('Comprar peças');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Comprar peças',
                            'cor' => '#f59e0b',
                            'icone' => 'fa-shopping-cart',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    $statusModel = new \App\Models\Status();
                    // Buscar "Pendente Cliente" primeiro, depois "Pendente", depois "Aguardando"
                    $statusPendente = $statusModel->findByNome('Pendente Cliente');
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('PENDENTE CLIENTE');
                    }
                    if (!$statusPendente) {
                    $statusPendente = $statusModel->findByNome('Pendente');
                    }
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('Aguardando');
                    }
                    
                    $dataLimite = date('Y-m-d', strtotime('+10 days'));
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Comprar peças em {$timestamp}]";
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= " Observação: {$observacaoInput}";
                    }
                    $observacaoFinal .= "\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite));
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    // Preparar dados de atualização com verificação de colunas existentes
                    $updateData = [
                        'condicao_id' => $condicaoId,
                        'observacoes' => $observacaoFinal
                    ];
                    
                    if ($statusPendente) {
                        $updateData['status_id'] = $statusPendente['id'];
                    }
                    
                    // Verificar se colunas existem antes de adicionar
                    if ($this->solicitacaoModel->colunaExisteBanco('data_limite_peca')) {
                        $updateData['data_limite_peca'] = $dataLimite;
                    } else {
                        $updateData['observacoes'] .= " (Data limite: " . date('d/m/Y', strtotime($dataLimite)) . ")";
                    }
                    
                    if ($this->solicitacaoModel->colunaExisteBanco('data_ultimo_lembrete')) {
                        $updateData['data_ultimo_lembrete'] = null;
                    }
                    if ($this->solicitacaoModel->colunaExisteBanco('lembretes_enviados')) {
                        $updateData['lembretes_enviados'] = 0;
                    }
                    if ($this->solicitacaoModel->colunaExisteBanco('data_limite_cancelamento')) {
                        $updateData['data_limite_cancelamento'] = $dataLimite;
                    }
                    
                    try {
                        $this->solicitacaoModel->update($id, $updateData);
                        
                        // Registrar no histórico com observações incluindo anexos
                        if ($statusPendente) {
                            // Criar observação resumida para o histórico
                            $observacaoHistorico = "[Comprar peças em {$timestamp}]";
                            if (!empty($observacaoInput)) {
                                $observacaoHistorico .= " Observação: {$observacaoInput}";
                            }
                            $observacaoHistorico .= "\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite));
                            if (!empty($anexosSalvos)) {
                                $observacaoHistorico .= "\nAnexos: " . implode(', ', $anexosSalvos);
                            }
                            
                            $this->registrarHistoricoLocatario($id, $statusPendente['id'], $observacaoHistorico);
                        }
                        
                        $this->json(['success' => true, 'message' => 'Informação registrada: necessário comprar peças. Prazo de 10 dias definido.']);
                    } catch (\Exception $e) {
                        // Se alguma coluna opcional não existir, remover e tentar novamente
                        $mensagem = $e->getMessage();
                        $optionalColumns = [
                            'data_limite_peca',
                            'data_ultimo_lembrete',
                            'lembretes_enviados',
                            'data_limite_cancelamento'
                        ];
                        $alterado = false;
                        foreach ($optionalColumns as $coluna) {
                            if (strpos($mensagem, $coluna) !== false && isset($updateData[$coluna])) {
                                unset($updateData[$coluna]);
                                $alterado = true;
                            }
                        }
                        
                        if ($alterado) {
                            error_log('Aviso: removendo colunas opcionais inexistentes ao salvar "Comprar peças".');
                            $this->solicitacaoModel->update($id, $updateData);
                            
                            // Registrar no histórico mesmo em caso de erro com colunas opcionais
                            if ($statusPendente) {
                                $observacaoHistorico = "[Comprar peças em {$timestamp}]";
                                if (!empty($observacaoInput)) {
                                    $observacaoHistorico .= " Observação: {$observacaoInput}";
                                }
                                $observacaoHistorico .= "\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite));
                                if (!empty($anexosSalvos)) {
                                    $observacaoHistorico .= "\nAnexos: " . implode(', ', $anexosSalvos);
                                }
                                
                                $this->registrarHistoricoLocatario($id, $statusPendente['id'], $observacaoHistorico);
                            }
                            
                            $this->json(['success' => true, 'message' => 'Informação registrada: necessário comprar peças.']);
                        } else {
                            throw $e;
                        }
                    }
                    break;
                    
                case 'reembolso':
                    if (empty($observacaoInput)) {
                        $this->json(['success' => false, 'message' => 'Justificativa é obrigatória para reembolso'], 400);
                        return;
                    }
                    if (empty($valorReembolso) || $valorReembolso <= 0) {
                        $this->json(['success' => false, 'message' => 'Valor do reembolso é obrigatório'], 400);
                        return;
                    }
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Reembolso solicitado em {$timestamp}]";
                    $observacaoFinal .= "\nJustificativa: {$observacaoInput}";
                    $observacaoFinal .= "\nValor solicitado: R$ " . number_format($valorReembolso, 2, ',', '.');
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $this->solicitacaoModel->update($id, [
                        'observacoes' => $observacaoFinal,
                        'precisa_reembolso' => 1,
                        'valor_reembolso' => floatval($valorReembolso)
                    ]);
                    $this->json(['success' => true, 'message' => 'Solicitação de reembolso registrada com sucesso!']);
                    break;
                    
                case 'ausente':
                    // Mesma lógica do TokenController: status "Cancelado", condição "Locatário se ausentou"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('Locatário se ausentou');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Locatário se ausentou',
                            'cor' => '#6366f1',
                            'icone' => 'fa-user-times',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    // Buscar status "Cancelado" (tenta várias variações)
                    $statusCancelado = $statusModel->findByNome('Cancelado');
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelada');
                    }
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelado pelo Locatário');
                    }
                    if (!$statusCancelado) {
                        $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelad%' OR nome LIKE '%Cancel%') AND status = 'ATIVO' LIMIT 1";
                        $statusCancelado = \App\Core\Database::fetch($sql);
                    }
                    
                    $observacaoFinal = $observacaoBase;
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= "\n\n[Precisei me ausentar em {$timestamp}] Observação: {$observacaoInput}";
                    } else {
                        $observacaoFinal .= "\n\n[Precisei me ausentar em {$timestamp}] Locatário se ausentou no horário agendado.";
                    }
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'condicao_id' => $condicaoId,
                        'observacoes' => $observacaoFinal,
                        'horario_confirmado' => 0,
                        'horario_confirmado_raw' => null
                    ];
                    
                    if ($statusCancelado) {
                        $updateData['status_id'] = $statusCancelado['id'];
                    }
                    
                    $this->solicitacaoModel->update($id, $updateData);
                    $this->json(['success' => true, 'message' => 'Sua solicitação foi cancelada. Você pode criar uma nova quando estiver disponível.']);
                    break;
                    
                case 'outros':
                    if (empty($observacaoInput)) {
                        $this->json(['success' => false, 'message' => 'Por favor, descreva o motivo'], 400);
                        return;
                    }
                    
                    // Mesma lógica do TokenController: status "Pendente", condição "outros"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('outros');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'outros',
                            'cor' => '#6b7280',
                            'icone' => 'fa-ellipsis-h',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    $statusPendente = $statusModel->findByNome('Pendente');
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('Aguardando');
                    }
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Outros em {$timestamp}] " . $observacaoInput;
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'condicao_id' => $condicaoId,
                        'observacoes' => $observacaoFinal
                    ];
                    
                    if ($statusPendente) {
                        $updateData['status_id'] = $statusPendente['id'];
                    }
                    
                    $this->solicitacaoModel->update($id, $updateData);
                    $this->json(['success' => true, 'message' => 'Sua mensagem foi registrada. Entraremos em contato em breve.']);
                    break;
                    
                default:
                    $this->json(['success' => false, 'message' => 'Ação não reconhecida'], 400);
                    return;
            }
            
        } catch (\Exception $e) {
            error_log('Erro ao executar ação [LocatarioController]: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao executar ação: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Processar upload de anexos
     */
    private function processarUploadAnexos(int $solicitacaoId, array $files): array
    {
        $anexosSalvos = [];
        $uploadDir = __DIR__ . '/../../Public/uploads/solicitacoes/' . $solicitacaoId . '/anexos/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            if ($files['size'][$i] > $maxSize) {
                continue;
            }
            
            $fileType = $files['type'][$i];
            if (!in_array($fileType, $allowedTypes)) {
                continue;
            }
            
            $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $fileName = uniqid('anexo_') . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                $anexosSalvos[] = 'uploads/solicitacoes/' . $solicitacaoId . '/anexos/' . $fileName;
            }
        }
        
        return $anexosSalvos;
    }
    
    /**
     * Página de avaliação NPS
     * GET /{instancia}/solicitacoes/{id}/avaliacao
     */
    public function avaliacao(string $instancia, int $id): void
    {
        $this->requireLocatarioAuth();
        
        if ($this->isPost()) {
            $this->salvarAvaliacao($instancia, $id);
            return;
        }
        
        $solicitacao = $this->solicitacaoModel->find($id);
        if (!$solicitacao) {
            $this->redirect($instancia . '/solicitacoes');
            return;
        }
        
        $locatario = $_SESSION['locatario'];
        if ($solicitacao['locatario_id'] != $locatario['id']) {
            $this->redirect($instancia . '/solicitacoes');
            return;
        }
        
        $this->view('locatario.avaliacao', [
            'locatario' => $locatario,
            'solicitacao' => $solicitacao,
            'instancia' => $instancia
        ]);
    }
    
    /**
     * Salvar avaliação NPS
     */
    private function salvarAvaliacao(string $instancia, int $id): void
    {
        $npsScore = $this->input('nps_score');
        $comentario = $this->input('comentario', '');
        
        if (empty($npsScore) || !is_numeric($npsScore)) {
            $this->json(['success' => false, 'message' => 'Score NPS é obrigatório'], 400);
            return;
        }
        
        $solicitacao = $this->solicitacaoModel->find($id);
        if (!$solicitacao) {
            $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
            return;
        }
        
        $locatario = $_SESSION['locatario'];
        if ($solicitacao['locatario_id'] != $locatario['id']) {
            $this->json(['success' => false, 'message' => 'Sem permissão'], 403);
            return;
        }
        
        try {
            // Salvar avaliação (pode criar uma tabela de avaliações ou salvar nas observações)
            $observacao = ($solicitacao['observacoes'] ?? '') . "\n\n[AVALIAÇÃO NPS - " . date('d/m/Y H:i:s') . "]";
            $observacao .= "\nScore: {$npsScore}/10";
            if (!empty($comentario)) {
                $observacao .= "\nComentário: {$comentario}";
            }
            
            $this->solicitacaoModel->update($id, [
                'observacoes' => $observacao
            ]);
            
            // TODO: Criar tabela de avaliações se necessário
            // Por enquanto, salvar nas observações
            
            $this->json(['success' => true, 'message' => 'Avaliação registrada com sucesso!']);
            
        } catch (\Exception $e) {
            error_log('Erro ao salvar avaliação [LocatarioController]: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao salvar avaliação'], 500);
        }
    }
    
    /**
     * Executar ação em solicitação pública usando token
     */
    public function executarAcaoComToken(string $token): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        
        // Buscar solicitação pelo token_acesso
        $sql = "SELECT id FROM solicitacoes WHERE token_acesso = ? LIMIT 1";
        $result = \App\Core\Database::fetch($sql, [$token]);
        
        if (!$result) {
            $this->json(['success' => false, 'message' => 'Token inválido ou expirado'], 404);
            return;
        }
        
        $solicitacaoId = $result['id'];
        $acao = $this->input('acao');
        $solicitacao = $this->solicitacaoModel->find($solicitacaoId);
        
        if (!$solicitacao) {
            $this->json(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
            return;
        }
        
        // Processar ação (mesma lógica do método executarAcao, mas sem verificação de login)
        try {
            $statusModel = new \App\Models\Status();
            $observacaoInput = $this->input('observacao', '');
            $valorReembolso = $this->input('valor_reembolso');
            
            // Processar upload de anexos
            $anexosSalvos = [];
            if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                $anexosSalvos = $this->processarUploadAnexos($solicitacaoId, $_FILES['anexos']);
            }
            
            $observacaoBase = $solicitacao['observacoes'] ?? '';
            $timestamp = date('d/m/Y H:i:s');
            
            switch ($acao) {
                case 'concluido':
                    $statusConcluido = $statusModel->findByNome('Concluído');
                    if (!$statusConcluido) {
                        $statusConcluido = $statusModel->findByNome('Concluido');
                    }
                    if ($statusConcluido) {
                        $observacaoFinal = $observacaoBase;
                        if (!empty($observacaoInput)) {
                            $observacaoFinal .= "\n\n[Concluído em {$timestamp}] Observação do locatário: {$observacaoInput}";
                        } else {
                            $observacaoFinal .= "\n\n[Concluído em {$timestamp}] Serviço concluído - confirmado pelo locatário";
                        }
                        if (!empty($anexosSalvos)) {
                            $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                        }
                        
                        $this->solicitacaoModel->update($solicitacaoId, [
                            'status_id' => $statusConcluido['id'],
                            'observacoes' => $observacaoFinal
                        ]);
                        
                        // Registrar no histórico que foi feito pelo LOCATÁRIO (via token)
                        $this->registrarHistoricoLocatario($solicitacaoId, $statusConcluido['id'], 'Solicitação marcada como concluída pelo locatário');
                        
                        // Atualizar contagem de CPF quando a solicitação é concluída (via token público)
                        try {
                            $cpfLocatario = $solicitacao['locatario_cpf'] ?? null;
                            $imobiliariaId = $solicitacao['imobiliaria_id'] ?? null;
                            $categoriaId = $solicitacao['categoria_id'] ?? null;
                            
                            if ($cpfLocatario && $imobiliariaId) {
                                $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                                $solicitacaoManualModel->atualizarContagemCPF(
                                    preg_replace('/[^0-9]/', '', $cpfLocatario),
                                    $imobiliariaId,
                                    $categoriaId
                                );
                            }
                        } catch (\Exception $e) {
                            error_log("Erro ao atualizar contagem de CPF (token): " . $e->getMessage());
                        }
                        
                        $this->json(['success' => true, 'message' => 'Solicitação marcada como concluída com sucesso!']);
                    } else {
                        $this->json(['success' => false, 'message' => 'Status "Concluído" não encontrado no sistema']);
                    }
                    break;
                    
                case 'cancelado':
                    if (empty($observacaoInput)) {
                        $this->json(['success' => false, 'message' => 'Observação é obrigatória para cancelamento'], 400);
                        return;
                    }
                    
                    // Buscar status "Cancelado"
                    $statusCancelado = $statusModel->findByNome('Cancelado');
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelada');
                    }
                    
                    if (!$statusCancelado) {
                        $this->json(['success' => false, 'message' => 'Status "Cancelado" não encontrado no sistema']);
                        return;
                    }
                    
                    // Buscar categoria "Cancelado"
                    $categoriaModel = new \App\Models\Categoria();
                    $sqlCategoria = "SELECT * FROM categorias WHERE nome = 'Cancelado' AND status = 'ATIVA' LIMIT 1";
                    $categoriaCancelado = \App\Core\Database::fetch($sqlCategoria);
                    
                    // Se não encontrar, buscar qualquer categoria com "Cancelado" no nome
                    if (!$categoriaCancelado) {
                        $sqlCategoria = "SELECT * FROM categorias WHERE nome LIKE '%Cancelado%' AND status = 'ATIVA' LIMIT 1";
                        $categoriaCancelado = \App\Core\Database::fetch($sqlCategoria);
                    }
                    
                    // Buscar condição "Cancelado pelo Locatário"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicaoCancelado = $condicaoModel->findByNome('Cancelado pelo Locatário');
                    
                    // Se não encontrar, buscar qualquer condição com "Cancelado" no nome
                    if (!$condicaoCancelado) {
                        $sqlCondicao = "SELECT * FROM condicoes WHERE nome LIKE '%Cancelado%' AND status = 'ATIVO' LIMIT 1";
                        $condicaoCancelado = \App\Core\Database::fetch($sqlCondicao);
                    }
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Cancelado em {$timestamp}] Motivo: {$observacaoInput}";
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'status_id' => $statusCancelado['id'],
                        'observacoes' => $observacaoFinal,
                        'motivo_cancelamento' => $observacaoInput
                    ];
                    
                    // Adicionar categoria se encontrada
                    if ($categoriaCancelado) {
                        $updateData['categoria_id'] = $categoriaCancelado['id'];
                    }
                    
                    // Adicionar condição se encontrada
                    if ($condicaoCancelado) {
                        $updateData['condicao_id'] = $condicaoCancelado['id'];
                    }
                    
                    $this->solicitacaoModel->update($solicitacaoId, $updateData);
                    
                    // Registrar no histórico
                    $sqlHistorico = "
                        INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ";
                    \App\Core\Database::query($sqlHistorico, [
                        $solicitacaoId,
                        $statusCancelado['id'],
                        null,
                        'Solicitação cancelada pelo locatário através do link público. Motivo: ' . $observacaoInput
                    ]);
                    
                    $this->json(['success' => true, 'message' => 'Solicitação cancelada com sucesso!']);
                    break;
                    
                case 'servico_nao_realizado':
                    // Mesma lógica: volta para "Nova Solicitação" com condição "Prestador não compareceu"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('Prestador não compareceu');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Prestador não compareceu',
                            'cor' => '#dc2626',
                            'icone' => 'fa-times-circle',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    $statusModel = new \App\Models\Status();
                    $statusNova = $statusModel->findByNome('Nova Solicitação');
                    
                    $observacaoFinal = $observacaoBase;
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= "\n\n[Serviço não realizado em {$timestamp}] Motivo: {$observacaoInput}";
                    } else {
                        $observacaoFinal .= "\n\n[Serviço não realizado em {$timestamp}] Prestador não compareceu no serviço agendado.";
                    }
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'observacoes' => $observacaoFinal
                    ];
                    
                    if ($statusNova) {
                        $updateData['status_id'] = $statusNova['id'];
                    }
                    if ($condicaoId) {
                        $updateData['condicao_id'] = $condicaoId;
                    }
                    
                    $this->solicitacaoModel->update($solicitacaoId, $updateData);
                    $this->json(['success' => true, 'message' => 'Informação registrada com sucesso! Solicitação reaberta.']);
                    break;
                    
                case 'comprar_pecas':
                    // Mesma lógica: status "Pendente Cliente", condição "Comprar peças", prazo de 10 dias
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('Comprar peças');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Comprar peças',
                            'cor' => '#f59e0b',
                            'icone' => 'fa-shopping-cart',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    // Buscar "Pendente Cliente" primeiro, depois "Pendente", depois "Aguardando"
                    $statusPendente = $statusModel->findByNome('Pendente Cliente');
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('PENDENTE CLIENTE');
                    }
                    if (!$statusPendente) {
                    $statusPendente = $statusModel->findByNome('Pendente');
                    }
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('Aguardando');
                    }
                    
                    $dataLimite = date('Y-m-d', strtotime('+10 days'));
                    
                    $observacaoFinal = $observacaoBase;
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= "\n\n[Comprar peças em {$timestamp}] Observação: {$observacaoInput}";
                    } else {
                        $observacaoFinal .= "\n\n[Comprar peças em {$timestamp}]";
                    }
                    $observacaoFinal .= "\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite));
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    // Preparar dados de atualização com verificação de colunas existentes
                    $updateData = [
                        'condicao_id' => $condicaoId,
                        'observacoes' => $observacaoFinal
                    ];
                    
                    if ($statusPendente) {
                        $updateData['status_id'] = $statusPendente['id'];
                    }
                    
                    // Verificar se colunas existem antes de adicionar
                    if ($this->solicitacaoModel->colunaExisteBanco('data_limite_peca')) {
                        $updateData['data_limite_peca'] = $dataLimite;
                    } else {
                        $updateData['observacoes'] .= " (Data limite: " . date('d/m/Y', strtotime($dataLimite)) . ")";
                    }
                    
                    if ($this->solicitacaoModel->colunaExisteBanco('data_ultimo_lembrete')) {
                        $updateData['data_ultimo_lembrete'] = null;
                    }
                    if ($this->solicitacaoModel->colunaExisteBanco('lembretes_enviados')) {
                        $updateData['lembretes_enviados'] = 0;
                    }
                    if ($this->solicitacaoModel->colunaExisteBanco('data_limite_cancelamento')) {
                        $updateData['data_limite_cancelamento'] = $dataLimite;
                    }
                    
                    try {
                        $this->solicitacaoModel->update($solicitacaoId, $updateData);
                        
                        // Registrar no histórico com observações incluindo anexos
                        if ($statusPendente) {
                            // Criar observação resumida para o histórico
                            $observacaoHistorico = "[Comprar peças em {$timestamp}]";
                            if (!empty($observacaoInput)) {
                                $observacaoHistorico .= " Observação: {$observacaoInput}";
                            }
                            $observacaoHistorico .= "\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite));
                            if (!empty($anexosSalvos)) {
                                $observacaoHistorico .= "\nAnexos: " . implode(', ', $anexosSalvos);
                            }
                            
                            $this->registrarHistoricoLocatario($solicitacaoId, $statusPendente['id'], $observacaoHistorico);
                        }
                        
                        $this->json(['success' => true, 'message' => 'Informação registrada: necessário comprar peças. Prazo de 10 dias definido.']);
                    } catch (\Exception $e) {
                        // Se alguma coluna opcional não existir, remover e tentar novamente
                        $mensagem = $e->getMessage();
                        $optionalColumns = [
                            'data_limite_peca',
                            'data_ultimo_lembrete',
                            'lembretes_enviados',
                            'data_limite_cancelamento'
                        ];
                        $alterado = false;
                        foreach ($optionalColumns as $coluna) {
                            if (strpos($mensagem, $coluna) !== false && isset($updateData[$coluna])) {
                                unset($updateData[$coluna]);
                                $alterado = true;
                            }
                        }
                        
                        if ($alterado) {
                            error_log('Aviso: removendo colunas opcionais inexistentes ao salvar "Comprar peças" (token público).');
                            $this->solicitacaoModel->update($solicitacaoId, $updateData);
                            
                            // Registrar no histórico mesmo em caso de erro com colunas opcionais
                            if ($statusPendente) {
                                $observacaoHistorico = "[Comprar peças em {$timestamp}]";
                                if (!empty($observacaoInput)) {
                                    $observacaoHistorico .= " Observação: {$observacaoInput}";
                                }
                                $observacaoHistorico .= "\nLocatário precisa comprar peças. Prazo: " . date('d/m/Y', strtotime($dataLimite));
                                if (!empty($anexosSalvos)) {
                                    $observacaoHistorico .= "\nAnexos: " . implode(', ', $anexosSalvos);
                                }
                                
                                $this->registrarHistoricoLocatario($solicitacaoId, $statusPendente['id'], $observacaoHistorico);
                            }
                            
                            $this->json(['success' => true, 'message' => 'Informação registrada: necessário comprar peças.']);
                        } else {
                            throw $e;
                        }
                    }
                    break;
                    
                case 'reembolso':
                    if (empty($observacaoInput)) {
                        $this->json(['success' => false, 'message' => 'Justificativa é obrigatória para reembolso'], 400);
                        return;
                    }
                    if (empty($valorReembolso) || $valorReembolso <= 0) {
                        $this->json(['success' => false, 'message' => 'Valor do reembolso é obrigatório'], 400);
                        return;
                    }
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Reembolso solicitado em {$timestamp}]";
                    $observacaoFinal .= "\nJustificativa: {$observacaoInput}";
                    $observacaoFinal .= "\nValor solicitado: R$ " . number_format($valorReembolso, 2, ',', '.');
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $this->solicitacaoModel->update($solicitacaoId, [
                        'observacoes' => $observacaoFinal,
                        'precisa_reembolso' => 1,
                        'valor_reembolso' => floatval($valorReembolso)
                    ]);
                    
                    // Registrar no histórico
                    $statusAtual = $this->solicitacaoModel->find($solicitacaoId);
                    if ($statusAtual && !empty($statusAtual['status_id'])) {
                        $sqlHistorico = "
                            INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                            VALUES (?, ?, ?, ?, NOW())
                        ";
                        \App\Core\Database::query($sqlHistorico, [
                            $solicitacaoId,
                            $statusAtual['status_id'],
                            null,
                            'Reembolso solicitado: R$ ' . number_format($valorReembolso, 2, ',', '.') . ' - ' . $observacaoInput
                        ]);
                    }
                    
                    $this->json(['success' => true, 'message' => 'Solicitação de reembolso registrada com sucesso!']);
                    break;
                    
                case 'reagendar':
                    // Para solicitações públicas, apenas registrar a intenção de reagendar
                    $observacaoFinal = $observacaoBase . "\n\n[Reagendamento solicitado em {$timestamp}]";
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= " Observação: {$observacaoInput}";
                    } else {
                        $observacaoFinal .= " Cliente solicitou reagendamento através do link público.";
                    }
                    
                    $this->solicitacaoModel->update($solicitacaoId, [
                        'observacoes' => $observacaoFinal
                    ]);
                    
                    // Registrar no histórico
                    $statusAtual = $this->solicitacaoModel->find($solicitacaoId);
                    if ($statusAtual && !empty($statusAtual['status_id'])) {
                        $sqlHistorico = "
                            INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                            VALUES (?, ?, ?, ?, NOW())
                        ";
                        \App\Core\Database::query($sqlHistorico, [
                            $solicitacaoId,
                            $statusAtual['status_id'],
                            null,
                            'Reagendamento solicitado pelo cliente através do link público'
                        ]);
                    }
                    
                    $this->json(['success' => true, 'message' => 'Solicitação de reagendamento registrada! A imobiliária entrará em contato.']);
                    break;
                    
                case 'ausente':
                    // Mesma lógica: status "Cancelado", condição "Locatário se ausentou"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('Locatário se ausentou');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'Locatário se ausentou',
                            'cor' => '#6366f1',
                            'icone' => 'fa-user-times',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    // Buscar status "Cancelado" (tenta várias variações)
                    $statusCancelado = $statusModel->findByNome('Cancelado');
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelada');
                    }
                    if (!$statusCancelado) {
                        $statusCancelado = $statusModel->findByNome('Cancelado pelo Locatário');
                    }
                    if (!$statusCancelado) {
                        $sql = "SELECT * FROM status WHERE (nome LIKE '%Cancelad%' OR nome LIKE '%Cancel%') AND status = 'ATIVO' LIMIT 1";
                        $statusCancelado = \App\Core\Database::fetch($sql);
                    }
                    
                    $observacaoFinal = $observacaoBase;
                    if (!empty($observacaoInput)) {
                        $observacaoFinal .= "\n\n[Precisei me ausentar em {$timestamp}] Observação: {$observacaoInput}";
                    } else {
                        $observacaoFinal .= "\n\n[Precisei me ausentar em {$timestamp}] Locatário se ausentou no horário agendado.";
                    }
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'condicao_id' => $condicaoId,
                        'observacoes' => $observacaoFinal,
                        'horario_confirmado' => 0,
                        'horario_confirmado_raw' => null
                    ];
                    
                    if ($statusCancelado) {
                        $updateData['status_id'] = $statusCancelado['id'];
                    }
                    
                    $this->solicitacaoModel->update($solicitacaoId, $updateData);
                    $this->json(['success' => true, 'message' => 'Sua solicitação foi cancelada. Você pode criar uma nova quando estiver disponível.']);
                    break;
                    
                case 'outros':
                    if (empty($observacaoInput)) {
                        $this->json(['success' => false, 'message' => 'Por favor, descreva o motivo'], 400);
                        return;
                    }
                    
                    // Mesma lógica: status "Pendente", condição "outros"
                    $condicaoModel = new \App\Models\Condicao();
                    $condicao = $condicaoModel->findByNome('outros');
                    if (!$condicao) {
                        $condicaoId = $condicaoModel->create([
                            'nome' => 'outros',
                            'cor' => '#6b7280',
                            'icone' => 'fa-ellipsis-h',
                            'status' => 'ATIVO'
                        ]);
                    } else {
                        $condicaoId = $condicao['id'];
                    }
                    
                    $statusPendente = $statusModel->findByNome('Pendente');
                    if (!$statusPendente) {
                        $statusPendente = $statusModel->findByNome('Aguardando');
                    }
                    
                    $observacaoFinal = $observacaoBase . "\n\n[Outros em {$timestamp}] " . $observacaoInput;
                    if (!empty($anexosSalvos)) {
                        $observacaoFinal .= "\nAnexos: " . implode(', ', $anexosSalvos);
                    }
                    
                    $updateData = [
                        'condicao_id' => $condicaoId,
                        'observacoes' => $observacaoFinal
                    ];
                    
                    if ($statusPendente) {
                        $updateData['status_id'] = $statusPendente['id'];
                    }
                    
                    $this->solicitacaoModel->update($solicitacaoId, $updateData);
                    $this->json(['success' => true, 'message' => 'Sua mensagem foi registrada. Entraremos em contato em breve.']);
                    break;
                    
                default:
                    $this->json(['success' => false, 'message' => 'Ação não reconhecida'], 400);
                    return;
            }
        } catch (\Exception $e) {
            error_log('Erro ao executar ação pública: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao processar ação: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Rastreamento público de solicitação com token (sem login)
     * Permite que pessoas vejam o status da solicitação usando o token enviado no WhatsApp
     */
    public function rastreamentoComToken(string $token): void
    {
        // Buscar solicitação pelo token_acesso
        $sql = "SELECT id FROM solicitacoes WHERE token_acesso = ? LIMIT 1";
        $result = \App\Core\Database::fetch($sql, [$token]);
        
        if (!$result) {
            $this->view('errors.404', [
                'message' => 'Link inválido ou expirado'
            ]);
            return;
        }
        
        // Buscar dados completos da solicitação
        $solicitacaoModel = new \App\Models\Solicitacao();
        $solicitacao = $solicitacaoModel->getDetalhes($result['id']);
        
        if (!$solicitacao) {
            $this->view('errors.404', [
                'message' => 'Solicitação não encontrada'
            ]);
            return;
        }
        
        // Buscar fotos
        try {
            $fotos = $solicitacaoModel->getFotos($result['id']);
        } catch (\Exception $e) {
            $fotos = [];
        }
        
        // Buscar histórico de status
        try {
            $historicoStatus = $solicitacaoModel->getHistoricoStatus($result['id']);
        } catch (\Exception $e) {
            $historicoStatus = [];
        }
        
        // Buscar imobiliária
        $imobiliariaModel = new \App\Models\Imobiliaria();
        $imobiliaria = $imobiliariaModel->find($solicitacao['imobiliaria_id']);
        
        if (!$imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Imobiliária não encontrada'
            ]);
            return;
        }
        
        // Exibir página de rastreamento (similar à tela de solicitação do locatário, mas sem login)
        $this->view('locatario.rastreamento-publico', [
            'solicitacao' => $solicitacao,
            'imobiliaria' => $imobiliaria,
            'fotos' => $fotos,
            'historicoStatus' => $historicoStatus,
            'token' => $token
        ]);
    }
    
    /**
     * Buscar ou criar token de reagendamento para rastreamento público
     * GET /rastreamento/{token}/reagendar
     */
    public function buscarTokenReagendamento(string $token): void
    {
        // Buscar solicitação pelo token de acesso
        $sql = "SELECT id, protocolo_seguradora, numero_solicitacao FROM solicitacoes WHERE token_acesso = ? LIMIT 1";
        $result = \App\Core\Database::fetch($sql, [$token]);
        
        if (!$result) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Link inválido ou expirado'], 404);
            } else {
                $this->view('errors.404', [
                    'message' => 'Link inválido ou expirado'
                ]);
            }
            return;
        }
        
        $solicitacaoId = $result['id'];
        $protocolo = $result['protocolo_seguradora'] ?? $result['numero_solicitacao'] ?? ('KSS' . $solicitacaoId);
        
        // Buscar token de reagendamento existente e válido
        $tokenModel = new \App\Models\ScheduleConfirmationToken();
        
        // Primeiro, tentar buscar token específico para reagendamento (não usado e não expirado)
        $sql = "
            SELECT * FROM schedule_confirmation_tokens
            WHERE solicitacao_id = ?
            AND expires_at > NOW()
            AND (used_at IS NULL OR used_at = '')
            AND action_type = 'reschedule'
            ORDER BY created_at DESC
            LIMIT 1
        ";
        $tokenReagendamento = \App\Core\Database::fetch($sql, [$solicitacaoId]);
        
        // Se não encontrar token específico de reagendamento, buscar qualquer token válido (não usado)
        if (!$tokenReagendamento) {
            $sql = "
                SELECT * FROM schedule_confirmation_tokens
                WHERE solicitacao_id = ?
                AND expires_at > NOW()
                AND (used_at IS NULL OR used_at = '')
                ORDER BY created_at DESC
                LIMIT 1
            ";
            $tokenReagendamento = \App\Core\Database::fetch($sql, [$solicitacaoId]);
        }
        
        // Se não existir token válido, criar um novo token de reagendamento
        if (!$tokenReagendamento) {
            $tokenReagendamentoToken = $tokenModel->createToken(
                $solicitacaoId,
                $protocolo,
                null, // scheduled_date
                null, // scheduled_time
                'reschedule' // action_type
            );
        } else {
            $tokenReagendamentoToken = $tokenReagendamento['token'];
        }
        
        // Se for requisição AJAX, retornar JSON
        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'token' => $tokenReagendamentoToken,
                'url' => url('reagendamento-horario?token=' . $tokenReagendamentoToken)
            ]);
            return;
        }
        
        // Redirecionar para a página de reagendamento
        $this->redirect(url('reagendamento-horario?token=' . $tokenReagendamentoToken));
    }
    
    /**
     * Enviar notificação WhatsApp
     */
    private function enviarNotificacaoWhatsApp(int $solicitacaoId, string $tipo, array $extraData = []): void
    {
        error_log("🔵 DEBUG [enviarNotificacaoWhatsApp] - INICIADO - Solicitação ID: {$solicitacaoId}, Tipo: {$tipo}");
        
        try {
            $whatsappService = new \App\Services\WhatsAppService();
            error_log("🔵 DEBUG [enviarNotificacaoWhatsApp] - WhatsAppService criado, chamando sendMessage...");
            
            $result = $whatsappService->sendMessage($solicitacaoId, $tipo, $extraData);
            
            error_log("🔵 DEBUG [enviarNotificacaoWhatsApp] - Resultado do sendMessage: " . json_encode($result));
            
            if (!$result['success']) {
                error_log('❌ Erro WhatsApp [LocatarioController]: ' . $result['message']);
            } else {
                error_log("✅ WhatsApp enviado com sucesso [LocatarioController] - Solicitação ID: {$solicitacaoId}");
            }
        } catch (\Exception $e) {
            error_log('❌ Erro ao enviar WhatsApp [LocatarioController]: ' . $e->getMessage());
            error_log('❌ Stack trace: ' . $e->getTraceAsString());
        }
    }
    
}
