<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Condicao;

class CondicoesController extends Controller
{
    private Condicao $condicaoModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->condicaoModel = new Condicao();
    }

    public function index(): void
    {
        $condicoes = $this->condicaoModel->getAll();

        $this->view('condicoes.index', [
            'condicoes' => $condicoes
        ]);
    }

    public function create(): void
    {
        $this->view('condicoes.create');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            redirect(url('admin/condicoes/create'));
        }

        $data = [
            'nome' => $this->input('nome'),
            'cor' => $this->input('cor'),
            'ordem' => $this->input('ordem') ?: $this->condicaoModel->getProximaOrdem(),
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
            
            $this->view('condicoes.create', [
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $condicaoId = $this->condicaoModel->create($data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Condição criada com sucesso!',
                    'id' => $condicaoId
                ]);
                return;
            }
            
            redirect(url('admin/condicoes'), 'Condição criada com sucesso', 'success');
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao criar condição: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('condicoes.create', [
                'error' => 'Erro ao criar condição: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function edit(int $id): void
    {
        $condicao = $this->condicaoModel->find($id);

        if (!$condicao) {
            $this->view('errors.404');
            return;
        }

        $this->view('condicoes.edit', [
            'condicao' => $condicao
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            redirect(url("admin/condicoes/$id/edit"));
        }

        $condicao = $this->condicaoModel->find($id);

        if (!$condicao) {
            $this->view('errors.404');
            return;
        }

        $data = [
            'nome' => $this->input('nome'),
            'cor' => $this->input('cor'),
            'ordem' => $this->input('ordem'),
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
            
            $this->view('condicoes.edit', [
                'errors' => $errors,
                'condicao' => array_merge($condicao, $data)
            ]);
            return;
        }

        try {
            $this->condicaoModel->update($id, $data);
            
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Condição atualizada com sucesso!'
                ]);
                return;
            }
            
            redirect(url('admin/condicoes'), 'Condição atualizada com sucesso', 'success');
        } catch (\Exception $e) {
            // Se for requisição AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao atualizar condição: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('condicoes.edit', [
                'error' => 'Erro ao atualizar condição: ' . $e->getMessage(),
                'condicao' => array_merge($condicao, $data)
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
            // Verificar se a condição está sendo usada
            $emUso = $this->condicaoModel->isUsado($id);
            
            if ($emUso) {
                $this->json(['error' => 'Esta condição não pode ser excluída pois está sendo usada por solicitações'], 400);
                return;
            }

            $this->condicaoModel->delete($id);
            $this->json(['success' => true, 'message' => 'Condição excluída com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao excluir condição: ' . $e->getMessage()], 500);
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

            // Atualizar cada condição
            foreach ($ordens as $item) {
                if (!isset($item['id']) || !isset($item['ordem'])) {
                    ob_end_clean();
                    $this->json(['error' => 'Item inválido', 'item' => $item], 400);
                    return;
                }
                
                $id = intval($item['id']);
                $ordem = intval($item['ordem']);
                
                $this->condicaoModel->update($id, ['ordem' => $ordem]);
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
     * API: Buscar dados da condição para exibir no offcanvas
     */
    public function api(int $id): void
    {
        $condicao = $this->condicaoModel->find($id);
        
        if (!$condicao) {
            $this->json(['success' => false, 'message' => 'Condição não encontrada'], 404);
            return;
        }
        
        $this->json([
            'success' => true,
            'condicao' => $condicao
        ]);
    }
}

