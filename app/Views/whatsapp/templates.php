<?php
// Usa o layout admin
ob_start();
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
		<h3 class="text-xl font-semibold text-gray-800">Templates WhatsApp</h3>
        <a href="#" id="btn-open-modal" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
			<i class="fas fa-plus mr-2"></i>Novo Template
		</a>
	</div>

	<div class="mb-4 text-sm text-gray-600">
		<p>Gerencie as mensagens enviadas via WhatsApp.</p>
	</div>

    <div class="space-y-6">
        <?php if (!empty($templates)): foreach ($templates as $tpl): ?>
        <div class="border rounded-lg overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 flex items-center justify-between">
                <div class="font-medium">
                    <?= htmlspecialchars($tpl['nome']) ?>
                    <?php if (!empty($tpl['padrao'])): ?>
                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Padrão</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-xs text-gray-500">Tipo: <?= htmlspecialchars($tpl['tipo']) ?> • <?= !empty($tpl['ativo']) ? 'Ativo' : 'Inativo' ?></div>
                    <a href="<?= url('admin/templates-whatsapp/' . $tpl['id'] . '/edit') ?>" title="Editar" class="text-gray-500 hover:text-gray-700"><i class="far fa-edit"></i></a>
                    <form method="post" action="<?= url('admin/templates-whatsapp/' . $tpl['id'] . '/delete') ?>" onsubmit="return confirm('Excluir este template?');">
                        <button type="submit" title="Excluir" class="text-gray-500 hover:text-red-600"><i class="far fa-trash-alt"></i></button>
                    </form>
                </div>
            </div>
            <pre class="p-4 text-sm bg-gray-50 whitespace-pre-wrap"><?= htmlspecialchars($tpl['corpo']) ?></pre>
        </div>
        <?php endforeach; else: ?>
        <div class="border rounded-lg p-6 text-gray-500 text-sm">Nenhum template cadastrado.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Template -->
<div id="modal-template" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="absolute inset-0 flex items-start justify-center py-10 px-4">
        <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b flex-shrink-0">
                <h4 class="text-lg font-semibold">Novo Template</h4>
                <button id="btn-close-modal" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <form method="post" action="<?= isset($editTemplate) ? url('admin/templates-whatsapp/' . $editTemplate['id']) : url('admin/templates-whatsapp') ?>" class="px-6 py-4 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600">Nome do Template</label>
                        <input type="text" name="nome" class="mt-1 w-full border rounded px-3 py-2" value="<?= htmlspecialchars($editTemplate['nome'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Tipo de Mensagem</label>
                        <select name="tipo" class="mt-1 w-full border rounded px-3 py-2">
                            <?php $tipoSel = $editTemplate['tipo'] ?? 'Nova Solicitação'; ?>
                            <option <?= $tipoSel==='Nova Solicitação'?'selected':'' ?>>Nova Solicitação</option>
                            <option <?= $tipoSel==='Horário Confirmado'?'selected':'' ?>>Horário Confirmado</option>
                            <option <?= $tipoSel==='Solicitar Novos Horários'?'selected':'' ?>>Solicitar Novos Horários</option>
                            <option <?= $tipoSel==='Atualização de Status'?'selected':'' ?>>Atualização de Status</option>
                            <option <?= $tipoSel==='Cancelamento'?'selected':'' ?>>Cancelamento</option>
                            <option <?= $tipoSel==='Confirmação de Serviço'?'selected':'' ?>>Confirmação de Serviço</option>
                            <option <?= $tipoSel==='Lembrete Pré-Serviço'?'selected':'' ?>>Lembrete Pré-Serviço</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="text-sm text-gray-600">Corpo da Mensagem</label>
                    <textarea id="corpo" name="corpo" rows="8" class="mt-1 w-full border rounded px-3 py-2" required><?= htmlspecialchars($editTemplate['corpo'] ?? '') ?></textarea>
                </div>

                <input type="hidden" name="variaveis[]" id="variaveis-collector" value="">

                <div class="mt-4">
                    <div class="text-sm text-gray-600 mb-2">Variáveis Disponíveis</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <?php
                        $vars = [
                            'protocol' => 'Protocolo',
                            'contrato_numero' => 'Nº Contrato',
                            'protocolo_seguradora' => 'Protocolo Seguradora',
                            'cliente_nome' => 'Nome Cliente',
                            'cliente_cpf' => 'CPF',
                            'cliente_telefone' => 'Telefone',
                            'endereco_completo' => 'Endereço Completo',
                            'servico_tipo' => 'Tipo de Serviço',
                            'descricao_problema' => 'Descrição Problema',
                            'status_atual' => 'Status',
                            'data_agendamento' => 'Data Agendamento',
                            'horario_agendamento' => 'Horário Agendamento',
                            'imobiliaria_nome' => 'Imobiliária',
                            'link_rastreamento' => 'Link Rastreamento',
                            'link_confirmacao' => 'Link Confirmação',
                            'link_cancelamento' => 'Link Cancelamento Horário',
                            'link_cancelamento_solicitacao' => 'Link Cancelar Solicitação',
                            'link_compra_peca' => 'Link Compra Peça',
                            'link_reagendamento' => 'Link Reagendamento',
                            'prestador_nome' => 'Prestador',
                        ];
                        foreach ($vars as $k => $label): ?>
                        <button type="button" data-var="<?= $k ?>" class="px-3 py-2 border rounded text-sm hover:bg-gray-50 insert-var"><?= $label ?></button>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Use variáveis no formato {{variavel}}.</p>
                </div>

                <div class="mt-4 flex items-center space-x-6">
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" name="ativo" class="h-4 w-4" <?= (isset($editTemplate) ? (!empty($editTemplate['ativo'])?'checked':'') : 'checked') ?>>
                        <span>Ativo</span>
                    </label>
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" name="padrao" class="h-4 w-4" <?= !empty($editTemplate['padrao'])?'checked':'' ?> >
                        <span>Template Padrão</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" id="btn-cancel" class="px-4 py-2 border rounded">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    const modal = document.getElementById('modal-template');
    const openBtn = document.getElementById('btn-open-modal');
    const closeBtn = document.getElementById('btn-close-modal');
    const cancelBtn = document.getElementById('btn-cancel');
    const corpo = document.getElementById('corpo');
    const collector = document.getElementById('variaveis-collector');
    let varsUsed = [];

    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }

    openBtn?.addEventListener('click', function(e){ e.preventDefault(); open(); });
    closeBtn?.addEventListener('click', function(){ close(); });
    cancelBtn?.addEventListener('click', function(){ close(); });

    // Auto-abrir se edição
    <?php if (isset($editTemplate)): ?>
    open();
    <?php endif; ?>

    document.querySelectorAll('.insert-var').forEach(function(btn){
        btn.addEventListener('click', function(){
            const key = this.getAttribute('data-var');
            const token = '{{' + key + '}}';
            insertAtCursor(corpo, token);
            if (!varsUsed.includes(key)) { varsUsed.push(key); collector.value = JSON.stringify(varsUsed); }
        });
    });

    function insertAtCursor(field, text){
        const start = field.selectionStart || 0;
        const end = field.selectionEnd || 0;
        const before = field.value.substring(0, start);
        const after = field.value.substring(end);
        field.value = before + text + after;
        const pos = start + text.length;
        field.selectionStart = field.selectionEnd = pos;
        field.focus();
    }
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>


