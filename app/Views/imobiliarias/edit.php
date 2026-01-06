<?php
/**
 * View: Editar Imobiliária
 */
$title = 'Editar Imobiliária';
$currentPage = 'imobiliarias';
$pageTitle = 'Editar Imobiliária';
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-4">
        <li>
            <div>
                <a href="<?= url('admin/imobiliarias') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-building"></i>
                    <span class="sr-only">Imobiliárias</span>
                </a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-sm font-medium text-gray-500">Editar Imobiliária</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Formulário -->
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Editar Imobiliária</h3>
        <p class="text-sm text-gray-500">Atualize os dados da imobiliária</p>
    </div>
    
    <form method="POST" action="<?= url('admin/imobiliarias/' . $imobiliaria['id']) ?>" class="p-6 space-y-8" enctype="multipart/form-data">
        <?= \App\Core\View::csrfField() ?>
        <input type="hidden" name="_method" value="PUT">
        
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

        <!-- Informações Básicas -->
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome Fantasia -->
                <div>
                    <label for="nome_fantasia" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome Fantasia
                    </label>
                    <input type="text" 
                           id="nome_fantasia" 
                           name="nome_fantasia" 
                           value="<?= htmlspecialchars($imobiliaria['nome_fantasia'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Nome fantasia da imobiliária">
                </div>

                <!-- Razão Social -->
                <div>
                    <label for="razao_social" class="block text-sm font-medium text-gray-700 mb-2">
                        Razão Social
                    </label>
                    <input type="text" 
                           id="razao_social" 
                           name="razao_social" 
                           value="<?= htmlspecialchars($imobiliaria['razao_social'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Razão social da empresa">
                </div>

                <!-- CNPJ -->
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-2">
                        CNPJ
                    </label>
                    <div class="flex space-x-2">
                        <input type="text" 
                               id="cnpj" 
                               name="cnpj" 
                               value="<?= htmlspecialchars($imobiliaria['cnpj'] ?? '') ?>"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="00.000.000/0000-00"
                               maxlength="18">
                        <button type="button" 
                                onclick="buscarCnpj()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </div>
                </div>

                <!-- Instância -->
                <div>
                    <label for="instancia" class="block text-sm font-medium text-gray-700 mb-2">
                        Instância
                    </label>
                    <input type="text" 
                           id="instancia" 
                           name="instancia" 
                           value="<?= htmlspecialchars($imobiliaria['instancia'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="ex: demo, topx">
                    <p class="mt-1 text-sm text-gray-500">Usado na URL: localhost/kss/{instancia}</p>
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Endereço</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- CEP -->
                <div>
                    <label for="endereco_cep" class="block text-sm font-medium text-gray-700 mb-2">
                        CEP
                    </label>
                    <input type="text" 
                           id="endereco_cep" 
                           name="endereco_cep" 
                           value="<?= htmlspecialchars($imobiliaria['endereco_cep'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="00000-000"
                           maxlength="9">
                </div>

                <!-- Logradouro -->
                <div class="md:col-span-2">
                    <label for="endereco_logradouro" class="block text-sm font-medium text-gray-700 mb-2">
                        Logradouro
                    </label>
                    <input type="text" 
                           id="endereco_logradouro" 
                           name="endereco_logradouro" 
                           value="<?= htmlspecialchars($imobiliaria['endereco_logradouro'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Rua, Avenida, etc.">
                </div>

                <!-- Número -->
                <div>
                    <label for="endereco_numero" class="block text-sm font-medium text-gray-700 mb-2">
                        Número
                    </label>
                    <input type="text" 
                           id="endereco_numero" 
                           name="endereco_numero" 
                           value="<?= htmlspecialchars($imobiliaria['endereco_numero'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="123">
                </div>

                <!-- Complemento -->
                <div>
                    <label for="endereco_complemento" class="block text-sm font-medium text-gray-700 mb-2">
                        Complemento
                    </label>
                    <input type="text" 
                           id="endereco_complemento" 
                           name="endereco_complemento" 
                           value="<?= htmlspecialchars($imobiliaria['endereco_complemento'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Sala, Andar, etc.">
                </div>

                <!-- Bairro -->
                <div>
                    <label for="endereco_bairro" class="block text-sm font-medium text-gray-700 mb-2">
                        Bairro
                    </label>
                    <input type="text" 
                           id="endereco_bairro" 
                           name="endereco_bairro" 
                           value="<?= htmlspecialchars($imobiliaria['endereco_bairro'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Nome do bairro">
                </div>

                <!-- Cidade -->
                <div>
                    <label for="endereco_cidade" class="block text-sm font-medium text-gray-700 mb-2">
                        Cidade
                    </label>
                    <input type="text" 
                           id="endereco_cidade" 
                           name="endereco_cidade" 
                           value="<?= htmlspecialchars($imobiliaria['endereco_cidade'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Nome da cidade">
                </div>

                <!-- Estado -->
                <div>
                    <label for="endereco_estado" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado
                    </label>
                    <select id="endereco_estado" 
                            name="endereco_estado" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione o estado</option>
                        <option value="AC" <?= ($imobiliaria['endereco_estado'] ?? '') === 'AC' ? 'selected' : '' ?>>Acre</option>
                        <option value="AL" <?= ($imobiliaria['endereco_estado'] ?? '') === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                        <option value="AP" <?= ($imobiliaria['endereco_estado'] ?? '') === 'AP' ? 'selected' : '' ?>>Amapá</option>
                        <option value="AM" <?= ($imobiliaria['endereco_estado'] ?? '') === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                        <option value="BA" <?= ($imobiliaria['endereco_estado'] ?? '') === 'BA' ? 'selected' : '' ?>>Bahia</option>
                        <option value="CE" <?= ($imobiliaria['endereco_estado'] ?? '') === 'CE' ? 'selected' : '' ?>>Ceará</option>
                        <option value="DF" <?= ($imobiliaria['endereco_estado'] ?? '') === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                        <option value="ES" <?= ($imobiliaria['endereco_estado'] ?? '') === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                        <option value="GO" <?= ($imobiliaria['endereco_estado'] ?? '') === 'GO' ? 'selected' : '' ?>>Goiás</option>
                        <option value="MA" <?= ($imobiliaria['endereco_estado'] ?? '') === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                        <option value="MT" <?= ($imobiliaria['endereco_estado'] ?? '') === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                        <option value="MS" <?= ($imobiliaria['endereco_estado'] ?? '') === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?= ($imobiliaria['endereco_estado'] ?? '') === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                        <option value="PA" <?= ($imobiliaria['endereco_estado'] ?? '') === 'PA' ? 'selected' : '' ?>>Pará</option>
                        <option value="PB" <?= ($imobiliaria['endereco_estado'] ?? '') === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                        <option value="PR" <?= ($imobiliaria['endereco_estado'] ?? '') === 'PR' ? 'selected' : '' ?>>Paraná</option>
                        <option value="PE" <?= ($imobiliaria['endereco_estado'] ?? '') === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                        <option value="PI" <?= ($imobiliaria['endereco_estado'] ?? '') === 'PI' ? 'selected' : '' ?>>Piauí</option>
                        <option value="RJ" <?= ($imobiliaria['endereco_estado'] ?? '') === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                        <option value="RN" <?= ($imobiliaria['endereco_estado'] ?? '') === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                        <option value="RS" <?= ($imobiliaria['endereco_estado'] ?? '') === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                        <option value="RO" <?= ($imobiliaria['endereco_estado'] ?? '') === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                        <option value="RR" <?= ($imobiliaria['endereco_estado'] ?? '') === 'RR' ? 'selected' : '' ?>>Roraima</option>
                        <option value="SC" <?= ($imobiliaria['endereco_estado'] ?? '') === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                        <option value="SP" <?= ($imobiliaria['endereco_estado'] ?? '') === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                        <option value="SE" <?= ($imobiliaria['endereco_estado'] ?? '') === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                        <option value="TO" <?= ($imobiliaria['endereco_estado'] ?? '') === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Contato -->
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Contato</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Telefone -->
                <div>
                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">
                        Telefone
                    </label>
                    <input type="text" 
                           id="telefone" 
                           name="telefone" 
                           value="<?= htmlspecialchars($imobiliaria['telefone'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="(11) 99999-9999"
                           maxlength="15">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($imobiliaria['email'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="contato@imobiliaria.com.br">
                </div>
            </div>
        </div>

        <!-- Configurações da API -->
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Configurações da API KSI</h4>
            
            <!-- Integração -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="integracao_ativa" 
                           value="1"
                           <?= (isset($imobiliaria['integracao_ativa']) && $imobiliaria['integracao_ativa']) || (!isset($imobiliaria['integracao_ativa'])) ? 'checked' : '' ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-700">
                        Ativar Integração
                    </span>
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    Quando desativada, os usuários serão automaticamente redirecionados para a tela de solicitação manual ao acessar a instância ou selecionar a imobiliária.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- API ID -->
                <div>
                    <label for="api_id" class="block text-sm font-medium text-gray-700 mb-2">
                        ID da API
                    </label>
                    <input type="text" 
                           id="api_id" 
                           name="api_id" 
                           value="<?= htmlspecialchars($imobiliaria['api_id'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="42">
                </div>

                <!-- URL Base -->
                <div>
                    <label for="url_base" class="block text-sm font-medium text-gray-700 mb-2">
                        URL Base
                    </label>
                    <input type="url" 
                           id="url_base" 
                           name="url_base" 
                           value="<?= htmlspecialchars($imobiliaria['url_base'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://www.exemplo.com.br">
                </div>

                <!-- Token -->
                <div>
                    <label for="token" class="block text-sm font-medium text-gray-700 mb-2">
                        Token
                    </label>
                    <input type="text" 
                           id="token" 
                           name="token" 
                           value="<?= htmlspecialchars($imobiliaria['token'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Token de autenticação">
                </div>

                <!-- Instância -->
                <div>
                    <label for="instancia" class="block text-sm font-medium text-gray-700 mb-2">
                        Instância
                    </label>
                    <input type="text" 
                           id="instancia" 
                           name="instancia" 
                           value="<?= htmlspecialchars($imobiliaria['instancia'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="demo, topx, etc.">
                </div>

                <!-- API Token -->
            </div>
        </div>

        <!-- Personalização -->
        <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Personalização</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Logo -->
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">
                        Logo
                    </label>
                    <div class="flex items-center space-x-4">
                        <?php if ($imobiliaria['logo']): ?>
                            <img src="<?= url('Public/uploads/logos/' . $imobiliaria['logo']) ?>" 
                                 alt="Logo atual" 
                                 class="w-16 h-16 rounded-lg object-cover border border-gray-200">
                        <?php endif; ?>
                        <input type="file" 
                               id="logo" 
                               name="logo" 
                               accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</p>
                </div>

                <!-- Cor Primária -->
                <div>
                    <label for="cor_primaria" class="block text-sm font-medium text-gray-700 mb-2">
                        Cor Primária
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="color" 
                               id="cor_primaria" 
                               name="cor_primaria" 
                               value="<?= htmlspecialchars($imobiliaria['cor_primaria'] ?? '#3B82F6') ?>"
                               class="w-12 h-10 border border-gray-300 rounded-md">
                        <input type="text" 
                               id="cor_primaria_text" 
                               value="<?= htmlspecialchars($imobiliaria['cor_primaria'] ?? '#3B82F6') ?>"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="#3B82F6">
                    </div>
                </div>

                <!-- Cor Secundária -->
                <div>
                    <label for="cor_secundaria" class="block text-sm font-medium text-gray-700 mb-2">
                        Cor Secundária
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="color" 
                               id="cor_secundaria" 
                               name="cor_secundaria" 
                               value="<?= htmlspecialchars($imobiliaria['cor_secundaria'] ?? '#1E40AF') ?>"
                               class="w-12 h-10 border border-gray-300 rounded-md">
                        <input type="text" 
                               id="cor_secundaria_text" 
                               value="<?= htmlspecialchars($imobiliaria['cor_secundaria'] ?? '#1E40AF') ?>"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="#1E40AF">
                    </div>
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div>
            <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">
                Observações
            </label>
            <textarea id="observacoes" 
                      name="observacoes" 
                      rows="4" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Observações adicionais sobre a imobiliária"><?= htmlspecialchars($imobiliaria['observacoes'] ?? '') ?></textarea>
        </div>

        <!-- Botões -->
        <div class="flex justify-end space-x-4">
            <a href="<?= url('admin/imobiliarias') ?>" 
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i>
                Salvar Alterações
            </button>
        </div>
    </form>
</div>

<!-- Informações da Receita Federal -->
<div id="receita-info" class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4 hidden">
    <h4 class="text-lg font-medium text-blue-900 mb-2">Informações da Receita Federal</h4>
    <div id="receita-content" class="text-sm text-blue-800"></div>
</div>

<script>
// Máscaras
document.addEventListener('DOMContentLoaded', function() {
    // Máscara CNPJ
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }

    // Máscara CEP
    const cepInput = document.getElementById('endereco_cep');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }

    // Máscara Telefone
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    }

    // Sincronizar cores
    const corPrimaria = document.getElementById('cor_primaria');
    const corPrimariaText = document.getElementById('cor_primaria_text');
    const corSecundaria = document.getElementById('cor_secundaria');
    const corSecundariaText = document.getElementById('cor_secundaria_text');

    if (corPrimaria && corPrimariaText) {
        corPrimaria.addEventListener('change', function() {
            corPrimariaText.value = this.value;
        });
        corPrimariaText.addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-F]{6}$/i)) {
                corPrimaria.value = this.value;
            }
        });
    }

    if (corSecundaria && corSecundariaText) {
        corSecundaria.addEventListener('change', function() {
            corSecundariaText.value = this.value;
        });
        corSecundariaText.addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-F]{6}$/i)) {
                corSecundaria.value = this.value;
            }
        });
    }
});

// Buscar CNPJ
function buscarCnpj() {
    const cnpjInput = document.getElementById('cnpj');
    const button = event.target;
    const csrfToken = document.querySelector('input[name="_token"]');
    
    if (!cnpjInput || !button || !csrfToken) {
        console.error('Elementos não encontrados');
        showNotification('Erro: Elementos do formulário não encontrados', 'error');
        return;
    }
    
    const cnpj = cnpjInput.value.replace(/\D/g, '');
    
    if (cnpj.length !== 14) {
        showNotification('CNPJ deve ter 14 dígitos', 'error');
        return;
    }
    
    // Mostrar loading
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
    button.disabled = true;
    
    fetch('<?= url('admin/imobiliarias/buscar-cnpj') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken.value
        },
        body: JSON.stringify({ cnpj: cnpj })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            preencherCampos(data.data);
            mostrarInformacoesReceita(data.data);
            showNotification('Dados do CNPJ carregados com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao buscar dados do CNPJ: ' + error.message, 'error');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function preencherCampos(dados) {
    const campos = {
        'razao_social': dados.nome || '',
        'nome_fantasia': dados.fantasia || dados.nome || '',
        'endereco_logradouro': dados.logradouro || '',
        'endereco_numero': dados.numero || '',
        'endereco_complemento': dados.complemento || '',
        'endereco_bairro': dados.bairro || '',
        'endereco_cidade': dados.municipio || '',
        'endereco_estado': dados.uf || '',
        'endereco_cep': dados.cep || '',
        'email': dados.email || '',
        'telefone': dados.telefone || ''
    };
    
    Object.keys(campos).forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = campos[campo];
        }
    });
}

function mostrarInformacoesReceita(dados) {
    const infoDiv = document.getElementById('receita-info');
    const contentDiv = document.getElementById('receita-content');
    
    if (infoDiv && contentDiv) {
        contentDiv.innerHTML = `
            <p><strong>Situação:</strong> ${dados.situacao || 'N/A'}</p>
            <p><strong>Porte:</strong> ${dados.porte || 'N/A'}</p>
            <p><strong>Natureza Jurídica:</strong> ${dados.natureza_juridica || 'N/A'}</p>
            <p><strong>Capital Social:</strong> ${dados.capital_social || 'N/A'}</p>
            <p><strong>Atividade Principal:</strong> ${dados.atividade_principal?.[0]?.text || 'N/A'}</p>
        `;
        infoDiv.classList.remove('hidden');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
