<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Solicitacao;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Imobiliaria;

class PwaController extends Controller
{
    private Solicitacao $solicitacaoModel;
    private Categoria $categoriaModel;
    private Subcategoria $subcategoriaModel;
    private Imobiliaria $imobiliariaModel;

    public function __construct()
    {
        $this->solicitacaoModel = new Solicitacao();
        $this->categoriaModel = new Categoria();
        $this->subcategoriaModel = new Subcategoria();
        $this->imobiliariaModel = new Imobiliaria();
    }

    public function index(): void
    {
        $this->view('pwa.index');
    }

    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/pwa/solicitar');
        }

        $this->view('pwa.login');
    }

    public function authenticate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/pwa/login');
        }

        $instancia = $this->input('instancia');
        $cpf = $this->input('cpf');
        $senha = $this->input('senha');
        $locatarioId = $this->input('locatario_id'); // Para compatibilidade com formulário antigo

        // Validar campos obrigatórios
        if (empty($instancia)) {
            $this->view('pwa.index', ['error' => 'Instância é obrigatória']);
            return;
        }

        // Se vier CPF e senha (novo formulário), usar autenticação via API
        if (!empty($cpf) && !empty($senha)) {
            // Buscar imobiliária pela instância
            $imobiliaria = $this->imobiliariaModel->findByInstancia($instancia);
            
            if (!$imobiliaria) {
                $this->view('pwa.index', ['error' => 'Imobiliária não encontrada']);
                return;
            }

            // Autenticar via API KSI
            try {
                $ksiService = \App\Services\KsiApiService::fromImobiliaria($imobiliaria);
                $result = $ksiService->autenticarLocatario($cpf, $senha);
                
                if ($result['success']) {
                    $cliente = $result['cliente'];
                    
                    // Buscar imóveis do locatário
                    $imovelResult = $ksiService->buscarImovelLocatario($cliente['id_cliente']);
                    
                    // Salvar dados na sessão
                    $_SESSION['pwa_user'] = [
                        'id' => $cliente['id_cliente'],
                        'nome' => $cliente['nome'],
                        'cpf' => $cpf,
                        'email' => $cliente['email'] ?? null,
                        'telefone' => $cliente['telefone'] ?? null,
                        'whatsapp' => $cliente['whatsapp'] ?? $cliente['telefone'] ?? null,
                        'imobiliaria_id' => $imobiliaria['id'],
                        'imobiliaria_nome' => $imobiliaria['nome'],
                        'instancia' => $instancia,
                        'imoveis' => $imovelResult['success'] ? $imovelResult['imoveis'] : []
                    ];
                    $_SESSION['pwa_authenticated'] = true;
                    
                    // Redirecionar para a instância específica
                    $this->redirect('/' . $instancia . '/dashboard');
                } else {
                    $this->view('pwa.index', ['error' => $result['message'] ?? 'CPF ou senha inválidos']);
                }
            } catch (\Exception $e) {
                $this->view('pwa.index', ['error' => 'Erro ao autenticar: ' . $e->getMessage()]);
            }
        } 
        // Se vier locatario_id (formulário antigo), manter compatibilidade
        else if (!empty($locatarioId)) {
            // Buscar imobiliária pela instância
            $imobiliaria = $this->imobiliariaModel->findByInstancia($instancia);
            
            if (!$imobiliaria) {
                $this->view('pwa.login', ['error' => 'Instância não encontrada']);
                return;
            }

            // Simulação de autenticação (compatibilidade)
            $locatario = [
                'id' => $locatarioId,
                'nome' => 'Locatário Teste',
                'telefone' => '11999999999',
                'email' => 'locatario@teste.com',
                'imobiliaria_id' => $imobiliaria['id']
            ];

            $_SESSION['pwa_user'] = $locatario;
            $_SESSION['pwa_authenticated'] = true;

            $this->redirect('/pwa/solicitar');
        } else {
            $this->view('pwa.index', ['error' => 'CPF/CNPJ e senha são obrigatórios']);
        }
    }

    public function solicitar(): void
    {
        if (!$this->isPwaAuthenticated()) {
            $this->redirect('/pwa/login');
        }

        $categorias = $this->categoriaModel->getAtivas();
        
        $this->view('pwa.solicitar', [
            'categorias' => $categorias,
            'user' => $_SESSION['pwa_user']
        ]);
    }

    public function createSolicitacao(): void
    {
        if (!$this->isPost() || !$this->isPwaAuthenticated()) {
            $this->redirect('/pwa/solicitar');
        }

        $user = $_SESSION['pwa_user'];
        
        $data = [
            'imobiliaria_id' => $user['imobiliaria_id'],
            'categoria_id' => $this->input('categoria_id'),
            'subcategoria_id' => $this->input('subcategoria_id'),
            'locatario_id' => $user['id'],
            'locatario_nome' => $user['nome'],
            'locatario_telefone' => $user['telefone'],
            'locatario_email' => $user['email'],
            'imovel_endereco' => $this->input('imovel_endereco'),
            'imovel_numero' => $this->input('imovel_numero'),
            'imovel_complemento' => $this->input('imovel_complemento'),
            'imovel_bairro' => $this->input('imovel_bairro'),
            'imovel_cidade' => $this->input('imovel_cidade'),
            'imovel_estado' => $this->input('imovel_estado'),
            'imovel_cep' => $this->input('imovel_cep'),
            'descricao_problema' => $this->input('descricao_problema'),
            'data_agendamento' => $this->input('data_agendamento'),
            'horario_agendamento' => $this->input('horario_agendamento'),
            'prioridade' => $this->input('prioridade', 'NORMAL')
        ];

        $errors = $this->validate([
            'categoria_id' => 'required',
            'subcategoria_id' => 'required',
            'imovel_endereco' => 'required|min:5',
            'descricao_problema' => 'required|min:10',
            'data_agendamento' => 'required',
            'horario_agendamento' => 'required'
        ], $data);

        if (!empty($errors)) {
            $categorias = $this->categoriaModel->getAtivas();
            $subcategorias = $this->subcategoriaModel->getByCategoria($data['categoria_id']);
            
            $this->view('pwa.solicitar', [
                'categorias' => $categorias,
                'subcategorias' => $subcategorias,
                'user' => $user,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $solicitacaoId = $this->solicitacaoModel->create($data);
            
            // Aqui seria implementado o upload de fotos
            // e o envio de notificações
            
            $this->redirect("/pwa/solicitacao/$solicitacaoId");
        } catch (\Exception $e) {
            $categorias = $this->categoriaModel->getAtivas();
            
            $this->view('pwa.solicitar', [
                'categorias' => $categorias,
                'user' => $user,
                'error' => 'Erro ao criar solicitação: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function solicitacoes(): void
    {
        if (!$this->isPwaAuthenticated()) {
            $this->redirect('/pwa/login');
        }

        $user = $_SESSION['pwa_user'];
        $solicitacoes = $this->solicitacaoModel->getByLocatario($user['id']);

        $this->view('pwa.solicitacoes', [
            'solicitacoes' => $solicitacoes,
            'user' => $user
        ]);
    }

    public function showSolicitacao(int $id): void
    {
        if (!$this->isPwaAuthenticated()) {
            $this->redirect('/pwa/login');
        }

        $user = $_SESSION['pwa_user'];
        $solicitacao = $this->solicitacaoModel->getDetalhes($id);
        
        if (!$solicitacao || $solicitacao['locatario_id'] != $user['id']) {
            $this->view('errors.404');
            return;
        }

        $fotos = $this->solicitacaoModel->getFotos($id);
        $historico = $this->solicitacaoModel->getHistoricoStatus($id);

        $this->view('pwa.solicitacao', [
            'solicitacao' => $solicitacao,
            'fotos' => $fotos,
            'historico' => $historico,
            'user' => $user
        ]);
    }

    private function isPwaAuthenticated(): bool
    {
        return isset($_SESSION['pwa_authenticated']) && $_SESSION['pwa_authenticated'] === true;
    }

    /**
     * API: Buscar estados disponíveis
     */
    public function getEstados(): void
    {
        $estados = $this->imobiliariaModel->getEstados();
        $this->json($estados);
    }

    /**
     * API: Buscar cidades por estado
     */
    public function getCidades(): void
    {
        $estado = $this->input('estado');
        
        if (empty($estado)) {
            $this->json(['error' => 'Estado é obrigatório'], 400);
            return;
        }

        $cidades = $this->imobiliariaModel->getCidadesPorEstado($estado);
        $this->json($cidades);
    }

    /**
     * API: Buscar imobiliárias por localização
     */
    public function getImobiliarias(): void
    {
        $estado = $this->input('estado');
        $cidade = $this->input('cidade');
        
        if (empty($estado) || empty($cidade)) {
            $this->json(['error' => 'Estado e cidade são obrigatórios'], 400);
            return;
        }

        $imobiliarias = $this->imobiliariaModel->getImobiliariasPorLocalizacao($estado, $cidade);
        
        // Adicionar status da integração para cada imobiliária
        foreach ($imobiliarias as &$imob) {
            $imob['integracao_ativa'] = isset($imob['integracao_ativa']) ? (bool)$imob['integracao_ativa'] : true;
        }
        
        $this->json($imobiliarias);
    }
}
