<?php
$title = $title ?? 'Informar Compra de Peça';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-shopping-cart text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Informar Compra de Peça</h2>
                <p class="text-gray-600 mt-2">Confirme que você comprou a peça e selecione novos horários para o atendimento</p>
            </div>

            <?php if (isset($error) && $error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Informações da Solicitação</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($tokenData['protocol'] ?? $solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                    <?php if (!empty($solicitacao['categoria_nome'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Categoria:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['categoria_nome']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($solicitacao['subcategoria_nome'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subcategoria:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['subcategoria_nome']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário -->
            <form method="POST" action="<?= url('compra-peca?token=' . urlencode($token)) ?>" id="formCompraPeca" class="space-y-6">
                
                <!-- Seleção de Data -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Selecione uma Data
                    </label>
                    <div class="relative cursor-pointer">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar-alt text-gray-400"></i>
                        </div>
                        <input type="date" id="data_selecionada" name="data_selecionada" 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm text-gray-700 cursor-pointer transition-colors"
                               placeholder="dd/mm/2025"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="mt-2 flex items-center text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1.5"></i>
                        <span>Atendimentos disponíveis apenas em dias úteis (segunda a sexta-feira)</span>
                    </div>
                </div>
                
                <!-- Seleção de Horário -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Selecione um Horário
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <label class="relative">
                            <input type="radio" name="horario_selecionado" value="08:00-11:00" class="sr-only horario-radio">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                            </div>
                        </label>
                        
                        <label class="relative">
                            <input type="radio" name="horario_selecionado" value="11:00-14:00" class="sr-only horario-radio">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                            </div>
                        </label>
                        
                        <label class="relative">
                            <input type="radio" name="horario_selecionado" value="14:00-17:00" class="sr-only horario-radio">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                            </div>
                        </label>
                        
                        <label class="relative">
                            <input type="radio" name="horario_selecionado" value="17:00-20:00" class="sr-only horario-radio">
                            <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                            </div>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-3 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <span>Selecione uma data e um horário. Você pode informar até 3 opções.</span>
                    </p>
                </div>
                
                <!-- Horários Selecionados -->
                <div id="horarios-selecionados" class="hidden">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">
                        Horários Selecionados (<span id="contador-horarios">0</span>/3)
                    </h4>
                    <div id="lista-horarios" class="space-y-2">
                        <!-- Horários serão inseridos aqui via JavaScript -->
                    </div>
                </div>

                <input type="hidden" name="novas_datas" id="novas_datas" value="[]">

                <!-- Botões -->
                <div class="flex gap-3 pt-4">
                    <button type="submit" id="btn-continuar" disabled
                            class="flex-1 bg-gray-400 text-white py-3 px-6 rounded-lg font-medium cursor-not-allowed transition-colors">
                        <i class="fas fa-check mr-2"></i>
                        Confirmar Compra e Agendar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Melhorar aparência do input de data */
input[type="date"] {
    position: relative;
    cursor: pointer;
    font-family: inherit;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    position: absolute;
    right: 12px;
    width: 20px;
    height: 20px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

input[type="date"]:focus {
    outline: none;
}

.horario-card {
    transition: all 0.2s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de agendamento
    const dataInput = document.getElementById('data_selecionada');
    const horarioRadios = document.querySelectorAll('.horario-radio');
    const horarioCards = document.querySelectorAll('.horario-card');
    const horariosSelecionados = document.getElementById('horarios-selecionados');
    const listaHorarios = document.getElementById('lista-horarios');
    const contadorHorarios = document.getElementById('contador-horarios');
    const btnContinuar = document.getElementById('btn-continuar');
    
    let horariosEscolhidos = [];
    let dataSelecionadaAtual = dataInput ? dataInput.value : '';
    
    // Abrir calendário ao clicar no campo de data
    if (dataInput) {
        dataInput.removeAttribute('readonly');
        
        const abrirCalendario = function() {
            try {
                if (dataInput.showPicker) {
                    dataInput.showPicker();
                } else {
                    dataInput.focus();
                    dataInput.click();
                }
            } catch (e) {
                console.log('Calendário será aberto pelo navegador');
            }
        };
        
        dataInput.addEventListener('click', abrirCalendario);
        
        const containerData = dataInput.closest('.relative');
        if (containerData) {
            containerData.addEventListener('click', function(e) {
                if (e.target !== dataInput) {
                    abrirCalendario();
                }
            });
        }
        
        // Validação: bloquear seleção de fins de semana
        dataInput.addEventListener('change', function() {
            if (!this.value) return;
            
            const dataSelecionada = new Date(this.value + 'T12:00:00');
            const diaDaSemana = dataSelecionada.getDay(); // 0 = Domingo, 6 = Sábado
            dataSelecionadaAtual = this.value;
            
            // Resetar seleção de horários quando mudar a data
            horarioRadios.forEach(r => {
                r.checked = false;
            });
            atualizarEstiloCards();
            
            if (diaDaSemana === 0 || diaDaSemana === 6) {
                const nomeDia = diaDaSemana === 0 ? 'domingo' : 'sábado';
                alert('⚠️ Atendimentos não são realizados aos fins de semana.\n\nA data selecionada é um ' + nomeDia + '.\nPor favor, selecione um dia útil (segunda a sexta-feira).');
                this.value = '';
                dataSelecionadaAtual = '';
            }
        });
    }
    
    // Seleção de horário - adiciona automaticamente quando selecionado (igual nova solicitação)
    horarioRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const data = dataSelecionadaAtual;
            const horario = this.value;
            
            if (!data) {
                alert('Por favor, selecione uma data primeiro');
                this.checked = false;
                return;
            }
            
            if (data && horario) {
                const horarioCompleto = `${formatarData(data)} - ${horario}`;
                
                // Verificar se já existe (sem alerta, igual nova solicitação)
                if (!horariosEscolhidos.includes(horarioCompleto) && horariosEscolhidos.length < 3) {
                    horariosEscolhidos.push(horarioCompleto);
                    atualizarListaHorarios();
                    // Resetar seleção após adicionar
                    this.checked = false;
                    atualizarEstiloCards();
                } else if (horariosEscolhidos.length >= 3) {
                    alert('Você pode selecionar no máximo 3 horários.');
                    this.checked = false;
                } else {
                    // Horário já existe - apenas desmarcar sem alerta
                    this.checked = false;
                    atualizarEstiloCards();
                }
            }
        });
    });

    // Click no card de horário também seleciona o radio
    horarioCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Evitar duplo disparo se o click foi no radio
            if (e.target.type === 'radio') {
                return;
            }
            const label = this.closest('label');
            const radio = label ? label.querySelector('.horario-radio') : null;
            if (radio && !radio.checked) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    function atualizarListaHorarios() {
        if (horariosEscolhidos.length > 0) {
            horariosSelecionados.classList.remove('hidden');
            contadorHorarios.textContent = horariosEscolhidos.length;
            
            listaHorarios.innerHTML = '';
            horariosEscolhidos.forEach((horario, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3';
                div.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-clock text-green-600 mr-2"></i>
                        <span class="text-sm text-green-800">${horario}</span>
                    </div>
                    <button type="button" onclick="removerHorario(${index})" 
                            class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                listaHorarios.appendChild(div);
            });
            
            // Habilitar botão continuar se tiver pelo menos 1 horário
            if (horariosEscolhidos.length > 0) {
                btnContinuar.disabled = false;
                btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        } else {
            horariosSelecionados.classList.add('hidden');
            btnContinuar.disabled = true;
            btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
            btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
        }
    }
    
    window.removerHorario = function(index) {
        horariosEscolhidos.splice(index, 1);
        atualizarListaHorarios();
    };

    function atualizarEstiloCards() {
        horarioCards.forEach(card => {
            const radio = card.closest('label') ? card.closest('label').querySelector('.horario-radio') : null;
            const ativo = radio && radio.checked;
            card.classList.toggle('border-green-500', ativo);
            card.classList.toggle('bg-green-50', ativo);
            card.classList.toggle('border-gray-200', !ativo);
        });
    }
    
    function formatarData(data) {
        const [ano, mes, dia] = data.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Salvar horários no formulário antes de enviar (formato igual nova solicitação)
    const form = document.getElementById('formCompraPeca');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validar se há pelo menos 1 horário
            if (horariosEscolhidos.length === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos uma data e horário');
                return false;
            }
            
            // Converter: "29/10/2025 - 08:00-11:00" → "2025-10-29 08:00:00"
            const horariosFormatados = horariosEscolhidos.map(horario => {
                const [dataStr, faixaHorario] = horario.split(' - ');
                if (!dataStr || !faixaHorario) {
                    console.error('Erro ao processar horário:', horario);
                    return null;
                }
                const [dia, mes, ano] = dataStr.split('/');
                const horarioInicial = faixaHorario.split('-')[0];
                if (!dia || !mes || !ano || !horarioInicial) {
                    console.error('Erro ao processar componentes do horário:', { dataStr, faixaHorario, dia, mes, ano, horarioInicial });
                    return null;
                }
                return `${ano}-${mes}-${dia} ${horarioInicial}:00`;
            }).filter(h => h !== null);
            
            // Validar novamente após conversão
            if (horariosFormatados.length === 0) {
                e.preventDefault();
                alert('Erro ao processar os horários selecionados. Por favor, tente novamente.');
                return false;
            }
            
            // Enviar como JSON no formato esperado
            const novasDatasInput = document.getElementById('novas_datas');
            if (novasDatasInput) {
                novasDatasInput.value = JSON.stringify(horariosFormatados);
                console.log('Horários enviados:', horariosFormatados);
            } else {
                console.error('Campo novas_datas não encontrado!');
                e.preventDefault();
                alert('Erro ao processar formulário. Por favor, recarregue a página e tente novamente.');
                return false;
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/public.php';
?>
