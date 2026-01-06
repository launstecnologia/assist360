<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Status;

class StatusController extends Controller
{
    private Status $statusModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->statusModel = new Status();
    }

    public function index(): void
    {
        $status = $this->statusModel->getAll();

        $this->view('status.index', [
            'status' => $status
        ]);
    }

    public function create(): void
    {
        $this->view('status.create');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            redirect(url('admin/status/create'));
        }

        $data = [
            'nome' => $this->input('nome'),
            'cor' => $this->input('cor'),
            'ordem' => $this->input('ordem') ?: $this->statusModel->getProximaOrdem(),
            'visivel_kanban' => $this->input('visivel_kanban') === 'on' || $this->input('visivel_kanban') === '1',
            'status' => 'ATIVO'
        ];

        $errors = $this->validate([
            'nome' => 'required|min:3',
            'cor' => 'required'
        ], $data);

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
            
            $this->view('status.create', [
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $statusId = $this->statusModel->create($data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Status criado com sucesso!',
                    'id' => $statusId
                ]);
                return;
            }
            
            redirect(url('admin/status'), 'Status criado com sucesso', 'success');
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao criar status: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('status.create', [
                'error' => 'Erro ao criar status: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function edit(int $id): void
    {
        $status = $this->statusModel->find($id);

        if (!$status) {
            $this->view('errors.404');
            return;
        }

        $this->view('status.edit', [
            'status' => $status
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            redirect(url("admin/status/$id/edit"));
        }

        $status = $this->statusModel->find($id);

        if (!$status) {
            $this->view('errors.404');
            return;
        }

        $data = [
            'nome' => $this->input('nome'),
            'cor' => $this->input('cor'),
            'ordem' => $this->input('ordem'),
            'visivel_kanban' => $this->input('visivel_kanban') === 'on' || $this->input('visivel_kanban') === '1',
            'status' => $this->input('status')
        ];

        $errors = $this->validate([
            'nome' => 'required|min:3',
            'cor' => 'required',
            'ordem' => 'required',
            'status' => 'required'
        ], $data);

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
            
            $this->view('status.edit', [
                'errors' => $errors,
                'status' => array_merge($status, $data)
            ]);
            return;
        }

        try {
            $this->statusModel->update($id, $data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso!'
                ]);
                return;
            }
            
            redirect(url('admin/status'), 'Status atualizado com sucesso', 'success');
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao atualizar status: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('status.edit', [
                'error' => 'Erro ao atualizar status: ' . $e->getMessage(),
                'status' => array_merge($status, $data)
            ]);
        }
    }

    public function delete(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            // Verificar se o status está sendo usado
            $emUso = $this->statusModel->isUsado($id);
            
            if ($emUso) {
                $this->json(['error' => 'Este status não pode ser excluído pois está sendo usado por solicitações'], 400);
                return;
            }

            $this->statusModel->delete($id);
            $this->json(['success' => true, 'message' => 'Status excluído com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao excluir status: ' . $e->getMessage()], 500);
        }
    }

    public function reordenar(): void
    {
        try {
            // Limpar qualquer output anterior
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Iniciar novo buffer limpo
            ob_start();
            
            if (!$this->isPost()) {
                ob_end_clean();
                $this->json(['error' => 'Método não permitido'], 405);
                return;
            }

            // Ler JSON do body da requisição
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            $ordens = $data['ordens'] ?? null;

            if (!$ordens || !is_array($ordens)) {
                ob_end_clean();
                $this->json(['error' => 'Dados inválidos', 'received' => $data], 400);
                return;
            }

            // Atualizar cada status
            foreach ($ordens as $item) {
                if (!isset($item['id']) || !isset($item['ordem'])) {
                    ob_end_clean();
                    $this->json(['error' => 'Item inválido', 'item' => $item], 400);
                    return;
                }
                
                $id = intval($item['id']);
                $ordem = intval($item['ordem']);
                
                $this->statusModel->update($id, ['ordem' => $ordem]);
            }

            ob_end_clean();
            $this->json(['success' => true, 'message' => 'Ordem atualizada com sucesso']);
            
        } catch (\Exception $e) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            $this->json(['error' => 'Exceção: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
    
    /**
     * API: Buscar dados do status para exibir no offcanvas
     */
    public function api(int $id): void
    {
        $status = $this->statusModel->find($id);
        
        if (!$status) {
            $this->json(['success' => false, 'message' => 'Status não encontrado'], 404);
            return;
        }
        
        $this->json([
            'success' => true,
            'status' => $status
        ]);
    }
}

