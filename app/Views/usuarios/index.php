<?php
$title = 'Gerenciamento de Usuários';
$currentPage = 'usuarios';
$pageTitle = 'Usuários';
ob_start();
?>

<!-- Barra de Ações -->
<div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="flex-1 w-full sm:w-auto">
        <form method="GET" action="<?= url('admin/usuarios') ?>" class="flex gap-2">
            <input 
                type="text" 
                name="busca" 
                placeholder="Buscar por nome, email, CPF ou código..." 
                value="<?= htmlspecialchars($busca ?? '') ?>"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <button 
        onclick="abrirOffcanvasNovoUsuario()" 
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>
        Novo Usuário
    </button>
</div>

<!-- Tabela de Usuários -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Cadastro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível Acesso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                        Nenhum usuário encontrado
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #<?= str_pad($usuario['id'], 4, '0', STR_PAD_LEFT) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($usuario['nome']) ?></div>
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($usuario['telefone'] ?? '') ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($usuario['email']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($usuario['cpf'] ?? '') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($usuario['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?= $usuario['nivel_permissao'] === 'ADMINISTRADOR' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                            <?= htmlspecialchars($usuario['nivel_permissao']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                value="" 
                                class="sr-only peer" 
                                <?= $usuario['status'] === 'ATIVO' ? 'checked' : '' ?>
                                onchange="toggleStatus(<?= $usuario['id'] ?>)"
                            >
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <button 
                                onclick="abrirOffcanvasEditar(<?= $usuario['id'] ?>)" 
                                class="text-blue-600 hover:text-blue-900" 
                                title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                onclick="abrirOffcanvasTrocarSenha(<?= $usuario['id'] ?>)" 
                                class="text-yellow-600 hover:text-yellow-900" 
                                title="Trocar Senha">
                                <i class="fas fa-key"></i>
                            </button>
                            <button 
                                onclick="abrirOffcanvasRestringir(<?= $usuario['id'] ?>)" 
                                class="text-orange-600 hover:text-orange-900" 
                                title="Restringir">
                                <i class="fas fa-ban"></i>
                            </button>
                            <button 
                                onclick="excluirUsuario(<?= $usuario['id'] ?>)" 
                                class="text-red-600 hover:text-red-900" 
                                title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Offcanvas para Editar Usuário -->
<div id="offcanvasEditar" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasEditar()"></div>
    <div id="offcanvasEditarPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[900px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-user-edit text-gray-600"></i>
                    <h2 class="text-xl font-bold text-gray-900">Editar Usuário</h2>
                </div>
                <button onclick="fecharOffcanvasEditar()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="offcanvasEditarContent" class="p-6">
            <div id="loadingEditar" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando dados do usuário...</p>
                </div>
            </div>
            <div id="formEditar" class="hidden"></div>
        </div>
    </div>
</div>

<!-- Offcanvas para Trocar Senha -->
<div id="offcanvasTrocarSenha" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasTrocarSenha()"></div>
    <div id="offcanvasTrocarSenhaPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[500px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-key text-gray-600"></i>
                    <h2 class="text-xl font-bold text-gray-900">Trocar Senha</h2>
                </div>
                <button onclick="fecharOffcanvasTrocarSenha()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <form id="formTrocarSenha" onsubmit="trocarSenha(event)">
                <input type="hidden" id="trocarSenhaUsuarioId" name="usuario_id" value="">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nova Senha <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="novaSenha" 
                        name="nova_senha" 
                        required 
                        minlength="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Mínimo de 6 caracteres"
                    >
                    <p class="text-xs text-gray-500 mt-1">A senha deve ter pelo menos 6 caracteres</p>
                </div>
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                    <button 
                        type="button" 
                        onclick="fecharOffcanvasTrocarSenha()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Offcanvas para Novo Usuário -->
<div id="offcanvasNovoUsuario" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasNovoUsuario()"></div>
    <div id="offcanvasNovoUsuarioPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[900px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-user-plus text-gray-600"></i>
                    <h2 class="text-xl font-bold text-gray-900">Novo Usuário</h2>
                </div>
                <button onclick="fecharOffcanvasNovoUsuario()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <form id="formNovoUsuario" onsubmit="salvarNovoUsuario(event)">
                <!-- Dados Pessoais -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Pessoais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nome Completo <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="nome" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                CPF <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="cpf" 
                                required
                                maxlength="14"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="000.000.000-00"
                                oninput="aplicarMascaraCPF(this)"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                E-mail <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefone <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="telefone" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="(00) 00000-0000"
                                oninput="aplicarMascaraTelefone(this)"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Senha <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="senha" 
                                required
                                minlength="6"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Endereço do Usuário</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                CEP <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="cep" 
                                id="cepNovo"
                                required
                                maxlength="9"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="00000-000"
                                oninput="aplicarMascaraCEP(this)"
                                onblur="buscarCEP(this.value, 'enderecoNovo', 'bairroNovo', 'cidadeNovo', 'ufNovo')"
                            >
                        </div>

                        <div></div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Endereço <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="endereco" 
                                id="enderecoNovo"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Número <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="numero" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Complemento
                            </label>
                            <input 
                                type="text" 
                                name="complemento" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bairro <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="bairro" 
                                id="bairroNovo"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cidade <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="cidade" 
                                id="cidadeNovo"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                UF <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="uf" 
                                id="ufNovo"
                                required
                                maxlength="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                            >
                        </div>
                    </div>
                </div>

                <!-- Credenciais e Permissões -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Credenciais e Permissões</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nível de Acesso <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="nivel_permissao" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Selecione...</option>
                                <option value="OPERADOR">Operador</option>
                                <option value="ADMINISTRADOR">Administrador</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Operador: acesso às solicitações | Administrador: acesso total</p>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                    <button 
                        type="button" 
                        onclick="fecharOffcanvasNovoUsuario()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Offcanvas para Restringir Usuário -->
<div id="offcanvasRestringir" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasRestringir()"></div>
    <div id="offcanvasRestringirPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[500px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-ban text-gray-600"></i>
                    <h2 class="text-xl font-bold text-gray-900">Restringir Usuário</h2>
                </div>
                <button onclick="fecharOffcanvasRestringir()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <div id="loadingRestringir" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando dados do usuário...</p>
                </div>
            </div>
            <div id="formRestringir" class="hidden">
                <div class="mb-6">
                    <p class="text-gray-700 mb-4">Você está prestes a alterar o status deste usuário:</p>
                    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
                        <p class="text-sm text-gray-600 mb-1">Nome:</p>
                        <p class="text-lg font-medium text-gray-900" id="restringirNome"></p>
                        <p class="text-sm text-gray-600 mb-1 mt-3">Status Atual:</p>
                        <p class="text-lg font-medium" id="restringirStatusAtual"></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Novo Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="novoStatus" 
                            name="status" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                    <button 
                        type="button" 
                        onclick="fecharOffcanvasRestringir()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button 
                        type="button" 
                        onclick="confirmarRestringir()" 
                        class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Confirmar Alteração
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

<script>
let usuarioEditandoId = null;
let usuarioRestringindoId = null;

// ========== FUNÇÕES DE EDIÇÃO ==========
function abrirOffcanvasEditar(usuarioId) {
    const offcanvas = document.getElementById('offcanvasEditar');
    const panel = document.getElementById('offcanvasEditarPanel');
    const loadingEditar = document.getElementById('loadingEditar');
    const formEditar = document.getElementById('formEditar');
    
    usuarioEditandoId = usuarioId;
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loadingEditar.classList.remove('hidden');
    formEditar.classList.add('hidden');
    
    fetch(`<?= url('admin/usuarios/') ?>${usuarioId}/api`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarFormularioEditar({
                    ...data.usuario,
                    paginasDisponiveis: data.paginasDisponiveis || [],
                    paginasPermitidas: data.paginasPermitidas || []
                });
            } else {
                formEditar.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                        <p class="text-gray-600">${data.message || 'Erro ao carregar dados do usuário'}</p>
                    </div>
                `;
            }
            loadingEditar.classList.add('hidden');
            formEditar.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Erro:', error);
            formEditar.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                    <p class="text-gray-600">Erro ao carregar dados do usuário</p>
                </div>
            `;
            loadingEditar.classList.add('hidden');
            formEditar.classList.remove('hidden');
        });
}

function fecharOffcanvasEditar() {
    const offcanvas = document.getElementById('offcanvasEditar');
    const panel = document.getElementById('offcanvasEditarPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        offcanvas.classList.add('hidden');
        usuarioEditandoId = null;
    }, 300);
}

// Função auxiliar para renderizar páginas disponíveis
function renderizarPaginasDisponiveis(paginasDisponiveis, paginasPermitidas) {
    const paginasPadrao = {
        // GERAL
        'dashboard': 'Dashboard',
        'relatorios': 'Relatórios',
        'kanban': 'Kanban',
        'upload': 'Upload',
        // OPERAÇÕES
        'solicitacoes-manuais': 'Solicitações Manuais',
        'templates-whatsapp': 'Templates WhatsApp',
        'whatsapp-instances': 'Instâncias WhatsApp',
        // ADMINISTRAÇÃO
        'imobiliarias': 'Imobiliárias',
        'usuarios': 'Usuários',
        'categorias': 'Categorias',
        'status': 'Status',
        'condicoes': 'Condições/Pendências',
        // SISTEMA
        'configuracoes': 'Configurações',
        'cron-jobs': 'Cron Jobs',
        'logs': 'Visualizador de Logs',
        'migracoes': 'Migrações'
    };
    
    // Se paginasDisponiveis é um objeto (vindo da API), converter para array
    let paginas = [];
    if (paginasDisponiveis && typeof paginasDisponiveis === 'object' && !Array.isArray(paginasDisponiveis)) {
        paginas = Object.keys(paginasDisponiveis).map(codigo => ({
            codigo: codigo,
            nome: paginasDisponiveis[codigo]
        }));
    } else if (Array.isArray(paginasDisponiveis) && paginasDisponiveis.length > 0) {
        paginas = paginasDisponiveis;
    } else {
        // Usar padrão se não vier nada
        paginas = Object.keys(paginasPadrao).map(codigo => ({
            codigo: codigo,
            nome: paginasPadrao[codigo]
        }));
    }
    
    // Organizar páginas por seções
    const secoes = {
        'GERAL': ['dashboard', 'relatorios', 'kanban', 'upload'],
        'OPERAÇÕES': ['solicitacoes-manuais', 'templates-whatsapp', 'whatsapp-instances'],
        'ADMINISTRAÇÃO': ['imobiliarias', 'usuarios', 'categorias', 'status', 'condicoes'],
        'SISTEMA': ['configuracoes', 'cron-jobs', 'logs', 'migracoes']
    };
    
    let html = `
        <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
            <div class="mb-3">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="selecionar-todas-paginas-editar"
                        class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        onchange="selecionarTodasPaginas(this.checked)"
                    >
                    <span class="text-sm font-medium text-gray-700">Selecionar Todas</span>
                </label>
            </div>
    `;
    
    // Renderizar cada seção
    Object.keys(secoes).forEach(nomeSecao => {
        html += `
            <div class="mb-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">
                    ${nomeSecao}
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
        `;
        
        secoes[nomeSecao].forEach(codigo => {
            const nome = paginasPadrao[codigo] || codigo;
            const checked = paginasPermitidas && paginasPermitidas.includes(codigo) ? 'checked' : '';
            
            html += `
                <label class="flex items-center p-2 bg-white rounded border border-gray-200 hover:bg-blue-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="paginas_permitidas[]"
                        value="${codigo}"
                        class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 pagina-checkbox-editar"
                        ${checked}
                        onchange="atualizarSelecionarTodas()"
                    >
                    <span class="text-sm text-gray-700">${escapeHtml(nome)}</span>
                </label>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    html += `
        </div>
    `;
    
    return html;
}

function renderizarFormularioEditar(usuario) {
    const formEditar = document.getElementById('formEditar');
    
    const html = `
        <form id="formEditarUsuario" onsubmit="salvarEdicao(event)">
            <!-- Dados Pessoais -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Pessoais</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nome Completo <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="nome" 
                            value="${escapeHtml(usuario.nome || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            CPF <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="cpf" 
                            value="${escapeHtml(usuario.cpf || '')}"
                            required
                            maxlength="14"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="000.000.000-00"
                            oninput="aplicarMascaraCPF(this)"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            E-mail <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            value="${escapeHtml(usuario.email || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Telefone <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="telefone" 
                            value="${escapeHtml(usuario.telefone || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="(00) 00000-0000"
                            oninput="aplicarMascaraTelefone(this)"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nova Senha (deixe em branco para não alterar)
                        </label>
                        <input 
                            type="password" 
                            name="senha" 
                            minlength="6"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Endereço do Usuário</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            CEP <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="cep" 
                            id="cepEditar"
                            value="${escapeHtml(usuario.cep || '')}"
                            required
                            maxlength="9"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="00000-000"
                            oninput="aplicarMascaraCEP(this)"
                            onblur="buscarCEP(this.value, 'enderecoEditar', 'bairroEditar', 'cidadeEditar', 'ufEditar')"
                        >
                    </div>

                    <div></div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Endereço <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="endereco" 
                            id="enderecoEditar"
                            value="${escapeHtml(usuario.endereco || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Número <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="numero" 
                            value="${escapeHtml(usuario.numero || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Complemento
                        </label>
                        <input 
                            type="text" 
                            name="complemento" 
                            value="${escapeHtml(usuario.complemento || '')}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Bairro <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="bairro" 
                            id="bairroEditar"
                            value="${escapeHtml(usuario.bairro || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cidade <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="cidade" 
                            id="cidadeEditar"
                            value="${escapeHtml(usuario.cidade || '')}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            UF <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="uf" 
                            id="ufEditar"
                            value="${escapeHtml(usuario.uf || '')}"
                            required
                            maxlength="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                        >
                    </div>
                </div>
            </div>

            <!-- Credenciais e Permissões -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Credenciais e Permissões</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nível de Acesso <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="nivel_permissao" 
                            id="nivel_permissao_editar"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="toggleSecaoPaginas(this.value)"
                        >
                            <option value="">Selecione...</option>
                            <option value="OPERADOR" ${usuario.nivel_permissao === 'OPERADOR' ? 'selected' : ''}>Operador</option>
                            <option value="ADMINISTRADOR" ${usuario.nivel_permissao === 'ADMINISTRADOR' ? 'selected' : ''}>Administrador</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="status" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="ATIVO" ${usuario.status === 'ATIVO' ? 'selected' : ''}>Ativo</option>
                            <option value="INATIVO" ${usuario.status === 'INATIVO' ? 'selected' : ''}>Inativo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Páginas/Abas Permitidas (apenas para operadores) -->
            <div id="paginas-section-editar" class="mb-8" style="display: ${usuario.nivel_permissao === 'OPERADOR' ? 'block' : 'none'};">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Páginas/Abas Permitidas</h3>
                <p class="text-sm text-gray-600 mb-4">Selecione as abas que aparecerão no menu lateral para este operador:</p>
                
                ${renderizarPaginasDisponiveis(usuario.paginasDisponiveis || [], usuario.paginasPermitidas || [])}
                
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Se nenhuma página for selecionada, o operador não verá nenhuma aba no menu lateral.
                </p>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <button 
                    type="button" 
                    onclick="fecharOffcanvasEditar()" 
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
            </div>
        </form>
    `;
    
    formEditar.innerHTML = html;
    
    // Aguardar um pouco para garantir que o DOM foi atualizado antes de anexar event listeners
    setTimeout(() => {
        const nivelSelect = document.getElementById('nivel_permissao_editar');
        if (nivelSelect) {
            nivelSelect.addEventListener('change', function() {
                toggleSecaoPaginas(this.value);
            });
        }
        
        const selecionarTodas = document.getElementById('selecionar-todas-paginas-editar');
        if (selecionarTodas) {
            selecionarTodas.addEventListener('change', function() {
                selecionarTodasPaginas(this.checked);
            });
        }
        
        document.querySelectorAll('.pagina-checkbox-editar').forEach(checkbox => {
            checkbox.addEventListener('change', atualizarSelecionarTodas);
        });
    }, 100);
}

function toggleSecaoPaginas(nivelPermissao) {
    const secao = document.getElementById('paginas-section-editar');
    if (secao) {
        secao.style.display = nivelPermissao === 'OPERADOR' ? 'block' : 'none';
    }
}

function selecionarTodasPaginas(checked) {
    const checkboxes = document.querySelectorAll('.pagina-checkbox-editar');
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
}

function atualizarSelecionarTodas() {
    const checkboxes = document.querySelectorAll('.pagina-checkbox-editar');
    const todosChecked = Array.from(checkboxes).every(cb => cb.checked);
    const selecionarTodas = document.getElementById('selecionar-todas-paginas-editar');
    if (selecionarTodas) {
        selecionarTodas.checked = todosChecked;
    }
}

function salvarEdicao(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Coletar checkboxes de páginas permitidas
    const paginasPermitidas = [];
    document.querySelectorAll('.pagina-checkbox-editar:checked').forEach(checkbox => {
        paginasPermitidas.push(checkbox.value);
    });
    
    const data = Object.fromEntries(formData);
    data.paginas_permitidas = paginasPermitidas;
    
    fetch(`<?= url('admin/usuarios/') ?>${usuarioEditandoId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message || 'Usuário atualizado com sucesso');
            fecharOffcanvasEditar();
            location.reload();
        } else {
            alert(result.error || 'Erro ao atualizar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar usuário');
    });
}

// ========== FUNÇÕES DE TROCAR SENHA ==========
function abrirOffcanvasTrocarSenha(usuarioId) {
    const offcanvas = document.getElementById('offcanvasTrocarSenha');
    const panel = document.getElementById('offcanvasTrocarSenhaPanel');
    
    document.getElementById('trocarSenhaUsuarioId').value = usuarioId;
    document.getElementById('novaSenha').value = '';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    document.getElementById('novaSenha').focus();
}

function fecharOffcanvasTrocarSenha() {
    const offcanvas = document.getElementById('offcanvasTrocarSenha');
    const panel = document.getElementById('offcanvasTrocarSenhaPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => offcanvas.classList.add('hidden'), 300);
}

function trocarSenha(event) {
    event.preventDefault();
    
    const usuarioId = document.getElementById('trocarSenhaUsuarioId').value;
    const novaSenha = document.getElementById('novaSenha').value;
    
    fetch(`<?= url('admin/usuarios/') ?>${usuarioId}/resetar-senha`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ nova_senha: novaSenha })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            fecharOffcanvasTrocarSenha();
            location.reload();
        } else {
            alert(data.error || 'Erro ao trocar senha');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao trocar senha');
    });
}

// ========== FUNÇÕES DE RESTRINGIR ==========
function abrirOffcanvasRestringir(usuarioId) {
    const offcanvas = document.getElementById('offcanvasRestringir');
    const panel = document.getElementById('offcanvasRestringirPanel');
    const loadingRestringir = document.getElementById('loadingRestringir');
    const formRestringir = document.getElementById('formRestringir');
    
    usuarioRestringindoId = usuarioId;
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loadingRestringir.classList.remove('hidden');
    formRestringir.classList.add('hidden');
    
    fetch(`<?= url('admin/usuarios/') ?>${usuarioId}/api`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('restringirNome').textContent = data.usuario.nome;
                const statusAtual = data.usuario.status === 'ATIVO' ? 'Ativo' : 'Inativo';
                const statusClass = data.usuario.status === 'ATIVO' ? 'text-green-600' : 'text-red-600';
                document.getElementById('restringirStatusAtual').textContent = statusAtual;
                document.getElementById('restringirStatusAtual').className = `text-lg font-medium ${statusClass}`;
                document.getElementById('novoStatus').value = data.usuario.status === 'ATIVO' ? 'INATIVO' : 'ATIVO';
            } else {
                alert(data.message || 'Erro ao carregar dados do usuário');
                fecharOffcanvasRestringir();
            }
            loadingRestringir.classList.add('hidden');
            formRestringir.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados do usuário');
            fecharOffcanvasRestringir();
        });
}

function fecharOffcanvasRestringir() {
    const offcanvas = document.getElementById('offcanvasRestringir');
    const panel = document.getElementById('offcanvasRestringirPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        offcanvas.classList.add('hidden');
        usuarioRestringindoId = null;
    }, 300);
}

function confirmarRestringir() {
    const novoStatus = document.getElementById('novoStatus').value;
    
    fetch(`<?= url('admin/usuarios/') ?>${usuarioRestringindoId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            fecharOffcanvasRestringir();
            location.reload();
        } else {
            alert(data.error || 'Erro ao alterar status');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao alterar status');
    });
}

// ========== FUNÇÕES AUXILIARES ==========
function toggleStatus(usuarioId) {
    if (confirm('Deseja realmente alterar o status deste usuário?')) {
        fetch(`<?= url('admin/usuarios/') ?>${usuarioId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.error || 'Erro ao atualizar status');
                location.reload();
            }
        })
        .catch(error => {
            alert('Erro ao atualizar status');
            console.error('Error:', error);
            location.reload();
        });
    } else {
        location.reload();
    }
}

function excluirUsuario(usuarioId) {
    if (confirm('Deseja realmente excluir este usuário? Esta ação não pode ser desfeita.')) {
        fetch(`<?= url('admin/usuarios/') ?>${usuarioId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.error || 'Erro ao excluir usuário');
            }
        })
        .catch(error => {
            alert('Erro ao excluir usuário');
            console.error('Error:', error);
        });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function aplicarMascaraCPF(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = value;
}

function aplicarMascaraTelefone(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
    value = value.replace(/(\d)(\d{4})$/, '$1-$2');
    input.value = value;
}

function aplicarMascaraCEP(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = value;
}

function buscarCEP(cep, enderecoId, bairroId, cidadeId, ufId) {
    const cepLimpo = cep.replace(/\D/g, '');
    
    if (cepLimpo.length === 8) {
        fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById(enderecoId).value = data.logradouro || '';
                    document.getElementById(bairroId).value = data.bairro || '';
                    document.getElementById(cidadeId).value = data.localidade || '';
                    document.getElementById(ufId).value = data.uf || '';
                }
            })
            .catch(error => console.error('Erro ao buscar CEP:', error));
    }
}

// ========== FUNÇÕES DE NOVO USUÁRIO ==========
function abrirOffcanvasNovoUsuario() {
    const offcanvas = document.getElementById('offcanvasNovoUsuario');
    const panel = document.getElementById('offcanvasNovoUsuarioPanel');
    
    // Limpar formulário
    document.getElementById('formNovoUsuario').reset();
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    // Focar no primeiro campo
    setTimeout(() => {
        const primeiroCampo = document.querySelector('#formNovoUsuario input[name="nome"]');
        if (primeiroCampo) primeiroCampo.focus();
    }, 100);
}

function fecharOffcanvasNovoUsuario() {
    const offcanvas = document.getElementById('offcanvasNovoUsuario');
    const panel = document.getElementById('offcanvasNovoUsuarioPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        offcanvas.classList.add('hidden');
        // Limpar formulário ao fechar
        document.getElementById('formNovoUsuario').reset();
    }, 300);
}

function salvarNovoUsuario(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch(`<?= url('admin/usuarios') ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message || 'Usuário criado com sucesso');
            fecharOffcanvasNovoUsuario();
            location.reload();
        } else {
            alert(result.error || 'Erro ao criar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao criar usuário');
    });
}

// Fechar offcanvas com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        fecharOffcanvasEditar();
        fecharOffcanvasTrocarSenha();
        fecharOffcanvasRestringir();
        fecharOffcanvasNovoUsuario();
    }
});
</script>

