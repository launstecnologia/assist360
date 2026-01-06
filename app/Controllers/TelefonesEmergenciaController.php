<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\TelefoneEmergencia;

class TelefonesEmergenciaController extends Controller
{
    private TelefoneEmergencia $telefoneModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->telefoneModel = new TelefoneEmergencia();
    }

    public function index(): void
    {
        $telefones = $this->telefoneModel->findAll([], 'numero ASC');

        $this->view('telefones-emergencia.index', [
            'pageTitle' => 'Telefones de Emergência',
            'currentPage' => 'telefones-emergencia',
            'user' => $_SESSION['user'] ?? null,
            'telefones' => $telefones
        ]);
    }

    public function create(): void
    {
        $this->view('telefones-emergencia.create', [
            'pageTitle' => 'Novo Telefone de Emergência',
            'currentPage' => 'telefones-emergencia',
            'user' => $_SESSION['user'] ?? null
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect(url('admin/telefones-emergencia/create'));
        }

        $data = [
            'numero' => $this->input('numero'),
            'descricao' => $this->input('descricao', ''),
            'is_ativo' => $this->input('is_ativo', 1) ? 1 : 0
        ];

        $errors = $this->validate([
            'numero' => 'required|min:3|max:20'
        ], $data);

        if (!empty($errors)) {
            $this->view('telefones-emergencia.create', [
                'pageTitle' => 'Novo Telefone de Emergência',
                'currentPage' => 'telefones-emergencia',
                'user' => $_SESSION['user'] ?? null,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $this->telefoneModel->create($data);
            $_SESSION['flash_message'] = 'Telefone de emergência criado com sucesso';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/telefones-emergencia'));
        } catch (\Exception $e) {
            $this->view('telefones-emergencia.create', [
                'pageTitle' => 'Novo Telefone de Emergência',
                'currentPage' => 'telefones-emergencia',
                'user' => $_SESSION['user'] ?? null,
                'error' => 'Erro ao criar telefone de emergência: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function edit(int $id): void
    {
        $telefone = $this->telefoneModel->find($id);

        if (!$telefone) {
            $this->view('errors.404');
            return;
        }

        $this->view('telefones-emergencia.edit', [
            'pageTitle' => 'Editar Telefone de Emergência',
            'currentPage' => 'telefones-emergencia',
            'user' => $_SESSION['user'] ?? null,
            'telefone' => $telefone
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect(url("admin/telefones-emergencia/$id/edit"));
        }

        $telefone = $this->telefoneModel->find($id);

        if (!$telefone) {
            $this->view('errors.404');
            return;
        }

        $data = [
            'numero' => $this->input('numero'),
            'descricao' => $this->input('descricao', ''),
            'is_ativo' => $this->input('is_ativo', 1) ? 1 : 0
        ];

        $errors = $this->validate([
            'numero' => 'required|min:3|max:20'
        ], $data);

        if (!empty($errors)) {
            $this->view('telefones-emergencia.edit', [
                'pageTitle' => 'Editar Telefone de Emergência',
                'currentPage' => 'telefones-emergencia',
                'user' => $_SESSION['user'] ?? null,
                'telefone' => $telefone,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $this->telefoneModel->update($id, $data);
            $_SESSION['flash_message'] = 'Telefone de emergência atualizado com sucesso';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/telefones-emergencia'));
        } catch (\Exception $e) {
            $this->view('telefones-emergencia.edit', [
                'pageTitle' => 'Editar Telefone de Emergência',
                'currentPage' => 'telefones-emergencia',
                'user' => $_SESSION['user'] ?? null,
                'telefone' => $telefone,
                'error' => 'Erro ao atualizar telefone de emergência: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(int $id): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método não permitido'], 405);
            return;
        }

        try {
            $this->telefoneModel->delete($id);
            $this->json(['success' => true, 'message' => 'Telefone de emergência excluído com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao excluir telefone de emergência: ' . $e->getMessage()], 500);
        }
    }
}

