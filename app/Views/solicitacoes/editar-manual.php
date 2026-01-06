<?php
/**
 * View: Editar Solicitação Manual (Admin)
 * Formulário para admin editar solicitação manual existente
 */
$title = 'Editar Solicitação Manual - Portal do Operador';
$currentPage = 'solicitacoes-manuais';
ob_start();

// Formatar CPF e WhatsApp para exibição
$cpfFormatado = !empty($solicitacao['cpf']) ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $solicitacao['cpf']) : '';
$whatsappFormatado = !empty($solicitacao['whatsapp']) ? preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $solicitacao['whatsapp']) : '';
$cepFormatado = !empty($solicitacao['cep']) ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', $solicitacao['cep']) : '';

// Formatar horários preferenciais para exibição
$horariosExistentes = [];
if (!empty($solicitacao['horarios_preferenciais'])) {
    $horarios = is_array($solicitacao['horarios_preferenciais']) 
        ? $solicitacao['horarios_preferenciais'] 
        : json_decode($solicitacao['horarios_preferenciais'], true);
    
    if (is_array($horarios)) {
        foreach ($horarios as $horario) {
            // Converter formato "2025-10-29 08:00:00" para "29/10/2025 - 08:00-11:00"
            if (preg_match('/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/', $horario, $matches)) {
                $ano = $matches[1];
                $mes = $matches[2];
                $dia = $matches[3];
                $hora = (int)$matches[4];
                $min = $matches[5];
                
                // Determinar faixa de horário
                $horaFim = 0;
                if ($hora == 8) $horaFim = 11;
                elseif ($hora == 11) $horaFim = 14;
                elseif ($hora == 14) $horaFim = 17;
                elseif ($hora == 17) $horaFim = 20;
                else $horaFim = $hora + 3;
                
                $horariosExistentes[] = sprintf('%s/%s/%s - %02d:%s-%02d:%s', $dia, $mes, $ano, $hora, $min, $horaFim, $min);
            } elseif (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*-\s*(\d{2}):(\d{2})-(\d{2}):(\d{2})/', $horario, $matches)) {
                // Já está no formato correto
                $horariosExistentes[] = $horario;
            }
        }
    }
}
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-edit mr-2"></i>
                Editar Solicitação Manual
            </h1>
            <p class="text-gray-600 mt-1">
                Edite os dados da solicitação manual #<?= $solicitacao['id'] ?>
            </p>
        </div>
        <div>
            <a href="<?= url('admin/solicitacoes-manuais') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['error']) && $_GET['error']): ?>
<div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
    <div class="flex">
        <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
        <div><?= htmlspecialchars($_GET['error']) ?></div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success']): ?>
<div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
    <div class="flex">
        <i class="fas fa-check-circle mt-1 mr-3"></i>
        <div><?= htmlspecialchars($_GET['success']) ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Formulário -->
<form method="POST" action="<?= url('admin/solicitacoes-manuais/' . $solicitacao['id'] . '/editar') ?>" enctype="multipart/form-data" class="space-y-6">
    
    <!-- Dados Pessoais -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-user mr-2 text-blue-600"></i>
            Dados Pessoais
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Imobiliária *</label>
                <select name="imobiliaria_id" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione...</option>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <option value="<?= $imob['id'] ?>" <?= ($solicitacao['imobiliaria_id'] == $imob['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($imob['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                <input type="text" name="nome_completo" required
                       value="<?= htmlspecialchars($solicitacao['nome_completo'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                <input type="text" name="cpf" id="cpf" required maxlength="14"
                       value="<?= htmlspecialchars($cpfFormatado) ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="000.000.000-00">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp *</label>
                <input type="text" name="whatsapp" id="whatsapp" required
                       value="<?= htmlspecialchars($whatsappFormatado) ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="(00) 00000-0000">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nº do Contrato</label>
                <input type="text" name="numero_contrato"
                       value="<?= htmlspecialchars($solicitacao['numero_contrato'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status_id"
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Status Padrão (Nova Solicitação)</option>
                    <?php foreach ($statusList as $status): ?>
                        <option value="<?= $status['id'] ?>" <?= ($solicitacao['status_id'] == $status['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Endereço -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>
            Endereço do Imóvel
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Imóvel *</label>
                <select name="tipo_imovel" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione...</option>
                    <option value="Residencial" <?= ($solicitacao['tipo_imovel'] == 'Residencial') ? 'selected' : '' ?>>Residencial</option>
                    <option value="Comercial" <?= ($solicitacao['tipo_imovel'] == 'Comercial') ? 'selected' : '' ?>>Comercial</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" name="subtipo_imovel"
                       value="<?= htmlspecialchars($solicitacao['subtipo_imovel'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="Ex: Apartamento, Casa, Loja...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CEP *</label>
                <div class="flex gap-2">
                    <input type="text" name="cep" id="cep" required maxlength="9"
                           value="<?= htmlspecialchars($cepFormatado) ?>"
                           class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                           placeholder="00000-000">
                    <button type="button" id="btn-buscar-cep"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-search mr-2"></i>Buscar
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Digite o CEP e clique em buscar para preencher automaticamente</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
                <input type="text" name="endereco" id="endereco" required
                       value="<?= htmlspecialchars($solicitacao['endereco'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
                <input type="text" name="numero" id="numero" required
                       value="<?= htmlspecialchars($solicitacao['numero'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                <input type="text" name="complemento" id="complemento"
                       value="<?= htmlspecialchars($solicitacao['complemento'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro *</label>
                <input type="text" name="bairro" id="bairro" required
                       value="<?= htmlspecialchars($solicitacao['bairro'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade *</label>
                <input type="text" name="cidade" id="cidade" required
                       value="<?= htmlspecialchars($solicitacao['cidade'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                <select name="estado" id="estado" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione...</option>
                    <?php
                    $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                    foreach ($estados as $uf):
                    ?>
                        <option value="<?= $uf ?>" <?= ($solicitacao['estado'] == $uf) ? 'selected' : '' ?>><?= $uf ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Serviço -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cog mr-2 text-purple-600"></i>
            Serviço Solicitado
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>
                <select name="categoria_id" id="categoria_id" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= $categoria['id'] ?>" <?= ($solicitacao['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categoria['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subcategoria *</label>
                <select name="subcategoria_id" id="subcategoria_id" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione primeiro a categoria</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Local da Manutenção</label>
                <input type="text" name="local_manutencao"
                       value="<?= htmlspecialchars($solicitacao['local_manutencao'] ?? '') ?>"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="Ex: Sala, Cozinha, Quarto...">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição do Problema *</label>
                <textarea name="descricao_problema" required rows="4"
                          class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                          placeholder="Descreva detalhadamente o problema..."><?= htmlspecialchars($solicitacao['descricao_problema'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
    
    <!-- Horários Preferenciais -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-clock mr-2 text-orange-600"></i>
            Horários Preferenciais (Opcional)
        </h2>
        <p class="text-sm text-gray-600 mb-4">Selecione até 3 datas e horários preferenciais para o atendimento</p>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                <input type="date" id="data_selecionada" 
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Horário</label>
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
            </div>
            
            <div id="horarios-selecionados" class="<?= !empty($horariosExistentes) ? '' : 'hidden' ?>">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    Horários Selecionados (<span id="contador-horarios"><?= count($horariosExistentes) ?></span>/3)
                </h4>
                <div id="lista-horarios" class="space-y-2">
                    <?php foreach ($horariosExistentes as $index => $horario): ?>
                        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-green-600 mr-2"></i>
                                <span class="text-sm text-green-800"><?= htmlspecialchars($horario) ?></span>
                            </div>
                            <button type="button" onclick="removerHorario(<?= $index ?>)" 
                                    class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <input type="hidden" name="horarios_opcoes" id="horarios_opcoes" value="<?= htmlspecialchars(json_encode($horariosExistentes)) ?>">
        </div>
    </div>
    
    <!-- Fotos -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-images mr-2 text-green-600"></i>
            Fotos
        </h2>
        <?php if (!empty($solicitacao['fotos']) && is_array($solicitacao['fotos']) && count($solicitacao['fotos']) > 0): ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Fotos Existentes</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <?php foreach ($solicitacao['fotos'] as $index => $foto): ?>
                        <div class="relative group">
                            <img src="<?= htmlspecialchars($foto) ?>" 
                                 alt="Foto <?= $index + 1 ?>" 
                                 class="w-full h-24 object-cover rounded-lg border border-gray-200">
                            <button type="button" onclick="removerFotoExistente(<?= $index ?>)" 
                                    class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar Novas Fotos</label>
            <input type="file" name="fotos[]" multiple accept="image/*"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition-all">
            <p class="text-xs text-gray-500 mt-2">Você pode selecionar múltiplas fotos (JPG, PNG, GIF, WEBP)</p>
        </div>
        <input type="hidden" name="fotos_existentes" id="fotos_existentes" value="<?= htmlspecialchars(json_encode($solicitacao['fotos'] ?? [])) ?>">
    </div>
    
    <!-- Botões -->
    <div class="flex justify-end gap-3">
        <a href="<?= url('admin/solicitacoes-manuais') ?>" 
           class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
            Cancelar
        </a>
        <button type="submit" 
                class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-save mr-2"></i>
            Salvar Alterações
        </button>
    </div>
</form>

<script>
// Inicializar horários existentes
let horariosEscolhidos = <?= json_encode($horariosExistentes) ?>;

// Atualizar subcategorias quando categoria mudar
const categoriaSelect = document.getElementById('categoria_id');
const subcategoriaSelect = document.getElementById('subcategoria_id');
const categoriaIdAtual = <?= $solicitacao['categoria_id'] ?? 0 ?>;
const subcategoriaIdAtual = <?= $solicitacao['subcategoria_id'] ?? 0 ?>;

function atualizarSubcategorias() {
    const categoriaId = categoriaSelect.value;
    subcategoriaSelect.innerHTML = '<option value="">Selecione...</option>';
    
    if (categoriaId) {
        const categoria = <?= json_encode($categorias) ?>.find(c => c.id == categoriaId);
        if (categoria && categoria.subcategorias) {
            categoria.subcategorias.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.nome;
                if (sub.id == subcategoriaIdAtual) {
                    option.selected = true;
                }
                subcategoriaSelect.appendChild(option);
            });
        }
    }
}

categoriaSelect.addEventListener('change', atualizarSubcategorias);

// Carregar subcategorias ao carregar a página
if (categoriaIdAtual) {
    categoriaSelect.value = categoriaIdAtual;
    atualizarSubcategorias();
}

// Máscaras de input
document.getElementById('cpf')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    }
});

document.getElementById('whatsapp')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
        }
        e.target.value = value;
    }
});

document.getElementById('cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 8) {
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        e.target.value = value;
    }
});

// Buscar CEP via API
document.getElementById('btn-buscar-cep')?.addEventListener('click', async function() {
    const cepInput = document.getElementById('cep');
    const cep = cepInput.value.replace(/\D/g, '');
    
    if (cep.length !== 8) {
        alert('CEP inválido. Digite um CEP com 8 dígitos.');
        cepInput.focus();
        return;
    }
    
    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Buscando...';
    btn.disabled = true;
    
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (data.erro) {
            alert('CEP não encontrado. Verifique o CEP digitado.');
            cepInput.focus();
        } else {
            document.getElementById('endereco').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
            document.getElementById('numero').focus();
        }
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
        alert('Erro ao buscar CEP. Tente novamente.');
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
});

// Buscar CEP automaticamente ao sair do campo
document.getElementById('cep')?.addEventListener('blur', async function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        const btn = document.getElementById('btn-buscar-cep');
        if (btn) {
            btn.click();
        }
    }
});

// Sistema de seleção de horários
const dataInput = document.getElementById('data_selecionada');
const horarioRadios = document.querySelectorAll('.horario-radio');
const horarioCards = document.querySelectorAll('.horario-card');
const horariosSelecionados = document.getElementById('horarios-selecionados');
const listaHorarios = document.getElementById('lista-horarios');
const contadorHorarios = document.getElementById('contador-horarios');

let dataSelecionadaAtual = '';

dataInput?.addEventListener('change', function() {
    if (!this.value) return;
    const dataSelecionada = new Date(this.value + 'T12:00:00');
    const diaDaSemana = dataSelecionada.getDay();
    dataSelecionadaAtual = this.value;
    
    if (diaDaSemana === 0 || diaDaSemana === 6) {
        alert('⚠️ Atendimentos não são realizados aos fins de semana.');
        this.value = '';
        dataSelecionadaAtual = '';
        return;
    }
    
    horarioRadios.forEach(r => r.checked = false);
    atualizarEstiloCards();
});

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
            
            if (!horariosEscolhidos.includes(horarioCompleto) && horariosEscolhidos.length < 3) {
                horariosEscolhidos.push(horarioCompleto);
                atualizarListaHorarios();
                this.checked = false;
                atualizarEstiloCards();
            } else if (horariosEscolhidos.length >= 3) {
                alert('Você pode selecionar no máximo 3 horários.');
                this.checked = false;
            } else {
                this.checked = false;
                atualizarEstiloCards();
            }
        }
    });
});

horarioCards.forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.type === 'radio') return;
        const label = this.closest('label');
        const radio = label?.querySelector('.horario-radio');
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
    } else {
        horariosSelecionados.classList.add('hidden');
    }
}

window.removerHorario = function(index) {
    horariosEscolhidos.splice(index, 1);
    atualizarListaHorarios();
};

// Remover fotos existentes
let fotosExistentes = <?= json_encode($solicitacao['fotos'] ?? []) ?>;

window.removerFotoExistente = function(index) {
    if (confirm('Deseja realmente remover esta foto?')) {
        fotosExistentes.splice(index, 1);
        document.getElementById('fotos_existentes').value = JSON.stringify(fotosExistentes);
        location.reload(); // Recarregar para atualizar a lista
    }
};

function atualizarEstiloCards() {
    horarioCards.forEach(card => {
        const radio = card.closest('label')?.querySelector('.horario-radio');
        const ativo = radio?.checked;
        card.classList.toggle('border-green-500', ativo);
        card.classList.toggle('bg-green-50', ativo);
        card.classList.toggle('border-gray-200', !ativo);
    });
}

function formatarData(data) {
    const [ano, mes, dia] = data.split('-');
    return `${dia}/${mes}/${ano}`;
}

// Salvar horários antes de enviar
document.querySelector('form').addEventListener('submit', function(e) {
    const horariosFormatados = horariosEscolhidos.map(horario => {
        const [dataStr, faixaHorario] = horario.split(' - ');
        const [dia, mes, ano] = dataStr.split('/');
        const horarioInicial = faixaHorario.split('-')[0];
        return `${ano}-${mes}-${dia} ${horarioInicial}:00`;
    });
    
    document.getElementById('horarios_opcoes').value = JSON.stringify(horariosFormatados);
    
    // Atualizar fotos existentes
    document.getElementById('fotos_existentes').value = JSON.stringify(fotosExistentes);
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

