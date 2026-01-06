<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($imobiliaria['nome'] ?? 'KSS') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?= $imobiliaria['cor_primaria'] ?? '#3B82F6' ?>;
            --secondary-color: <?= $imobiliaria['cor_secundaria'] ?? '#1E40AF' ?>;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- DEBUG: Verificar valores -->
    <?php 
    $debugLocatario = isset($locatario) ? 'SIM' : 'NÃO';
    $debugWhatsapp = isset($locatario['whatsapp']) ? var_export($locatario['whatsapp'], true) : 'NÃO DEFINIDO';
    $debugWhatsappVazio = isset($locatario) && $locatario && (empty($locatario['whatsapp']) || trim($locatario['whatsapp']) === '');
    
    // Log direto no HTML para debug (visível no código fonte)
    echo "<!-- DEBUG MODAL: Locatario=$debugLocatario, WhatsApp=$debugWhatsapp, Vazio=" . ($debugWhatsappVazio ? 'SIM' : 'NÃO') . " -->\n";
    
    // TESTE: Forçar modal sempre para garantir que funciona
    // Depois remover esta linha e usar apenas $debugWhatsappVazio
    $mostrarModal = true; // TEMPORÁRIO: Forçar para teste
    
    if ($mostrarModal): 
    ?>
    <div id="modal-whatsapp-forced" style="position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0,0,0,0.9) !important; z-index: 999999 !important; display: flex !important; align-items: center !important; justify-content: center !important; visibility: visible !important; opacity: 1 !important;">
        <div style="background: white; padding: 40px; border-radius: 15px; max-width: 500px; width: 90%; z-index: 1000000; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <h2 style="color: #25D366; margin-bottom: 20px; font-size: 24px; display: flex; align-items: center; gap: 10px;">
                <i class="fab fa-whatsapp" style="font-size: 32px;"></i>
                Cadastrar WhatsApp
            </h2>
            <p style="margin-bottom: 20px; color: #333;">Para receber notificações importantes sobre suas solicitações, precisamos do seu número de WhatsApp.</p>
            <form id="form-whatsapp-forced" onsubmit="event.preventDefault(); salvarWhatsappForced(event);">
                <input type="text" 
                       id="whatsapp-input-forced" 
                       name="whatsapp" 
                       required
                       placeholder="(00) 00000-0000"
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; margin-bottom: 15px;">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" 
                            onclick="document.getElementById('modal-whatsapp-forced').style.display='none'"
                            style="padding: 12px 24px; border: 2px solid #ddd; background: white; border-radius: 8px; cursor: pointer;">
                        Depois
                    </button>
                    <button type="submit" 
                            style="padding: 12px 24px; background: #25D366; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                        Salvar WhatsApp
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        console.log('✅ MODAL FORÇADO CARREGADO!');
        // Garantir que o modal está visível
        setTimeout(function() {
            const modal = document.getElementById('modal-whatsapp-forced');
            if (modal) {
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                console.log('✅ Modal forçado está visível');
            } else {
                console.error('❌ Modal forçado não encontrado!');
            }
        }, 100);
        
        function salvarWhatsappForced(e) {
            const whatsapp = document.getElementById('whatsapp-input-forced').value;
            const formData = new FormData();
            formData.append('whatsapp', whatsapp);
            formData.append('nome', '<?= htmlspecialchars($locatario['nome'] ?? '') ?>');
            formData.append('email', '<?= htmlspecialchars($locatario['email'] ?? '') ?>');
            
            fetch('/<?= htmlspecialchars($imobiliaria['instancia']) ?>/atualizar-perfil', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('WhatsApp cadastrado com sucesso!');
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao salvar WhatsApp');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao conectar com o servidor. Tente novamente.');
            });
        }
    </script>
    <?php endif; ?>
    
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 32) ?>
                    <?php if (!empty($imobiliaria['logo'])): ?>
                        <div class="h-px w-6 bg-gray-300"></div>
                        <img class="h-8 w-auto" src="<?= url('Public/uploads/logos/' . $imobiliaria['logo']) ?>" alt="<?= htmlspecialchars($imobiliaria['nome']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="h-8 w-8 bg-blue-600 rounded flex items-center justify-center" style="display: none;">
                            <i class="fas fa-building text-white text-xs"></i>
                        </div>
                    <?php elseif (!empty($imobiliaria['nome'])): ?>
                        <div class="h-px w-6 bg-gray-300"></div>
                        <div class="h-8 w-8 bg-blue-600 rounded flex items-center justify-center">
                            <i class="fas fa-building text-white text-xs"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Olá, <?= htmlspecialchars($locatario['nome'] ?? 'Usuário') ?></span>
                    <a href="/<?= htmlspecialchars($imobiliaria['instancia']) ?>/logout" class="text-sm text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Seus Endereços -->
        <?php if (!empty($imoveis)): ?>
        <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-map-marker-alt mr-2"></i>
                Seus Endereços
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($imoveis as $imovel): ?>
                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start">
                        <i class="fas fa-home text-blue-500 mt-1 mr-3"></i>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">
                                <?= htmlspecialchars($imovel['endereco_logradouro']) ?>
                                <?php if (!empty($imovel['endereco_numero'])): ?>
                                    , <?= htmlspecialchars($imovel['endereco_numero']) ?>
                                <?php endif; ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <?= htmlspecialchars($imovel['endereco_bairro']) ?>, 
                                <?= htmlspecialchars($imovel['endereco_cidade']) ?> - 
                                <?= htmlspecialchars($imovel['endereco_estado']) ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                CEP: <?= htmlspecialchars($imovel['endereco_cep']) ?>
                            </p>
                            <?php if (!empty($imovel['contrato_cod'])): ?>
                            <p class="text-xs text-blue-600 mt-2">
                                Contrato: <?= htmlspecialchars($imovel['contrato_cod']) ?>
                                <?php if (!empty($imovel['contrato_dv'])): ?>
                                    -<?= htmlspecialchars($imovel['contrato_dv']) ?>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Ações Rápidas -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h2>
                <div class="space-y-3">
                    <a href="/<?= htmlspecialchars($imobiliaria['instancia']) ?>/nova-solicitacao" 
                       class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                        <i class="fas fa-plus text-green-600 mr-3"></i>
                        <span class="text-green-800 font-medium">Nova Solicitação</span>
                    </a>
                    <a href="/<?= htmlspecialchars($imobiliaria['instancia']) ?>/solicitacoes" 
                       class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <i class="fas fa-list text-blue-600 mr-3"></i>
                        <span class="text-blue-800 font-medium">Minhas Solicitações</span>
                    </a>
                    <a href="/<?= htmlspecialchars($imobiliaria['instancia']) ?>/perfil" 
                       class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                        <i class="fas fa-user text-purple-600 mr-3"></i>
                        <span class="text-purple-800 font-medium">Meu Perfil</span>
                    </a>
                </div>
            </div>

            <!-- Informações Importantes -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações Importantes</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-clock text-yellow-500 mt-1 mr-3"></i>
                        <span class="text-gray-700">As solicitações são processadas em até 24 horas</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-phone text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-700">Você receberá atualizações via WhatsApp</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-calendar text-blue-500 mt-1 mr-3"></i>
                        <span class="text-gray-700">Agendamentos podem ser cancelados até 1 dia antes</span>
                    </div>
                    
                    <!-- Endereço da Imobiliária -->
                    <?php if (!empty($imobiliaria['endereco_logradouro'])): ?>
                    <div class="flex items-start">
                        <i class="fas fa-map-marker-alt text-red-500 mt-1 mr-3"></i>
                        <div class="text-gray-700">
                            <div class="font-medium">Endereço da Imobiliária:</div>
                            <div class="text-sm">
                                <?= htmlspecialchars($imobiliaria['endereco_logradouro']) ?>
                                <?php if (!empty($imobiliaria['endereco_numero'])): ?>
                                    , <?= htmlspecialchars($imobiliaria['endereco_numero']) ?>
                                <?php endif; ?>
                                <?php if (!empty($imobiliaria['endereco_complemento'])): ?>
                                    - <?= htmlspecialchars($imobiliaria['endereco_complemento']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm">
                                <?php if (!empty($imobiliaria['endereco_bairro'])): ?>
                                    <?= htmlspecialchars($imobiliaria['endereco_bairro']) ?>
                                <?php endif; ?>
                                <?php if (!empty($imobiliaria['endereco_cidade'])): ?>
                                    - <?= htmlspecialchars($imobiliaria['endereco_cidade']) ?>
                                <?php endif; ?>
                                <?php if (!empty($imobiliaria['endereco_estado'])): ?>
                                    /<?= htmlspecialchars($imobiliaria['endereco_estado']) ?>
                                <?php endif; ?>
                                <?php if (!empty($imobiliaria['endereco_cep'])): ?>
                                    - CEP: <?= htmlspecialchars($imobiliaria['endereco_cep']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Seus Dados -->
        <?php if ($locatario): ?>
        <div class="mt-8 bg-white rounded-lg shadow-sm border p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-user mr-2"></i>
                    Seus Dados
                </h2>
                <a href="/<?= htmlspecialchars($imobiliaria['instancia']) ?>/perfil" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Informações Pessoais -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                        <i class="fas fa-user mr-2"></i>
                        Informações Pessoais
                    </h3>
                    <p class="text-gray-900 font-medium"><?= htmlspecialchars($locatario['nome']) ?></p>
                </div>
                
                <!-- WhatsApp -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                        <i class="fab fa-whatsapp mr-2 text-green-500"></i>
                        WhatsApp
                    </h3>
                    <p class="text-gray-900 font-medium">
                        <?= !empty($locatario['whatsapp']) ? htmlspecialchars($locatario['whatsapp']) : 'Não cadastrado' ?>
                    </p>
                    <?php if (empty($locatario['whatsapp'])): ?>
                    <p class="text-xs text-gray-500 mt-1">Usado para enviar notificações importantes sobre suas solicitações</p>
                    <!-- DEBUG: Forçar modal aqui também -->
                    <script>
                        console.log('🔍 WhatsApp está vazio! Deve mostrar modal.');
                        // Forçar exibição do modal
                        setTimeout(function() {
                            const modal = document.getElementById('modal-whatsapp-forced');
                            if (modal) {
                                modal.style.display = 'flex';
                                console.log('✅ Modal forçado encontrado e exibido!');
                            } else {
                                console.error('❌ Modal forçado NÃO encontrado no DOM!');
                            }
                        }, 500);
                    </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status da Conta -->
        <?php if ($locatario): ?>
        <div class="mt-8 bg-white rounded-lg shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Status da Conta</h2>
            
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    Ativo
                </span>
                <div class="text-sm text-gray-600">
                    <p>Última sincronização: <?= date('d/m/Y', strtotime($locatario['ultima_sincronizacao'])) ?></p>
                    <p class="text-xs text-gray-500">
                        Dados sincronizados em: <?= date('d/m/Y, H:i:s', strtotime($locatario['ultima_sincronizacao'])) ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal Cadastrar WhatsApp (versão original com Tailwind) -->
    <?php 
    $whatsappVazio = isset($locatario) && $locatario && (empty($locatario['whatsapp']) || trim($locatario['whatsapp']) === '');
    if ($whatsappVazio): 
    ?>
    <div id="modal-whatsapp" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4" style="display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important;">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full z-[10000] relative">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fab fa-whatsapp text-green-500 mr-2"></i>
                    Cadastrar WhatsApp
                </h3>
                <button onclick="fecharModalWhatsapp()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="px-6 py-4">
                <p class="text-gray-700 mb-4">
                    Para receber notificações importantes sobre suas solicitações, precisamos do seu número de WhatsApp.
                </p>
                
                <form id="form-whatsapp" onsubmit="salvarWhatsapp(event)">
                    <div class="mb-4">
                        <label for="whatsapp-input" class="block text-sm font-medium text-gray-700 mb-2">
                            Número do WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="whatsapp-input" 
                               name="whatsapp" 
                               required
                               placeholder="(00) 00000-0000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Formato: (XX) XXXXX-XXXX</p>
                    </div>
                    
                    <div id="alert-whatsapp" class="hidden mb-4 p-3 rounded"></div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="fecharModalWhatsapp()"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Depois
                        </button>
                        <button type="submit" 
                                id="btn-salvar-whatsapp"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Salvar WhatsApp
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Garantir que o modal apareça
        <?php 
        $whatsappVazioScript = isset($locatario) && $locatario && (empty($locatario['whatsapp']) || trim($locatario['whatsapp']) === '');
        if ($whatsappVazioScript): 
        ?>
        console.log('=== DEBUG MODAL WHATSAPP ===');
        console.log('WhatsApp vazio: SIM');
        console.log('Locatario existe: <?= isset($locatario) && $locatario ? "SIM" : "NÃO" ?>');
        console.log('WhatsApp value: <?= htmlspecialchars(isset($locatario['whatsapp']) ? var_export($locatario['whatsapp'], true) : "NÃO DEFINIDO") ?>');
        
        // Forçar exibição do modal ao carregar a página
        (function() {
            function mostrarModal() {
                const modal = document.getElementById('modal-whatsapp');
                console.log('Tentando exibir modal...', modal);
                if (modal) {
                    console.log('✅ Modal WhatsApp encontrado, exibindo...');
                    modal.style.display = 'flex';
                    modal.style.zIndex = '99999';
                    modal.style.position = 'fixed';
                    modal.style.top = '0';
                    modal.style.left = '0';
                    modal.style.right = '0';
                    modal.style.bottom = '0';
                    modal.style.width = '100vw';
                    modal.style.height = '100vh';
                    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                    modal.style.visibility = 'visible';
                    modal.style.opacity = '1';
                    console.log('Modal estilos aplicados:', {
                        display: modal.style.display,
                        zIndex: modal.style.zIndex,
                        position: modal.style.position
                    });
                } else {
                    console.error('❌ Modal WhatsApp não encontrado no DOM!');
                    console.log('Elementos com ID modal:', document.querySelectorAll('[id*="modal"]'));
                }
            }
            
            // Tentar imediatamente
            mostrarModal();
            
            // Tentar após DOM carregar
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', mostrarModal);
            } else {
                // DOM já carregado
                setTimeout(mostrarModal, 50);
            }
            
            // Tentar após delays maiores (fallback)
            setTimeout(mostrarModal, 100);
            setTimeout(mostrarModal, 500);
            setTimeout(mostrarModal, 1000);
        })();
        
        // Máscara para WhatsApp
        document.getElementById('whatsapp-input').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        function fecharModalWhatsapp() {
            document.getElementById('modal-whatsapp').style.display = 'none';
        }

        function mostrarAlerta(mensagem, tipo) {
            const alertDiv = document.getElementById('alert-whatsapp');
            alertDiv.className = `mb-4 p-3 rounded ${tipo === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'}`;
            alertDiv.textContent = mensagem;
            alertDiv.classList.remove('hidden');
            
            setTimeout(() => {
                alertDiv.classList.add('hidden');
            }, 5000);
        }

        function salvarWhatsapp(event) {
            event.preventDefault();
            
            const whatsapp = document.getElementById('whatsapp-input').value.trim();
            const btnSalvar = document.getElementById('btn-salvar-whatsapp');
            const originalText = btnSalvar.innerHTML;
            
            // Validar WhatsApp
            const whatsappLimpo = whatsapp.replace(/\D/g, '');
            if (whatsappLimpo.length < 10 || whatsappLimpo.length > 11) {
                mostrarAlerta('WhatsApp inválido. Use o formato (XX) XXXXX-XXXX', 'error');
                return;
            }
            
            // Desabilitar botão
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
            
            // Enviar requisição
            const formData = new FormData();
            formData.append('whatsapp', whatsapp);
            formData.append('nome', '<?= htmlspecialchars($locatario['nome'] ?? '') ?>');
            formData.append('email', '<?= htmlspecialchars($locatario['email'] ?? '') ?>');
            
            fetch('/<?= htmlspecialchars($imobiliaria['instancia']) ?>/atualizar-perfil', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta('WhatsApp cadastrado com sucesso!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mostrarAlerta(data.message || 'Erro ao salvar WhatsApp', 'error');
                    btnSalvar.disabled = false;
                    btnSalvar.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao conectar com o servidor. Tente novamente.', 'error');
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = originalText;
            });
        }
        <?php else: ?>
        console.log('=== MODAL NÃO DEVE APARECER ===');
        console.log('WhatsApp vazio: NÃO');
        console.log('Valor WhatsApp:', '<?= htmlspecialchars(var_export($whatsappValue, true)) ?>');
        <?php endif; ?>
    </script>
    
    <?php if ($whatsappVazio): ?>
    <!-- Teste: Verificar se modal está no HTML -->
    <script>
        window.addEventListener('load', function() {
            const modal = document.getElementById('modal-whatsapp');
            if (!modal) {
                console.error('🚨 ERRO CRÍTICO: Modal não encontrado no HTML!');
                alert('ERRO: Modal WhatsApp não foi renderizado no HTML. Verifique os logs do servidor.');
            } else {
                console.log('✅ Modal encontrado no HTML');
                // Forçar exibição uma última vez
                modal.style.cssText = 'display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 99999 !important; background-color: rgba(0, 0, 0, 0.5) !important; visibility: visible !important; opacity: 1 !important;';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>