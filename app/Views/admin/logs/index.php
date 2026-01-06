<?php
$title = $title ?? 'Visualizador de Logs';
$currentPage = 'logs';
$pageTitle = 'Visualizador de Logs';
ob_start();
?>

<!-- Cabeçalho -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-file-alt mr-2"></i>
            Visualizador de Logs
        </h1>
        <p class="text-gray-600 mt-1">Visualize e analise os logs do sistema</p>
    </div>
    <div class="flex gap-2">
        <?php if (!empty($logs['file_exists']) && $logs['file_exists']): ?>
            <a href="<?= url('admin/logs/download?file=' . urlencode($logFile)) ?>" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-download mr-2"></i>
                Download
            </a>
            <button onclick="limparLog()" 
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-trash mr-2"></i>
                Limpar Log
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros e Controles -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <form method="GET" action="<?= url('admin/logs') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Seleção de Arquivo -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo de Log</label>
            <select name="file" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php if (empty($availableLogs) || !is_array($availableLogs)): ?>
                    <option value="">Nenhum arquivo de log disponível</option>
                <?php else: ?>
                    <?php foreach ($availableLogs as $log): ?>
                        <?php 
                        $logName = $log['name'] ?? '';
                        $logLabel = $log['label'] ?? $log['name'] ?? 'Log';
                        $logSize = isset($log['size']) ? number_format($log['size'] / 1024, 2) : '0.00';
                        $isSelected = ($logFile ?? '') === $logName;
                        ?>
                        <option value="<?= htmlspecialchars($logName) ?>" 
                                <?= $isSelected ? 'selected' : '' ?>>
                            <?= htmlspecialchars($logLabel) ?> (<?= $logSize ?> KB)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- Filtro de Texto -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar (texto)</label>
            <input type="text" 
                   name="filter" 
                   value="<?= htmlspecialchars($filter) ?>" 
                   placeholder="Ex: DEBUG confirmacaoHorario"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Número de Linhas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Linhas</label>
            <select name="lines" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="50" <?= $lines === 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $lines === 100 ? 'selected' : '' ?>>100</option>
                <option value="200" <?= $lines === 200 ? 'selected' : '' ?>>200</option>
                <option value="500" <?= $lines === 500 ? 'selected' : '' ?>>500</option>
                <option value="1000" <?= $lines === 1000 ? 'selected' : '' ?>>1000</option>
            </select>
        </div>

        <!-- Botão de Busca -->
        <div class="flex items-end">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i>
                Buscar
            </button>
        </div>
    </form>

    <!-- Informações do Arquivo -->
    <?php if (!empty($logs['file_exists']) && $logs['file_exists']): ?>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Total de linhas:</span>
                <span class="font-semibold text-gray-900"><?= number_format($logs['total_lines'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-600">Exibindo:</span>
                <span class="font-semibold text-gray-900"><?= number_format($logs['showing_lines'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-600">Tamanho:</span>
                <span class="font-semibold text-gray-900"><?= number_format(($logs['file_size'] ?? 0) / 1024, 2) ?> KB</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Área de Logs -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto" style="max-height: 70vh; overflow-y: auto;">
        <?php if (!empty($logs['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl mr-3 mt-1"></i>
                    <div class="flex-1">
                        <h3 class="text-red-800 font-semibold mb-1">Erro ao carregar log</h3>
                        <p class="text-red-700 text-sm break-words">
                            <?= htmlspecialchars($logs['error'] ?? 'Erro desconhecido') ?>
                        </p>
                        <?php if (isset($logs['error']) && (stripos($logs['error'], 'não encontrado') !== false || stripos($logs['error'], 'não existe') !== false)): ?>
                            <p class="text-red-600 text-xs mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                O arquivo de log não foi encontrado no caminho especificado. Verifique se o arquivo existe e se o caminho está correto.
                            </p>
                            <p class="text-red-600 text-xs mt-1">
                                <i class="fas fa-lightbulb mr-1"></i>
                                <strong>Dica:</strong> Verifique os logs disponíveis no dropdown acima ou entre em contato com o administrador do sistema.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php elseif (empty($logs['content']) || count($logs['content']) === 0): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-file-alt text-5xl mb-4 block"></i>
                <p class="text-lg font-medium mb-2">Nenhum log encontrado</p>
                <?php if (!empty($filter)): ?>
                    <p class="text-sm">Nenhuma linha corresponde ao filtro: <strong class="text-gray-300"><?= htmlspecialchars($filter) ?></strong></p>
                    <p class="text-sm mt-2">
                        <a href="<?= url('admin/logs?file=' . urlencode($logFile) . '&lines=' . $lines) ?>" 
                           class="text-blue-400 hover:text-blue-300 underline">
                            Remover filtro e ver todos os logs
                        </a>
                    </p>
                <?php else: ?>
                    <p class="text-sm">O arquivo de log está vazio ou não contém dados.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="font-mono text-sm">
                <?php if (count($logs['content']) > 0): ?>
                    <?php foreach ($logs['content'] as $line): ?>
                        <div class="mb-1 px-2 py-1 rounded hover:bg-gray-800 transition-colors <?= $line['highlight'] ? 'bg-gray-800 border-l-4 ' . ($line['type'] === 'error' ? 'border-red-500' : ($line['type'] === 'warning' ? 'border-yellow-500' : ($line['type'] === 'debug' ? 'border-blue-500' : 'border-green-500'))) : '' ?>">
                            <div class="flex items-start gap-2">
                                <span class="text-gray-500 select-none flex-shrink-0" style="min-width: 70px; font-size: 0.85rem;">
                                    <?= number_format($line['number']) ?>
                                </span>
                                <span class="flex-1 break-words whitespace-pre-wrap <?= 
                                    $line['type'] === 'error' ? 'text-red-400' : 
                                    ($line['type'] === 'warning' ? 'text-yellow-400' : 
                                    ($line['type'] === 'debug' ? 'text-blue-400' : 
                                    ($line['type'] === 'success' ? 'text-green-400' : 'text-gray-300')))
                                ?>">
                                    <?= htmlspecialchars($line['content']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <p>Nenhuma linha de log para exibir</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Atualização Automática -->
<div class="mt-4 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" id="autoRefresh" class="mr-2">
            <span class="text-sm text-gray-700">Atualizar automaticamente (30s)</span>
        </label>
    </div>
    <button onclick="scrollToTop()" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-up mr-2"></i>
        Voltar ao topo
    </button>
</div>

<script>
let autoRefreshInterval = null;

document.getElementById('autoRefresh')?.addEventListener('change', function(e) {
    if (e.target.checked) {
        autoRefreshInterval = setInterval(() => {
            location.reload();
        }, 30000); // 30 segundos
    } else {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }
});

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limparLog() {
    if (!confirm('Tem certeza que deseja limpar este log? Um backup será criado automaticamente.')) {
        return;
    }

    const formData = new FormData();
    formData.append('file', '<?= htmlspecialchars($logFile) ?>');

    fetch('<?= url('admin/logs/limpar') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Log limpo com sucesso! ' + (data.message || ''));
            location.reload();
        } else {
            alert('Erro ao limpar log: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao limpar log');
    });
}

// Destacar linhas ao passar o mouse
document.querySelectorAll('.font-mono > div').forEach(line => {
    line.addEventListener('mouseenter', function() {
        this.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
    });
    line.addEventListener('mouseleave', function() {
        if (!this.classList.contains('bg-gray-800')) {
            this.style.backgroundColor = '';
        }
    });
});
</script>

<style>
/* Scrollbar personalizada */
.bg-gray-900::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.bg-gray-900::-webkit-scrollbar-track {
    background: #1f2937;
    border-radius: 4px;
}

.bg-gray-900::-webkit-scrollbar-thumb {
    background: #4b5563;
    border-radius: 4px;
}

.bg-gray-900::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
