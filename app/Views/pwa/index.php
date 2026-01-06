<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $app['name'] ?> - Locatário</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= $app['name'] ?>">
</head>
<body class="bg-white min-h-screen">
    <!-- Main Content -->
    <main class="max-w-md mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <!-- Logo KSS -->
            <div class="flex justify-center mb-4">
                <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 40) ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Assistência 360°
            </h1>
            <p class="text-gray-600 text-sm">
                Selecione a sua imobiliária
            </p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Mensagens de Erro -->
            <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário Por Localização -->
            <div>
                <form method="GET" id="formLogin" class="space-y-6" onsubmit="return redirecionarParaInstancia(event)">
                    <input type="hidden" name="instancia" id="instancia_selecionada" required>
                    
                    <!-- Logo da Imobiliária (aparece quando selecionada) -->
                    <div id="logo-imobiliaria-container" class="hidden mb-4">
                        <div class="flex justify-center">
                            <img id="logo-imobiliaria" src="" alt="" class="h-16 w-auto max-w-xs object-contain" onerror="this.style.display='none';">
                        </div>
                    </div>
                    
                    <!-- Estado -->
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-building text-gray-400 mr-1"></i>
                            Estado
                        </label>
                        <select id="estado" 
                                name="estado" 
                                onchange="carregarCidades()"
                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white">
                            <option value="">Selecione o estado</option>
                        </select>
                        <span id="contador-estado" class="text-xs text-gray-500 mt-1"></span>
                    </div>
                    
                    <!-- Cidade -->
                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                            Cidade
                        </label>
                        <select id="cidade" 
                                name="cidade" 
                                onchange="carregarImobiliarias()"
                                disabled
                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 appearance-none bg-gray-100">
                            <option value="">Selecione primeiro o estado</option>
                        </select>
                        <span id="contador-cidade" class="text-xs text-gray-500 mt-1"></span>
                    </div>
                    
                    <!-- Imobiliária -->
                    <div>
                        <label for="imobiliaria" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-building text-gray-400 mr-1"></i>
                            Imobiliária
                        </label>
                        <select id="imobiliaria" 
                                name="imobiliaria" 
                                onchange="selecionarImobiliaria()"
                                disabled
                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 appearance-none bg-gray-100">
                            <option value="">Selecione primeiro a cidade</option>
                        </select>
                        <span id="contador-imobiliaria" class="text-xs text-gray-500 mt-1"></span>
                        
                        <!-- Confirmação visual -->
                        <div id="confirmacao-imobiliaria" class="hidden mt-2 flex items-center gap-2 text-green-600">
                            <i class="fas fa-check-circle"></i>
                            <span id="nome-imobiliaria-selecionada" class="text-sm font-semibold"></span>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            id="btnAcessar"
                            disabled
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Acessar Assistência
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Carregar estados ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            carregarEstados();
        });
        
        async function carregarEstados() {
            try {
                const response = await fetch('<?= url('api/estados') ?>');
                const estados = await response.json();
                
                const selectEstado = document.getElementById('estado');
                selectEstado.innerHTML = '<option value="">Selecione o estado</option>';
                
                estados.forEach(estado => {
                    const option = document.createElement('option');
                    option.value = estado.estado;
                    option.textContent = estado.estado;
                    selectEstado.appendChild(option);
                });
            } catch (error) {
                console.error('Erro ao carregar estados:', error);
            }
        }
        
        async function carregarCidades() {
            const estado = document.getElementById('estado').value;
            const selectCidade = document.getElementById('cidade');
            const selectImobiliaria = document.getElementById('imobiliaria');
            const contadorCidade = document.getElementById('contador-cidade');
            const contadorImobiliaria = document.getElementById('contador-imobiliaria');
            const confirmacao = document.getElementById('confirmacao-imobiliaria');
            
            // Limpar cidades e imobiliárias
            selectCidade.innerHTML = '<option value="">Selecione primeiro o estado</option>';
            selectCidade.disabled = true;
            selectImobiliaria.innerHTML = '<option value="">Selecione primeiro a cidade</option>';
            selectImobiliaria.disabled = true;
            contadorCidade.textContent = '';
            contadorImobiliaria.textContent = '';
            confirmacao.classList.add('hidden');
            document.getElementById('instancia_selecionada').value = '';
            document.getElementById('btnAcessar').disabled = true;
            
            if (!estado) {
                return;
            }
            
            try {
                const response = await fetch(`<?= url('api/cidades') ?>?estado=${encodeURIComponent(estado)}`);
                const cidades = await response.json();
                
                selectCidade.innerHTML = '<option value="">Selecione a cidade</option>';
                selectCidade.disabled = false;
                
                cidades.forEach(cidade => {
                    const option = document.createElement('option');
                    option.value = cidade.cidade;
                    option.textContent = cidade.cidade;
                    selectCidade.appendChild(option);
                });
                
                // Atualizar contador
                const count = cidades.length;
                contadorCidade.textContent = `(${count} ${count === 1 ? 'disponível' : 'disponíveis'})`;
            } catch (error) {
                console.error('Erro ao carregar cidades:', error);
            }
        }
        
        async function carregarImobiliarias() {
            const estado = document.getElementById('estado').value;
            const cidade = document.getElementById('cidade').value;
            const selectImobiliaria = document.getElementById('imobiliaria');
            const contadorImobiliaria = document.getElementById('contador-imobiliaria');
            const confirmacao = document.getElementById('confirmacao-imobiliaria');
            const logoContainer = document.getElementById('logo-imobiliaria-container');
            const logoImobiliaria = document.getElementById('logo-imobiliaria');
            
            // Limpar imobiliárias
            selectImobiliaria.innerHTML = '<option value="">Selecione primeiro a cidade</option>';
            selectImobiliaria.disabled = true;
            contadorImobiliaria.textContent = '';
            confirmacao.classList.add('hidden');
            logoContainer.classList.add('hidden');
            logoImobiliaria.src = '';
            document.getElementById('instancia_selecionada').value = '';
            document.getElementById('btnAcessar').disabled = true;
            
            if (!estado || !cidade) {
                return;
            }
            
            try {
                const response = await fetch(`<?= url('api/imobiliarias') ?>?estado=${encodeURIComponent(estado)}&cidade=${encodeURIComponent(cidade)}`);
                const imobiliarias = await response.json();
                
                selectImobiliaria.innerHTML = '<option value="">Selecione a imobiliária</option>';
                selectImobiliaria.disabled = false;
                
                imobiliarias.forEach(imob => {
                    const option = document.createElement('option');
                    option.value = imob.instancia;
                    option.setAttribute('data-nome', imob.nome || imob.nome_fantasia || '');
                    option.setAttribute('data-logo', imob.logo || '');
                    option.setAttribute('data-integracao-ativa', imob.integracao_ativa !== false ? '1' : '0');
                    option.textContent = imob.nome || imob.nome_fantasia || imob.instancia;
                    selectImobiliaria.appendChild(option);
                });
                
                // Atualizar contador
                const count = imobiliarias.length;
                contadorImobiliaria.textContent = `(${count} ${count === 1 ? 'encontrada' : 'encontradas'})`;
            } catch (error) {
                console.error('Erro ao carregar imobiliárias:', error);
            }
        }
        
        function selecionarImobiliaria() {
            const selectImobiliaria = document.getElementById('imobiliaria');
            const instancia = selectImobiliaria.value;
            const selectedOption = selectImobiliaria.options[selectImobiliaria.selectedIndex];
            const nome = selectedOption?.getAttribute('data-nome') || '';
            const logo = selectedOption?.getAttribute('data-logo') || '';
            const confirmacao = document.getElementById('confirmacao-imobiliaria');
            const nomeSelecionada = document.getElementById('nome-imobiliaria-selecionada');
            const btnAcessar = document.getElementById('btnAcessar');
            const logoContainer = document.getElementById('logo-imobiliaria-container');
            const logoImobiliaria = document.getElementById('logo-imobiliaria');
            
            if (instancia) {
                document.getElementById('instancia_selecionada').value = instancia;
                nomeSelecionada.textContent = nome || instancia;
                confirmacao.classList.remove('hidden');
                btnAcessar.disabled = false;
                
                // Exibir logo da imobiliária
                if (logo) {
                    logoImobiliaria.src = '<?= url('Public/uploads/logos/') ?>' + logo;
                    logoImobiliaria.alt = nome || instancia;
                    logoContainer.classList.remove('hidden');
                    logoImobiliaria.style.display = 'block';
                } else {
                    logoContainer.classList.add('hidden');
                }
            } else {
                confirmacao.classList.add('hidden');
                logoContainer.classList.add('hidden');
                logoImobiliaria.src = '';
                document.getElementById('instancia_selecionada').value = '';
                btnAcessar.disabled = true;
            }
        }
        
        function redirecionarParaInstancia(event) {
            event.preventDefault();
            const instancia = document.getElementById('instancia_selecionada').value;
            const selectImobiliaria = document.getElementById('imobiliaria');
            const selectedOption = selectImobiliaria.options[selectImobiliaria.selectedIndex];
            const integracaoAtiva = selectedOption?.getAttribute('data-integracao-ativa') === '1';
            
            if (!instancia) {
                alert('Por favor, selecione uma imobiliária');
                return false;
            }
            
            // Verificar se a integração está ativa
            if (!integracaoAtiva) {
                // Redirecionar para solicitação manual se integração estiver desativada
                const baseUrl = '<?= url('') ?>';
                const urlSolicitacaoManual = baseUrl.endsWith('/') ? baseUrl + instancia + '/solicitacao-manual' : baseUrl + '/' + instancia + '/solicitacao-manual';
                window.location.href = urlSolicitacaoManual;
                return false;
            }
            
            // Redirecionar para a URL da instância (login normal)
            const baseUrl = '<?= url('') ?>';
            const urlInstancia = baseUrl.endsWith('/') ? baseUrl + instancia : baseUrl + '/' + instancia;
            window.location.href = urlInstancia;
            return false;
        }
    </script>
</body>
</html>
