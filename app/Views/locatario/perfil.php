<?php
/**
 * View: Perfil do Locatário
 */
$title = 'Meu Perfil - Assistência 360°';
$currentPage = 'locatario-perfil';
ob_start();
?>

<!-- Header -->
<div class="mb-8">
    <!-- Botão Voltar -->
    <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
       class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        <span class="text-sm font-medium">Voltar para Dashboard</span>
    </a>
    
    <h1 class="text-2xl font-bold text-gray-900">
        <i class="fas fa-user mr-2"></i>
        Meu Perfil
    </h1>
    <p class="text-gray-600 mt-1">
        Visualize e gerencie suas informações pessoais
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Personal Information -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-user mr-2"></i>
                        Seus Dados
                    </h2>
                    <button onclick="editarDados()" 
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-edit mr-1"></i>
                        Editar
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Info -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Informações Pessoais</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-user text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($locatario['nome']) ?></p>
                                    <p class="text-xs text-gray-500">Nome completo</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-id-card text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($locatario['cpf']) ?></p>
                                    <p class="text-xs text-gray-500">CPF/CNPJ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Contato</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fab fa-whatsapp text-green-500 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= !empty($locatario['whatsapp']) ? htmlspecialchars($locatario['whatsapp']) : 'Não cadastrado' ?>
                                    </p>
                                    <p class="text-xs text-gray-500">WhatsApp</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= !empty($locatario['email']) ? htmlspecialchars($locatario['email']) : 'Não cadastrado' ?>
                                    </p>
                                    <p class="text-xs text-gray-500">E-mail</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Properties -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-home mr-2"></i>
                    Seus Imóveis
                </h2>
                <p class="text-sm text-gray-500 mt-1">Imóveis vinculados ao seu contrato</p>
            </div>
            
            <div class="p-6">
                <?php if (!empty($locatario['imoveis'])): ?>
                    <div class="space-y-4">
                        <?php foreach ($locatario['imoveis'] as $imovel): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-home text-gray-400 mr-2"></i>
                                            <h3 class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($imovel['endereco'] ?? 'Endereço não informado') ?>
                                            </h3>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <?= htmlspecialchars($imovel['bairro'] ?? '') ?> - 
                                            <?= htmlspecialchars($imovel['cidade'] ?? '') ?>/<?= htmlspecialchars($imovel['estado'] ?? '') ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            CEP: <?= htmlspecialchars($imovel['cep'] ?? '') ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Contrato: <?= htmlspecialchars($imovel['contrato'] ?? '') ?></p>
                                        <p class="text-xs text-gray-500">Cód: <?= htmlspecialchars($imovel['codigo'] ?? '') ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-home text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum imóvel encontrado</h3>
                        <p class="text-gray-500">Não foram encontrados imóveis vinculados ao seu contrato.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Account Status -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Status da Conta</h2>
            </div>
            
            <div class="p-6">
                <div class="text-center">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mb-4">
                        <i class="fas fa-check-circle mr-1"></i>
                        Ativo
                    </div>
                    
                    <div class="space-y-3 text-sm text-gray-600">
                        <div>
                            <p class="font-medium text-gray-900">Última sincronização</p>
                            <p><?= date('d/m/Y') ?></p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Dados sincronizados em</p>
                            <p><?= date('d/m/Y, H:i:s') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Ações Rápidas</h2>
            </div>
            
            <div class="p-6">
        <div class="space-y-3">
            <?php 
            $acessoPermitido = $locatario['acesso_permitido'] ?? true; // Por padrão, permite acesso (compatibilidade)
            if ($acessoPermitido): ?>
                <a href="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" 
                   class="flex items-center p-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    <i class="fas fa-plus-circle mr-3 text-green-600"></i>
                    <span>Nova Solicitação</span>
                </a>
            <?php else: ?>
                <div class="flex items-center p-3 text-gray-400 cursor-not-allowed rounded-lg" 
                     title="<?= htmlspecialchars($locatario['motivo_bloqueio'] ?? 'Acesso bloqueado') ?>">
                    <i class="fas fa-lock mr-3 text-gray-400"></i>
                    <span>Nova Solicitação Indisponível</span>
                </div>
            <?php endif; ?>
            <a href="<?= url($locatario['instancia'] . '/solicitacoes') ?>" 
               class="flex items-center p-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <i class="fas fa-list mr-3 text-blue-600"></i>
                <span>Minhas Solicitações</span>
            </a>
            <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
               class="flex items-center p-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <i class="fas fa-home mr-3 text-purple-600"></i>
                <span>Dashboard</span>
            </a>
        </div>
            </div>
        </div>
        
        <!-- Real Estate Info -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Imobiliária</h2>
            </div>
            
            <div class="p-6">
                <div class="text-center">
                    <?php if (!empty($locatario['imobiliaria_logo'])): ?>
                        <img src="<?= url('Public/uploads/logos/' . $locatario['imobiliaria_logo']) ?>" 
                             alt="<?= htmlspecialchars($locatario['imobiliaria_nome']) ?>" 
                             class="mx-auto h-12 w-auto mb-3"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="mx-auto h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center mb-3" style="display: none;">
                            <i class="fas fa-building text-white"></i>
                        </div>
                    <?php else: ?>
                        <div class="mx-auto h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center mb-3">
                            <i class="fas fa-building text-white"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($locatario['imobiliaria_nome']) ?>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        <?= htmlspecialchars($locatario['imobiliaria_nome_fantasia'] ?? '') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Editar Dados</h3>
                <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Alert Message -->
            <div id="alert-message" class="hidden mb-4 p-3 rounded-lg"></div>
            
            <form id="edit-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nome Completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nome" id="edit-nome" value="<?= htmlspecialchars($locatario['nome']) ?>" 
                           required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        WhatsApp <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="whatsapp" id="edit-whatsapp" value="<?= htmlspecialchars($locatario['whatsapp'] ?? '') ?>" 
                           placeholder="(00) 00000-0000"
                           required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Usado para notificações importantes</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" id="edit-email" value="<?= htmlspecialchars($locatario['email'] ?? '') ?>" 
                           placeholder="seu@email.com"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="fecharModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="submit-btn"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarDados() {
    document.getElementById('edit-modal').classList.remove('hidden');
    // Limpar mensagens anteriores
    hideAlert();
}

function fecharModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    hideAlert();
}

function showAlert(message, type = 'error') {
    const alertDiv = document.getElementById('alert-message');
    alertDiv.classList.remove('hidden', 'bg-red-50', 'text-red-800', 'border-red-200', 'bg-green-50', 'text-green-800', 'border-green-200');
    
    if (type === 'error') {
        alertDiv.classList.add('bg-red-50', 'text-red-800', 'border-red-200', 'border');
        alertDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
    } else if (type === 'success') {
        alertDiv.classList.add('bg-green-50', 'text-green-800', 'border-green-200', 'border');
        alertDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
    }
}

function hideAlert() {
    const alertDiv = document.getElementById('alert-message');
    alertDiv.classList.add('hidden');
}

document.getElementById('edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const originalBtnText = submitBtn.innerHTML;
    
    // Validar campos obrigatórios
    const nome = document.getElementById('edit-nome').value.trim();
    const whatsapp = document.getElementById('edit-whatsapp').value.trim();
    
    if (!nome) {
        showAlert('O nome é obrigatório', 'error');
        return;
    }
    
    if (!whatsapp) {
        showAlert('O WhatsApp é obrigatório', 'error');
        return;
    }
    
    // Validar formato do WhatsApp
    const whatsappLimpo = whatsapp.replace(/\D/g, '');
    if (whatsappLimpo.length < 10 || whatsappLimpo.length > 11) {
        showAlert('WhatsApp inválido. Use o formato (XX) XXXXX-XXXX', 'error');
        return;
    }
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    
    const formData = new FormData(this);
    const url = '<?= url($locatario['instancia'] . '/atualizar-perfil') ?>';
    
    console.log('Enviando requisição para:', url);
    console.log('Dados:', {
        nome: formData.get('nome'),
        email: formData.get('email'),
        whatsapp: formData.get('whatsapp')
    });
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        // Verificar se a resposta é OK
        if (!response.ok) {
            throw new Error('Erro HTTP: ' + response.status);
        }
        
        // Verificar se é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Se não for JSON, ler como texto para debug
            return response.text().then(text => {
                console.error('Resposta não é JSON:', text);
                throw new Error('Resposta inválida do servidor');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Atualizar os dados na tela
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            // Se sessão expirou, redirecionar para login
            if (data.redirect) {
                showAlert(data.message, 'error');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showAlert(data.message || 'Erro ao atualizar dados', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        showAlert('Erro ao conectar com o servidor. Tente novamente.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

// Máscara para WhatsApp no modal
document.getElementById('edit-whatsapp').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        if (value.length > 10) {
            // Formato: (XX) XXXXX-XXXX
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length > 6) {
            // Formato: (XX) XXXX-XXXX
            value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else if (value.length > 2) {
            // Formato: (XX) XXXX
            value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        } else {
            // Formato: (XX
            value = value.replace(/(\d*)/, '($1');
        }
    }
    
    e.target.value = value;
});

// Fechar modal ao clicar fora
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>
