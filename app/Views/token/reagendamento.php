<?php
$title = $title ?? 'Reagendar Horário';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                    <i class="fas fa-calendar-alt text-yellow-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Reagendar Horário</h2>
                <p class="text-gray-600 mt-2">Selecione novas datas e horários preferenciais</p>
            </div>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Informações do Agendamento Atual</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($tokenData['protocol'] ?? $solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                    <?php if ($tokenData['scheduled_date']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data Atual:</span>
                        <span class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($tokenData['scheduled_date'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($tokenData['scheduled_time']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Horário Atual:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($tokenData['scheduled_time']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário de Reagendamento -->
            <form method="POST" action="<?= url('reagendamento-horario?token=' . urlencode($token)) ?>" id="formReagendamento" class="space-y-6">
                
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
                        Selecione uma data e um horário e clique em <strong>Salvar Horário</strong>. Você pode informar até 3 opções.
                    </p>
                    <button type="button" id="btn-adicionar-horario"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-blue-700 transition-colors disabled:bg-gray-300 disabled:text-gray-600 disabled:cursor-not-allowed"
                            disabled>
                        <i class="fas fa-plus mr-2 text-xs"></i>Salvar Horário
                    </button>
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

                <!-- Navigation -->
                <div class="flex justify-between pt-6">
                    <a href="<?= url('confirmacao-horario?token=' . urlencode($token)) ?>" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Voltar
                    </a>
                    <button type="submit" id="btn-continuar" disabled
                            class="px-6 py-3 bg-gray-400 text-white font-medium rounded-lg cursor-not-allowed transition-colors">
                        Solicitar Reagendamento
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500">
            <div class="flex items-center justify-center mb-2">
                <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 24) ?>
            </div>
            <p>KSS Seguros - Assistência 360°</p>
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

/* Estilo quando a data está vazia (placeholder visual) */
input[type="date"]:not(:focus)::-webkit-datetime-edit {
    color: transparent;
}

input[type="date"]:not(:focus)::before {
    content: attr(placeholder);
    color: #9ca3af;
    margin-right: 8px;
}

input[type="date"]:valid:not(:focus)::before,
input[type="date"]:focus::before {
    content: none;
}

input[type="date"]:valid:not(:focus)::-webkit-datetime-edit {
    color: #374151;
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
    const btnAdicionarHorario = document.getElementById('btn-adicionar-horario');
    
    let horariosEscolhidos = [];
    let horarioSelecionadoAtual = '';
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
            horarioSelecionadoAtual = '';
            resetarRadios();
            atualizarEstiloCards();
            
            if (diaDaSemana === 0 || diaDaSemana === 6) {
                const nomeDia = diaDaSemana === 0 ? 'domingo' : 'sábado';
                alert('⚠️ Atendimentos não são realizados aos fins de semana.\n\nA data selecionada é um ' + nomeDia + '.\nPor favor, selecione um dia útil (segunda a sexta-feira).');
                this.value = '';
                dataSelecionadaAtual = '';
            }

            atualizarEstadoSalvar();
        });
    }
    
    // Seleção de horário
    horarioRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (!dataSelecionadaAtual) {
                alert('Por favor, selecione uma data primeiro');
                this.checked = false;
                return;
            }

            horarioSelecionadoAtual = this.value;
            atualizarEstiloCards();
            atualizarEstadoSalvar();

            if (!btnAdicionarHorario) {
                adicionarHorarioSelecionado(true);
            }
        });
    });

    // Click no card de horário também seleciona o radio
    horarioCards.forEach(card => {
        card.addEventListener('click', function() {
            const label = this.closest('label');
            const radio = label ? label.querySelector('.horario-radio') : null;
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });

    if (btnAdicionarHorario) {
        btnAdicionarHorario.addEventListener('click', function() {
            if (this.disabled) {
                return;
            }
            adicionarHorarioSelecionado(false);
        });
    }

    function adicionarHorarioSelecionado(auto = false) {
        const data = dataSelecionadaAtual;
        const horario = horarioSelecionadoAtual;

        if (!data || !horario) {
            if (!auto) {
                alert('Selecione uma data e um horário antes de salvar.');
            }
            return;
        }

        const horarioCompleto = `${formatarData(data)} - ${horario}`;

        if (horariosEscolhidos.includes(horarioCompleto)) {
            if (!auto) {
                alert('Este horário já foi adicionado. Escolha outro.');
            }
            return;
        }

        if (horariosEscolhidos.length >= 3) {
            if (!auto) {
                alert('Você pode selecionar no máximo 3 horários.');
            }
            return;
        }

        horariosEscolhidos.push(horarioCompleto);
        console.log('[Reagendamento] Horário adicionado:', horarioCompleto, 'Lista atual:', horariosEscolhidos);
        atualizarListaHorarios();

        horarioSelecionadoAtual = '';
        resetarRadios();
        atualizarEstiloCards();
        atualizarEstadoSalvar();
    }
    
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

        atualizarEstadoSalvar();
    }
    
    window.removerHorario = function(index) {
        horariosEscolhidos.splice(index, 1);
        console.log('[Reagendamento] Horário removido. Lista atual:', horariosEscolhidos);
        atualizarListaHorarios();
        atualizarEstiloCards();
        atualizarEstadoSalvar();
    };

    function resetarRadios() {
        horarioRadios.forEach(r => {
            r.checked = false;
        });
        horarioSelecionadoAtual = '';
    }

    function atualizarEstiloCards() {
        horarioCards.forEach(card => {
            const radio = card.closest('label') ? card.closest('label').querySelector('.horario-radio') : null;
            const ativo = radio && radio.value === horarioSelecionadoAtual;
            card.classList.toggle('border-green-500', ativo);
            card.classList.toggle('bg-green-50', ativo);
            card.classList.toggle('border-gray-200', !ativo);
        });
    }

    function atualizarEstadoSalvar() {
        const podeAdicionar = Boolean(dataSelecionadaAtual && horarioSelecionadoAtual && horariosEscolhidos.length < 3);
        if (btnAdicionarHorario) {
            btnAdicionarHorario.disabled = !podeAdicionar;
            btnAdicionarHorario.classList.toggle('bg-blue-600', podeAdicionar);
            btnAdicionarHorario.classList.toggle('hover:bg-blue-700', podeAdicionar);
            btnAdicionarHorario.classList.toggle('text-white', podeAdicionar);
            btnAdicionarHorario.classList.toggle('bg-gray-300', !podeAdicionar);
            btnAdicionarHorario.classList.toggle('text-gray-600', !podeAdicionar);
            btnAdicionarHorario.classList.toggle('cursor-not-allowed', !podeAdicionar);
        }
    }
    
    function formatarData(data) {
        const [ano, mes, dia] = data.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Salvar horários no formulário antes de enviar
    const form = document.getElementById('formReagendamento');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Converter: "11/11/2025 - 08:00-11:00" → "11/11/2025 - 08:00-11:00"
            const horariosFormatados = horariosEscolhidos.map(horario => {
                // Formato já está correto: "dd/mm/yyyy - HH:MM-HH:MM"
                return horario;
            });
            
            // Atualizar campo hidden
            document.getElementById('novas_datas').value = JSON.stringify(horariosFormatados);
            console.log('[Reagendamento] Horários enviados:', horariosFormatados);
            
            // Validar se há pelo menos 1 horário
            if (horariosFormatados.length === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos uma data e horário');
                return false;
            }
        });
    }

    atualizarEstadoSalvar();
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/public.php';
?>
