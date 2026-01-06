<?php
$title = 'Novo Usuário';
$currentPage = 'usuarios';
$pageTitle = 'Novo Usuário';
ob_start();
?>

<div class="max-w-4xl">
    <div class="mb-6">
        <a href="<?= url('admin/usuarios') ?>" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>
            Voltar para lista
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="<?= url('admin/usuarios') ?>">
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
                            value="<?= htmlspecialchars($data['nome'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($data['cpf'] ?? '') ?>"
                            required
                            maxlength="14"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="000.000.000-00"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            E-mail <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            value="<?= htmlspecialchars($data['email'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($data['telefone'] ?? '') ?>"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="(00) 00000-0000"
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
                            id="cep"
                            value="<?= htmlspecialchars($data['cep'] ?? '') ?>"
                            required
                            maxlength="9"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="00000-000"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Endereço <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="endereco" 
                            id="endereco"
                            value="<?= htmlspecialchars($data['endereco'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($data['numero'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($data['complemento'] ?? '') ?>"
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
                            id="bairro"
                            value="<?= htmlspecialchars($data['bairro'] ?? '') ?>"
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
                            id="cidade"
                            value="<?= htmlspecialchars($data['cidade'] ?? '') ?>"
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
                            id="uf"
                            value="<?= htmlspecialchars($data['uf'] ?? '') ?>"
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
                            <option value="OPERADOR" <?= ($data['nivel_permissao'] ?? '') === 'OPERADOR' ? 'selected' : '' ?>>Operador</option>
                            <option value="ADMINISTRADOR" <?= ($data['nivel_permissao'] ?? '') === 'ADMINISTRADOR' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Operador: acesso às solicitações | Administrador: acesso total</p>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="<?= url('admin/usuarios') ?>" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Usuário
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

<script>
// Buscar CEP
document.getElementById('cep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    
    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('endereco').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('uf').value = data.uf;
                }
            })
            .catch(error => console.error('Erro ao buscar CEP:', error));
    }
});

// Máscaras
document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});

document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
    value = value.replace(/(\d)(\d{4})$/, '$1-$2');
    e.target.value = value;
});

document.getElementById('cep').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{5})(\d)/, '$1-$2');
    e.target.value = value;
});
</script>

