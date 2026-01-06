<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Imobiliaria;
use App\Models\Locatario;
use App\Services\KsiApiService;

class InstanciaController extends Controller
{
    private Imobiliaria $imobiliariaModel;
    private Locatario $locatarioModel;
    private ?array $imobiliaria = null;

    public function __construct()
    {
        $this->imobiliariaModel = new Imobiliaria();
        $this->locatarioModel = new Locatario();
        
        // Buscar imobiliária pela instância da URL
        $instancia = $this->getInstanciaFromUrl();
        if ($instancia) {
            $this->imobiliaria = $this->imobiliariaModel->findByInstancia($instancia);
        }
    }

    public function index(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            // Exibir tela de imobiliária não encontrada
            $instancia = $instancia ?? $this->getInstanciaFromUrl();
            $this->view('locatario.imobiliaria-nao-encontrada', [
                'instancia' => $instancia
            ]);
            return;
        }

        // Redirecionar para login se não estiver autenticado
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['instancia']) || $_SESSION['instancia'] !== $this->imobiliaria['instancia']) {
            $this->redirect('/' . $this->imobiliaria['instancia'] . '/login');
            return;
        }

        $this->redirect('/' . $this->imobiliaria['instancia'] . '/dashboard');
    }

    public function login(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            // Exibir tela de imobiliária não encontrada
            $instancia = $instancia ?? $this->getInstanciaFromUrl();
            $this->view('locatario.imobiliaria-nao-encontrada', [
                'instancia' => $instancia
            ]);
            return;
        }

        // Se já estiver logado, redirecionar para dashboard
        if (isset($_SESSION['user_id']) && isset($_SESSION['instancia']) && $_SESSION['instancia'] === $this->imobiliaria['instancia']) {
            $this->redirect('/' . $this->imobiliaria['instancia'] . '/dashboard');
            return;
        }

        $this->view('instancia.login', [
            'imobiliaria' => $this->imobiliaria,
            'error' => $_GET['error'] ?? null
        ]);
    }

    public function authenticate(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        $cpf = $this->input('cpf');
        $senha = $this->input('senha');

        if (empty($cpf) || empty($senha)) {
            $this->redirect('/' . $this->imobiliaria['instancia'] . '/login?error=' . urlencode('CPF e senha são obrigatórios'));
            return;
        }

        try {
            // Debug: verificar dados da imobiliária
            error_log('Imobiliária encontrada: ' . json_encode($this->imobiliaria));
            
            // Autenticar via API KSI
            $ksiService = KsiApiService::fromImobiliaria($this->imobiliaria);
            $result = $ksiService->autenticarLocatario($cpf, $senha);
            
            error_log('Resultado da autenticação: ' . json_encode($result));

            if ($result['success']) {
                // Salvar/atualizar dados do locatário no banco
                $clienteData = $result['cliente'];
                $locatarioData = [
                    'imobiliaria_id' => $this->imobiliaria['id'],
                    'ksi_cliente_id' => $clienteData['id_cliente'],
                    'nome' => $clienteData['nome'],
                    'cpf' => $cpf,
                    'email' => $clienteData['email'] ?? null,
                    'telefone' => $clienteData['telefone'] ?? null,
                    'endereco_logradouro' => $clienteData['endereco'] ?? null,
                    'endereco_numero' => $clienteData['numero'] ?? null,
                    'endereco_complemento' => $clienteData['complemento'] ?? null,
                    'endereco_bairro' => $clienteData['bairro'] ?? null,
                    'endereco_cidade' => $clienteData['cidade'] ?? null,
                    'endereco_estado' => $clienteData['estado'] ?? null,
                    'endereco_cep' => $clienteData['cep'] ?? null
                ];
                
                $locatario = $this->locatarioModel->createOrUpdate($locatarioData);
                
                // Buscar imóveis do locatário via API
                $imoveisResult = $ksiService->buscarImovelLocatario($clienteData['id_cliente']);
                
                // Preparar imóveis para validação (formato raw da API)
                $imoveisParaValidacao = [];
                if ($imoveisResult['success'] && !empty($imoveisResult['dados'])) {
                    // Se vier em formato raw da API
                    $imoveisParaValidacao = $imoveisResult['dados'];
                } elseif ($imoveisResult['success'] && !empty($imoveisResult['imoveis'])) {
                    // Se vier já processado
                    $imoveisParaValidacao = $imoveisResult['imoveis'];
                }
                
                // Validar regras de acesso (Bolsão e Data do Contrato)
                $validacaoAcesso = \App\Services\ValidacaoAcessoService::validarRegrasAcesso(
                    $cpf,
                    $this->imobiliaria['id'],
                    $imoveisParaValidacao
                );
                
                if ($imoveisResult['success'] && !empty($imoveisResult['imoveis'])) {
                    foreach ($imoveisResult['imoveis'] as $imovel) {
                        $imovelData = [
                            'ksi_imovel_cod' => $imovel['ImoCod'],
                            'endereco_logradouro' => $imovel['ImoEnd'],
                            'endereco_numero' => $imovel['ImoEndNum'],
                            'endereco_complemento' => $imovel['ImoEndCompl'],
                            'endereco_bairro' => $imovel['ImoBaiNom'],
                            'endereco_cidade' => $imovel['ImoCidNom'],
                            'endereco_estado' => $imovel['ImoUF'],
                            'endereco_cep' => $imovel['ImoEndCep'],
                            'contrato_cod' => $imovel['Ctr'][0]['CtrCod'] ?? null,
                            'contrato_dv' => $imovel['Ctr'][0]['CtrDV'] ?? null
                        ];
                        $this->locatarioModel->addImovel($locatario['id'], $imovelData);
                    }
                }

                // Salvar dados do usuário na sessão
                $_SESSION['user_id'] = $clienteData['id_cliente'];
                $_SESSION['locatario_id'] = $locatario['id'];
                $_SESSION['user_name'] = $clienteData['nome'];
                $_SESSION['user_cpf'] = $cpf;
                $_SESSION['instancia'] = $this->imobiliaria['instancia'];
                $_SESSION['imobiliaria_id'] = $this->imobiliaria['id'];
                $_SESSION['user_level'] = 'LOCATARIO';
                $_SESSION['cliente_data'] = $clienteData;
                
                // Salvar dados completos do locatário na sessão
                $_SESSION['locatario'] = [
                    'id' => $locatario['id'],
                    'nome' => $clienteData['nome'],
                    'cpf' => $cpf,
                    'email' => $clienteData['email'] ?? null,
                    'telefone' => $clienteData['telefone'] ?? null,
                    'whatsapp' => $locatario['whatsapp'] ?? null,
                    'imobiliaria_id' => $this->imobiliaria['id'],
                    'ksi_cliente_id' => $clienteData['id_cliente'],
                    'instancia' => $this->imobiliaria['instancia'],
                    'imoveis' => $imoveisResult['success'] ? ($imoveisResult['imoveis'] ?? []) : [],
                    'ultima_sincronizacao' => date('Y-m-d H:i:s'),
                    'acesso_permitido' => $validacaoAcesso['permitido'],
                    'motivo_bloqueio' => $validacaoAcesso['motivo_bloqueio'] ?? null,
                    'validacao_bolsao' => $validacaoAcesso['bolsao'] ?? false
                ];

                $this->redirect('/' . $this->imobiliaria['instancia'] . '/dashboard');
            } else {
                $this->redirect('/' . $this->imobiliaria['instancia'] . '/login?error=' . urlencode($result['message']));
            }
        } catch (\Exception $e) {
            $this->redirect('/' . $this->imobiliaria['instancia'] . '/login?error=' . urlencode('Erro interno do servidor'));
        }
    }

    public function dashboard(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Instância não encontrada ou inativa'
            ]);
            return;
        }

        $this->checkAuth();

        // IGUAL O PERFIL: Pegar direto da sessão
        $locatario = $_SESSION['locatario'] ?? null;
        
        if (!$locatario) {
            $this->redirect('/' . $this->imobiliaria['instancia']);
            return;
        }
        
        // Buscar WhatsApp do banco se estiver vazio
        $whatsappInicial = $locatario['whatsapp'] ?? 'NÃO DEFINIDO';
        error_log('InstanciaController::dashboard - WhatsApp inicial: ' . var_export($whatsappInicial, true));
        
        $whatsappVazio = empty($locatario['whatsapp']) || trim($locatario['whatsapp']) === '' || $locatario['whatsapp'] === null;
        error_log('InstanciaController::dashboard - WhatsApp vazio (antes buscar banco): ' . ($whatsappVazio ? 'SIM' : 'NÃO'));
        
        if ($whatsappVazio) {
            $cpfLimpo = str_replace(['.', '-'], '', $locatario['cpf']);
            $locatarioBanco = $this->locatarioModel->findByCpfAndImobiliaria($cpfLimpo, $this->imobiliaria['id']);
            
            if ($locatarioBanco) {
                $whatsappBanco = trim($locatarioBanco['whatsapp'] ?? '');
                error_log('InstanciaController::dashboard - WhatsApp do banco: ' . var_export($whatsappBanco, true));
                if (!empty($whatsappBanco)) {
                    $locatario['whatsapp'] = $whatsappBanco;
                    $locatario['telefone'] = $locatarioBanco['telefone'] ?? '';
                    $locatario['email'] = $locatarioBanco['email'] ?? '';
                    
                    // Atualizar sessão
                    $_SESSION['locatario']['whatsapp'] = $locatario['whatsapp'];
                    $_SESSION['locatario']['telefone'] = $locatario['telefone'];
                    $_SESSION['locatario']['email'] = $locatario['email'];
                    error_log('InstanciaController::dashboard - WhatsApp atualizado da sessão/banco: ' . $locatario['whatsapp']);
                } else {
                    // Garantir que está vazio
                    $locatario['whatsapp'] = '';
                    error_log('InstanciaController::dashboard - WhatsApp do banco está vazio, definindo como string vazia');
                }
            } else {
                // Garantir que está vazio
                $locatario['whatsapp'] = '';
                error_log('InstanciaController::dashboard - Locatário não encontrado no banco, definindo WhatsApp como string vazia');
            }
        }
        
        // Verificação final
        $whatsappFinal = $locatario['whatsapp'] ?? 'NÃO DEFINIDO';
        $whatsappVazioFinal = empty($locatario['whatsapp']) || trim($locatario['whatsapp']) === '' || $locatario['whatsapp'] === null;
        error_log('InstanciaController::dashboard - WhatsApp final: ' . var_export($whatsappFinal, true));
        error_log('InstanciaController::dashboard - WhatsApp vazio (final): ' . ($whatsappVazioFinal ? 'SIM' : 'NÃO'));
        error_log('InstanciaController::dashboard - Deve mostrar modal: ' . ($whatsappVazioFinal ? 'SIM' : 'NÃO'));

        $this->view('instancia.dashboard', [
            'imobiliaria' => $this->imobiliaria,
            'locatario' => $locatario,
            'imoveis' => $locatario['imoveis'] ?? [],
            'cliente' => $_SESSION['cliente_data'] ?? null
        ]);
    }

    public function solicitacoes(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Instância não encontrada ou inativa'
            ]);
            return;
        }

        $this->checkAuth();

        // Buscar solicitações do locatário
        $solicitacoes = $this->getSolicitacoesLocatario();

        $this->view('instancia.solicitacoes', [
            'imobiliaria' => $this->imobiliaria,
            'solicitacoes' => $solicitacoes
        ]);
    }

    public function solicitacao(string $instancia = null, int $id = null): void
    {
        if (!$this->imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Instância não encontrada ou inativa'
            ]);
            return;
        }

        $this->checkAuth();

        // Buscar solicitação específica
        $solicitacao = $this->getSolicitacaoById($id);

        if (!$solicitacao || $solicitacao['locatario_id'] != $_SESSION['user_id']) {
            $this->view('errors.404', [
                'message' => 'Solicitação não encontrada'
            ]);
            return;
        }

        $this->view('instancia.solicitacao', [
            'imobiliaria' => $this->imobiliaria,
            'solicitacao' => $solicitacao
        ]);
    }

    public function perfil(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Instância não encontrada ou inativa'
            ]);
            return;
        }

        $this->checkAuth();

        $this->view('instancia.perfil', [
            'imobiliaria' => $this->imobiliaria,
            'cliente' => $_SESSION['cliente_data'] ?? null
        ]);
    }

    public function atualizarPerfil(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        $this->checkAuth();

        try {
            if (!$this->isPost()) {
                $this->json(['success' => false, 'message' => 'Método não permitido']);
                return;
            }

            $locatarioId = $_SESSION['locatario_id'] ?? null;
            if (!$locatarioId) {
                $this->json(['success' => false, 'message' => 'Locatário não encontrado']);
                return;
            }

            // Receber dados do formulário
            $nome = trim($this->input('nome', ''));
            $email = trim($this->input('email', ''));
            $whatsapp = trim($this->input('whatsapp', ''));

            // Validar dados
            if (empty($nome)) {
                $this->json(['success' => false, 'message' => 'O nome é obrigatório']);
                return;
            }

            // Validar email se fornecido
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->json(['success' => false, 'message' => 'E-mail inválido']);
                return;
            }

            // Validar WhatsApp se fornecido
            if (!empty($whatsapp)) {
                $whatsappLimpo = preg_replace('/\D/', '', $whatsapp);
                if (strlen($whatsappLimpo) < 10 || strlen($whatsappLimpo) > 11) {
                    $this->json(['success' => false, 'message' => 'WhatsApp inválido. Use o formato (XX) XXXXX-XXXX']);
                    return;
                }
            }

            // Preparar dados para atualização
            $dados = [
                'nome' => $nome,
                'whatsapp' => $whatsapp,
                'telefone' => $whatsapp // Usar whatsapp como telefone também
            ];

            if (!empty($email)) {
                $dados['email'] = $email;
            }

            // Atualizar no banco
            $sucesso = $this->locatarioModel->updateDadosPessoais($locatarioId, $dados);

            if ($sucesso) {
                // Atualizar dados na sessão
                if (isset($_SESSION['locatario'])) {
                    $_SESSION['locatario']['nome'] = $nome;
                    $_SESSION['locatario']['whatsapp'] = $whatsapp;
                    $_SESSION['locatario']['telefone'] = $whatsapp;
                    if (!empty($email)) {
                        $_SESSION['locatario']['email'] = $email;
                    }
                }

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
            $this->json([
                'success' => false,
                'message' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }

    public function novaSolicitacao(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->view('errors.404', [
                'message' => 'Instância não encontrada ou inativa'
            ]);
            return;
        }

        $this->checkAuth();

        // Buscar categorias disponíveis
        $categorias = $this->getCategorias();

        $this->view('instancia.nova-solicitacao', [
            'imobiliaria' => $this->imobiliaria,
            'categorias' => $categorias
        ]);
    }

    public function criarSolicitacao(string $instancia = null): void
    {
        if (!$this->imobiliaria) {
            $this->json(['error' => 'Instância não encontrada'], 404);
            return;
        }

        $this->checkAuth();

        // Implementar criação de solicitação
        $this->json(['success' => true, 'message' => 'Solicitação criada com sucesso']);
    }

    public function logout(string $instancia = null): void
    {
        // Guardar instância antes de destruir a sessão
        $instanciaAtual = $this->imobiliaria['instancia'] ?? $_SESSION['instancia'] ?? $instancia;
        
        // Limpar apenas dados do locatário
        unset($_SESSION['user_id']);
        unset($_SESSION['locatario_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_cpf']);
        unset($_SESSION['instancia']);
        unset($_SESSION['imobiliaria_id']);
        unset($_SESSION['user_level']);
        unset($_SESSION['cliente_data']);
        
        // Redirecionar para login da instância (SEM /login)
        if ($instanciaAtual) {
            $this->redirect('/' . $instanciaAtual);
        } else {
            $this->redirect('/');
        }
    }

    private function getInstanciaFromUrl(): ?string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        // Remover o base path se existir
        $basePath = trim(defined('FOLDER') ? FOLDER : '', '/');
        if (!empty($basePath) && !empty($segments) && $segments[0] === $basePath) {
            array_shift($segments);
        }
        
        // O primeiro segmento restante é a instância
        return $segments[0] ?? null;
    }

    private function checkAuth(): void
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['instancia']) || $_SESSION['instancia'] !== $this->imobiliaria['instancia']) {
            $this->redirect('/' . $this->imobiliaria['instancia'] . '/login');
        }
    }

    private function getSolicitacoesLocatario(): array
    {
        // Implementar busca de solicitações do locatário
        return [];
    }

    private function getSolicitacaoById(int $id): ?array
    {
        $solicitacaoModel = new \App\Models\Solicitacao();
        $solicitacao = $solicitacaoModel->getDetalhes($id);
        
        if (!$solicitacao) {
            return null;
        }
        
        // Buscar fotos
        try {
            $fotos = $solicitacaoModel->getFotos($id);
        } catch (\Exception $e) {
            $fotos = [];
        }
        $solicitacao['fotos'] = $fotos;
        
        // Buscar histórico de status
        try {
            $historicoStatus = $solicitacaoModel->getHistoricoStatus($id);
        } catch (\Exception $e) {
            $historicoStatus = [];
        }
        $solicitacao['historico_status'] = $historicoStatus;
        
        return $solicitacao;
    }

    private function getCategorias(): array
    {
        // Implementar busca de categorias
        return [];
    }
}
