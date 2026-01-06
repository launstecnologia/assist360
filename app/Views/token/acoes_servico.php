<?php
$title = $title ?? 'Ações do Serviço';
ob_start();
?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 mb-4">
                    <?php if (!empty($solicitacao['imobiliaria_logo'])): ?>
                        <img src="<?= url('Public/uploads/logos/' . $solicitacao['imobiliaria_logo']) ?>" 
                             alt="<?= htmlspecialchars($solicitacao['imobiliaria_nome'] ?? 'Imobiliária') ?>" 
                             class="h-16 w-16 object-contain rounded-lg border border-gray-200 bg-white p-1"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center" style="display: none;">
                            <i class="fas fa-building text-blue-600 text-2xl"></i>
                        </div>
                    <?php else: ?>
                        <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-building text-blue-600 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Como foi o serviço?</h2>
                <p class="text-gray-600 mt-2">Selecione uma das opções abaixo</p>
            </div>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="text-sm space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900">
                            <?php 
                            $numeroSolicitacao = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']);
                            echo htmlspecialchars($numeroSolicitacao);
                            ?>
                        </span>
                    </div>
                    <?php if ($solicitacao['data_agendamento']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data:</span>
                        <span class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($solicitacao['data_agendamento'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php 
                    // Formatar horário no formato "08:00 às 11:00"
                    $horarioFormatado = '';
                    if (!empty($solicitacao['horario_agendamento'])) {
                        $horario = $solicitacao['horario_agendamento'];
                        // Se já estiver no formato "08:00 às 11:00", usar direto
                        if (strpos($horario, ' às ') !== false) {
                            $horarioFormatado = $horario;
                        } 
                        // Se for formato "08:00-11:00", converter para "08:00 às 11:00"
                        elseif (preg_match('/(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?/', $horario, $matches)) {
                            $horarioFormatado = $matches[1] . ' às ' . $matches[2];
                        }
                        // Se for apenas "08:00:00" ou "08:00", assumir 3 horas de intervalo
                        elseif (preg_match('/(\d{2}):(\d{2})(?::\d{2})?/', $horario, $matches)) {
                            $horaInicio = $matches[1] . ':' . $matches[2];
                            $horaFim = date('H:i', strtotime($horaInicio . ' +3 hours'));
                            $horarioFormatado = $horaInicio . ' às ' . $horaFim;
                        } else {
                            $horarioFormatado = $horario;
                        }
                    }
                    ?>
                    <?php if ($horarioFormatado): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Horário:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($horarioFormatado) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Opções de Ação -->
            <div id="opcoesAcoes">
                <form id="formAcoes" class="space-y-4">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <!-- 1. Serviço Realizado -->
                    <button type="button" 
                            onclick="processarAcao('servico_realizado')"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors flex items-center justify-center gap-3">
                        <i class="fas fa-check-circle text-xl"></i>
                        <span>Serviço realizado com sucesso</span>
                    </button>

                    <!-- 2. Prestador não compareceu -->
                    <button type="button" 
                            onclick="processarAcao('nao_compareceu')"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors flex items-center justify-center gap-3">
                        <i class="fas fa-times-circle text-xl"></i>
                        <span>Prestador não compareceu no serviço agendado</span>
                    </button>

                    <!-- 3. Precisa comprar peças -->
                    <button type="button" 
                            onclick="processarAcao('precisa_pecas')"
                            class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors flex items-center justify-center gap-3">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span>Precisa comprar peças</span>
                    </button>

                    <!-- 4. Precisei me ausentar -->
                    <button type="button" 
                            onclick="processarAcao('ausente')"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors flex items-center justify-center gap-3">
                        <i class="fas fa-user-times text-xl"></i>
                        <span>Precisei me ausentar</span>
                    </button>

                    <!-- 5. Outros -->
                    <div class="space-y-2">
                        <button type="button" 
                                onclick="mostrarCampoOutros()"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors flex items-center justify-center gap-3">
                            <i class="fas fa-ellipsis-h text-xl"></i>
                            <span>Outros</span>
                        </button>
                        
                        <div id="campoOutros" class="hidden">
                            <textarea name="descricao" 
                                      id="descricaoOutros"
                                      placeholder="Descreva o motivo..."
                                      class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                                      rows="4"></textarea>
                            <button type="button" 
                                    onclick="processarAcao('outros')"
                                    class="w-full mt-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                Enviar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Mensagem de Feedback -->
            <div id="mensagemFeedback" class="hidden mt-6"></div>
        </div>
    </div>
    
    <!-- Footer com Logo KSS -->
    <footer class="mt-12 text-center pb-8">
        <div class="flex items-center justify-center">
            <?php 
            $kssLogoUrl = \App\Core\Url::kssLogo();
            if (!empty($kssLogoUrl)): ?>
                <img src="<?= htmlspecialchars($kssLogoUrl) ?>" 
                     alt="KSS ASSISTÊNCIA 360°" 
                     class="h-12 w-auto"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="flex items-center space-x-2" style="display: none;">
                    <span class="text-green-600 font-bold text-lg">KSS</span>
                    <span class="text-gray-600 text-sm">ASSISTÊNCIA 360°</span>
                </div>
            <?php else: ?>
                <div class="flex items-center space-x-2">
                    <span class="text-green-600 font-bold text-lg">KSS</span>
                    <span class="text-gray-600 text-sm">ASSISTÊNCIA 360°</span>
                </div>
            <?php endif; ?>
        </div>
    </footer>
</div>

<script>
function mostrarCampoOutros() {
    document.getElementById('campoOutros').classList.remove('hidden');
    document.getElementById('descricaoOutros').focus();
}

function processarAcao(acao) {
    const form = document.getElementById('formAcoes');
    const formData = new FormData(form);
    formData.append('acao', acao);
    
    // Se for "outros", verificar se tem descrição
    if (acao === 'outros') {
        const descricao = document.getElementById('descricaoOutros').value.trim();
        if (!descricao) {
            alert('Por favor, descreva o motivo');
            return;
        }
        formData.set('descricao', descricao);
    }
    
    // Esconder opções de ação
    const opcoesDiv = document.getElementById('opcoesAcoes');
    opcoesDiv.style.display = 'none';
    
    // Mostrar loading
    const mensagemDiv = document.getElementById('mensagemFeedback');
    mensagemDiv.className = 'p-8 rounded-lg bg-blue-50 border-2 border-blue-300';
    mensagemDiv.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-blue-600 text-4xl mb-4"></i>
            <p class="text-blue-800 text-lg font-semibold">Processando...</p>
        </div>
    `;
    mensagemDiv.classList.remove('hidden');
    
    fetch('/acoes-servico', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mensagemDiv.className = 'p-12 rounded-lg bg-green-50 border-2 border-green-400';
            mensagemDiv.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-check-circle text-green-600 text-5xl mb-6"></i>
                    <p class="text-green-800 text-2xl font-bold mb-2">${data.message}</p>
                </div>
            `;
            
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 3000);
            }
        } else {
            mensagemDiv.className = 'p-12 rounded-lg bg-red-50 border-2 border-red-400';
            mensagemDiv.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-exclamation-circle text-red-600 text-5xl mb-6"></i>
                    <p class="text-red-800 text-2xl font-bold mb-2">${data.message}</p>
                    <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Tentar Novamente
                    </button>
                </div>
            `;
            
            // Mostrar opções novamente em caso de erro
            opcoesDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mensagemDiv.className = 'p-12 rounded-lg bg-red-50 border-2 border-red-400';
        mensagemDiv.innerHTML = `
            <div class="text-center">
                <i class="fas fa-exclamation-circle text-red-600 text-5xl mb-6"></i>
                <p class="text-red-800 text-2xl font-bold mb-2">Erro ao processar ação. Tente novamente.</p>
                <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Tentar Novamente
                </button>
            </div>
        `;
        
        // Mostrar opções novamente em caso de erro
        opcoesDiv.style.display = 'block';
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/public.php';
?>

