<?php
/**
 * View: Nova Solicitação Manual (Admin)
 * Formulário para admin criar solicitação manual
 */
$title = 'Nova Solicitação Manual - Portal do Operador';
$currentPage = 'solicitacoes-manuais';
ob_start();
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-plus-circle mr-2"></i>
                Nova Solicitação Manual
            </h1>
            <p class="text-gray-600 mt-1">
                Registre uma nova solicitação manual no sistema
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
<form method="POST" action="<?= url('admin/solicitacoes-manuais/nova') ?>" enctype="multipart/form-data" class="space-y-6">
    
    <!-- Dados Pessoais -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-user mr-2 text-blue-600"></i>
            Dados Pessoais
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Imobiliária *</label>
                <select name="imobiliaria_id" id="imobiliaria_id" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione...</option>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <option value="<?= $imob['id'] ?>"><?= htmlspecialchars($imob['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                <input type="text" name="cpf" id="cpf" required maxlength="14"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="000.000.000-00">
                <p class="text-xs text-gray-500 mt-1" id="cpf-status"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                <input type="text" name="nome_completo" id="nome_completo" required
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp *</label>
                <input type="text" name="whatsapp" id="whatsapp" required
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="(00) 00000-0000">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nº do Contrato</label>
                <input type="text" name="numero_contrato"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status_id"
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Status Padrão (Nova Solicitação)</option>
                    <?php foreach ($statusList as $status): ?>
                        <option value="<?= $status['id'] ?>"><?= htmlspecialchars($status['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Atribuição final
                    <i class="fas fa-info-circle text-gray-400 ml-1" title="Bolsão: CPF validado no bolsão. Cortesia: escolhida pelo admin. Recusado: escolhida pelo admin. Dt Assinatura: validação por data de assinatura (Regra 2)."></i>
                </label>
                <select name="tipo_qualificacao" id="tipo_qualificacao"
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">-- Selecione --</option>
                    <option value="CORTESIA">Cortesia</option>
                    <option value="NAO_QUALIFICADA" selected>Recusado</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Escolha a atribuição final para esta solicitação manual</p>
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
                    <option value="Residencial">Residencial</option>
                    <option value="Comercial">Comercial</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" name="subtipo_imovel"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="Ex: Apartamento, Casa, Loja...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CEP *</label>
                <div class="flex gap-2">
                    <input type="text" name="cep" id="cep" required maxlength="9"
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
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
                <input type="text" name="numero" id="numero" required
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                <input type="text" name="complemento" id="complemento"
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bairro *</label>
                <input type="text" name="bairro" id="bairro" required
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cidade *</label>
                <input type="text" name="cidade" id="cidade" required
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                <select name="estado" id="estado" required
                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <option value="">Selecione...</option>
                    <option value="AC">Acre</option>
                    <option value="AL">Alagoas</option>
                    <option value="AP">Amapá</option>
                    <option value="AM">Amazonas</option>
                    <option value="BA">Bahia</option>
                    <option value="CE">Ceará</option>
                    <option value="DF">Distrito Federal</option>
                    <option value="ES">Espírito Santo</option>
                    <option value="GO">Goiás</option>
                    <option value="MA">Maranhão</option>
                    <option value="MT">Mato Grosso</option>
                    <option value="MS">Mato Grosso do Sul</option>
                    <option value="MG">Minas Gerais</option>
                    <option value="PA">Pará</option>
                    <option value="PB">Paraíba</option>
                    <option value="PR">Paraná</option>
                    <option value="PE">Pernambuco</option>
                    <option value="PI">Piauí</option>
                    <option value="RJ">Rio de Janeiro</option>
                    <option value="RN">Rio Grande do Norte</option>
                    <option value="RS">Rio Grande do Sul</option>
                    <option value="RO">Rondônia</option>
                    <option value="RR">Roraima</option>
                    <option value="SC">Santa Catarina</option>
                    <option value="SP">São Paulo</option>
                    <option value="SE">Sergipe</option>
                    <option value="TO">Tocantins</option>
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
                        <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
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
                       class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="Ex: Sala, Cozinha, Quarto...">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição do Problema *</label>
                <textarea name="descricao_problema" required rows="4"
                          class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                          placeholder="Descreva detalhadamente o problema..."></textarea>
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
            
            <div id="horarios-selecionados" class="hidden">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    Horários Selecionados (<span id="contador-horarios">0</span>/3)
                </h4>
                <div id="lista-horarios" class="space-y-2">
                    <!-- Horários serão inseridos aqui via JavaScript -->
                </div>
            </div>
            
            <input type="hidden" name="horarios_opcoes" id="horarios_opcoes" value="[]">
        </div>
    </div>
    
    <!-- Fotos -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-images mr-2 text-green-600"></i>
            Fotos (Opcional)
        </h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexar Fotos</label>
                <input type="file" name="fotos[]" multiple accept="image/*"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition-all">
                <p class="text-xs text-gray-500 mt-2">Você pode selecionar múltiplas fotos (JPG, PNG, GIF, WEBP)</p>
            </div>
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
            Salvar Solicitação
        </button>
    </div>
</form>

<script>
// Atualizar subcategorias quando categoria mudar
document.getElementById('categoria_id').addEventListener('change', function() {
    const categoriaId = this.value;
    const subcategoriaSelect = document.getElementById('subcategoria_id');
    
    subcategoriaSelect.innerHTML = '<option value="">Selecione...</option>';
    
    if (categoriaId) {
        const categoria = <?= json_encode($categorias) ?>.find(c => c.id == categoriaId);
        if (categoria && categoria.subcategorias) {
            categoria.subcategorias.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.nome;
                subcategoriaSelect.appendChild(option);
            });
        }
    }
});

// Máscaras de input
let cpfTimeout;
document.getElementById('cpf')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    }
    
    // Buscar dados quando CPF estiver completo (11 dígitos)
    const cpfLimpo = e.target.value.replace(/\D/g, '');
    const imobiliariaId = document.getElementById('imobiliaria_id')?.value;
    const statusElement = document.getElementById('cpf-status');
    
    // Limpar timeout anterior
    if (cpfTimeout) {
        clearTimeout(cpfTimeout);
    }
    
    if (cpfLimpo.length === 11 && imobiliariaId) {
        statusElement.textContent = 'Buscando dados...';
        statusElement.className = 'text-xs text-blue-500 mt-1';
        
        // Aguardar 500ms após parar de digitar
        cpfTimeout = setTimeout(async () => {
            try {
                const response = await fetch('<?= url("admin/solicitacoes-manuais/buscar-cpf") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        cpf: cpfLimpo,
                        imobiliaria_id: imobiliariaId
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.exists) {
                    // Preencher campos automaticamente
                    if (result.data.nome) {
                        document.getElementById('nome_completo').value = result.data.nome;
                    }
                    if (result.data.whatsapp) {
                        const whatsapp = result.data.whatsapp.replace(/\D/g, '');
                        let formatted = whatsapp;
                        if (whatsapp.length <= 10) {
                            formatted = whatsapp.replace(/(\d{2})(\d)/, '($1) $2');
                            formatted = formatted.replace(/(\d{4})(\d)/, '$1-$2');
                        } else {
                            formatted = whatsapp.replace(/(\d{2})(\d)/, '($1) $2');
                            formatted = formatted.replace(/(\d{5})(\d)/, '$1-$2');
                        }
                        document.getElementById('whatsapp').value = formatted;
                    }
                    if (result.data.endereco_logradouro) {
                        document.getElementById('endereco').value = result.data.endereco_logradouro;
                    }
                    if (result.data.endereco_numero) {
                        document.getElementById('numero').value = result.data.endereco_numero;
                    }
                    if (result.data.endereco_complemento) {
                        document.getElementById('complemento').value = result.data.endereco_complemento;
                    }
                    if (result.data.endereco_bairro) {
                        document.getElementById('bairro').value = result.data.endereco_bairro;
                    }
                    if (result.data.endereco_cidade) {
                        document.getElementById('cidade').value = result.data.endereco_cidade;
                    }
                    if (result.data.endereco_estado) {
                        document.getElementById('estado').value = result.data.endereco_estado;
                    }
                    if (result.data.endereco_cep) {
                        const cep = result.data.endereco_cep.replace(/\D/g, '');
                        document.getElementById('cep').value = cep.replace(/(\d{5})(\d)/, '$1-$2');
                    }
                    if (result.data.numero_contrato) {
                        document.querySelector('input[name="numero_contrato"]').value = result.data.numero_contrato;
                    }
                    
                    statusElement.textContent = '✓ CPF encontrado - Dados preenchidos automaticamente';
                    statusElement.className = 'text-xs text-green-600 mt-1';
                    
                    // Se CPF encontrado (bolsão validado), pode ser CORTESIA
                    const tipoQualificacaoSelect = document.getElementById('tipo_qualificacao');
                    if (tipoQualificacaoSelect) {
                        tipoQualificacaoSelect.value = 'CORTESIA';
                    }
                } else {
                    statusElement.textContent = 'CPF não encontrado - Preencha os dados manualmente';
                    statusElement.className = 'text-xs text-gray-500 mt-1';
                    
                    // Se CPF não encontrado, automaticamente NAO_QUALIFICADA
                    const tipoQualificacaoSelect = document.getElementById('tipo_qualificacao');
                    if (tipoQualificacaoSelect) {
                        tipoQualificacaoSelect.value = 'NAO_QUALIFICADA';
                        tipoQualificacaoSelect.disabled = true;
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar CPF:', error);
                statusElement.textContent = '';
            }
        }, 500);
    } else {
        statusElement.textContent = '';
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
            // Preencher campos automaticamente
            document.getElementById('endereco').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
            
            // Focar no campo número após preencher
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

// Buscar CEP automaticamente ao sair do campo (se tiver 8 dígitos)
document.getElementById('cep')?.addEventListener('blur', async function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        const btn = document.getElementById('btn-buscar-cep');
        if (btn) {
            btn.click();
        }
    }
});

// Sistema de seleção de horários (igual nova solicitação)
const dataInput = document.getElementById('data_selecionada');
const horarioRadios = document.querySelectorAll('.horario-radio');
const horarioCards = document.querySelectorAll('.horario-card');
const horariosSelecionados = document.getElementById('horarios-selecionados');
const listaHorarios = document.getElementById('lista-horarios');
const contadorHorarios = document.getElementById('contador-horarios');

let horariosEscolhidos = [];
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
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

