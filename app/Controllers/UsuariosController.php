<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuario;
use App\Models\Imobiliaria;

class UsuariosController extends Controller
{
    private Usuario $usuarioModel;
    private Imobiliaria $imobiliariaModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->usuarioModel = new Usuario();
        $this->imobiliariaModel = new Imobiliaria();
    }

    public function index(): void
    {
        $busca = $this->input('busca');
        $filtros = [];

        if ($busca) {
            $filtros['busca'] = $busca;
        }

        $usuarios = $this->usuarioModel->getAll($filtros);

        $this->view('usuarios.index', [
            'usuarios' => $usuarios,
            'busca' => $busca
        ]);
    }

    public function create(): void
    {
        $this->view('usuarios.create');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            redirect(url('admin/usuarios/create'));
        }

        $data = [
            'nome' => $this->input('nome'),
            'email' => $this->input('email'),
            'telefone' => $this->input('telefone'),
            'cpf' => $this->input('cpf'),
            'senha' => $this->input('senha'),
            'endereco' => $this->input('endereco'),
            'numero' => $this->input('numero'),
            'complemento' => $this->input('complemento'),
            'bairro' => $this->input('bairro'),
            'cidade' => $this->input('cidade'),
            'uf' => $this->input('uf'),
            'cep' => $this->input('cep'),
            'nivel_permissao' => $this->input('nivel_permissao'),
            'status' => 'ATIVO'
        ];

        $errors = $this->validate([
            'nome' => 'required|min:3',
            'email' => 'required|email',
            'telefone' => 'required',
            'cpf' => 'required|min:11',
            'senha' => 'required|min:6',
            'endereco' => 'required',
            'numero' => 'required',
            'bairro' => 'required',
            'cidade' => 'required',
            'uf' => 'required|min:2',
            'cep' => 'required',
            'nivel_permissao' => 'required'
        ], $data);

        if (!empty($errors)) {
            // Se for requisição AJAX, retornar JSON com erros
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erros de validação',
                    'errors' => $errors
                ], 400);
                return;
            }
            
            $this->view('usuarios.create', [
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        // Verificar se email já existe
        if ($this->usuarioModel->findByEmail($data['email'])) {
            // Se for requisição AJAX, retornar JSON com erro
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Este email já está cadastrado'
                ], 400);
                return;
            }
            
            $this->view('usuarios.create', [
                'error' => 'Este email já está cadastrado',
                'data' => $data
            ]);
            return;
        }

        // Verificar se CPF já existe
        if ($this->usuarioModel->findByCpf($data['cpf'])) {
            // Se for requisição AJAX, retornar JSON com erro
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Este CPF já está cadastrado'
                ], 400);
                return;
            }
            
            $this->view('usuarios.create', [
                'error' => 'Este CPF já está cadastrado',
                'data' => $data
            ]);
            return;
        }

        try {
            $this->usuarioModel->create($data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Usuário criado com sucesso']);
                return;
            }
            
            redirect(url('admin/usuarios'), 'Usuário criado com sucesso', 'success');
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON com erro
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Erro ao criar usuário: ' . $e->getMessage()], 500);
                return;
            }
            
            $this->view('usuarios.create', [
                'error' => 'Erro ao criar usuário: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function edit(int $id): void
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            $this->view('errors.404');
            return;
        }

        // Buscar páginas permitidas para este operador
        $paginasPermitidas = [];
        if ($this->usuarioModel->isOperador($usuario)) {
            $paginasPermitidas = $this->usuarioModel->getPaginasPermitidas($id);
        }
        
        // Definir páginas disponíveis (TODAS as abas do sistema)
        $paginasDisponiveis = [
            // GERAL
            'dashboard' => 'Dashboard',
            'relatorios' => 'Relatórios',
            'kanban' => 'Kanban',
            'upload' => 'Upload',
            // OPERAÇÕES
            'solicitacoes-manuais' => 'Solicitações Manuais',
            'templates-whatsapp' => 'Templates WhatsApp',
            'whatsapp-instances' => 'Instâncias WhatsApp',
            // ADMINISTRAÇÃO
            'imobiliarias' => 'Imobiliárias',
            'usuarios' => 'Usuários',
            'categorias' => 'Categorias',
            'status' => 'Status',
            'condicoes' => 'Condições/Pendências',
            // SISTEMA
            'configuracoes' => 'Configurações',
            'cron-jobs' => 'Cron Jobs',
            'logs' => 'Visualizador de Logs',
            'migracoes' => 'Migrações'
        ];

        $this->view('usuarios.edit', [
            'usuario' => $usuario,
            'paginasDisponiveis' => $paginasDisponiveis,
            'paginasPermitidas' => $paginasPermitidas
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            redirect(url("admin/usuarios/$id/edit"));
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            $this->view('errors.404');
            return;
        }

        $data = [
            'nome' => $this->input('nome'),
            'email' => $this->input('email'),
            'telefone' => $this->input('telefone'),
            'cpf' => $this->input('cpf'),
            'endereco' => $this->input('endereco'),
            'numero' => $this->input('numero'),
            'complemento' => $this->input('complemento'),
            'bairro' => $this->input('bairro'),
            'cidade' => $this->input('cidade'),
            'uf' => $this->input('uf'),
            'cep' => $this->input('cep'),
            'nivel_permissao' => $this->input('nivel_permissao'),
            'status' => $this->input('status')
        ];

        // Atualizar senha apenas se foi preenchida
        $senha = $this->input('senha');
        if (!empty($senha)) {
            $data['senha'] = $senha;
        }

        $errors = $this->validate([
            'nome' => 'required|min:3',
            'email' => 'required|email',
            'telefone' => 'required',
            'cpf' => 'required|min:11',
            'endereco' => 'required',
            'numero' => 'required',
            'bairro' => 'required',
            'cidade' => 'required',
            'uf' => 'required|min:2',
            'cep' => 'required',
            'nivel_permissao' => 'required',
            'status' => 'required'
        ], $data);

        if (!empty($errors)) {
            // Se for requisição AJAX, retornar JSON com erros
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erros de validação',
                    'errors' => $errors
                ], 400);
                return;
            }
            
            $this->view('usuarios.edit', [
                'errors' => $errors,
                'usuario' => array_merge($usuario, $data)
            ]);
            return;
        }

        // Verificar se email já existe (exceto o próprio usuário)
        $usuarioComEmail = $this->usuarioModel->findByEmail($data['email']);
        if ($usuarioComEmail && $usuarioComEmail['id'] != $id) {
            // Se for requisição AJAX, retornar JSON com erro
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Este email já está cadastrado para outro usuário'
                ], 400);
                return;
            }
            
            $this->view('usuarios.edit', [
                'error' => 'Este email já está cadastrado para outro usuário',
                'usuario' => array_merge($usuario, $data)
            ]);
            return;
        }

        // Verificar se CPF já existe (exceto o próprio usuário)
        $usuarioComCpf = $this->usuarioModel->findByCpf($data['cpf']);
        if ($usuarioComCpf && $usuarioComCpf['id'] != $id) {
            // Se for requisição AJAX, retornar JSON com erro
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Este CPF já está cadastrado para outro usuário'
                ], 400);
                return;
            }
            
            $this->view('usuarios.edit', [
                'error' => 'Este CPF já está cadastrado para outro usuário',
                'usuario' => array_merge($usuario, $data)
            ]);
            return;
        }

        try {
            $this->usuarioModel->update($id, $data);
            
            // Se for operador, salvar páginas permitidas
            if ($data['nivel_permissao'] === 'OPERADOR') {
                // Salvar páginas permitidas
                $paginasPermitidas = $this->input('paginas_permitidas', []);
                if (is_string($paginasPermitidas)) {
                    $paginasPermitidas = explode(',', $paginasPermitidas);
                }
                $paginasPermitidas = array_filter((array)$paginasPermitidas);
                $this->usuarioModel->salvarPaginasPermitidas($id, $paginasPermitidas);
            } else {
                // Se mudou para administrador, remover todas as permissões
                $this->usuarioModel->salvarPaginasPermitidas($id, []);
            }
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
                return;
            }
            
            redirect(url('admin/usuarios'), 'Usuário atualizado com sucesso', 'success');
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON com erro
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Erro ao atualizar usuário: ' . $e->getMessage()], 500);
                return;
            }
            
            $this->view('usuarios.edit', [
                'error' => 'Erro ao atualizar usuário: ' . $e->getMessage(),
                'usuario' => array_merge($usuario, $data)
            ]);
        }
    }

    public function delete(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        // Não permitir excluir o próprio usuário
        $currentUser = $this->getUser();
        if ($currentUser['id'] == $id) {
            $this->json(['error' => 'Você não pode excluir seu próprio usuário'], 400);
            return;
        }

        try {
            $this->usuarioModel->delete($id);
            $this->json(['success' => true, 'message' => 'Usuário excluído com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao excluir usuário: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        // Não permitir desativar o próprio usuário
        $currentUser = $this->getUser();
        if ($currentUser['id'] == $id) {
            $this->json(['error' => 'Você não pode alterar o status do seu próprio usuário'], 400);
            return;
        }

        try {
            $usuario = $this->usuarioModel->find($id);
            
            if (!$usuario) {
                $this->json(['error' => 'Usuário não encontrado'], 404);
                return;
            }

            $novoStatus = $usuario['status'] === 'ATIVO' ? 'INATIVO' : 'ATIVO';
            $this->usuarioModel->update($id, ['status' => $novoStatus]);
            
            $this->json([
                'success' => true,
                'message' => 'Status atualizado com sucesso',
                'status' => $novoStatus
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao atualizar status: ' . $e->getMessage()], 500);
        }
    }

    public function resetarSenha(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        $novaSenha = $this->input('nova_senha');

        if (empty($novaSenha) || strlen($novaSenha) < 6) {
            $this->json(['error' => 'A nova senha deve ter pelo menos 6 caracteres'], 400);
            return;
        }

        try {
            $this->usuarioModel->update($id, ['senha' => $novaSenha]);
            $this->json(['success' => true, 'message' => 'Senha resetada com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao resetar senha: ' . $e->getMessage()], 500);
        }
    }

    public function api(int $id): void
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            $this->json(['success' => false, 'message' => 'Usuário não encontrado'], 404);
            return;
        }

        // Remover senha dos dados retornados
        unset($usuario['senha']);
        
        // Buscar páginas permitidas para este operador
        $paginasPermitidas = [];
        if ($this->usuarioModel->isOperador($usuario)) {
            $paginasPermitidas = $this->usuarioModel->getPaginasPermitidas($id);
        }
        
        // Definir páginas disponíveis (TODAS as abas do sistema)
        $paginasDisponiveis = [
            // GERAL
            'dashboard' => 'Dashboard',
            'relatorios' => 'Relatórios',
            'kanban' => 'Kanban',
            'upload' => 'Upload',
            // OPERAÇÕES
            'solicitacoes-manuais' => 'Solicitações Manuais',
            'templates-whatsapp' => 'Templates WhatsApp',
            'whatsapp-instances' => 'Instâncias WhatsApp',
            // ADMINISTRAÇÃO
            'imobiliarias' => 'Imobiliárias',
            'usuarios' => 'Usuários',
            'categorias' => 'Categorias',
            'status' => 'Status',
            'condicoes' => 'Condições/Pendências',
            // SISTEMA
            'configuracoes' => 'Configurações',
            'cron-jobs' => 'Cron Jobs',
            'logs' => 'Visualizador de Logs',
            'migracoes' => 'Migrações'
        ];
 
        $this->json([
            'success' => true,
            'usuario' => $usuario,
            'paginasDisponiveis' => $paginasDisponiveis,
            'paginasPermitidas' => $paginasPermitidas
        ]);
    }
}

