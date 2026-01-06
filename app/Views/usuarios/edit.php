<?php
$title = 'Editar Usuário';
$currentPage = 'usuarios';
$pageTitle = 'Editar Usuário';
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
        <form method="POST" action="<?= url("admin/usuarios/{$usuario['id']}") ?>">
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
                            value="<?= htmlspecialchars($usuario['nome']) ?>"
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
                            value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($usuario['email']) ?>"
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
                            value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="(00) 00000-0000"
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
                            id="cep"
                            value="<?= htmlspecialchars($usuario['cep'] ?? '') ?>"
                            required
                            maxlength="9"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="00000-000"
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
                            id="endereco"
                            value="<?= htmlspecialchars($usuario['endereco'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($usuario['numero'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($usuario['complemento'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($usuario['bairro'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($usuario['uf'] ?? '') ?>"
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
                            id="nivel_permissao"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Selecione...</option>
                            <option value="OPERADOR" <?= $usuario['nivel_permissao'] === 'OPERADOR' ? 'selected' : '' ?>>Operador</option>
                            <option value="ADMINISTRADOR" <?= $usuario['nivel_permissao'] === 'ADMINISTRADOR' ? 'selected' : '' ?>>Administrador</option>
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
                            <option value="ATIVO" <?= $usuario['status'] === 'ATIVO' ? 'selected' : '' ?>>Ativo</option>
                            <option value="INATIVO" <?= $usuario['status'] === 'INATIVO' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Páginas/Abas Permitidas (apenas para operadores) -->
            <div id="paginas-section" class="mb-8" style="display: <?= ($usuario['nivel_permissao'] ?? '') === 'OPERADOR' ? 'block' : 'none' ?>;">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Páginas/Abas Permitidas</h3>
                <p class="text-sm text-gray-600 mb-4">Selecione as abas que aparecerão no menu lateral para este operador:</p>
                
                <?php 
                $paginasPermitidas = $paginasPermitidas ?? [];
                $paginasDisponiveis = $paginasDisponiveis ?? [
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
                ?>
                
                <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <div class="mb-3">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="selecionar-todas-paginas"
                                class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span class="text-sm font-medium text-gray-700">Selecionar Todas</span>
                        </label>
                    </div>
                    
                    <?php
                    // Organizar páginas por seções
                    $secoes = [
                        'GERAL' => ['dashboard', 'relatorios', 'kanban', 'upload'],
                        'OPERAÇÕES' => ['solicitacoes-manuais', 'templates-whatsapp', 'whatsapp-instances'],
                        'ADMINISTRAÇÃO' => ['imobiliarias', 'usuarios', 'categorias', 'status', 'condicoes'],
                        'SISTEMA' => ['configuracoes', 'cron-jobs', 'logs', 'migracoes']
                    ];
                    ?>
                    
                    <?php foreach ($secoes as $nomeSecao => $paginasSecao): ?>
                        <div class="mb-4">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">
                                <?= $nomeSecao ?>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                <?php foreach ($paginasSecao as $paginaCodigo): ?>
                                    <?php if (isset($paginasDisponiveis[$paginaCodigo])): ?>
                                        <label class="flex items-center p-2 bg-white rounded border border-gray-200 hover:bg-blue-50 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                name="paginas_permitidas[]"
                                                value="<?= $paginaCodigo ?>"
                                                class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 pagina-checkbox"
                                                <?= in_array($paginaCodigo, $paginasPermitidas) ? 'checked' : '' ?>
                                            >
                                            <span class="text-sm text-gray-700"><?= htmlspecialchars($paginasDisponiveis[$paginaCodigo]) ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Se nenhuma página for selecionada, o operador não verá nenhuma aba no menu lateral.
                </p>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="<?= url('admin/usuarios') ?>" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
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

// Mostrar/ocultar seção de páginas conforme nível de permissão
const nivelSelect = document.getElementById('nivel_permissao');
if (nivelSelect) {
    function atualizarVisibilidadePaginas() {
        const paginasSection = document.getElementById('paginas-section');
        if (paginasSection) {
            paginasSection.style.display = nivelSelect.value === 'OPERADOR' ? 'block' : 'none';
        }
    }
    
    // Aplicar visibilidade inicial
    atualizarVisibilidadePaginas();
    
    // Aplicar quando mudar
    nivelSelect.addEventListener('change', atualizarVisibilidadePaginas);
}

// Selecionar todas as páginas
document.getElementById('selecionar-todas-paginas').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.pagina-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Atualizar botão "Selecionar Todas" quando checkboxes individuais mudarem
document.querySelectorAll('.pagina-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const todosChecked = document.querySelectorAll('.pagina-checkbox:checked').length === document.querySelectorAll('.pagina-checkbox').length;
        document.getElementById('selecionar-todas-paginas').checked = todosChecked;
    });
});
</script>

