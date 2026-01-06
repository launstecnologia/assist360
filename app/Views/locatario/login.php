<?php
/**
 * View: Login do Locatário
 */
$title = 'Login - Assistência 360°';
$currentPage = 'locatario-login';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo da Imobiliária -->
        <div class="text-center mb-8">
            <?php if (!empty($imobiliaria['logo'])): ?>
                <img src="<?= url('Public/uploads/logos/' . $imobiliaria['logo']) ?>" 
                     alt="<?= htmlspecialchars($imobiliaria['nome']) ?>" 
                     class="mx-auto h-20 w-auto"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="mx-auto h-20 w-20 bg-blue-600 rounded-lg flex items-center justify-center" style="display: none;">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
            <?php else: ?>
                <div class="mx-auto h-20 w-20 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Título Principal -->
        <h1 class="text-center text-3xl font-bold text-gray-900 mb-8">
            Assistência 360°
        </h1>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <!-- Mensagens de Erro/Sucesso -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST" action="<?= url($instancia) ?>">
                <?= \App\Core\View::csrfField() ?>
                <input type="hidden" name="instancia" value="<?= htmlspecialchars($instancia) ?>">
                
                <div>
                    <label for="cpf" class="block text-sm font-medium text-gray-700">
                        CPF/CNPJ
                    </label>
                    <p class="mt-0.5 mb-1 text-[10px] text-gray-500 leading-tight">
                        Do Locatário (inquilino) que consta no contrato de locação da imobiliária
                    </p>
                    <div class="mt-1">
                        <input id="cpf" 
                               name="cpf" 
                               type="text" 
                               required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="CPF/CNPJ do Locatário"
                               value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700">
                        Senha
                    </label>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Senha utilizada para acessar a área do cliente da imobiliária
                    </p>
                    <div class="mt-1 relative">
                        <input id="senha" 
                               name="senha" 
                               type="password" 
                               required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Digite sua senha">
                        <button type="button" 
                                onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i id="password-icon" class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <a href="#" 
                           onclick="event.preventDefault(); abrirModalEsqueciSenha();"
                           class="font-medium text-blue-600 hover:text-blue-500">
                            Esqueci minha senha
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Acessar Assistência
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?= url($instancia . '/solicitacao-manual') ?>" class="text-sm text-blue-600 hover:text-blue-500">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        Não estou conseguindo acesso - Fazer solicitação manual
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logo KSS pequena abaixo do card -->
    <div class="mt-6 flex justify-center items-center">
        <?= kss_logo('mx-auto', 'KSS ASSISTÊNCIA 360°', 20) ?>
    </div>
</div>

<!-- Modal Esqueci Minha Senha -->
<div id="modal-esqueci-senha" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 relative">
        <!-- Botão Fechar -->
        <button onclick="fecharModalEsqueciSenha()" 
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <!-- Conteúdo do Modal -->
        <div class="mt-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-key mr-2 text-blue-600"></i>
                Esqueci minha senha
            </h3>
            
            <div class="space-y-4">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r">
                    <p class="text-sm text-gray-700 leading-relaxed">
                        Ao clicar em <strong>"Esqueci a senha"</strong> você será redirecionado para preencher o <strong>CPF/CNPJ do Locatário (inquilino)</strong> que consta no contrato de locação da imobiliária.
                    </p>
                </div>
                
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r">
                    <p class="text-sm text-gray-700 leading-relaxed">
                     O procedimento para recuperação de senha será enviado para o <strong>E-mail</strong> ou <strong>SMS</strong> informados no cadastro da imobiliária.
                    </p>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="mt-6 flex justify-end gap-3">
                <button onclick="fecharModalEsqueciSenha()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Fechar
                </button>
                <?php if (!empty($imobiliaria['url_base'])): ?>
                    <?php 
                    $urlBase = rtrim($imobiliaria['url_base'], '/');
                    $urlEsqueciSenha = $urlBase . '/kurole_include/ksi/clientes/acesso/login/#abrirEsqueciSenha';
                    ?>
                    <a href="<?= htmlspecialchars($urlEsqueciSenha) ?>" 
                       target="_blank"
                       class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Continuar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('senha');
    const passwordIcon = document.getElementById('password-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('fa-eye');
        passwordIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('fa-eye-slash');
        passwordIcon.classList.add('fa-eye');
    }
}

// Funções para o modal Esqueci Minha Senha
function abrirModalEsqueciSenha() {
    const modal = document.getElementById('modal-esqueci-senha');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevenir scroll do body
    }
}

function fecharModalEsqueciSenha() {
    const modal = document.getElementById('modal-esqueci-senha');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restaurar scroll do body
    }
}

// Fechar modal ao clicar fora dele
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-esqueci-senha');
    if (modal && e.target === modal) {
        fecharModalEsqueciSenha();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalEsqueciSenha();
    }
});

// Máscara para CPF/CNPJ
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        // CPF: 000.000.000-00
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        // CNPJ: 00.000.000/0000-00
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
    }
    
    e.target.value = value;
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>
