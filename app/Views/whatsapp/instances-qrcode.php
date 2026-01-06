<?php
ob_start();
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Conectar Instância: <?= htmlspecialchars($instance['nome']) ?></h3>
        <a href="<?= url('admin/whatsapp-instances') ?>" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    <div class="max-w-md mx-auto">
        <!-- Token Bearer -->
        <?php if (!empty($instance['token'])): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-blue-800 mb-1">Token Bearer:</label>
                    <div class="flex items-center space-x-2">
                        <code class="text-xs bg-white px-3 py-2 rounded font-mono flex-1 break-all"><?= htmlspecialchars($instance['token']) ?></code>
                        <button onclick="copiarToken('<?= htmlspecialchars($instance['token']) ?>')" 
                                class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                                title="Copiar token">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="bg-gray-50 rounded-lg p-6 text-center">
            <?php if (!empty($instance['qrcode'])): ?>
                <div class="mb-4">
                    <img id="qrcode-img" 
                         src="data:image/png;base64,<?= htmlspecialchars($instance['qrcode']) ?>" 
                         alt="QR Code WhatsApp"
                         class="mx-auto border-4 border-white shadow-lg">
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Escaneie este QR code com o WhatsApp para conectar a instância.
                </p>
                <div class="flex items-center justify-center space-x-4">
                    <button onclick="atualizarQrcode()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-sync-alt mr-2"></i>Atualizar QR Code
                    </button>
                    <button onclick="verificarStatus()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i>Verificar Status
                    </button>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                    <p class="text-gray-600 mb-4">QR Code não disponível</p>
                    <button onclick="atualizarQrcode()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-sync-alt mr-2"></i>Gerar QR Code
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div id="status-info" class="mt-4 p-4 rounded-lg hidden">
            <div class="flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                <span id="status-message"></span>
            </div>
        </div>

        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-lightbulb mr-2"></i>
                <strong>Dica:</strong> O QR code expira após alguns minutos. Se não conseguir escanear, clique em "Atualizar QR Code".
            </p>
        </div>
    </div>
</div>

<script>
let statusInterval = null;

function atualizarQrcode() {
    const statusInfo = document.getElementById('status-info');
    const statusMessage = document.getElementById('status-message');
    let qrcodeImg = document.getElementById('qrcode-img');
    let qrcodeContainer = qrcodeImg ? qrcodeImg.closest('.mb-4') : null;
    const qrcodeSection = document.querySelector('.bg-gray-50.rounded-lg');
    
    // Mostrar loading
    statusInfo.classList.remove('hidden');
    statusMessage.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Gerando QR Code...';
    statusInfo.className = 'mt-4 p-4 rounded-lg bg-blue-50 border border-blue-200';
    
    fetch('<?= url('admin/whatsapp-instances/' . $instance['id'] . '/atualizar-qrcode') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(r => {
        if (!r.ok) {
            return r.json().then(data => {
                throw new Error(data.error || 'Erro ao atualizar QR code');
            });
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            if (data.connected) {
                // Já está conectado
                statusInfo.className = 'mt-4 p-4 rounded-lg bg-green-50 border border-green-200';
                statusMessage.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-2"></i>Instância já está conectada!';
                
                setTimeout(() => {
                    window.location.href = '<?= url('admin/whatsapp-instances') ?>';
                }, 2000);
            } else if (data.qrcode) {
                // QR Code gerado com sucesso
                // Limpar prefixo data:image se houver no QR code
                let qrcodeBase64 = String(data.qrcode).trim();
                
                // Remover prefixo data:image de diferentes formas
                if (qrcodeBase64.includes('data:image')) {
                    // Se contém o prefixo, extrair tudo após a vírgula
                    const parts = qrcodeBase64.split(',');
                    if (parts.length > 1) {
                        qrcodeBase64 = parts.slice(1).join(','); // Pega tudo após a primeira vírgula
                    } else {
                        // Se não tem vírgula, usar regex para remover
                        qrcodeBase64 = qrcodeBase64.replace(/^data:image\/[^;]+;base64,?/i, '');
                    }
                }
                
                // Validar se o base64 não está vazio
                qrcodeBase64 = qrcodeBase64.trim();
                
                if (!qrcodeBase64 || qrcodeBase64.length < 100) {
                    // Base64 muito curto, provavelmente inválido
                    console.error('QR code base64 inválido ou muito curto:', qrcodeBase64.substring(0, 50));
                    statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
                    statusMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-2"></i>QR code recebido está inválido. Tente novamente.';
                    return;
                }
                
                statusInfo.className = 'mt-4 p-4 rounded-lg bg-green-50 border border-green-200';
                statusMessage.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-2"></i>QR Code atualizado com sucesso!';
                
                // Verificar se o elemento de imagem existe
                if (!qrcodeImg) {
                    // Criar o container e a imagem se não existirem
                    const containerDiv = document.createElement('div');
                    containerDiv.className = 'mb-4';
                    
                    qrcodeImg = document.createElement('img');
                    qrcodeImg.id = 'qrcode-img';
                    qrcodeImg.alt = 'QR Code WhatsApp';
                    qrcodeImg.className = 'mx-auto border-4 border-white shadow-lg';
                    
                    containerDiv.appendChild(qrcodeImg);
                    
                    // Inserir antes do parágrafo de instruções ou no início da seção
                    if (qrcodeSection) {
                        const instructionP = qrcodeSection.querySelector('p.text-sm.text-gray-600');
                        if (instructionP) {
                            qrcodeSection.insertBefore(containerDiv, instructionP);
                        } else {
                            qrcodeSection.insertBefore(containerDiv, qrcodeSection.firstChild);
                        }
                    }
                    
                    qrcodeContainer = containerDiv;
                    
                    // Remover a mensagem de "QR Code não disponível" se existir
                    const noQrcodeDiv = qrcodeSection.querySelector('.text-center.py-8');
                    if (noQrcodeDiv) {
                        noQrcodeDiv.remove();
                    }
                    
                    // Adicionar o parágrafo de instruções se não existir
                    if (!qrcodeSection.querySelector('p.text-sm.text-gray-600')) {
                        const instructionP = document.createElement('p');
                        instructionP.className = 'text-sm text-gray-600 mb-4';
                        instructionP.textContent = 'Escaneie este QR code com o WhatsApp para conectar a instância.';
                        containerDiv.insertAdjacentElement('afterend', instructionP);
                    }
                }
                
                // Limpar event listeners anteriores para evitar múltiplos handlers
                const newImg = qrcodeImg.cloneNode(true);
                qrcodeImg.parentNode.replaceChild(newImg, qrcodeImg);
                qrcodeImg = newImg;
                
                // Atualizar a imagem do QR code
                const imageUrl = 'data:image/png;base64,' + qrcodeBase64 + '?t=' + new Date().getTime();
                
                qrcodeImg.onload = function() {
                    // Garantir que o container está visível após carregar
                    if (qrcodeContainer) {
                        qrcodeContainer.style.display = 'block';
                    }
                    // Ocultar mensagem de status após sucesso
                    setTimeout(() => {
                        statusInfo.classList.add('hidden');
                    }, 2000);
                };
                
                qrcodeImg.onerror = function() {
                    console.error('Erro ao carregar imagem do QR code');
                    console.error('Base64 length:', qrcodeBase64.length);
                    console.error('Base64 preview:', qrcodeBase64.substring(0, 100));
                    statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
                    statusMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-2"></i>Erro ao exibir QR code. O base64 pode estar inválido. Tente recarregar a página.';
                };
                
                // Definir o src após configurar os event listeners
                qrcodeImg.src = imageUrl;
                qrcodeImg.style.display = 'block';
                
                // Garantir que o container está visível
                if (qrcodeContainer) {
                    qrcodeContainer.style.display = 'block';
                }
            } else {
                // Sucesso mas sem QR code (caso raro)
                statusInfo.className = 'mt-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200';
                statusMessage.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>Resposta recebida mas QR code não encontrado. Tente novamente.';
            }
        } else {
            // Erro
            statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
            let errorMsg = data.error || 'Erro desconhecido';
            
            // Adicionar informações de debug se disponíveis (apenas em desenvolvimento)
            if (data.debug && console) {
                console.error('Debug info:', data.debug);
            }
            
            // Mensagens mais amigáveis para erros comuns
            if (errorMsg.includes('não existe') || errorMsg.includes('não encontrada')) {
                errorMsg = 'A instância não foi encontrada na Evolution API. Verifique se o nome da instância está correto e se a instância foi criada na Evolution API.';
            } else if (errorMsg.includes('Connection') || errorMsg.includes('timeout')) {
                errorMsg = 'Erro de conexão com a Evolution API. Verifique se a URL da API está correta e se o servidor está acessível.';
            } else if (errorMsg.includes('401') || errorMsg.includes('Unauthorized')) {
                errorMsg = 'Erro de autenticação. Verifique se a API Key está correta.';
            }
            
            statusMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-2"></i><strong>Erro:</strong> ' + errorMsg;
        }
    })
    .catch(err => {
        statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
        statusMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-2"></i>Erro ao gerar QR Code: ' + err.message;
        console.error('Erro completo:', err);
    });
}

function verificarStatus() {
    const statusInfo = document.getElementById('status-info');
    const statusMessage = document.getElementById('status-message');
    
    statusInfo.classList.remove('hidden');
    statusMessage.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando...';
    statusInfo.className = 'mt-4 p-4 rounded-lg bg-blue-50 border border-blue-200';
    
    fetch('<?= url('admin/whatsapp-instances/' . $instance['id'] . '/verificar-status') ?>')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.status === 'CONECTADO') {
                    statusInfo.className = 'mt-4 p-4 rounded-lg bg-green-50 border border-green-200';
                    statusMessage.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-2"></i>Instância conectada! Número: ' + (data.numero_whatsapp || 'N/A');
                    
                    // Parar verificação automática
                    if (statusInterval) {
                        clearInterval(statusInterval);
                    }
                    
                    // Redirecionar após 2 segundos
                    setTimeout(() => {
                        window.location.href = '<?= url('admin/whatsapp-instances') ?>';
                    }, 2000);
                } else if (data.status === 'CONECTANDO') {
                    statusInfo.className = 'mt-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200';
                    statusMessage.innerHTML = '<i class="fas fa-clock text-yellow-600 mr-2"></i>Aguardando conexão...';
                    
                    // Iniciar verificação automática
                    if (!statusInterval) {
                        statusInterval = setInterval(verificarStatus, 3000);
                    }
                } else {
                    statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
                    statusMessage.innerHTML = '<i class="fas fa-times-circle text-red-600 mr-2"></i>Instância desconectada. Atualize o QR code.';
                }
            } else {
                statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
                statusMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-2"></i>Erro: ' + (data.error || 'Erro desconhecido');
            }
        })
        .catch(err => {
            statusInfo.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
            statusMessage.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-2"></i>Erro ao verificar status';
            console.error(err);
        });
}

// Verificar status automaticamente a cada 5 segundos se estiver conectando
<?php if ($instance['status'] === 'CONECTANDO'): ?>
document.addEventListener('DOMContentLoaded', function() {
    statusInterval = setInterval(verificarStatus, 5000);
});
<?php endif; ?>

function copiarToken(token) {
    navigator.clipboard.writeText(token).then(function() {
        alert('Token copiado para a área de transferência!');
    }, function(err) {
        // Fallback para navegadores mais antigos
        const textarea = document.createElement('textarea');
        textarea.value = token;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            alert('Token copiado para a área de transferência!');
        } catch (err) {
            alert('Erro ao copiar token. Token: ' + token);
        }
        document.body.removeChild(textarea);
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>

