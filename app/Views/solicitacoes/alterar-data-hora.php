<?php
$title = 'Alterar Data/Hora de Solicitações';
$currentPage = 'alterar-data-hora';
$pageTitle = 'Alterar Data/Hora de Solicitações';
ob_start();
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
            Alterar Data/Hora de Solicitações
        </h1>
        <p class="text-gray-600">Busque e selecione solicitações para alterar suas datas e horários de início e fim</p>
    </div>

    <!-- Filtros de Busca -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-search mr-2 text-blue-600"></i>
            Buscar Solicitações
        </h2>
        
        <form id="formBuscar" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número da Solicitação</label>
                <input type="text" id="numero_solicitacao" name="numero_solicitacao" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número do Contrato</label>
                <input type="text" id="numero_contrato" name="numero_contrato" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Locatário</label>
                <input type="text" id="locatario_nome" name="locatario_nome" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status_id" name="status_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <?php foreach ($status ?? [] as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Imobiliária</label>
                <select id="imobiliaria_id" name="imobiliaria_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas</option>
                    <?php foreach ($imobiliarias ?? [] as $i): ?>
                        <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Criação (Início)</label>
                <input type="date" id="data_inicio" name="data_inicio" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Criação (Fim)</label>
                <input type="date" id="data_fim" name="data_fim" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Agendamento (Início)</label>
                <input type="date" id="agendamento_inicio" name="agendamento_inicio" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Agendamento (Fim)</label>
                <input type="date" id="agendamento_fim" name="agendamento_fim" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex items-end">
                <button type="button" onclick="buscarSolicitacoes()" 
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-list mr-2 text-blue-600"></i>
                Resultados
            </h2>
            <div class="flex items-center gap-2">
                <button type="button" onclick="selecionarTodos()" 
                        class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-check-square mr-1"></i>
                    Selecionar Todos
                </button>
                <button type="button" onclick="desmarcarTodos()" 
                        class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-square mr-1"></i>
                    Desmarcar Todos
                </button>
            </div>
        </div>
        
        <div id="resultadosContainer" class="space-y-2 max-h-[600px] overflow-y-auto">
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                <p>Carregando solicitações...</p>
            </div>
        </div>
    </div>

    <!-- Formulário de Alteração -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-edit mr-2 text-blue-600"></i>
            Alterar Data/Hora
        </h2>
        
        <form id="formAlterar" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Data de Início <span class="text-red-500">*</span>
                </label>
                <input type="date" id="data_inicio_alterar" name="data_inicio" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Horário de Início <span class="text-red-500">*</span>
                </label>
                <input type="time" id="horario_inicio" name="horario_inicio" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Data de Fim (Opcional)
                </label>
                <input type="date" id="data_fim_alterar" name="data_fim"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Horário de Fim (Opcional)
                </label>
                <input type="time" id="horario_fim" name="horario_fim"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="md:col-span-2">
                <button type="button" onclick="salvarAlteracoes()" 
                        class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
            </div>
        </form>
        
        <div id="mensagemResultado" class="mt-4 hidden"></div>
    </div>
</div>

<script>
let solicitacoesSelecionadas = new Set();

// Carregar todas as solicitações automaticamente ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    buscarSolicitacoes();
});

function buscarSolicitacoes() {
    const form = document.getElementById('formBuscar');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    const container = document.getElementById('resultadosContainer');
    container.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-3"></i><p class="text-gray-600">Buscando...</p></div>';
    
    fetch(`<?= url('admin/solicitacoes/buscar/api') ?>?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || data.message || 'Erro ao buscar solicitações');
                }).catch(() => {
                    throw new Error(`Erro ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.solicitacoes) {
                renderizarResultados(data.solicitacoes);
            } else {
                const errorMsg = data.error || data.message || 'Erro ao buscar solicitações';
                container.innerHTML = `<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-3xl mb-3"></i><p>${errorMsg}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            const errorMsg = error.message || 'Erro ao buscar solicitações';
            container.innerHTML = `<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-3xl mb-3"></i><p>${errorMsg}</p></div>`;
        });
}

function renderizarResultados(solicitacoes) {
    const container = document.getElementById('resultadosContainer');
    
    if (solicitacoes.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500"><i class="fas fa-inbox text-3xl mb-3"></i><p>Nenhuma solicitação encontrada</p></div>';
        return;
    }
    
    let html = '<div class="space-y-2">';
    
    solicitacoes.forEach(sol => {
        // Formatar data de agendamento
        let dataAgendamento = '-';
        if (sol.data_agendamento && sol.data_agendamento !== '0000-00-00') {
            try {
                const data = new Date(sol.data_agendamento + 'T00:00:00');
                dataAgendamento = data.toLocaleDateString('pt-BR');
            } catch (e) {
                dataAgendamento = sol.data_agendamento;
            }
        }
        
        // Formatar horário (remover segundos se houver)
        let horarioAgendamento = '-';
        if (sol.horario_agendamento && sol.horario_agendamento !== '00:00:00') {
            horarioAgendamento = sol.horario_agendamento.substring(0, 5); // Pegar apenas HH:MM
        }
        
        const numeroSolicitacao = sol.numero_solicitacao || (sol.id ? 'KSS' + sol.id : 'N/A');
        const isSelected = solicitacoesSelecionadas.has(sol.id);
        
        html += `
            <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors ${isSelected ? 'bg-blue-50 border-blue-300' : ''}">
                <div class="flex items-center gap-3">
                    <input type="checkbox" 
                           value="${sol.id}" 
                           ${isSelected ? 'checked' : ''}
                           onchange="toggleSelecao(${sol.id})"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">${numeroSolicitacao}</span>
                            <span class="text-sm text-gray-500">•</span>
                            <span class="text-sm text-gray-600">${sol.locatario_nome || 'N/A'}</span>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            <span>Contrato: ${sol.numero_contrato || 'N/A'}</span>
                            <span class="mx-2">•</span>
                            <span>Agendamento: ${dataAgendamento} ${horarioAgendamento !== '-' ? horarioAgendamento : ''}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function toggleSelecao(id) {
    if (solicitacoesSelecionadas.has(id)) {
        solicitacoesSelecionadas.delete(id);
    } else {
        solicitacoesSelecionadas.add(id);
    }
    buscarSolicitacoes(); // Re-renderizar para atualizar visual
}

function selecionarTodos() {
    const checkboxes = document.querySelectorAll('#resultadosContainer input[type="checkbox"]');
    checkboxes.forEach(cb => {
        solicitacoesSelecionadas.add(parseInt(cb.value));
        cb.checked = true;
    });
    buscarSolicitacoes();
}

function desmarcarTodos() {
    solicitacoesSelecionadas.clear();
    const checkboxes = document.querySelectorAll('#resultadosContainer input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    buscarSolicitacoes();
}

function salvarAlteracoes() {
    const ids = Array.from(solicitacoesSelecionadas);
    
    if (ids.length === 0) {
        alert('Selecione pelo menos uma solicitação');
        return;
    }
    
    const dataInicio = document.getElementById('data_inicio_alterar').value;
    const horarioInicio = document.getElementById('horario_inicio').value;
    const dataFim = document.getElementById('data_fim_alterar').value;
    const horarioFim = document.getElementById('horario_fim').value;
    
    if (!dataInicio || !horarioInicio) {
        alert('Data e horário de início são obrigatórios');
        return;
    }
    
    if (!confirm(`Deseja realmente alterar a data/hora de ${ids.length} solicitação(ões)?`)) {
        return;
    }
    
    const payload = {
        ids: ids,
        data_inicio: dataInicio,
        horario_inicio: horarioInicio,
        data_fim: dataFim || null,
        horario_fim: horarioFim || null
    };
    
    const mensagemDiv = document.getElementById('mensagemResultado');
    mensagemDiv.classList.remove('hidden');
    mensagemDiv.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i><p class="text-gray-600">Salvando...</p></div>';
    
    fetch('<?= url('admin/solicitacoes/atualizar-data-hora-bulk') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mensagemDiv.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-900">${data.message}</p>
                            ${data.erros && data.erros.length > 0 ? `
                                <p class="text-sm text-green-700 mt-1">Alguns erros ocorreram:</p>
                                <ul class="text-sm text-green-700 mt-1 list-disc list-inside">
                                    ${data.erros.map(e => `<li>${e}</li>`).join('')}
                                </ul>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            // Limpar seleções e recarregar resultados
            solicitacoesSelecionadas.clear();
            buscarSolicitacoes();
        } else {
            mensagemDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl mr-3"></i>
                        <p class="font-semibold text-red-900">${data.error || 'Erro ao salvar alterações'}</p>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mensagemDiv.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl mr-3"></i>
                    <p class="font-semibold text-red-900">Erro ao salvar alterações</p>
                </div>
            </div>
        `;
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

