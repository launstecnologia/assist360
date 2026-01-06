<?php
/**
 * View: Upload de CSV
 */
$title = 'Upload de CSV';
$currentPage = 'upload';
$pageTitle = 'Upload de CSV';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Upload de CSV</h2>
        <p class="text-sm text-gray-600">Faça upload de arquivos CSV para importar CPFs e contratos</p>
    </div>
</div>

<div class="bg-white shadow rounded-lg p-6">
    <div class="mb-4">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Instruções</h3>
        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
            <li>O arquivo deve estar no formato CSV (separado por vírgula ou ponto e vírgula)</li>
            <li>Selecione a imobiliária para a qual deseja importar os dados</li>
            <li>Colunas obrigatórias: <strong>inquilino_doc</strong> (CPF/CNPJ), <strong>contrato</strong></li>
            <li>Colunas opcionais: inquilino_nome, ImoFinalidade, cidade, estado, bairro, CEP, endereco, numero, complemento, unidade</li>
        </ul>
    </div>

    <form id="form-upload-csv" enctype="multipart/form-data" class="mt-6">
        <div class="mb-4">
            <label for="imobiliaria_id" class="block text-sm font-medium text-gray-700 mb-2">
                Imobiliária <span class="text-red-500">*</span>
            </label>
            <select name="imobiliaria_id" id="imobiliaria_id" required
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Selecione uma imobiliária</option>
                <?php foreach ($imobiliarias as $imob): ?>
                    <option value="<?= $imob['id'] ?>">
                        <?= htmlspecialchars($imob['nome_fantasia'] ?? $imob['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="mt-1 text-xs text-gray-500">Todos os arquivos selecionados serão importados para a imobiliária escolhida</p>
        </div>

        <div class="mb-4">
            <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                Selecione um ou mais arquivos CSV
            </label>
            
            <!-- Zona de Drag and Drop -->
            <div id="drop-zone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors bg-gray-50 hover:bg-blue-50">
                <input type="file" name="csv_file[]" id="csv_file" accept=".csv" multiple
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                <div id="drop-zone-content" class="relative z-0">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        Arraste arquivos CSV aqui ou clique para selecionar
                    </p>
                    <p class="text-xs text-gray-500">
                        Você pode selecionar múltiplos arquivos de uma vez (Ctrl+Click ou Cmd+Click)
                    </p>
                </div>
            </div>
            
            <p class="mt-2 text-xs text-gray-500 hidden" id="drag-hint">
                <i class="fas fa-info-circle mr-1"></i>
                Solte os arquivos aqui para fazer upload
            </p>
        </div>

        <div id="arquivos-selecionados" class="mb-4 hidden">
            <p class="text-sm font-medium text-gray-700 mb-2">Arquivos selecionados:</p>
            <ul id="lista-arquivos" class="text-sm text-gray-600 space-y-1"></ul>
        </div>

        <div class="flex items-center space-x-3">
            <button type="submit" id="upload-csv-button" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-upload mr-2"></i>
                Enviar Arquivos
            </button>
        </div>
    </form>

    <div id="upload-csv-result" class="mt-6 hidden"></div>
</div>

<!-- Seção de Histórico -->
<div class="bg-white shadow rounded-lg p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Histórico de Uploads</h3>
        <div class="flex items-center space-x-3">
            <button type="button" id="btn-filtros" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-filter mr-2"></i> Filtros
            </button>
            <button type="button" id="btn-atualizar-historico" class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-sync-alt mr-1"></i> Atualizar
            </button>
        </div>
    </div>
    
    <div id="historico-container" class="mt-4">
        <p class="text-sm text-gray-500 text-center py-8">
            Selecione uma imobiliária para visualizar o histórico de uploads
        </p>
    </div>
</div>

<!-- Modal de Filtros -->
<div id="modal-filtros" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-7xl shadow-lg rounded-md bg-white mb-10">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Filtros de Imobiliárias e Lançamentos</h3>
                <button onclick="fecharModalFiltros()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Filtros -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por Nome</label>
                        <input type="text" 
                               id="filtro-busca-nome" 
                               placeholder="Digite o nome da imobiliária..."
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                               onkeypress="if(event.key === 'Enter') aplicarFiltros()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status de Lançamento</label>
                        <select id="filtro-status-lancado" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos</option>
                            <option value="sim">Lançado</option>
                            <option value="nao">Não Lançado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                        <select id="filtro-mes" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos os meses</option>
                            <option value="01">Janeiro</option>
                            <option value="02">Fevereiro</option>
                            <option value="03">Março</option>
                            <option value="04">Abril</option>
                            <option value="05">Maio</option>
                            <option value="06">Junho</option>
                            <option value="07">Julho</option>
                            <option value="08">Agosto</option>
                            <option value="09">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                        <select id="filtro-ano" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <?php
                            $anoAtual = (int)date('Y');
                            for ($ano = $anoAtual; $ano >= $anoAtual - 5; $ano--) {
                                $selected = ($ano === $anoAtual) ? 'selected' : '';
                                echo "<option value=\"{$ano}\" {$selected}>{$ano}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="aplicarFiltros()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-search mr-2"></i> Filtrar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Imobiliárias -->
            <div id="container-imobiliarias-filtros" class="max-h-[550px] overflow-y-auto overflow-x-auto relative">
                <p class="text-sm text-gray-500 text-center py-8">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Carregando imobiliárias...
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Visualização da Planilha -->
<div id="modal-visualizar-planilha" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white mb-10">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modal-planilha-titulo">Visualizar Planilha</h3>
                <button onclick="fecharModalPlanilha()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Conteúdo do histórico -->
            <div id="modal-planilha-content" class="max-h-[500px] overflow-y-auto overflow-x-auto relative">
                <p class="text-sm text-gray-500 text-center py-8">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Carregando dados...
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Remoção -->
<div id="modal-remover" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Confirmar Remoção</h3>
                <button onclick="fecharModalRemover()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">
                    Você está prestes a remover o registro do histórico:
                </p>
                <p class="text-sm font-medium text-gray-900" id="modal-nome-arquivo"></p>
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">
                    Para confirmar, digite <strong>REMOVER</strong> no campo abaixo:
                </p>
                <input type="text" 
                       id="input-confirmar-remover" 
                       placeholder="Digite REMOVER"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                <input type="hidden" id="modal-id-remover">
            </div>
            
            <div class="flex justify-end space-x-3">
                <button onclick="fecharModalRemover()" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancelar
                </button>
                <button onclick="confirmarRemover()" 
                        id="btn-confirmar-remover"
                        disabled
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    Remover
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para carregar histórico
function carregarHistorico(imobiliariaId) {
    const container = document.getElementById('historico-container');
    
    if (!imobiliariaId) {
        container.innerHTML = '<p class="text-sm text-gray-500 text-center py-8">Selecione uma imobiliária para visualizar o histórico de uploads</p>';
        return;
    }
    
    container.innerHTML = '<p class="text-sm text-gray-500 text-center py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando histórico...</p>';
    
    fetch(`<?= url('admin/upload/historico') ?>/${imobiliariaId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.historico && data.historico.length > 0) {
            let html = '<div class="max-h-[400px] overflow-y-auto overflow-x-auto relative"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50 sticky top-0 z-10 shadow-sm"><tr>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Data/Hora</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Arquivo</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Usuário</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Total</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Sucesso</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Erros</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Ações</th>';
            html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
            
            data.historico.forEach(item => {
                const dataFormatada = new Date(item.created_at).toLocaleString('pt-BR');
                const tamanhoMB = (item.tamanho_arquivo / (1024 * 1024)).toFixed(2);
                html += `<tr class="hover:bg-gray-50">`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${dataFormatada}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="font-medium">${item.nome_arquivo}</div>
                    <div class="text-xs text-gray-500">${tamanhoMB} MB</div>
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.usuario_nome || item.usuario_email}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.total_registros}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">${item.registros_sucesso}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm ${item.registros_erro > 0 ? 'text-red-600 font-medium' : 'text-gray-500'}">${item.registros_erro}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button onclick="abrirModalRemover(${item.id}, '${item.nome_arquivo.replace(/'/g, "\\'")}')" 
                            class="text-red-600 hover:text-red-800 focus:outline-none" title="Remover">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>`;
                html += `</tr>`;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-sm text-gray-500 text-center py-8">Nenhum histórico de upload encontrado para esta imobiliária</p>';
        }
    })
    .catch(error => {
        console.error('Erro ao carregar histórico:', error);
        container.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Erro ao carregar histórico</p>';
    });
}

// Carregar histórico quando imobiliária muda
document.getElementById('imobiliaria_id').addEventListener('change', function() {
    const imobiliariaId = this.value;
    carregarHistorico(imobiliariaId);
});

// Botão atualizar histórico
document.getElementById('btn-atualizar-historico').addEventListener('click', function() {
    const imobiliariaId = document.getElementById('imobiliaria_id').value;
    carregarHistorico(imobiliariaId);
});

// Função para processar arquivos selecionados
function processarArquivosSelecionados(files) {
    const arquivosSelecionados = document.getElementById('arquivos-selecionados');
    const listaArquivos = document.getElementById('lista-arquivos');
    const fileInput = document.getElementById('csv_file');
    const dropZone = document.getElementById('drop-zone');
    const dragHint = document.getElementById('drag-hint');
    
    // Atualizar input file com os arquivos
    const dataTransfer = new DataTransfer();
    Array.from(files).forEach(file => {
        // Validar extensão
        if (file.name.toLowerCase().endsWith('.csv')) {
            dataTransfer.items.add(file);
        }
    });
    fileInput.files = dataTransfer.files;
    
    if (fileInput.files && fileInput.files.length > 0) {
        arquivosSelecionados.classList.remove('hidden');
        listaArquivos.innerHTML = '';
        
        Array.from(fileInput.files).forEach((file, index) => {
            const li = document.createElement('li');
            const tamanhoMB = (file.size / (1024 * 1024)).toFixed(2);
            li.innerHTML = `<i class="fas fa-file-csv mr-2 text-blue-600"></i>${file.name} <span class="text-gray-400">(${tamanhoMB} MB)</span>`;
            listaArquivos.appendChild(li);
        });
        
        if (dropZone) {
            dropZone.classList.remove('border-gray-300', 'bg-gray-50');
            dropZone.classList.add('border-green-400', 'bg-green-50');
        }
        if (dragHint) dragHint.classList.add('hidden');
    } else {
        arquivosSelecionados.classList.add('hidden');
        if (dropZone) {
            dropZone.classList.remove('border-green-400', 'bg-green-50');
            dropZone.classList.add('border-gray-300', 'bg-gray-50');
        }
    }
}

// Mostrar arquivos selecionados quando mudar input
document.getElementById('csv_file').addEventListener('change', function(e) {
    processarArquivosSelecionados(this.files);
});

// Drag and Drop
const dropZone = document.getElementById('drop-zone');
const dragHint = document.getElementById('drag-hint');

if (dropZone) {
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('border-gray-300', 'bg-gray-50');
        dropZone.classList.add('border-blue-500', 'bg-blue-100');
        if (dragHint) dragHint.classList.remove('hidden');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Verificar se realmente saiu da zona (não apenas de um filho)
        if (!dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('border-blue-500', 'bg-blue-100');
            dropZone.classList.add('border-gray-300', 'bg-gray-50');
            if (dragHint) dragHint.classList.add('hidden');
        }
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        dropZone.classList.remove('border-blue-500', 'bg-blue-100');
        dropZone.classList.add('border-gray-300', 'bg-gray-50');
        if (dragHint) dragHint.classList.add('hidden');
        
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            processarArquivosSelecionados(files);
        }
    });
}

// Enviar formulário
document.getElementById('form-upload-csv').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const fileInput = document.getElementById('csv_file');
    const imobiliariaSelect = document.getElementById('imobiliaria_id');
    const button = document.getElementById('upload-csv-button');
    const result = document.getElementById('upload-csv-result');
    
    // Validar imobiliária
    if (!imobiliariaSelect.value) {
        alert('Por favor, selecione uma imobiliária');
        return;
    }
    
    // Validar arquivos
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Por favor, selecione pelo menos um arquivo CSV');
        return;
    }
    
    const formData = new FormData();
    
    // Adicionar imobiliária
    formData.append('imobiliaria_id', imobiliariaSelect.value);
    
    // Adicionar todos os arquivos
    Array.from(fileInput.files).forEach((file, index) => {
        formData.append(`csv_file[]`, file);
    });
    
    const originalText = button.innerHTML;
    const totalArquivos = fileInput.files.length;
    button.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Enviando ${totalArquivos} arquivo(s)...`;
    button.disabled = true;
    result.classList.add('hidden');
    
    fetch('<?= url('admin/upload/processar') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        result.classList.remove('hidden');
        
        if (data.success) {
            let html = `<div class="p-4 rounded-md bg-green-50 border border-green-200">
                <p class="font-medium text-green-800 mb-2">${data.message}</p>`;
            
            // Mostrar resumo por arquivo se houver múltiplos
            if (data.arquivos && data.arquivos.length > 0) {
                html += `<div class="mt-3 space-y-2">`;
                data.arquivos.forEach(arquivo => {
                    const statusClass = arquivo.erros > 0 ? 'text-yellow-700' : 'text-green-700';
                    html += `<div class="text-sm ${statusClass}">
                        <i class="fas fa-file-csv mr-1"></i>
                        <strong>${arquivo.nome}:</strong> ${arquivo.sucessos} sucesso(s), ${arquivo.erros} erro(s)
                    </div>`;
                });
                html += `</div>`;
            }
            
            if (data.erros > 0 && data.detalhes_erros && data.detalhes_erros.length > 0) {
                html += `<details class="mt-3">
                    <summary class="text-sm text-green-700 cursor-pointer">Ver detalhes dos erros (${data.erros})</summary>
                    <ul class="mt-2 text-sm text-green-600 list-disc list-inside space-y-1 max-h-60 overflow-y-auto">`;
                data.detalhes_erros.forEach(erro => {
                    html += `<li>${erro}</li>`;
                });
                html += `</ul></details>`;
            }
            
            html += `</div>`;
            result.innerHTML = html;
            
            // Atualizar histórico após upload bem-sucedido
            if (data.imobiliaria_id) {
                carregarHistorico(data.imobiliaria_id);
            }
            
            // Limpar formulário se tudo foi processado com sucesso
            if (data.erros === 0) {
                fileInput.value = '';
                document.getElementById('arquivos-selecionados').classList.add('hidden');
            }
        } else {
            result.innerHTML = `<div class="p-4 rounded-md bg-red-50 border border-red-200">
                <p class="font-medium text-red-800">Erro: ${data.error || 'Erro desconhecido'}</p>
            </div>`;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        result.classList.remove('hidden');
        result.innerHTML = `<div class="p-4 rounded-md bg-red-50 border border-red-200">
            <p class="font-medium text-red-800">Erro ao enviar arquivos</p>
        </div>`;
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
});

// Funções do modal de remoção
let idRegistroRemover = null;
let imobiliariaIdAtual = null;

function abrirModalRemover(id, nomeArquivo) {
    idRegistroRemover = id;
    document.getElementById('modal-id-remover').value = id;
    document.getElementById('modal-nome-arquivo').textContent = nomeArquivo;
    document.getElementById('input-confirmar-remover').value = '';
    document.getElementById('btn-confirmar-remover').disabled = true;
    document.getElementById('modal-remover').classList.remove('hidden');
    document.getElementById('input-confirmar-remover').focus();
    
    // Salvar imobiliária atual
    imobiliariaIdAtual = document.getElementById('imobiliaria_id').value;
}

function fecharModalRemover() {
    document.getElementById('modal-remover').classList.add('hidden');
    idRegistroRemover = null;
    document.getElementById('input-confirmar-remover').value = '';
}

// Validar input de confirmação
document.addEventListener('DOMContentLoaded', function() {
    const inputConfirmar = document.getElementById('input-confirmar-remover');
    const btnConfirmar = document.getElementById('btn-confirmar-remover');
    
    if (inputConfirmar) {
        inputConfirmar.addEventListener('input', function() {
            const valor = this.value.trim();
            btnConfirmar.disabled = valor !== 'REMOVER';
        });
        
        // Permitir remover com Enter se estiver habilitado
        inputConfirmar.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !btnConfirmar.disabled) {
                confirmarRemover();
            }
        });
    }
});

function confirmarRemover() {
    const inputConfirmar = document.getElementById('input-confirmar-remover');
    const valor = inputConfirmar.value.trim();
    
    if (valor !== 'REMOVER') {
        alert('Por favor, digite REMOVER para confirmar a remoção');
        return;
    }
    
    if (!idRegistroRemover) {
        alert('Erro: ID do registro não encontrado');
        return;
    }
    
    const btnConfirmar = document.getElementById('btn-confirmar-remover');
    const textoOriginal = btnConfirmar.innerHTML;
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Removendo...';
    
    fetch(`<?= url('admin/upload/remover') ?>/${idRegistroRemover}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModalRemover();
            
            // Recarregar histórico
            if (imobiliariaIdAtual) {
                carregarHistorico(imobiliariaIdAtual);
            }
            
            // Mostrar mensagem de sucesso
            const result = document.getElementById('upload-csv-result');
            result.classList.remove('hidden');
            result.innerHTML = `<div class="p-4 rounded-md bg-green-50 border border-green-200">
                <p class="font-medium text-green-800">${data.message}</p>
            </div>`;
            
            // Esconder mensagem após 3 segundos
            setTimeout(() => {
                result.classList.add('hidden');
            }, 3000);
        } else {
            alert('Erro ao remover registro: ' + (data.error || 'Erro desconhecido'));
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = textoOriginal;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover registro');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = textoOriginal;
    });
}

// Funções do modal de filtros
function abrirModalFiltros() {
    document.getElementById('modal-filtros').classList.remove('hidden');
    carregarFiltros();
}

function fecharModalFiltros() {
    document.getElementById('modal-filtros').classList.add('hidden');
}

function aplicarFiltros() {
    carregarFiltros();
}

function carregarFiltros() {
    const container = document.getElementById('container-imobiliarias-filtros');
    container.innerHTML = '<p class="text-sm text-gray-500 text-center py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando imobiliárias...</p>';
    
    const buscaNome = document.getElementById('filtro-busca-nome').value.trim();
    const statusLancado = document.getElementById('filtro-status-lancado').value;
    const mes = document.getElementById('filtro-mes').value;
    const ano = document.getElementById('filtro-ano').value;
    
    const params = new URLSearchParams();
    if (buscaNome) params.append('busca_nome', buscaNome);
    if (statusLancado) params.append('status_lancado', statusLancado);
    if (mes) params.append('mes', mes);
    if (ano) params.append('ano', ano);
    
    fetch(`<?= url('admin/upload/filtros') ?>?${params.toString()}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.imobiliarias && data.imobiliarias.length > 0) {
            let html = '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50 sticky top-0 z-10 shadow-sm"><tr>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Imobiliária</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Status</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Upload</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Lançado</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Último Lançamento</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Total Uploads</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Ações</th>';
            html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
            
            data.imobiliarias.forEach(imob => {
                const statusLancadoClass = imob.tem_lancamento ? 'text-green-600' : 'text-red-600';
                const statusLancadoTexto = imob.tem_lancamento ? 'Sim' : 'Não';
                const statusImobiliariaClass = imob.status === 'ATIVA' ? 'text-green-600' : 'text-gray-400';
                
                html += `<tr class="hover:bg-gray-50">`;
                html += `<td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">${escapeHtml(imob.nome_fantasia || imob.nome)}</div>
                    <div class="text-xs text-gray-500">ID: ${imob.id}</div>
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm ${statusImobiliariaClass}">${escapeHtml(imob.status)}</span>
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <div id="drop-zone-${imob.id}" class="relative border border-dashed border-gray-300 rounded px-2 py-1 text-center hover:border-blue-400 transition-colors bg-gray-50 hover:bg-blue-50 cursor-pointer">
                                <input type="file" 
                                       id="file-upload-${imob.id}" 
                                       accept=".csv" 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                       onchange="prepararUpload(${imob.id}, this.files[0])">
                                <div class="relative z-0 flex items-center justify-center gap-1.5">
                                    <i class="fas fa-cloud-upload-alt text-xs text-gray-400"></i>
                                    <span class="text-xs text-gray-700 whitespace-nowrap">Arraste ou clique</span>
                                </div>
                            </div>
                        </div>
                        <button id="btn-upload-${imob.id}" 
                                onclick="enviarUpload(${imob.id})"
                                disabled
                                class="inline-flex items-center px-2 py-1 border border-transparent rounded text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <i class="fas fa-paper-plane text-xs mr-1"></i>
                            Enviar
                        </button>
                    </div>
                    <div id="upload-status-${imob.id}" class="text-xs text-gray-500 mt-1 truncate max-w-[200px]" title=""></div>
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium ${statusLancadoClass}">${statusLancadoTexto}</span>
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${imob.ultimo_lancamento ? escapeHtml(imob.ultimo_lancamento) : '<span class="text-gray-400">Nunca</span>'}
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${imob.total_uploads}
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button onclick="visualizarPlanilha(${imob.id}, '${escapeHtml(imob.nome_fantasia || imob.nome)}')" 
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-eye mr-1"></i>
                        Visualizar
                    </button>
                </td>`;
                html += `</tr>`;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
            
            // Adicionar eventos de drag and drop para cada imobiliária
            data.imobiliarias.forEach(imob => {
                const dropZone = document.getElementById(`drop-zone-${imob.id}`);
                const fileInput = document.getElementById(`file-upload-${imob.id}`);
                
                if (dropZone && fileInput) {
                    dropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropZone.classList.remove('border-gray-300', 'bg-gray-50');
                        dropZone.classList.add('border-blue-500', 'bg-blue-100');
                    });
                    
                    dropZone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (!dropZone.contains(e.relatedTarget)) {
                            dropZone.classList.remove('border-blue-500', 'bg-blue-100');
                            dropZone.classList.add('border-gray-300', 'bg-gray-50');
                        }
                    });
                    
                    dropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        dropZone.classList.remove('border-blue-500', 'bg-blue-100');
                        dropZone.classList.add('border-gray-300', 'bg-gray-50');
                        
                        const files = e.dataTransfer.files;
                        if (files && files.length > 0) {
                            const arquivo = files[0];
                            if (arquivo.name.toLowerCase().endsWith('.csv')) {
                                // Atualizar input file
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(arquivo);
                                fileInput.files = dataTransfer.files;
                                prepararUpload(imob.id, arquivo);
                            } else {
                                alert('Por favor, selecione um arquivo CSV');
                            }
                        }
                    });
                }
            });
        } else {
            container.innerHTML = '<p class="text-sm text-gray-500 text-center py-8">Nenhuma imobiliária encontrada com os filtros aplicados</p>';
        }
    })
    .catch(error => {
        console.error('Erro ao carregar filtros:', error);
        container.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Erro ao carregar imobiliárias</p>';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Armazenar arquivo selecionado por imobiliária
const arquivosSelecionadosModal = {};

function prepararUpload(imobiliariaId, arquivo) {
    const dropZone = document.getElementById(`drop-zone-${imobiliariaId}`);
    
    if (!arquivo) {
        arquivosSelecionadosModal[imobiliariaId] = null;
        document.getElementById(`btn-upload-${imobiliariaId}`).disabled = true;
        const statusEl = document.getElementById(`upload-status-${imobiliariaId}`);
        if (statusEl) {
            statusEl.textContent = '';
            statusEl.title = '';
            statusEl.classList.remove('text-green-600', 'font-medium');
            statusEl.classList.add('text-gray-500');
        }
        if (dropZone) {
            dropZone.classList.remove('border-green-400', 'bg-green-50');
            dropZone.classList.add('border-gray-300', 'bg-gray-50');
            // Restaurar texto original
            const content = dropZone.querySelector('.relative.z-0');
            if (content) {
                content.innerHTML = `<i class="fas fa-cloud-upload-alt text-xs text-gray-400"></i><span class="text-xs text-gray-700 whitespace-nowrap ml-1.5">Arraste ou clique</span>`;
            }
        }
        return;
    }
    
    // Validar extensão
    if (!arquivo.name.toLowerCase().endsWith('.csv')) {
        alert('Por favor, selecione um arquivo CSV');
        document.getElementById(`file-upload-${imobiliariaId}`).value = '';
        if (dropZone) {
            dropZone.classList.remove('border-red-400', 'bg-red-50');
            dropZone.classList.add('border-gray-300', 'bg-gray-50');
        }
        return;
    }
    
    arquivosSelecionadosModal[imobiliariaId] = arquivo;
    document.getElementById(`btn-upload-${imobiliariaId}`).disabled = false;
    
    // Atualizar status com nome do arquivo
    const statusEl = document.getElementById(`upload-status-${imobiliariaId}`);
    if (statusEl) {
        statusEl.textContent = arquivo.name;
        statusEl.title = arquivo.name; // Tooltip com nome completo
        statusEl.classList.remove('text-gray-500');
        statusEl.classList.add('text-green-600', 'font-medium');
    }
    
    // Atualizar visual da drop zone
    if (dropZone) {
        dropZone.classList.remove('border-gray-300', 'bg-gray-50');
        dropZone.classList.add('border-green-400', 'bg-green-50');
        // Atualizar texto interno
        const content = dropZone.querySelector('.relative.z-0');
        if (content) {
            const nomeArquivo = arquivo.name.length > 15 ? arquivo.name.substring(0, 12) + '...' : arquivo.name;
            content.innerHTML = `<i class="fas fa-file-csv text-xs text-green-600"></i><span class="text-xs text-green-700 font-medium ml-1">${nomeArquivo}</span>`;
        }
    }
}

function visualizarPlanilha(imobiliariaId, nomeImobiliaria) {
    const modal = document.getElementById('modal-visualizar-planilha');
    const titulo = document.getElementById('modal-planilha-titulo');
    const content = document.getElementById('modal-planilha-content');
    
    titulo.textContent = `Dados da Planilha - ${nomeImobiliaria}`;
    content.innerHTML = '<p class="text-sm text-gray-500 text-center py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando dados...</p>';
    modal.classList.remove('hidden');
    
    fetch(`<?= url('admin/upload/historico') ?>/${imobiliariaId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.historico && data.historico.length > 0) {
            let html = '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50 sticky top-0 z-10 shadow-sm"><tr>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Data/Hora</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Arquivo</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Usuário</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Total</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Sucesso</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Erros</th>';
            html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Ações</th>';
            html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
            
            data.historico.forEach(item => {
                const dataFormatada = new Date(item.created_at).toLocaleString('pt-BR');
                const tamanhoMB = (item.tamanho_arquivo / (1024 * 1024)).toFixed(2);
                html += `<tr class="hover:bg-gray-50">`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${dataFormatada}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="font-medium">${escapeHtml(item.nome_arquivo)}</div>
                    <div class="text-xs text-gray-500">${tamanhoMB} MB</div>
                </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(item.usuario_nome || item.usuario_email || 'N/A')}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.total_registros}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">${item.registros_sucesso}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm ${item.registros_erro > 0 ? 'text-red-600 font-medium' : 'text-gray-500'}">${item.registros_erro}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${item.caminho_arquivo ? `
                        <button onclick="baixarPlanilha(${item.id}, '${escapeHtml(item.nome_arquivo)}')" 
                                class="inline-flex items-center px-3 py-1.5 border border-blue-300 rounded-md text-xs font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-download mr-1"></i>
                            Baixar
                        </button>
                    ` : '<span class="text-xs text-gray-400">N/A</span>'}
                </td>`;
                html += `</tr>`;
            });
            
            html += '</tbody></table>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p class="text-sm text-gray-500 text-center py-8">Nenhum histórico de upload encontrado para esta imobiliária</p>';
        }
    })
    .catch(error => {
        console.error('Erro ao carregar histórico:', error);
        content.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Erro ao carregar dados</p>';
    });
}

function fecharModalPlanilha() {
    document.getElementById('modal-visualizar-planilha').classList.add('hidden');
}

function baixarPlanilha(historicoId, nomeArquivo) {
    // Criar link temporário para download
    const link = document.createElement('a');
    link.href = `<?= url('admin/upload/download') ?>/${historicoId}`;
    link.download = nomeArquivo;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function enviarUpload(imobiliariaId) {
    const arquivo = arquivosSelecionadosModal[imobiliariaId];
    if (!arquivo) {
        alert('Por favor, selecione um arquivo primeiro');
        return;
    }
    
    const btnUpload = document.getElementById(`btn-upload-${imobiliariaId}`);
    const statusUpload = document.getElementById(`upload-status-${imobiliariaId}`);
    const fileInput = document.getElementById(`file-upload-${imobiliariaId}`);
    
    const textoOriginal = btnUpload.innerHTML;
    btnUpload.disabled = true;
    btnUpload.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Enviando...';
    statusUpload.textContent = 'Enviando...';
    statusUpload.className = 'text-xs text-blue-600';
    
    const formData = new FormData();
    formData.append('imobiliaria_id', imobiliariaId);
    formData.append('csv_file[]', arquivo);
    
    fetch('<?= url('admin/upload/processar') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusUpload.textContent = 'Enviado com sucesso!';
            statusUpload.className = 'text-xs text-green-600';
            btnUpload.innerHTML = textoOriginal;
            
            // Limpar seleção
            arquivosSelecionadosModal[imobiliariaId] = null;
            fileInput.value = '';
            btnUpload.disabled = true;
            
            // Recarregar a lista após 1 segundo
            setTimeout(() => {
                aplicarFiltros();
            }, 1000);
            
            // Esconder status após 3 segundos
            setTimeout(() => {
                statusUpload.textContent = '';
            }, 3000);
        } else {
            statusUpload.textContent = 'Erro: ' + (data.error || 'Erro desconhecido');
            statusUpload.className = 'text-xs text-red-600';
            btnUpload.innerHTML = textoOriginal;
            btnUpload.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        statusUpload.textContent = 'Erro ao enviar arquivo';
        statusUpload.className = 'text-xs text-red-600';
        btnUpload.innerHTML = textoOriginal;
        btnUpload.disabled = false;
    });
}

// Event listener para abrir modal de filtros
document.addEventListener('DOMContentLoaded', function() {
    const btnFiltros = document.getElementById('btn-filtros');
    if (btnFiltros) {
        btnFiltros.addEventListener('click', abrirModalFiltros);
    }
    
    // Permitir fechar modal clicando fora
    const modalFiltros = document.getElementById('modal-filtros');
    if (modalFiltros) {
        modalFiltros.addEventListener('click', function(e) {
            if (e.target === modalFiltros) {
                fecharModalFiltros();
            }
        });
    }
    
    // Permitir fechar modal de planilha clicando fora
    const modalPlanilha = document.getElementById('modal-visualizar-planilha');
    if (modalPlanilha) {
        modalPlanilha.addEventListener('click', function(e) {
            if (e.target === modalPlanilha) {
                fecharModalPlanilha();
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

