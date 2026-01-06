<?php
$title = 'Relatórios';
$currentPage = 'relatorios';
$pageTitle = 'Relatórios';
ob_start();

$totalSolicitacoes = $resumo['total_solicitacoes'] ?? 0;
$totalPendentes = $resumo['total_pendentes'] ?? 0;
$totalAgendados = $resumo['total_agendados'] ?? 0;
$totalBuscando = $resumo['total_buscando_prestador'] ?? 0;
$totalConcluidos = $resumo['total_concluidos'] ?? 0;
$totalCancelados = $resumo['total_cancelados'] ?? 0;
$totalReembolsos = $resumo['total_reembolsos'] ?? 0;
$totalSlaAtrasado = $resumo['total_sla_atrasado'] ?? 0;
$suportaSla = $suportaSla ?? false;

$statusSelecionados = $filtros['status_ids'] ?? [];
if (empty($statusSelecionados) && isset($filtros['status_id'])) {
    $statusSelecionados = [$filtros['status_id']];
}

$statusSelecionados = array_map('strval', $statusSelecionados);

$limiteAtual = isset($limiteAtual) ? (int) $limiteAtual : 100;
$limiteOpcoes = [100, 200, 500, 1000];
if (!in_array($limiteAtual, $limiteOpcoes, true)) {
    $limiteOpcoes[] = $limiteAtual;
    sort($limiteOpcoes);
}

$temWhatsApp = array_key_exists('whatsapp_enviado', $solicitacoes[0] ?? []) && $solicitacoes[0]['whatsapp_enviado'] !== null;
$whatsappSelecionado = array_key_exists('whatsapp_enviado', $filtros)
    ? ($filtros['whatsapp_enviado'] ? '1' : '0')
    : '';

function formatarData(?string $data, bool $incluiHora = true): string
{
    if (!$data) {
        return '-';
    }

    $timestamp = strtotime($data);
    if ($timestamp === false) {
        return $data;
    }

    return $incluiHora ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
}

$colunasTabela = [
    [
        'key' => 'solicitacao',
        'label' => 'Solicitação',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'locatario',
        'label' => 'Locatário',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 text-sm text-gray-700'
    ],
    [
        'key' => 'contrato',
        'label' => 'Contrato',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'cpf',
        'label' => 'CPF',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'status',
        'label' => 'Status',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm'
    ],
    [
        'key' => 'condicao',
        'label' => 'Condição',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm'
    ],
    [
        'key' => 'categoria',
        'label' => 'Categoria',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'criada_em',
        'label' => 'Criada em',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'agendamento',
        'label' => 'Agendamento',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'atualizacao',
        'label' => 'Atualização',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'indicadores',
        'label' => 'Indicadores',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700'
    ],
    [
        'key' => 'reembolso',
        'label' => 'Reembolso',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm'
    ],
    [
        'key' => 'acoes',
        'label' => 'Ações',
        'header_class' => 'px-6 py-3 text-left',
        'cell_class' => 'px-6 py-4 whitespace-nowrap text-sm'
    ],
];

$colunasLabels = array_column($colunasTabela, 'label', 'key');
?>

<div class="space-y-8">
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
        <form method="GET" class="space-y-4" id="filtros-relatorios-form">
            <input type="hidden" name="limite" id="limite-input" value="<?= htmlspecialchars($limiteAtual) ?>">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[220px]">
                    <label for="locatario_nome" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Buscar</label>
                    <input type="text" id="locatario_nome" name="locatario_nome" value="<?= htmlspecialchars($filtros['locatario_nome'] ?? '') ?>" placeholder="Nome, telefone, CPF ou e-mail" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <div class="w-48 min-w-[170px]">
                    <label for="imobiliaria_id" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Imobiliária</label>
                    <select id="imobiliaria_id" name="imobiliaria_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Todas</option>
                        <?php foreach ($imobiliarias as $imobiliaria): ?>
                            <option value="<?= $imobiliaria['id'] ?>" <?= ($filtros['imobiliaria_id'] ?? '') == $imobiliaria['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($imobiliaria['nome'] ?? $imobiliaria['nome_fantasia'] ?? 'Sem nome') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-48 min-w-[170px]">
                    <label for="status_id" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Status</label>
                    <select id="status_id" name="status_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Todos</option>
                        <?php foreach ($statusLista as $status): ?>
                            <option value="<?= $status['id'] ?>" <?= ($filtros['status_id'] ?? '') == $status['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-48 min-w-[170px]">
                    <label for="condicao_id" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Condição</label>
                    <select id="condicao_id" name="condicao_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Todas</option>
                        <?php foreach ($condicoes as $condicao): ?>
                            <option value="<?= $condicao['id'] ?>" <?= ($filtros['condicao_id'] ?? '') == $condicao['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($condicao['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" id="toggle-advanced-filters" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-sliders-h mr-2"></i>Avançado
                    </button>
                    <a href="<?= url('admin/relatorios') ?>" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Limpar
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                </div>
            </div>

            <div id="advanced-filters-panel" class="hidden border-t border-gray-200 pt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= ($filtros['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="cpf" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">CPF do locatário</label>
                        <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($filtros['cpf'] ?? '') ?>" placeholder="000.000.000-00" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label for="data_inicio" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Criação (de)</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label for="data_fim" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Criação (até)</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus;border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Subcategoria</label>
                        <select id="subcategoria_id" name="subcategoria_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Todas</option>
                            <?php foreach ($subcategorias as $categoriaId => $grupo): ?>
                                <optgroup label="<?= htmlspecialchars($grupo['categoria_nome']) ?>">
                                    <?php foreach ($grupo['itens'] as $subcategoria): ?>
                                        <option value="<?= $subcategoria['id'] ?>" <?= ($filtros['subcategoria_id'] ?? '') == $subcategoria['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($subcategoria['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="numero_contrato" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Número do contrato</label>
                        <input type="text" id="numero_contrato" name="numero_contrato" value="<?= htmlspecialchars($filtros['numero_contrato'] ?? '') ?>" placeholder="Informe o nº" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label for="agendamento_inicio" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Agendamento (de)</label>
                        <input type="date" id="agendamento_inicio" name="agendamento_inicio" value="<?= htmlspecialchars($filtros['agendamento_inicio'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label for="agendamento_fim" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Agendamento (até)</label>
                        <input type="date" id="agendamento_fim" name="agendamento_fim" value="<?= htmlspecialchars($filtros['agendamento_fim'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Reembolso</label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg px-3 py-2">
                            <input type="checkbox" name="precisa_reembolso" value="1" class="text-blue-600 rounded" <?= !empty($filtros['precisa_reembolso']) ? 'checked' : '' ?>>
                            <span>Com reembolso</span>
                        </label>
                    </div>

                    <?php if ($suportaSla): ?>
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">SLA</label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg px-3 py-2">
                                <input type="checkbox" name="sla_atrasado" value="1" class="text-blue-600 rounded" <?= !empty($filtros['sla_atrasado']) ? 'checked' : '' ?>>
                                <span>Só SLA estourado</span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <?php if ($temWhatsApp): ?>
                        <div class="space-y-2">
                            <label for="whatsapp_enviado" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">WhatsApp</label>
                            <select id="whatsapp_enviado" name="whatsapp_enviado" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="" <?= $whatsappSelecionado === '' ? 'selected' : '' ?>>Todos</option>
                                <option value="1" <?= $whatsappSelecionado === '1' ? 'selected' : '' ?>>Enviado</option>
                                <option value="0" <?= $whatsappSelecionado === '0' ? 'selected' : '' ?>>Pendente</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Aplicar filtros avançados
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-6">
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total de Solicitações</p>
                    <p class="text-3xl font-semibold text-gray-900 mt-2"><?= number_format($totalSolicitacoes, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600"><i class="fas fa-list-ul"></i></span>
            </div>
        </article>
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Pendentes</p>
                    <p class="text-3xl font-semibold text-amber-600 mt-2"><?= number_format($totalPendentes, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-amber-600"><i class="fas fa-clock"></i></span>
            </div>
        </article>
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Agendados</p>
                    <p class="text-3xl font-semibold text-green-600 mt-2"><?= number_format($totalAgendados, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600"><i class="fas fa-calendar-check"></i></span>
            </div>
        </article>
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Buscando Prestador</p>
                    <p class="text-3xl font-semibold text-blue-600 mt-2"><?= number_format($totalBuscando, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-people-arrows"></i>
                </span>
            </div>
        </article>
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Concluídos</p>
                    <p class="text-3xl font-semibold text-emerald-600 mt-2"><?= number_format($totalConcluidos, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-600"><i class="fas fa-check-circle"></i></span>
            </div>
        </article>
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Cancelados</p>
                    <p class="text-3xl font-semibold text-rose-600 mt-2"><?= number_format($totalCancelados, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-rose-100 text-rose-600"><i class="fas fa-times-circle"></i></span>
            </div>
        </article>
        <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Reembolsos pendentes</p>
                    <p class="text-3xl font-semibold text-purple-600 mt-2"><?= number_format($totalReembolsos, 0, ',', '.') ?></p>
                </div>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-purple-100 text-purple-600"><i class="fas fa-file-invoice-dollar"></i></span>
            </div>
        </article>
        <?php if ($suportaSla): ?>
            <article class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">SLA estourado</p>
                        <p class="text-3xl font-semibold text-red-600 mt-2"><?= number_format($totalSlaAtrasado, 0, ',', '.') ?></p>
                    </div>
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600"><i class="fas fa-exclamation-triangle"></i></span>
                </div>
            </article>
        <?php endif; ?>
    </section>

    <section class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <header class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Solicitações encontradas</h3>
                <p class="text-sm text-gray-500">Mostrando até <?= number_format($limiteAtual, 0, ',', '.') ?> registros recentes conforme filtros aplicados.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-gray-100 rounded-full p-1">
                    <span class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Linhas</span>
                    <?php foreach ($limiteOpcoes as $opcao): ?>
                        <button type="button"
                                class="limite-button inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full transition-colors <?= $opcao === $limiteAtual ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-blue-600' ?>"
                                data-value="<?= $opcao ?>">
                            <?= number_format($opcao, 0, ',', '.') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="open-columns-modal" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors">
                    <i class="fas fa-grip-horizontal text-sm"></i>
                    Organizar colunas
                </button>
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-gray-500 bg-gray-100 rounded-full">
                    <?= number_format(count($solicitacoes ?? []), 0, ',', '.') ?> resultados
                </span>
            </div>
        </header>

        <?php if (!empty($solicitacoes)): ?>
            <div class="overflow-x-auto">
                <table id="relatorios-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <?php foreach ($colunasTabela as $coluna): ?>
                                <th class="<?= $coluna['header_class'] ?> select-none <?= $coluna['key'] === 'indicadores' ? 'min-w-[180px]' : '' ?>" data-column="<?= $coluna['key'] ?>">
                                    <span class="flex items-center gap-2">
                                        <?= htmlspecialchars($coluna['label']) ?>
                                        <i class="fas fa-grip-lines text-gray-300 text-xs"></i>
                                    </span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 text-sm text-gray-700">
                        <?php foreach ($solicitacoes as $solicitacao): ?>
                            <tr class="hover:bg-blue-50/40">
                                <?php foreach ($colunasTabela as $coluna): ?>
                                    <?php $colKey = $coluna['key']; ?>
                                    <td class="<?= $coluna['cell_class'] ?>" data-column="<?= $colKey ?>">
                                        <?php
                                        switch ($colKey) {
                                            case 'solicitacao':
                                                $numeroSolicitacao = $solicitacao['numero_solicitacao'] ?? null;
                                                if (!$numeroSolicitacao) {
                                                    $numeroSolicitacao = $solicitacao['id'] ?? null;
                                                }
                                                if ($numeroSolicitacao) {
                                                    ?>
                                                    <span class="font-semibold text-gray-900">#<?= htmlspecialchars($numeroSolicitacao) ?></span>
                                                    <?php
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'reembolso':
                                                if (!empty($solicitacao['precisa_reembolso'])) {
                                                    ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                                        Reembolso
                                                    </span>
                                                    <?php
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'contrato':
                                                if (!empty($solicitacao['numero_contrato'])) {
                                                    echo htmlspecialchars($solicitacao['numero_contrato']);
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'locatario':
                                                ?>
                                                <div class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['locatario_nome'] ?? 'Não informado') ?></div>
                                                <?php if (!empty($solicitacao['imobiliaria_nome'])): ?>
                                                    <div class="text-xs text-gray-500">Imobiliária: <?= htmlspecialchars($solicitacao['imobiliaria_nome']) ?></div>
                                                <?php endif; ?>
                                                <?php
                                                break;

                                            case 'cpf':
                                                if (!empty($solicitacao['locatario_cpf'])) {
                                                    echo htmlspecialchars($solicitacao['locatario_cpf']);
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'status':
                                                $statusCor = $solicitacao['status_cor'] ?? '#3B82F6';
                                                ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: <?= htmlspecialchars($statusCor) ?>1a; color: <?= htmlspecialchars($statusCor) ?>;">
                                                    <?= htmlspecialchars($solicitacao['status_nome'] ?? 'Sem status') ?>
                                                </span>
                                                <?php
                                                break;

                                            case 'condicao':
                                                if (!empty($solicitacao['condicao_nome'])) {
                                                    $condicaoCor = $solicitacao['condicao_cor'] ?? '#6B7280';
                                                    ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" style="background-color: <?= htmlspecialchars($condicaoCor) ?>1a; color: <?= htmlspecialchars($condicaoCor) ?>;">
                                                        <?= htmlspecialchars($solicitacao['condicao_nome']) ?>
                                                    </span>
                                                    <?php
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'categoria':
                                                if (!empty($solicitacao['categoria_nome'])) {
                                                    ?>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($solicitacao['categoria_nome']) ?></div>
                                                    <?php if (!empty($solicitacao['subcategoria_nome'])): ?>
                                                        <div class="text-xs text-gray-500">Sub: <?= htmlspecialchars($solicitacao['subcategoria_nome']) ?></div>
                                                    <?php endif; ?>
                                                    <?php
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'criada_em':
                                                echo formatarData($solicitacao['created_at'] ?? null);
                                                break;

                                            case 'agendamento':
                                                echo formatarData($solicitacao['data_agendamento'] ?? null, false);
                                                break;

                                            case 'atualizacao':
                                                echo formatarData($solicitacao['updated_at'] ?? null);
                                                break;

                                            case 'indicadores':
                                                $badges = [];
                                                if (!empty($solicitacao['prioridade'])) {
                                                    $badges[] = [
                                                        'label' => 'Prioridade: ' . ucfirst(strtolower($solicitacao['prioridade'])),
                                                        'class' => 'bg-amber-100 text-amber-700'
                                                    ];
                                                }
                                                if ($temWhatsApp && array_key_exists('whatsapp_enviado', $solicitacao)) {
                                                    if ($solicitacao['whatsapp_enviado']) {
                                                        $badges[] = ['label' => 'WhatsApp enviado', 'class' => 'bg-emerald-100 text-emerald-700'];
                                                    } else {
                                                        $badges[] = ['label' => 'WhatsApp pendente', 'class' => 'bg-red-100 text-red-700'];
                                                    }
                                                }

                                                if (!empty($badges)) {
                                                    ?>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($badges as $badge): ?>
                                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $badge['class'] ?>">
                                                                <?= htmlspecialchars($badge['label']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php
                                                } else {
                                                    echo '<span class="text-sm text-gray-500">-</span>';
                                                }
                                                break;

                                            case 'acoes':
                                                ?>
                                                <a href="<?= url('admin/solicitacoes/' . $solicitacao['id']) ?>" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                                                    <i class="fas fa-external-link-alt text-xs"></i> Ver detalhes
                                                </a>
                                                <?php
                                                break;

                                            default:
                                                echo '<span class="text-sm text-gray-500">-</span>';
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-6">
                <p class="text-gray-600 text-sm">Nenhuma solicitação encontrada com os filtros atuais. Ajuste os critérios e tente novamente.</p>
            </div>
        <?php endif; ?>
    </section>

    <div id="relatorios-column-config"
         data-default-order='<?= json_encode(array_column($colunasTabela, 'key'), JSON_UNESCAPED_UNICODE) ?>'
         data-labels='<?= json_encode($colunasLabels, JSON_UNESCAPED_UNICODE) ?>'
         class="hidden"
         aria-hidden="true"></div>
</div>

<div id="columns-overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-40 z-40"></div>
<div id="columns-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <div>
                <h4 class="text-lg font-semibold text-gray-900">Organizar colunas</h4>
                <p class="text-sm text-gray-500">Arraste para reordenar e personalize como preferir.</p>
            </div>
            <button type="button" id="close-columns-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-6">
            <ul id="columns-sortable" class="space-y-2">
                <?php foreach ($colunasTabela as $coluna): ?>
                    <li class="flex items-center justify-between px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 cursor-move"
                        draggable="true"
                        data-key="<?= $coluna['key'] ?>">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-grip-vertical text-gray-400"></i>
                            <?= htmlspecialchars($coluna['label']) ?>
                        </span>
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer">
                            <input type="checkbox"
                                   class="column-visible-toggle h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                   data-key="<?= $coluna['key'] ?>"
                                   checked>
                            Mostrar
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <button type="button" id="reset-columns-order" class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                Restaurar padrão
            </button>
            <div class="flex items-center gap-3">
                <button type="button" id="cancel-columns-order" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button type="button" id="save-columns-order" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleAdvancedBtn = document.getElementById('toggle-advanced-filters');
    const advancedPanel = document.getElementById('advanced-filters-panel');

    if (toggleAdvancedBtn && advancedPanel) {
        toggleAdvancedBtn.addEventListener('click', () => {
            const isHidden = advancedPanel.classList.toggle('hidden');
            if (isHidden) {
                toggleAdvancedBtn.classList.remove('bg-blue-600', 'text-white');
                toggleAdvancedBtn.classList.add('text-blue-600', 'border-blue-200');
            } else {
                toggleAdvancedBtn.classList.add('bg-blue-600', 'text-white');
                toggleAdvancedBtn.classList.remove('text-blue-600', 'border-blue-200');
            }
        });
    }

    const tableConfig = document.getElementById('relatorios-column-config');
    const columnsModal = document.getElementById('columns-modal');
    const columnsOverlay = document.getElementById('columns-overlay');
    const openColumnsBtn = document.getElementById('open-columns-modal');
    const closeColumnsBtn = document.getElementById('close-columns-modal');
    const cancelColumnsBtn = document.getElementById('cancel-columns-order');
    const saveColumnsBtn = document.getElementById('save-columns-order');
    const resetColumnsBtn = document.getElementById('reset-columns-order');
    const columnsList = document.getElementById('columns-sortable');
    const table = document.getElementById('relatorios-table');
    const limiteButtons = document.querySelectorAll('.limite-button');
    const limiteInput = document.getElementById('limite-input');
    const filtrosForm = document.getElementById('filtros-relatorios-form');

    if (limiteButtons.length && limiteInput && filtrosForm) {
        limiteButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const valor = btn.dataset.value;
                if (!valor || limiteInput.value === valor) {
                    if (limiteInput.value !== valor) {
                        filtrosForm.submit();
                    }
                    return;
                }
                limiteInput.value = valor;
                filtrosForm.submit();
            });
        });
    }

    if (tableConfig && columnsList) {
        const defaultOrder = JSON.parse(tableConfig.dataset.defaultOrder || '[]');
        const labels = JSON.parse(tableConfig.dataset.labels || '{}');

        const sanitizeOrder = (order) => {
            const seen = new Set();
            const normalized = [];
            const source = Array.isArray(order) ? order : [];
            source.forEach((key) => {
                if (defaultOrder.includes(key) && !seen.has(key)) {
                    seen.add(key);
                    normalized.push(key);
                }
            });
            defaultOrder.forEach((key) => {
                if (!seen.has(key)) {
                    normalized.push(key);
                }
            });
            return normalized;
        };

        const sanitizeVisibility = (visibility) => {
            const sanitized = {};
            const source = (visibility && typeof visibility === 'object') ? visibility : {};
            defaultOrder.forEach((key) => {
                sanitized[key] = typeof source[key] === 'boolean' ? source[key] : true;
            });
            return sanitized;
        };

        const getStoredOrder = () => {
            try {
                const raw = localStorage.getItem('relatoriosColumnsOrder');
                if (!raw) {
                    return null;
                }
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : null;
            } catch (error) {
                console.warn('Não foi possível carregar a ordem das colunas.', error);
                return null;
            }
        };

        const getStoredVisibility = () => {
            try {
                const raw = localStorage.getItem('relatoriosColumnsVisibility');
                if (!raw) {
                    return null;
                }
                const parsed = JSON.parse(raw);
                return (parsed && typeof parsed === 'object') ? parsed : null;
            } catch (error) {
                console.warn('Não foi possível carregar a visibilidade das colunas.', error);
                return null;
            }
        };

        const storeOrder = (order) => {
            try {
                localStorage.setItem('relatoriosColumnsOrder', JSON.stringify(order));
            } catch (error) {
                console.warn('Não foi possível salvar a ordem das colunas.', error);
            }
        };

        const storeVisibility = (visibility) => {
            try {
                localStorage.setItem('relatoriosColumnsVisibility', JSON.stringify(visibility));
            } catch (error) {
                console.warn('Não foi possível salvar a visibilidade das colunas.', error);
            }
        };

        const primaryKey = defaultOrder[0] || null;

        const ensurePrimary = (order, forceFront = false) => {
            if (!primaryKey) {
                return order;
            }
            const hasPrimary = order.includes(primaryKey);
            if (!hasPrimary) {
                return [primaryKey, ...order];
            }
            if (forceFront && order[0] !== primaryKey) {
                return [primaryKey, ...order.filter((key) => key !== primaryKey)];
            }
            return order;
        };

        const applyColumnOrder = (order) => {
            if (!table) {
                return;
            }
            const sanitized = sanitizeOrder(order);
            const headerRow = table.querySelector('thead tr');
            const bodyRows = table.querySelectorAll('tbody tr');

            sanitized.forEach((key) => {
                const th = headerRow?.querySelector(`th[data-column="${key}"]`);
                if (th) {
                    headerRow.appendChild(th);
                }
            });

            bodyRows.forEach((row) => {
                sanitized.forEach((key) => {
                    const cell = row.querySelector(`td[data-column="${key}"]`);
                    if (cell) {
                        row.appendChild(cell);
                    }
                });
            });
        };

        const applyColumnVisibility = (visibility) => {
            if (!table) {
                return;
            }
            const sanitized = sanitizeVisibility(visibility);
            const headerRow = table.querySelector('thead tr');
            const bodyRows = table.querySelectorAll('tbody tr');

            Object.entries(sanitized).forEach(([key, isVisible]) => {
                const th = headerRow?.querySelector(`th[data-column="${key}"]`);
                if (th) {
                    th.classList.toggle('hidden', !isVisible);
                }
                bodyRows.forEach((row) => {
                    const cell = row.querySelector(`td[data-column="${key}"]`);
                    if (cell) {
                        cell.classList.toggle('hidden', !isVisible);
                    }
                });
            });
        };

        const storedOrder = getStoredOrder();
        let currentOrder = sanitizeOrder(storedOrder || defaultOrder);
        if (storedOrder) {
            currentOrder = ensurePrimary(sanitizeOrder(storedOrder), false);
        } else {
            currentOrder = ensurePrimary(sanitizeOrder(defaultOrder), true);
        }

        let currentVisibility = sanitizeVisibility(getStoredVisibility());
        let workingOrder = [...currentOrder];
        let workingVisibility = { ...currentVisibility };

        const renderList = () => {
            workingOrder = sanitizeOrder(workingOrder);
            columnsList.innerHTML = '';
            workingOrder.forEach((key) => {
                const label = labels[key] || key;
                const isVisible = workingVisibility[key] !== undefined ? workingVisibility[key] : true;
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 cursor-move';
                li.setAttribute('draggable', 'true');
                li.dataset.key = key;
                li.innerHTML = `
                    <span class="flex items-center gap-3">
                        <i class="fas fa-grip-vertical text-gray-400"></i>
                        ${label}
                    </span>
                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer">
                        <input type="checkbox"
                               class="column-visible-toggle h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                               data-key="${key}"
                               ${isVisible ? 'checked' : ''}>
                        Mostrar
                    </label>
                `;
                columnsList.appendChild(li);
            });
        };

        renderList();
        applyColumnOrder(currentOrder);
        applyColumnVisibility(currentVisibility);

        let draggedItem = null;
        columnsList.addEventListener('dragstart', (event) => {
            const li = event.target.closest('li');
            if (!li) {
                return;
            }
            draggedItem = li;
            event.dataTransfer.effectAllowed = 'move';
            li.classList.add('opacity-50');
        });

        columnsList.addEventListener('dragend', () => {
            if (draggedItem) {
                draggedItem.classList.remove('opacity-50');
            }
            draggedItem = null;
            workingOrder = Array.from(columnsList.querySelectorAll('li')).map((li) => li.dataset.key);
        });

        columnsList.addEventListener('dragover', (event) => {
            event.preventDefault();
            const li = event.target.closest('li');
            if (!li || li === draggedItem) {
                return;
            }
            const rect = li.getBoundingClientRect();
            const offset = event.clientY - rect.top;
            if (offset > rect.height / 2) {
                li.after(draggedItem);
            } else {
                li.before(draggedItem);
            }
        });

        columnsList.addEventListener('drop', (event) => {
            event.preventDefault();
        });

        columnsList.addEventListener('mousedown', (event) => {
            if (event.target.closest('.column-visible-toggle') || event.target.closest('label')) {
                event.stopPropagation();
            }
        });

        columnsList.addEventListener('change', (event) => {
            const input = event.target;
            if (input && input.classList.contains('column-visible-toggle')) {
                const key = input.dataset.key;
                if (key) {
                    workingVisibility[key] = input.checked;
                }
            }
        });

        const openModal = () => {
            workingOrder = [...currentOrder];
            workingVisibility = { ...currentVisibility };
            renderList();
            columnsOverlay?.classList.remove('hidden');
            columnsModal?.classList.remove('hidden');
        };

        const closeModal = () => {
            columnsOverlay?.classList.add('hidden');
            columnsModal?.classList.add('hidden');
        };

        openColumnsBtn?.addEventListener('click', openModal);
        closeColumnsBtn?.addEventListener('click', closeModal);
        cancelColumnsBtn?.addEventListener('click', closeModal);
        columnsOverlay?.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && columnsModal && !columnsModal.classList.contains('hidden')) {
                closeModal();
            }
        });

        saveColumnsBtn?.addEventListener('click', () => {
            workingOrder = sanitizeOrder(Array.from(columnsList.querySelectorAll('li')).map((li) => li.dataset.key));
            workingVisibility = sanitizeVisibility(workingVisibility);

            currentOrder = [...workingOrder];
            currentVisibility = { ...workingVisibility };

            storeOrder(currentOrder);
            storeVisibility(currentVisibility);

            applyColumnOrder(currentOrder);
            applyColumnVisibility(currentVisibility);
            closeModal();
        });

        resetColumnsBtn?.addEventListener('click', () => {
            currentOrder = sanitizeOrder(defaultOrder);
            currentVisibility = sanitizeVisibility(null);
            workingOrder = [...currentOrder];
            workingVisibility = { ...currentVisibility };
            try {
                localStorage.removeItem('relatoriosColumnsOrder');
                localStorage.removeItem('relatoriosColumnsVisibility');
            } catch (error) {
                console.warn('Não foi possível limpar as preferências de colunas.', error);
            }
            renderList();
            applyColumnOrder(currentOrder);
            applyColumnVisibility(currentVisibility);
        });
    }
});
</script>

