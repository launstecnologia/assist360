<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WhatsappTemplate;
use App\Core\Database;

class WhatsappTemplatesController extends Controller
{
	public function index(): void
	{
		$user = $_SESSION['user'] ?? null;
		$app = require __DIR__ . '/../Config/config.php';

		WhatsappTemplate::ensureTable();
		$model = new WhatsappTemplate();
		$templates = $model->findAll([], 'created_at DESC');

		$this->view('whatsapp.templates', [
			'pageTitle' => 'Templates WhatsApp',
			'currentPage' => 'templates-whatsapp',
			'user' => $user,
			'app' => $app['app'] ?? ['name' => 'KSS Seguros'],
			'templates' => $templates
		]);
	}

	public function store(): void
	{
		$this->requireAuth();
		WhatsappTemplate::ensureTable();
		$model = new WhatsappTemplate();

		$nome = trim($_POST['nome'] ?? '');
		$tipo = trim($_POST['tipo'] ?? 'Nova Solicitação');
		$corpo = trim($_POST['corpo'] ?? '');
		$ativo = isset($_POST['ativo']) ? 1 : 0;
		$padrao = isset($_POST['padrao']) ? 1 : 0;
		$variaveis = $_POST['variaveis'] ?? [];

		$errors = [];
		if ($nome === '') { $errors[] = 'Nome do template é obrigatório.'; }
		if ($corpo === '') { $errors[] = 'Corpo da mensagem é obrigatório.'; }

		if (!empty($errors)) {
			$_SESSION['flash_message'] = implode('<br>', $errors);
			$_SESSION['flash_type'] = 'error';
			$this->redirect('/admin/templates-whatsapp');
			return;
		}

		// Se marcar como padrão, desmarca os demais do mesmo tipo
		if ($padrao) {
			Database::query('UPDATE whatsapp_templates SET padrao = 0 WHERE tipo = ?', [$tipo]);
		}

		$id = $model->create([
			'nome' => $nome,
			'tipo' => $tipo,
			'corpo' => $corpo,
			'variaveis' => json_encode($variaveis, JSON_UNESCAPED_UNICODE),
			'ativo' => $ativo,
			'padrao' => $padrao,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		]);

		$_SESSION['flash_message'] = 'Template salvo com sucesso.';
		$_SESSION['flash_type'] = 'success';
		$this->redirect('/admin/templates-whatsapp');
	}

	public function edit(int $id): void
	{
		$this->requireAuth();
		WhatsappTemplate::ensureTable();
		$model = new WhatsappTemplate();
		$template = $model->find($id);
		$templates = $model->findAll([], 'created_at DESC');
		$user = $_SESSION['user'] ?? null;
		$app = require __DIR__ . '/../Config/config.php';

		$this->view('whatsapp.templates', [
			'pageTitle' => 'Templates WhatsApp',
			'currentPage' => 'templates-whatsapp',
			'user' => $user,
			'app' => $app['app'] ?? ['name' => 'KSS Seguros'],
			'templates' => $templates,
			'editTemplate' => $template
		]);
	}

	public function update(int $id): void
	{
		$this->requireAuth();
		WhatsappTemplate::ensureTable();
		$model = new WhatsappTemplate();

		$nome = trim($_POST['nome'] ?? '');
		$tipo = trim($_POST['tipo'] ?? 'Nova Solicitação');
		$corpo = trim($_POST['corpo'] ?? '');
		$ativo = isset($_POST['ativo']) ? 1 : 0;
		$padrao = isset($_POST['padrao']) ? 1 : 0;
		$variaveis = $_POST['variaveis'] ?? [];

		$errors = [];
		if ($nome === '') { $errors[] = 'Nome do template é obrigatório.'; }
		if ($corpo === '') { $errors[] = 'Corpo da mensagem é obrigatório.'; }

		if (!empty($errors)) {
			$_SESSION['flash_message'] = implode('<br>', $errors);
			$_SESSION['flash_type'] = 'error';
			$this->redirect('/admin/templates-whatsapp/' . $id . '/edit');
			return;
		}

		if ($padrao) {
			Database::query('UPDATE whatsapp_templates SET padrao = 0 WHERE tipo = ? AND id <> ?', [$tipo, $id]);
		}

		$model->update($id, [
			'nome' => $nome,
			'tipo' => $tipo,
			'corpo' => $corpo,
			'variaveis' => json_encode($variaveis, JSON_UNESCAPED_UNICODE),
			'ativo' => $ativo,
			'padrao' => $padrao,
			'updated_at' => date('Y-m-d H:i:s'),
		]);

		$_SESSION['flash_message'] = 'Template atualizado com sucesso.';
		$_SESSION['flash_type'] = 'success';
		$this->redirect('/admin/templates-whatsapp');
	}

	public function destroy(int $id): void
	{
		$this->requireAuth();
		WhatsappTemplate::ensureTable();
		$model = new WhatsappTemplate();
		$model->delete($id);
		$_SESSION['flash_message'] = 'Template excluído com sucesso.';
		$_SESSION['flash_type'] = 'success';
		$this->redirect('/admin/templates-whatsapp');
	}
}


