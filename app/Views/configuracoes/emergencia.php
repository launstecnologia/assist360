<?php
$title = 'Configurações de Emergência';
$currentPage = 'configuracoes';
$pageTitle = 'Configurações de Emergência';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Configurações de Emergência</h1>
        <p class="text-gray-600 mt-1">Configure o telefone de emergência e horário comercial</p>
    </div>
    <a href="<?= url('admin/configuracoes') ?>" 
       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        Voltar
    </a>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="mb-4 p-4 rounded-lg <?= $_SESSION['flash_type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Configurações de Atendimento Emergencial</h3>
        <p class="text-sm text-gray-500 mt-1">Configure quando e como o telefone de emergência será exibido</p>
    </div>
    
    <form method="POST" action="<?= url('admin/configuracoes/emergencia') ?>" class="p-6 space-y-6">
        <?= \App\Core\View::csrfField() ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erro</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Telefone de Emergência -->
            <div class="md:col-span-2">
                <label for="telefone_emergencia" class="block text-sm font-medium text-gray-700">
                    Telefone de Emergência <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="telefone_emergencia" 
                       id="telefone_emergencia" 
                       value="<?= htmlspecialchars($telefone['valor'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Ex: 0800 123 4567"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Telefone 0800 que será exibido quando a solicitação for emergencial e fora do horário comercial
                </p>
            </div>
            
            <!-- Horário Comercial Início -->
            <div>
                <label for="horario_comercial_inicio" class="block text-sm font-medium text-gray-700">
                    Horário Comercial - Início <span class="text-red-500">*</span>
                </label>
                <input type="time" 
                       name="horario_comercial_inicio" 
                       id="horario_comercial_inicio" 
                       value="<?= htmlspecialchars($horarioInicio['valor'] ?? '08:00') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Horário de início do atendimento comercial
                </p>
            </div>
            
            <!-- Horário Comercial Fim -->
            <div>
                <label for="horario_comercial_fim" class="block text-sm font-medium text-gray-700">
                    Horário Comercial - Fim <span class="text-red-500">*</span>
                </label>
                <input type="time" 
                       name="horario_comercial_fim" 
                       id="horario_comercial_fim" 
                       value="<?= htmlspecialchars($horarioFim['valor'] ?? '17:30') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Horário de fim do atendimento comercial
                </p>
            </div>
            
            <!-- Dias da Semana Comerciais -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Dias da Semana Comerciais <span class="text-red-500">*</span>
                </label>
                <?php
                $diasSemana = [
                    1 => 'Segunda-feira',
                    2 => 'Terça-feira',
                    3 => 'Quarta-feira',
                    4 => 'Quinta-feira',
                    5 => 'Sexta-feira',
                    6 => 'Sábado',
                    7 => 'Domingo'
                ];
                $diasSelecionados = json_decode($diasSemana['valor'] ?? '[1,2,3,4,5]', true) ?? [1,2,3,4,5];
                ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <?php foreach ($diasSemana as $numero => $nome): ?>
                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 <?= in_array($numero, $diasSelecionados) ? 'bg-blue-50 border-blue-300' : '' ?>">
                            <input type="checkbox" 
                                   name="dias_semana_comerciais[]" 
                                   value="<?= $numero ?>"
                                   <?= in_array($numero, $diasSelecionados) ? 'checked' : '' ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700"><?= $nome ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Selecione os dias da semana em que o atendimento comercial está disponível. Fora desses dias e horários, o telefone de emergência será exibido.
                </p>
            </div>
        </div>
        
        <!-- Preview do Horário -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-2">
                <i class="fas fa-info-circle mr-2"></i>
                Resumo da Configuração
            </h4>
            <div class="text-sm text-blue-800 space-y-1">
                <p><strong>Telefone de Emergência:</strong> <span id="preview-telefone"><?= htmlspecialchars($telefone['valor'] ?? 'Não configurado') ?></span></p>
                <p><strong>Horário Comercial:</strong> <span id="preview-horario"><?= htmlspecialchars($horarioInicio['valor'] ?? '08:00') ?> às <?= htmlspecialchars($horarioFim['valor'] ?? '17:30') ?></span></p>
                <p><strong>Dias Comerciais:</strong> <span id="preview-dias">Segunda a Sexta</span></p>
                <p class="mt-2 text-xs text-blue-700">
                    O telefone de emergência será exibido quando a solicitação for emergencial E estiver fora do horário comercial configurado acima.
                </p>
            </div>
        </div>
        
        <!-- Botões -->
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a href="<?= url('admin/configuracoes') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-times mr-2"></i>
                Cancelar
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i>
                Salvar Configurações
            </button>
        </div>
    </form>
</div>

<script>
// Atualizar preview em tempo real
document.getElementById('telefone_emergencia').addEventListener('input', function() {
    document.getElementById('preview-telefone').textContent = this.value || 'Não configurado';
});

document.getElementById('horario_comercial_inicio').addEventListener('change', function() {
    const inicio = this.value;
    const fim = document.getElementById('horario_comercial_fim').value;
    document.getElementById('preview-horario').textContent = inicio + ' às ' + fim;
});

document.getElementById('horario_comercial_fim').addEventListener('change', function() {
    const inicio = document.getElementById('horario_comercial_inicio').value;
    const fim = this.value;
    document.getElementById('preview-horario').textContent = inicio + ' às ' + fim;
});

// Atualizar preview dos dias
document.querySelectorAll('input[name="dias_semana_comerciais[]"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const dias = {
            1: 'Segunda',
            2: 'Terça',
            3: 'Quarta',
            4: 'Quinta',
            5: 'Sexta',
            6: 'Sábado',
            7: 'Domingo'
        };
        
        const selecionados = Array.from(document.querySelectorAll('input[name="dias_semana_comerciais[]"]:checked'))
            .map(cb => parseInt(cb.value))
            .sort();
        
        if (selecionados.length === 0) {
            document.getElementById('preview-dias').textContent = 'Nenhum dia selecionado';
        } else if (selecionados.length === 5 && selecionados.join(',') === '1,2,3,4,5') {
            document.getElementById('preview-dias').textContent = 'Segunda a Sexta';
        } else {
            document.getElementById('preview-dias').textContent = selecionados.map(d => dias[d]).join(', ');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

