<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Configuracao;

class ConfiguracoesController extends Controller
{
    private Configuracao $configuracaoModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->configuracaoModel = new Configuracao();
    }

    public function index(): void
    {
        $configuracoes = $this->configuracaoModel->getAll();

        $this->view('configuracoes.index', [
            'pageTitle' => 'Configurações',
            'currentPage' => 'configuracoes',
            'user' => $_SESSION['user'] ?? null,
            'configuracoes' => $configuracoes
        ]);
    }

    public function create(): void
    {
        $this->view('configuracoes.create', [
            'pageTitle' => 'Nova Configuração',
            'currentPage' => 'configuracoes',
            'user' => $_SESSION['user'] ?? null
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect(url('admin/configuracoes/create'));
        }

        $data = [
            'chave' => $this->input('chave'),
            'valor' => $this->input('valor'),
            'tipo' => $this->input('tipo', 'string'),
            'descricao' => $this->input('descricao', '')
        ];

        $errors = $this->validate([
            'chave' => 'required|min:3|max:100',
            'valor' => 'required',
            'tipo' => 'required|in:string,number,boolean,json,time'
        ], $data);

        if (!empty($errors)) {
            $this->view('configuracoes.create', [
                'pageTitle' => 'Nova Configuração',
                'currentPage' => 'configuracoes',
                'user' => $_SESSION['user'] ?? null,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            // Verificar se a chave já existe
            $existente = $this->configuracaoModel->findByChave($data['chave']);
            if ($existente) {
                $this->view('configuracoes.create', [
                    'pageTitle' => 'Nova Configuração',
                    'currentPage' => 'configuracoes',
                    'user' => $_SESSION['user'] ?? null,
                    'error' => 'Já existe uma configuração com esta chave',
                    'data' => $data
                ]);
                return;
            }

            $this->configuracaoModel->create($data);
            $_SESSION['flash_message'] = 'Configuração criada com sucesso';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/configuracoes'));
        } catch (\Exception $e) {
            $this->view('configuracoes.create', [
                'pageTitle' => 'Nova Configuração',
                'currentPage' => 'configuracoes',
                'user' => $_SESSION['user'] ?? null,
                'error' => 'Erro ao criar configuração: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    public function edit(int $id): void
    {
        $configuracao = $this->configuracaoModel->find($id);

        if (!$configuracao) {
            $this->view('errors.404');
            return;
        }

        $this->view('configuracoes.edit', [
            'pageTitle' => 'Editar Configuração',
            'currentPage' => 'configuracoes',
            'user' => $_SESSION['user'] ?? null,
            'configuracao' => $configuracao
        ]);
    }

    public function update(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect(url("admin/configuracoes/$id/edit"));
        }

        $configuracao = $this->configuracaoModel->find($id);

        if (!$configuracao) {
            $this->view('errors.404');
            return;
        }

        $data = [
            'valor' => $this->input('valor'),
            'tipo' => $this->input('tipo', 'string'),
            'descricao' => $this->input('descricao', '')
        ];

        $errors = $this->validate([
            'valor' => 'required',
            'tipo' => 'required|in:string,number,boolean,json,time'
        ], $data);

        if (!empty($errors)) {
            $this->view('configuracoes.edit', [
                'pageTitle' => 'Editar Configuração',
                'currentPage' => 'configuracoes',
                'user' => $_SESSION['user'] ?? null,
                'configuracao' => $configuracao,
                'errors' => $errors,
                'data' => $data
            ]);
            return;
        }

        try {
            $this->configuracaoModel->update($id, $data);
            $_SESSION['flash_message'] = 'Configuração atualizada com sucesso';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/configuracoes'));
        } catch (\Exception $e) {
            $this->view('configuracoes.edit', [
                'pageTitle' => 'Editar Configuração',
                'currentPage' => 'configuracoes',
                'user' => $_SESSION['user'] ?? null,
                'configuracao' => $configuracao,
                'error' => 'Erro ao atualizar configuração: ' . $e->getMessage()
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
            $this->configuracaoModel->delete($id);
            $this->json(['success' => true, 'message' => 'Configuração excluída com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao excluir configuração: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Página de configurações de emergência (específica)
     */
    public function emergencia(): void
    {
        $configuracoes = $this->configuracaoModel->getConfiguracoesEmergencia();
        
        // Buscar configurações individuais
        $telefone = $this->configuracaoModel->findByChave('telefone_emergencia');
        $horarioInicio = $this->configuracaoModel->findByChave('horario_comercial_inicio');
        $horarioFim = $this->configuracaoModel->findByChave('horario_comercial_fim');
        $diasSemana = $this->configuracaoModel->findByChave('dias_semana_comerciais');

        $this->view('configuracoes.emergencia', [
            'pageTitle' => 'Configurações de Emergência',
            'currentPage' => 'configuracoes',
            'user' => $_SESSION['user'] ?? null,
            'configuracoes' => $configuracoes,
            'telefone' => $telefone,
            'horarioInicio' => $horarioInicio,
            'horarioFim' => $horarioFim,
            'diasSemana' => $diasSemana
        ]);
    }

    /**
     * Salvar configurações de emergência
     */
    public function salvarEmergencia(): void
    {
        if (!$this->isPost()) {
            $this->redirect(url('admin/configuracoes/emergencia'));
        }

        try {
            // Salvar telefone
            $telefone = $this->input('telefone_emergencia');
            $this->configuracaoModel->setValor(
                'telefone_emergencia',
                $telefone,
                'string',
                'Telefone de emergência 0800'
            );

            // Salvar horário comercial início
            $horarioInicio = $this->input('horario_comercial_inicio');
            $this->configuracaoModel->setValor(
                'horario_comercial_inicio',
                $horarioInicio,
                'time',
                'Horário de início do atendimento comercial (formato HH:MM)'
            );

            // Salvar horário comercial fim
            $horarioFim = $this->input('horario_comercial_fim');
            $this->configuracaoModel->setValor(
                'horario_comercial_fim',
                $horarioFim,
                'time',
                'Horário de fim do atendimento comercial (formato HH:MM)'
            );

            // Salvar dias da semana comerciais
            $diasSemana = $this->input('dias_semana_comerciais', []);
            if (!is_array($diasSemana)) {
                $diasSemana = [];
            }
            $this->configuracaoModel->setValor(
                'dias_semana_comerciais',
                $diasSemana,
                'json',
                'Dias da semana considerados comerciais (1=Segunda, 7=Domingo)'
            );

            $_SESSION['flash_message'] = 'Configurações de emergência salvas com sucesso';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/configuracoes/emergencia'));
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Erro ao salvar configurações: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect(url('admin/configuracoes/emergencia'));
        }
    }

    /**
     * Página de configurações do WhatsApp
     */
    public function whatsapp(): void
    {
        // Buscar configuração da URL base
        $urlBase = $this->configuracaoModel->findByChave('whatsapp_links_base_url');

        $this->view('configuracoes.whatsapp', [
            'pageTitle' => 'Configurações WhatsApp',
            'currentPage' => 'configuracoes',
            'user' => $_SESSION['user'] ?? null,
            'urlBase' => $urlBase
        ]);
    }

    /**
     * Salvar configurações do WhatsApp
     */
    public function salvarWhatsapp(): void
    {
        if (!$this->isPost()) {
            $this->redirect(url('admin/configuracoes/whatsapp'));
        }

        try {
            // Validar URL
            $urlBase = trim($this->input('whatsapp_links_base_url', ''));
            
            if (empty($urlBase)) {
                $_SESSION['flash_message'] = 'A URL base é obrigatória';
                $_SESSION['flash_type'] = 'error';
                $this->redirect(url('admin/configuracoes/whatsapp'));
                return;
            }

            // Validar formato da URL
            if (!preg_match('/^https?:\/\//', $urlBase)) {
                $_SESSION['flash_message'] = 'A URL deve começar com http:// ou https://';
                $_SESSION['flash_type'] = 'error';
                $this->redirect(url('admin/configuracoes/whatsapp'));
                return;
            }

            // Remover barra final se houver
            $urlBase = rtrim($urlBase, '/');

            // Salvar configuração
            $this->configuracaoModel->setValor(
                'whatsapp_links_base_url',
                $urlBase,
                'string',
                'URL base para links enviados nas mensagens WhatsApp (links de token, confirmação, cancelamento, etc.). Exemplo: https://seu-dominio.com.br'
            );

            $_SESSION['flash_message'] = 'Configurações do WhatsApp salvas com sucesso';
            $_SESSION['flash_type'] = 'success';
            $this->redirect(url('admin/configuracoes/whatsapp'));
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Erro ao salvar configurações: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect(url('admin/configuracoes/whatsapp'));
        }
    }
}

