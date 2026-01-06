<?php
/**
 * View: Criar Nova Imobiliária
 */
$title = 'Nova Imobiliária';
$currentPage = 'imobiliarias';
$pageTitle = 'Nova Imobiliária';
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-4">
        <li>
            <div>
                <a href="<?= url('imobiliarias') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-building"></i>
                    <span class="sr-only">Imobiliárias</span>
                </a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-sm font-medium text-gray-500">Nova Imobiliária</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Formulário -->
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Cadastrar Nova Imobiliária</h3>
        <p class="text-sm text-gray-500">Preencha os dados da imobiliária parceira</p>
    </div>
    
    <form method="POST" action="<?= url('imobiliarias') ?>" class="p-6 space-y-8" enctype="multipart/form-data">
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
        
        <!-- Dados Empresariais -->
        <div class="space-y-6">
            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Dados Empresariais</h4>
            
            <!-- Informações da Receita Federal -->
            <div id="receita-info" class="hidden bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Informações da Receita Federal</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <div id="receita-details" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <!-- Será preenchido via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- CNPJ -->
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700">
                        CNPJ
                    </label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input type="text" 
                               name="cnpj" 
                               id="cnpj" 
                               value="<?= htmlspecialchars($data['cnpj'] ?? '') ?>"
                               class="flex-1 border-gray-300 rounded-l-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['cnpj']) ? 'border-red-300' : '' ?>"
                               placeholder="00.000.000/0000-00">
                        <button type="button" 
                                id="buscar-cnpj-btn"
                                onclick="buscarCnpj()"
                                class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 text-sm hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search mr-1"></i>
                            Buscar
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Digite o CNPJ e clique em "Buscar" para preencher automaticamente os dados da Receita Federal
                    </p>
                    <?php if (isset($errors['cnpj'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['cnpj']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Razão Social -->
                <div>
                    <label for="razao_social" class="block text-sm font-medium text-gray-700">
                        Razão Social
                    </label>
                    <input type="text" 
                           name="razao_social" 
                           id="razao_social" 
                           value="<?= htmlspecialchars($data['razao_social'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['razao_social']) ? 'border-red-300' : '' ?>"
                           placeholder="Nome da empresa conforme registro">
                    <?php if (isset($errors['razao_social'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['razao_social']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Nome Fantasia -->
                <div class="md:col-span-2">
                    <label for="nome_fantasia" class="block text-sm font-medium text-gray-700">
                        Nome Fantasia
                    </label>
                    <input type="text" 
                           name="nome_fantasia" 
                           id="nome_fantasia" 
                           value="<?= htmlspecialchars($data['nome_fantasia'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['nome_fantasia']) ? 'border-red-300' : '' ?>"
                           placeholder="Nome comercial da empresa">
                    <?php if (isset($errors['nome_fantasia'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['nome_fantasia']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Endereço -->
        <div class="space-y-6">
            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Endereço</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Logradouro -->
                <div class="md:col-span-2">
                    <label for="endereco_logradouro" class="block text-sm font-medium text-gray-700">
                        Logradouro
                    </label>
                    <input type="text" 
                           name="endereco_logradouro" 
                           id="endereco_logradouro" 
                           value="<?= htmlspecialchars($data['endereco_logradouro'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['endereco_logradouro']) ? 'border-red-300' : '' ?>"
                           placeholder="Rua, Avenida, etc.">
                    <?php if (isset($errors['endereco_logradouro'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['endereco_logradouro']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Número -->
                <div>
                    <label for="endereco_numero" class="block text-sm font-medium text-gray-700">
                        Número
                    </label>
                    <input type="text" 
                           name="endereco_numero" 
                           id="endereco_numero" 
                           value="<?= htmlspecialchars($data['endereco_numero'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['endereco_numero']) ? 'border-red-300' : '' ?>"
                           placeholder="123">
                    <?php if (isset($errors['endereco_numero'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['endereco_numero']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Complemento -->
                <div>
                    <label for="endereco_complemento" class="block text-sm font-medium text-gray-700">
                        Complemento
                    </label>
                    <input type="text" 
                           name="endereco_complemento" 
                           id="endereco_complemento" 
                           value="<?= htmlspecialchars($data['endereco_complemento'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Sala, Andar, etc.">
                </div>
                
                <!-- Bairro -->
                <div>
                    <label for="endereco_bairro" class="block text-sm font-medium text-gray-700">
                        Bairro
                    </label>
                    <input type="text" 
                           name="endereco_bairro" 
                           id="endereco_bairro" 
                           value="<?= htmlspecialchars($data['endereco_bairro'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['endereco_bairro']) ? 'border-red-300' : '' ?>"
                           placeholder="Nome do bairro">
                    <?php if (isset($errors['endereco_bairro'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['endereco_bairro']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Cidade -->
                <div>
                    <label for="endereco_cidade" class="block text-sm font-medium text-gray-700">
                        Cidade
                    </label>
                    <input type="text" 
                           name="endereco_cidade" 
                           id="endereco_cidade" 
                           value="<?= htmlspecialchars($data['endereco_cidade'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['endereco_cidade']) ? 'border-red-300' : '' ?>"
                           placeholder="Nome da cidade">
                    <?php if (isset($errors['endereco_cidade'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['endereco_cidade']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Estado -->
                <div>
                    <label for="endereco_estado" class="block text-sm font-medium text-gray-700">
                        Estado
                    </label>
                    <select name="endereco_estado" 
                            id="endereco_estado"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['endereco_estado']) ? 'border-red-300' : '' ?>">
                        <option value="">Selecione</option>
                        <option value="AC" <?= ($data['endereco_estado'] ?? '') === 'AC' ? 'selected' : '' ?>>Acre</option>
                        <option value="AL" <?= ($data['endereco_estado'] ?? '') === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                        <option value="AP" <?= ($data['endereco_estado'] ?? '') === 'AP' ? 'selected' : '' ?>>Amapá</option>
                        <option value="AM" <?= ($data['endereco_estado'] ?? '') === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                        <option value="BA" <?= ($data['endereco_estado'] ?? '') === 'BA' ? 'selected' : '' ?>>Bahia</option>
                        <option value="CE" <?= ($data['endereco_estado'] ?? '') === 'CE' ? 'selected' : '' ?>>Ceará</option>
                        <option value="DF" <?= ($data['endereco_estado'] ?? '') === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                        <option value="ES" <?= ($data['endereco_estado'] ?? '') === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                        <option value="GO" <?= ($data['endereco_estado'] ?? '') === 'GO' ? 'selected' : '' ?>>Goiás</option>
                        <option value="MA" <?= ($data['endereco_estado'] ?? '') === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                        <option value="MT" <?= ($data['endereco_estado'] ?? '') === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                        <option value="MS" <?= ($data['endereco_estado'] ?? '') === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?= ($data['endereco_estado'] ?? '') === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                        <option value="PA" <?= ($data['endereco_estado'] ?? '') === 'PA' ? 'selected' : '' ?>>Pará</option>
                        <option value="PB" <?= ($data['endereco_estado'] ?? '') === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                        <option value="PR" <?= ($data['endereco_estado'] ?? '') === 'PR' ? 'selected' : '' ?>>Paraná</option>
                        <option value="PE" <?= ($data['endereco_estado'] ?? '') === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                        <option value="PI" <?= ($data['endereco_estado'] ?? '') === 'PI' ? 'selected' : '' ?>>Piauí</option>
                        <option value="RJ" <?= ($data['endereco_estado'] ?? '') === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                        <option value="RN" <?= ($data['endereco_estado'] ?? '') === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                        <option value="RS" <?= ($data['endereco_estado'] ?? '') === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                        <option value="RO" <?= ($data['endereco_estado'] ?? '') === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                        <option value="RR" <?= ($data['endereco_estado'] ?? '') === 'RR' ? 'selected' : '' ?>>Roraima</option>
                        <option value="SC" <?= ($data['endereco_estado'] ?? '') === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                        <option value="SP" <?= ($data['endereco_estado'] ?? '') === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                        <option value="SE" <?= ($data['endereco_estado'] ?? '') === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                        <option value="TO" <?= ($data['endereco_estado'] ?? '') === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                    </select>
                    <?php if (isset($errors['endereco_estado'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['endereco_estado']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- CEP -->
                <div>
                    <label for="endereco_cep" class="block text-sm font-medium text-gray-700">
                        CEP
                    </label>
                    <input type="text" 
                           name="endereco_cep" 
                           id="endereco_cep" 
                           value="<?= htmlspecialchars($data['endereco_cep'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['endereco_cep']) ? 'border-red-300' : '' ?>"
                           placeholder="00000-000">
                    <?php if (isset($errors['endereco_cep'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['endereco_cep']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Contato -->
        <div class="space-y-6">
            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Contato</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Telefone -->
                <div>
                    <label for="telefone" class="block text-sm font-medium text-gray-700">
                        Telefone
                    </label>
                    <input type="text" 
                           name="telefone" 
                           id="telefone" 
                           value="<?= htmlspecialchars($data['telefone'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['telefone']) ? 'border-red-300' : '' ?>"
                           placeholder="(00) 0000-0000">
                    <?php if (isset($errors['telefone'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['telefone']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['email']) ? 'border-red-300' : '' ?>"
                           placeholder="contato@empresa.com">
                    <?php if (isset($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Identidade Visual -->
        <div class="space-y-6">
            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Identidade Visual</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Logo -->
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">
                        Logo
                    </label>
                    <input type="file" 
                           name="logo" 
                           id="logo" 
                           accept="image/*"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">
                        Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB
                    </p>
                </div>
                
                <!-- Preview do Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Preview do Logo</label>
                    <div id="logo-preview" class="mt-1 w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                        <span class="text-gray-400 text-sm">Sem logo</span>
                    </div>
                </div>
                
                <!-- Cor Primária -->
                <div>
                    <label for="cor_primaria" class="block text-sm font-medium text-gray-700">
                        Cor Primária
                    </label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" 
                               name="cor_primaria" 
                               id="cor_primaria" 
                               value="<?= htmlspecialchars($data['cor_primaria'] ?? '#3B82F6') ?>"
                               class="h-10 w-16 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <input type="text" 
                               id="cor_primaria-text" 
                               value="<?= htmlspecialchars($data['cor_primaria'] ?? '#3B82F6') ?>"
                               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="#3B82F6">
                    </div>
                </div>
                
                <!-- Cor Secundária -->
                <div>
                    <label for="cor_secundaria" class="block text-sm font-medium text-gray-700">
                        Cor Secundária
                    </label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" 
                               name="cor_secundaria" 
                               id="cor_secundaria" 
                               value="<?= htmlspecialchars($data['cor_secundaria'] ?? '#1E40AF') ?>"
                               class="h-10 w-16 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <input type="text" 
                               id="cor_secundaria-text" 
                               value="<?= htmlspecialchars($data['cor_secundaria'] ?? '#1E40AF') ?>"
                               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="#1E40AF">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configurações da API -->
        <div class="space-y-6">
            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Configurações da API</h4>
            
            <!-- Integração -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="integracao_ativa" 
                           value="1"
                           <?= isset($data['integracao_ativa']) && $data['integracao_ativa'] ? 'checked' : 'checked' ?>
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
                    <label for="api_id" class="block text-sm font-medium text-gray-700">
                        ID da API
                    </label>
                    <input type="text" 
                           name="api_id" 
                           id="api_id" 
                           value="<?= htmlspecialchars($data['api_id'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="ID único da API">
                </div>
                
                <!-- URL Base -->
                <div>
                    <label for="url_base" class="block text-sm font-medium text-gray-700">
                        URL Base
                    </label>
                    <input type="url" 
                           name="url_base" 
                           id="url_base" 
                           value="<?= htmlspecialchars($data['url_base'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['url_base']) ? 'border-red-300' : '' ?>"
                           placeholder="https://www.exemplo.com.br">
                    <?php if (isset($errors['url_base'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['url_base']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Token -->
                <div>
                    <label for="token" class="block text-sm font-medium text-gray-700">
                        Token
                    </label>
                    <input type="text" 
                           name="token" 
                           id="token" 
                           value="<?= htmlspecialchars($data['token'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['token']) ? 'border-red-300' : '' ?>"
                           placeholder="Token de autenticação">
                    <?php if (isset($errors['token'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['token']) ?></p>
                    <?php endif; ?>
                </div>
                
                
                <!-- Instância -->
                <div>
                    <label for="instancia" class="block text-sm font-medium text-gray-700">
                        Instância
                    </label>
                    <input type="text" 
                           name="instancia" 
                           id="instancia" 
                           value="<?= htmlspecialchars($data['instancia'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['instancia']) ? 'border-red-300' : '' ?>"
                           placeholder="Nome da instância">
                    <?php if (isset($errors['instancia'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['instancia']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- URL Base -->
                <div>
                    <label for="url_base" class="block text-sm font-medium text-gray-700">
                        URL Base
                    </label>
                    <input type="url" 
                           name="url_base" 
                           id="url_base" 
                           value="<?= htmlspecialchars($data['url_base'] ?? '') ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="https://empresa.com">
                </div>
            </div>
        </div>
        
        <!-- Observações -->
        <div class="space-y-6">
            <h4 class="text-lg font-medium text-gray-900 border-b pb-2">Observações</h4>
            
            <div>
                <label for="observacoes" class="block text-sm font-medium text-gray-700">
                    Observações
                </label>
                <textarea name="observacoes" 
                          id="observacoes" 
                          rows="4"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                          placeholder="Informações adicionais sobre a imobiliária"><?= htmlspecialchars($data['observacoes'] ?? '') ?></textarea>
            </div>
        </div>
        
        <!-- Botões -->
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a href="<?= url('imobiliarias') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-times mr-2"></i>
                Cancelar
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i>
                Cadastrar Imobiliária
            </button>
        </div>
    </form>
</div>

<script>
// Preview do logo
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('logo-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover rounded-lg">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<span class="text-gray-400 text-sm">Sem logo</span>';
    }
});

// Máscaras
document.getElementById('cnpj').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/, '$1.$2');
    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
    value = value.replace(/(\d{4})(\d)/, '$1-$2');
    e.target.value = value;
});

document.getElementById('endereco_cep').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{5})(\d)/, '$1-$2');
    e.target.value = value;
});

document.getElementById('telefone').addEventListener('input', function(e) {
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

// Sincronização das cores
document.getElementById('cor_primaria').addEventListener('input', function() {
    document.getElementById('cor_primaria-text').value = this.value;
});

document.getElementById('cor_primaria-text').addEventListener('input', function() {
    const color = this.value;
    if (/^#[0-9A-F]{6}$/i.test(color)) {
        document.getElementById('cor_primaria').value = color;
    }
});

document.getElementById('cor_secundaria').addEventListener('input', function() {
    document.getElementById('cor_secundaria-text').value = this.value;
});

document.getElementById('cor_secundaria-text').addEventListener('input', function() {
    const color = this.value;
    if (/^#[0-9A-F]{6}$/i.test(color)) {
        document.getElementById('cor_secundaria').value = color;
    }
});

// Busca automática do CNPJ
function buscarCnpj() {
    const cnpjElement = document.getElementById('cnpj');
    const button = document.getElementById('buscar-cnpj-btn');
    const receitaInfo = document.getElementById('receita-info');
    const receitaDetails = document.getElementById('receita-details');
    
    // Verificar se os elementos existem
    if (!cnpjElement) {
        console.error('Elemento CNPJ não encontrado');
        showNotification('Erro: Campo CNPJ não encontrado', 'error');
        return;
    }
    
    if (!button) {
        console.error('Botão de busca não encontrado');
        showNotification('Erro: Botão de busca não encontrado', 'error');
        return;
    }
    
    const cnpj = cnpjElement.value;
    
    if (!cnpj || cnpj.length < 14) {
        showNotification('Por favor, digite um CNPJ válido', 'error');
        return;
    }
    
    // Mostrar loading
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Buscando...';
    button.disabled = true;
    
    // Buscar token CSRF
    const csrfToken = document.querySelector('input[name="_token"]');
    if (!csrfToken) {
        console.error('Token CSRF não encontrado');
        showNotification('Erro: Token de segurança não encontrado', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
        return;
    }
    
    // Fazer requisição
    fetch('<?= url('imobiliarias/buscar-cnpj') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken.value
        },
        body: JSON.stringify({ cnpj: cnpj })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Preencher campos automaticamente
            preencherCampos(data.data);
            
            // Mostrar informações da Receita Federal
            mostrarInformacoesReceita(data.data);
            
            // Mostrar sucesso
            showNotification('Dados da empresa carregados com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + (data.error || 'Erro desconhecido'), 'error');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        showNotification('Erro ao buscar dados do CNPJ: ' + error.message, 'error');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function preencherCampos(dados) {
    console.log('Preenchendo campos com dados:', dados);
    
    // Preencher campos básicos com verificação de existência
    const campos = {
        'razao_social': dados.razao_social,
        'nome_fantasia': dados.nome_fantasia,
        'endereco_logradouro': dados.endereco_logradouro,
        'endereco_numero': dados.endereco_numero,
        'endereco_complemento': dados.endereco_complemento,
        'endereco_bairro': dados.endereco_bairro,
        'endereco_cidade': dados.endereco_cidade,
        'endereco_estado': dados.endereco_estado,
        'endereco_cep': dados.endereco_cep,
        'telefone': dados.telefone,
        'email': dados.email
    };
    
    Object.keys(campos).forEach(campoId => {
        const elemento = document.getElementById(campoId);
        if (elemento && campos[campoId]) {
            elemento.value = campos[campoId];
            console.log(`Campo ${campoId} preenchido com: ${campos[campoId]}`);
        } else if (!elemento) {
            console.warn(`Elemento ${campoId} não encontrado`);
        }
    });
}

function mostrarInformacoesReceita(dados) {
    const receitaInfo = document.getElementById('receita-info');
    const receitaDetails = document.getElementById('receita-details');
    
    let html = '';
    
    if (dados.situacao) html += `<div><strong>Situação:</strong> ${dados.situacao}</div>`;
    if (dados.porte) html += `<div><strong>Porte:</strong> ${dados.porte}</div>`;
    if (dados.natureza_juridica) html += `<div><strong>Natureza Jurídica:</strong> ${dados.natureza_juridica}</div>`;
    if (dados.atividade_principal) html += `<div><strong>Atividade Principal:</strong> ${dados.atividade_principal}</div>`;
    if (dados.capital_social) html += `<div><strong>Capital Social:</strong> ${dados.capital_social}</div>`;
    if (dados.data_abertura) html += `<div><strong>Data de Abertura:</strong> ${dados.data_abertura}</div>`;
    
    receitaDetails.innerHTML = html;
    receitaInfo.classList.remove('hidden');
}

function showNotification(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' :
        type === 'error' ? 'bg-red-50 border border-red-200 text-red-800' :
        'bg-blue-50 border border-blue-200 text-blue-800'
    }`;
    
    notification.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                    'fa-info-circle'
                }"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
