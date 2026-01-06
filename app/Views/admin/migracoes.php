<?php
$title = $title ?? 'Migrações';
$currentPage = 'dashboard';
$pageTitle = 'Executar Migrações';
ob_start();
?>
<?php
$sqlScripts = $sqlScripts ?? [];
$previousScriptFile = $previous_script_file ?? '';
$previousSqlText = $previous_sql_text ?? '';
?>

<div class="max-w-3xl mx-auto">
    <?php if (!empty($error)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Migração de colunas principais</h2>
        <p class="text-sm text-gray-600 mb-4">Adiciona/atualiza colunas utilizadas pelo fluxo de agendamento e lembretes.</p>
        <ul class="text-sm mb-4">
            <li class="mb-1">descricao_card: 
                <?php if (!empty($hasDescricaoCard)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li class="mb-1">horario_confirmado: 
                <?php if (!empty($hasHorarioConfirmado)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li>horario_confirmado_raw: 
                <?php if (!empty($hasHorarioRaw)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li class="mb-1">confirmed_schedules (JSON): 
                <?php if (!empty($hasConfirmedSchedules)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li class="mb-1">datas_opcoes (JSON): 
                <?php if (!empty($hasDatasOpcoes)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li class="mb-1">data_limite_peca: 
                <?php if (!empty($hasDataLimitePeca)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li class="mb-1">data_ultimo_lembrete: 
                <?php if (!empty($hasDataUltimoLembrete)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
            <li>lembretes_enviados: 
                <?php if (!empty($hasLembretesEnviados)): ?>
                    <span class="text-green-700 font-semibold">Aplicada</span>
                <?php else: ?>
                    <span class="text-red-700 font-semibold">Pendente</span>
                <?php endif; ?>
            </li>
        </ul>
        <form method="POST" action="/admin/migracoes/run">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\View::csrfToken() ?>">
            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" <?= (!empty($hasDescricaoCard) && !empty($hasHorarioConfirmado) && !empty($hasHorarioRaw) && !empty($hasConfirmedSchedules) && !empty($hasDatasOpcoes) && !empty($hasDataLimitePeca) && !empty($hasDataUltimoLembrete) && !empty($hasLembretesEnviados)) ? 'disabled' : '' ?>>Executar migração</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Executar script SQL</h2>
        <p class="text-sm text-gray-600 mb-4">
            Utilize esta ferramenta para rodar rapidamente scripts `.sql` já existentes na pasta <code>scripts/</code> ou colar comandos manuais.
            Use com cautela: os comandos são executados diretamente no banco atual.
        </p>
        <form id="formRunScript" method="POST" action="/admin/migracoes/run-script" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\View::csrfToken() ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Selecionar arquivo .sql</label>
                <select name="script_file" id="script_file" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">-- Nenhum (usar apenas o SQL manual, se preenchido) --</option>
                    <?php foreach ($sqlScripts as $path => $label): ?>
                        <option value="<?= htmlspecialchars($path) ?>" <?= $previousScriptFile === $path ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Os scripts listados são lidos das pastas <code>scripts/</code> e <code>scripts/migrations/</code>.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SQL manual (opcional)</label>
                <textarea name="sql_text" id="sql_text" rows="6" class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"><?= htmlspecialchars($previousSqlText) ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Você pode colar comandos adicionais aqui. Eles serão executados após o arquivo selecionado (se houver).</p>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">As instruções são executadas na ordem exibida e separadas por ponto e vírgula.</span>
                <button type="submit" id="btnRunScript" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    <i class="fas fa-play mr-2"></i>
                    Executar script
                </button>
            </div>
            <div id="resultadoScript" class="hidden mt-4"></div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Limpar "Disponibilidade:" das Descrições</h2>
        <p class="text-sm text-gray-600 mb-4">Remove "Disponibilidade: ..." das descrições existentes que já foram adicionadas automaticamente.</p>
        <form id="formLimparDisponibilidade" method="POST" action="/admin/migracoes/limpar-disponibilidade" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\View::csrfToken() ?>">
            <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                <i class="fas fa-broom mr-2"></i>
                Limpar Descrições
            </button>
        </form>
        <div id="resultadoLimpar" class="mt-3 hidden"></div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Limpar todas as solicitações</h2>
        <p class="text-sm text-gray-600 mb-4">Apaga solicitações, histórico e fotos. Ação irreversível.</p>
        <form method="POST" action="/admin/migracoes/purge" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\View::csrfToken() ?>">
            <label class="block text-sm text-gray-700">Digite <strong>LIMPAR</strong> para confirmar</label>
            <input name="confirm_text" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="LIMPAR">
            <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Apagar tudo</button>
        </form>
    </div>
</div>

<script>
// Executar script SQL via AJAX
document.getElementById('formRunScript')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const btn = document.getElementById('btnRunScript');
    const resultado = document.getElementById('resultadoScript');
    const originalText = btn.innerHTML;
    const scriptFile = document.getElementById('script_file').value;
    const sqlText = document.getElementById('sql_text').value.trim();
    
    // Validar que pelo menos um campo está preenchido
    if (!scriptFile && !sqlText) {
        resultado.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Selecione um arquivo SQL ou informe o conteúdo manualmente.
            </div>
        `;
        resultado.classList.remove('hidden');
        return;
    }
    
    // Desabilitar botão e mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Executando...';
    resultado.classList.add('hidden');
    
    // Coletar dados do formulário
    const formData = new FormData(form);
    
    // Fazer requisição
    fetch('/admin/migracoes/run-script', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Verificar se a resposta é JSON ou HTML
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Se for HTML, recarregar a página para mostrar mensagem
            return response.text().then(() => {
                window.location.reload();
                return null;
            });
        }
    })
    .then(data => {
        if (data === null) {
            // Página foi recarregada
            return;
        }
        
        if (data.success) {
            resultado.innerHTML = `
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    ${data.message || 'Script executado com sucesso!'}
                    ${data.executadas ? `<br><strong>${data.executadas} instrução(ões) executada(s).</strong>` : ''}
                </div>
            `;
            resultado.classList.remove('hidden');
            
            // Limpar campos após sucesso
            document.getElementById('script_file').value = '';
            document.getElementById('sql_text').value = '';
        } else {
            resultado.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${data.error || 'Erro ao executar script'}
                </div>
            `;
            resultado.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        resultado.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Erro ao processar requisição. Tente novamente.
            </div>
        `;
        resultado.classList.remove('hidden');
    })
    .finally(() => {
        // Restaurar botão
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

// Limpar disponibilidade das descrições via AJAX
document.getElementById('formLimparDisponibilidade')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const btn = form.querySelector('button[type="submit"]');
    const resultado = document.getElementById('resultadoLimpar');
    const originalText = btn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Limpando...';
    resultado.classList.add('hidden');
    
    // Coletar dados do formulário
    const formData = new FormData(form);
    
    // Fazer requisição
    fetch('/admin/migracoes/limpar-disponibilidade', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultado.innerHTML = `
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    ${data.message}
                    ${data.atualizadas > 0 ? `<br><strong>${data.atualizadas} registro(s) atualizado(s).</strong>` : ''}
                    ${data.restantes > 0 ? `<br><small>Ainda há ${data.restantes} registro(s) com "Disponibilidade:" (pode ser formato diferente).</small>` : ''}
                </div>
            `;
            resultado.classList.remove('hidden');
        } else {
            resultado.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${data.error || 'Erro ao limpar descrições'}
                </div>
            `;
            resultado.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        resultado.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Erro ao processar requisição
            </div>
        `;
        resultado.classList.remove('hidden');
    })
    .finally(() => {
        // Restaurar botão
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>



