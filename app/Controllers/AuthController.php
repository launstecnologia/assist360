<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuario;

class AuthController extends Controller
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            redirect(url('admin/dashboard'));
        }

        $this->view('auth.login');
    }

    public function login(): void
    {
        if (!$this->isPost()) {
            redirect(url('login'));
        }

        $email = $this->input('email');
        $senha = $this->input('senha');

        $errors = $this->validate([
            'email' => 'required|email',
            'senha' => 'required|min:6'
        ], ['email' => $email, 'senha' => $senha]);

        if (!empty($errors)) {
            $this->view('auth.login', ['errors' => $errors, 'email' => $email]);
            return;
        }

        $user = $this->usuarioModel->authenticate($email, $senha);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            $_SESSION['user_level'] = $user['nivel_permissao'];
            
            // Implementar "Lembrar de mim" usando cookie
            $rememberMe = $this->input('remember_me');
            if ($rememberMe) {
                // Criar token único para o cookie
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 dias
                
                // Salvar token no banco de dados
                $this->usuarioModel->saveRememberToken($user['id'], $token, $expires);
                
                // Criar cookie seguro
                setcookie('remember_token', $token, $expires, '/', '', true, true);
            }
            
            redirect(url('admin/dashboard'));
        } else {
            $this->view('auth.login', [
                'error' => 'Email ou senha incorretos',
                'email' => $email
            ]);
        }
    }

    public function logout(): void
    {
        // Remover cookie de "lembrar de mim" se existir
        if (isset($_COOKIE['remember_token'])) {
            $this->usuarioModel->deleteRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        session_destroy();
        redirect(url('login'));
    }
    
    public function showForgotPassword(): void
    {
        if ($this->isAuthenticated()) {
            redirect(url('admin/dashboard'));
        }
        
        $this->view('auth.forgot-password');
    }
    
    public function sendPasswordReset(): void
    {
        if (!$this->isPost()) {
            redirect(url('forgot-password'));
        }
        
        $email = $this->input('email');
        
        $errors = $this->validate([
            'email' => 'required|email'
        ], ['email' => $email]);
        
        if (!empty($errors)) {
            $this->view('auth.forgot-password', ['errors' => $errors, 'email' => $email]);
            return;
        }
        
        $user = $this->usuarioModel->findByEmail($email);
        
        if (!$user) {
            // Por segurança, não revelar se o email existe ou não
            $this->view('auth.forgot-password', [
                'success' => 'Se o email estiver cadastrado, você receberá um link para redefinir sua senha.'
            ]);
            return;
        }
        
        // Gerar token de recuperação
        $token = bin2hex(random_bytes(32));
        $expires = time() + (60 * 60); // 1 hora
        
        // Salvar token no banco
        $this->usuarioModel->savePasswordResetToken($user['id'], $token, $expires);
        
        // Enviar email com link de recuperação
        $resetLink = url('reset-password?token=' . $token);
        
        try {
            $this->sendPasswordResetEmail($user['email'], $user['nome'], $resetLink);
            
            $this->view('auth.forgot-password', [
                'success' => 'Se o email estiver cadastrado, você receberá um link para redefinir sua senha.'
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao enviar email de recuperação: ' . $e->getMessage());
            $this->view('auth.forgot-password', [
                'error' => 'Erro ao enviar email. Tente novamente mais tarde.',
                'email' => $email
            ]);
        }
    }
    
    public function showResetPassword(): void
    {
        if ($this->isAuthenticated()) {
            redirect(url('admin/dashboard'));
        }
        
        $token = $this->input('token');
        
        if (!$token) {
            redirect(url('forgot-password'));
        }
        
        // Validar token
        $tokenData = $this->usuarioModel->validatePasswordResetToken($token);
        
        if (!$tokenData) {
            $this->view('auth.reset-password', [
                'error' => 'Token inválido ou expirado. Solicite um novo link de recuperação.',
                'invalid_token' => true
            ]);
            return;
        }
        
        $this->view('auth.reset-password', ['token' => $token]);
    }
    
    public function resetPassword(): void
    {
        if (!$this->isPost()) {
            redirect(url('forgot-password'));
        }
        
        $token = $this->input('token');
        $senha = $this->input('senha');
        $senhaConfirm = $this->input('senha_confirm');
        
        $errors = $this->validate([
            'senha' => 'required|min:6',
            'senha_confirm' => 'required'
        ], ['senha' => $senha, 'senha_confirm' => $senhaConfirm]);
        
        if ($senha !== $senhaConfirm) {
            $errors['senha_confirm'] = 'As senhas não coincidem';
        }
        
        if (!empty($errors)) {
            $this->view('auth.reset-password', [
                'errors' => $errors,
                'token' => $token
            ]);
            return;
        }
        
        // Validar token
        $tokenData = $this->usuarioModel->validatePasswordResetToken($token);
        
        if (!$tokenData) {
            $this->view('auth.reset-password', [
                'error' => 'Token inválido ou expirado. Solicite um novo link de recuperação.',
                'invalid_token' => true
            ]);
            return;
        }
        
        // Atualizar senha
        $this->usuarioModel->updatePassword($tokenData['usuario_id'], $senha);
        
        // Invalidar token
        $this->usuarioModel->deletePasswordResetToken($token);
        
        $this->view('auth.reset-password', [
            'success' => 'Senha redefinida com sucesso! Você já pode fazer login.',
            'token' => null
        ]);
    }
    
    private function sendPasswordResetEmail(string $email, string $nome, string $resetLink): void
    {
        $appName = env('APP_NAME', 'KSS Seguros');
        $appUrl = env('APP_URL', 'http://localhost');
        
        $subject = "Redefinição de Senha - {$appName}";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #10b981; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9fafb; padding: 30px; border-radius: 0 0 5px 5px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #10b981; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$appName}</h1>
                </div>
                <div class='content'>
                    <p>Olá, <strong>{$nome}</strong>!</p>
                    <p>Você solicitou a redefinição de senha para sua conta.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Redefinir Senha</a>
                    </p>
                    <p>Ou copie e cole o link abaixo no seu navegador:</p>
                    <p style='word-break: break-all; color: #6b7280;'>{$resetLink}</p>
                    <p><strong>Este link expira em 1 hora.</strong></p>
                    <p>Se você não solicitou esta redefinição, ignore este email.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " {$appName}. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $appName . ' <' . env('MAIL_FROM_ADDRESS', 'noreply@kss.launs.com.br') . '>',
            'Reply-To: ' . env('MAIL_FROM_ADDRESS', 'noreply@kss.launs.com.br'),
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $result = mail($email, $subject, $message, implode("\r\n", $headers));
        
        if (!$result) {
            throw new \Exception('Falha ao enviar email');
        }
    }

    public function showRegister(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/admin/dashboard');
        }

        $this->view('auth.register');
    }

    public function register(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/register');
        }

        $data = [
            'nome' => $this->input('nome'),
            'email' => $this->input('email'),
            'telefone' => $this->input('telefone'),
            'senha' => $this->input('senha'),
            'nivel_permissao' => 'OPERADOR',
            'status' => 'ATIVO'
        ];

        $errors = $this->validate([
            'nome' => 'required|min:3',
            'email' => 'required|email',
            'telefone' => 'required',
            'senha' => 'required|min:6'
        ], $data);

        if (!empty($errors)) {
            $this->view('auth.register', ['errors' => $errors, 'data' => $data]);
            return;
        }

        // Verificar se email já existe
        if ($this->usuarioModel->findByEmail($data['email'])) {
            $this->view('auth.register', [
                'error' => 'Este email já está cadastrado',
                'data' => $data
            ]);
            return;
        }

        try {
            $userId = $this->usuarioModel->create($data);
            
            // Fazer login automático
            $user = $this->usuarioModel->find($userId);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            $_SESSION['user_level'] = $user['nivel_permissao'];
            
            $this->redirect('/admin/dashboard');
        } catch (\Exception $e) {
            $this->view('auth.register', [
                'error' => 'Erro ao criar conta: ' . $e->getMessage(),
                'data' => $data
            ]);
        }
    }
}
