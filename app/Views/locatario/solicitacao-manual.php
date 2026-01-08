<?php
/**
 * View: Solicitação Manual (sem autenticação)
 * Fluxo de 5 etapas para usuários não logados
 */
$title = 'Solicitação de Assistência - ' . ($imobiliaria['nome'] ?? 'Assistência 360°');
$currentPage = 'solicitacao-manual';

// Helper para gerar URLs com ou sem token
$modoToken = isset($modo_token) && $modo_token;
$token = $token ?? null;
$urlHelper = function($path) use ($instancia, $modoToken, $token) {
    if ($modoToken && $token) {
        return url('solicitacao-manual/' . $token . $path);
    }
    return url($instancia . '/solicitacao-manual' . $path);
};

ob_start();

// Definir etapa atual
$etapaAtual = $etapa ?? 1;
$etapaAtual = (int)$etapaAtual;

// Definir steps
$steps = [
    1 => ['nome' => 'Dados', 'icone' => 'fas fa-user'],
    2 => ['nome' => 'Serviço', 'icone' => 'fas fa-cog'],
    3 => ['nome' => 'Descrição', 'icone' => 'fas fa-file-alt'],
    4 => ['nome' => 'Agendamento', 'icone' => 'fas fa-calendar-alt'],
    5 => ['nome' => 'Confirmação', 'icone' => 'fas fa-check']
];

// Função para gerar resumo das etapas anteriores
function gerarResumoEtapasManual($etapaAtual, $dados) {
    $resumo = [];
    
    // Garantir que $dados é um array
    if (!is_array($dados)) {
        $dados = [];
    }
    
    // Etapa 1: Dados
    if ($etapaAtual > 1 && !empty($dados['nome_completo'])) {
        $resumo[] = [
            'titulo' => 'Dados',
            'icone' => 'fas fa-user',
            'conteudo' => htmlspecialchars($dados['nome_completo'])
        ];
    }
    
    // Etapa 2: Serviço
    if ($etapaAtual > 2) {
        // Verificar se há múltiplas categorias selecionadas
        $categoriasSelecionadas = $dados['categorias_selecionadas'] ?? [];
        $temMultiplasCategorias = !empty($categoriasSelecionadas) && is_array($categoriasSelecionadas) && count($categoriasSelecionadas) > 1;
        
        if ($temMultiplasCategorias) {
            // Exibir todas as subcategorias selecionadas
            $subcategoriaModel = new \App\Models\Subcategoria();
            $nomesServicos = [];
            
            foreach ($categoriasSelecionadas as $cat) {
                if (!empty($cat['subcategoria_id'])) {
                    try {
                        $subcategoria = $subcategoriaModel->find($cat['subcategoria_id']);
                        if ($subcategoria && !empty($subcategoria['nome'])) {
                            $nomesServicos[] = htmlspecialchars($subcategoria['nome']);
                        }
                    } catch (\Exception $e) {
                        // Ignorar erro
                    }
                }
            }
            
            if (!empty($nomesServicos)) {
                $resumo[] = [
                    'titulo' => 'Serviços',
                    'icone' => 'fas fa-cog',
                    'conteudo' => implode(', ', $nomesServicos)
                ];
            }
        } elseif (!empty($dados['subcategoria_id'])) {
            // Seleção única
            try {
                $subcategoriaModel = new \App\Models\Subcategoria();
                $subcategoria = $subcategoriaModel->find($dados['subcategoria_id']);
                if ($subcategoria && !empty($subcategoria['nome'])) {
                    $resumo[] = [
                        'titulo' => 'Serviço',
                        'icone' => 'fas fa-cog',
                        'conteudo' => htmlspecialchars($subcategoria['nome'])
                    ];
                }
            } catch (\Exception $e) {
                // Se não conseguir buscar, usar categoria_id como fallback
                if (!empty($dados['categoria_id'])) {
                    try {
                        $categoriaModel = new \App\Models\Categoria();
                        $categoria = $categoriaModel->find($dados['categoria_id']);
                        if ($categoria && !empty($categoria['nome'])) {
                            $resumo[] = [
                                'titulo' => 'Serviço',
                                'icone' => 'fas fa-cog',
                                'conteudo' => htmlspecialchars($categoria['nome'])
                            ];
                        }
                    } catch (\Exception $e2) {
                        // Ignorar erro
                    }
                }
            }
        }
    }
    
    // Etapa 3: Descrição
    if ($etapaAtual > 3 && !empty($dados['descricao_problema'])) {
        $descricao = htmlspecialchars($dados['descricao_problema']);
        if (strlen($descricao) > 100) {
            $descricao = substr($descricao, 0, 100) . '...';
        }
        $resumo[] = [
            'titulo' => 'Descrição',
            'icone' => 'fas fa-file-alt',
            'conteudo' => $descricao
        ];
    }
    
    // Etapa 4: Agendamento
    if ($etapaAtual > 4 && !empty($dados['horarios_preferenciais'])) {
        $horarios = $dados['horarios_preferenciais'];
        if (is_array($horarios) && !empty($horarios)) {
            $primeiroHorario = $horarios[0];
            $horarioFormatado = $primeiroHorario;
            
            // Formatar horário: "2025-12-23 08:00:00-11:00:00" -> "23/12/2025 08:00 - 11:00"
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):\d{2}-(\d{2}):(\d{2}):\d{2}$/', $primeiroHorario, $matches)) {
                // Formato: YYYY-MM-DD HH:MM:SS-HH:MM:SS
                $horarioFormatado = $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5] . ' - ' . $matches[6] . ':' . $matches[7];
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $primeiroHorario, $matches)) {
                // Formato: YYYY-MM-DD HH:MM (sem intervalo)
                $horarioFormatado = $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5];
            }
            
            $totalHorarios = count($horarios);
            $texto = $horarioFormatado;
            if ($totalHorarios > 1) {
                $opcoesAdicionais = $totalHorarios - 1;
                $texto .= ' (+' . $opcoesAdicionais . ' ' . ($opcoesAdicionais > 1 ? 'opções' : 'opção') . ')';
            }
            
            $resumo[] = [
                'titulo' => 'Agendamento',
                'icone' => 'fas fa-calendar-alt',
                'conteudo' => $texto
            ];
        }
    }
    
    return $resumo;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8 text-center">
                <?php 
                // Verificar se a logo existe no sistema de arquivos
                $logoExiste = false;
                $logoUrl = '';
                
                if (!empty($imobiliaria['logo'])) {
                    // Caminho relativo ao diretório raiz do projeto
                    // app/Views/locatario -> app/Views -> app -> raiz
                    $rootPath = dirname(__DIR__, 3);
                    $logoPath = $rootPath . '/Public/uploads/logos/' . $imobiliaria['logo'];
                    $logoExiste = file_exists($logoPath);
                    $logoUrl = url('Public/uploads/logos/' . $imobiliaria['logo']);
                }
                ?>
                
                <?php if ($logoExiste && !empty($logoUrl)): ?>
                    <div class="flex justify-center mb-4">
                        <img src="<?= $logoUrl ?>" 
                             alt="<?= htmlspecialchars($imobiliaria['nome'] ?? 'Imobiliária') ?>" 
                             class="h-16 w-auto max-w-xs object-contain"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="h-16 w-16 bg-blue-600 rounded-lg flex items-center justify-center text-white text-2xl" style="display: none;">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex justify-center mb-4">
                        <div class="h-16 w-16 bg-blue-600 rounded-lg flex items-center justify-center text-white text-2xl">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                <?php endif; ?>
                
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <?= htmlspecialchars($imobiliaria['nome'] ?? 'Assistência 360°') ?>
                </h1>
                <p class="text-gray-600">Solicitação de Assistência</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <?php foreach ($steps as $numero => $step): ?>
                        <?php
                        $status = $numero < $etapaAtual ? 'complete' : ($numero == $etapaAtual ? 'current' : 'upcoming');
                        $circleClasses = [
                            'complete' => 'bg-green-600 border-green-600 text-white',
                            'current' => 'bg-green-600 border-green-600 text-white',
                            'upcoming' => 'bg-transparent border-gray-500 text-gray-400'
                        ][$status];
                        $textClasses = $status === 'upcoming' ? 'text-gray-400' : 'text-green-500';
                        ?>
                        <div class="flex items-center <?= $numero < count($steps) ? 'flex-1' : '' ?>">
                            <div class="flex flex-col items-center">
                                <div class="flex items-center justify-center w-12 h-12 rounded-full border-2 <?= $circleClasses ?>">
                                    <?php if ($status === 'complete'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <span class="font-semibold"><?= $numero ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs mt-2 font-medium <?= $textClasses ?>">
                                    <?= $step['nome'] ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Step Content -->
            <div class="bg-white rounded-lg shadow-md">
                <?php 
                // Exibir resumo das etapas anteriores (exceto na etapa 1 e na última etapa)
                if ($etapaAtual > 1 && $etapaAtual < 5):
                    // Garantir que $dados existe e é um array
                    $dadosResumo = is_array($dados ?? null) ? $dados : [];
                    // Se não tiver dados na variável, tentar pegar da sessão diretamente
                    if (empty($dadosResumo) && isset($_SESSION['solicitacao_manual'])) {
                        $dadosResumo = $_SESSION['solicitacao_manual'];
                    }
                    $resumoEtapas = gerarResumoEtapasManual($etapaAtual, $dadosResumo);
                    if (!empty($resumoEtapas)):
                ?>
                    <!-- Resumo das Etapas Anteriores - Dropdown -->
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200" style="position: relative; z-index: 1;">
                        <button type="button" 
                                id="btn-resumo-etapas"
                                class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-green-500 rounded-lg p-2 -m-2 cursor-pointer"
                                style="cursor: pointer; pointer-events: auto; position: relative; z-index: 2;">
                            <div class="flex items-center">
                                <i class="fas fa-list-ul text-gray-600 mr-2"></i>
                                <h3 class="text-sm font-medium text-gray-700 whitespace-nowrap">Resumo das Etapas Anteriores</h3>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="resumo-chevron"></i>
                        </button>
                        <div id="resumo-conteudo" class="hidden mt-3 pt-3 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-<?= min(count($resumoEtapas), 4) ?> gap-3">
                                <?php foreach ($resumoEtapas as $item): ?>
                                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                                        <div class="flex items-start">
                                            <i class="<?= $item['icone'] ?> text-gray-400 mr-2 mt-0.5 text-sm"></i>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-500 mb-1"><?= $item['titulo'] ?></p>
                                                <p class="text-sm text-gray-900 truncate" title="<?= htmlspecialchars($item['conteudo']) ?>">
                                                    <?= $item['conteudo'] ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endif; 
                ?>
                
                <?php if ($etapaAtual == 1): ?>
                    <!-- ETAPA 1: DADOS PESSOAIS -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-user mr-2 text-green-600"></i>
                            Dados Pessoais
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Por favor, informe seus dados de contato</p>
                    </div>
                    
                    <div class="p-6">
                        <form method="POST" action="<?= $urlHelper('') ?>" class="space-y-6" onsubmit="return validarFormularioEtapa1(event)">
                            <?= \App\Core\View::csrfField() ?>
                            <?php 
                            $tipoAtual = $dados['tipo_imovel'] ?? 'RESIDENCIAL';
                            $subtipoAtual = $dados['subtipo_imovel'] ?? '';
                            ?>
                            
                            <!-- CPF/CNPJ -->
                            <div id="cpf-container">
                                <label for="cpf" class="block text-sm font-medium text-gray-700 mb-2">
                                    CPF/CNPJ do locatário <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="cpf" name="cpf" required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                       placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                       maxlength="18"
                                       value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>">
                            </div>
                            
                            <!-- Nome Completo -->
                            <div>
                                <label for="nome_completo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome Completo do Locatário <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nome_completo" name="nome_completo" required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                       placeholder="Digite seu nome completo"
                                       value="<?= htmlspecialchars($dados['nome_completo'] ?? '') ?>">
                            </div>
                            
                            <!-- WhatsApp -->
                            <div>
                                <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                                    WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="whatsapp" name="whatsapp" required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                       placeholder="(00) 00000-0000"
                                       maxlength="15"
                                       value="<?= htmlspecialchars($dados['whatsapp'] ?? '') ?>">
                                <p class="text-xs text-gray-500 mt-1">Informe um WhatsApp válido para contato</p>
                            </div>
                            
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>
                                    Endereço do Imóvel Alugado
                                </h3>

                                <!-- Finalidade da Locação -->
                                <div class="mb-6">
                                    <label for="finalidade_locacao" class="block text-sm font-medium text-gray-700 mb-2">
                                        Finalidade da Locação <span class="text-red-500">*</span>
                                    </label>
                                    <select id="finalidade_locacao" name="tipo_imovel" required
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        <option value="RESIDENCIAL" <?= $tipoAtual === 'RESIDENCIAL' ? 'selected' : '' ?>>Residencial</option>
                                        <option value="COMERCIAL" <?= $tipoAtual === 'COMERCIAL' ? 'selected' : '' ?>>Comercial</option>
                                    </select>
                                </div>

                                <!-- Tipo de Imóvel (Casa/Apartamento) - apenas para Residencial -->
                                <div id="subtipo-container" class="<?= $tipoAtual === 'RESIDENCIAL' ? '' : 'hidden' ?> mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Tipo de Imóvel <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-6">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="subtipo_imovel" value="CASA"
                                                   class="h-4 w-4 text-green-600 border-gray-300 subtipo-imovel-radio"
                                                   <?= $subtipoAtual === 'CASA' ? 'checked' : '' ?>>
                                            <i class="fas fa-home text-gray-700"></i>
                                            <span class="text-sm text-gray-900">Casa</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="subtipo_imovel" value="APARTAMENTO"
                                                   class="h-4 w-4 text-green-600 border-gray-300 subtipo-imovel-radio"
                                                   <?= $subtipoAtual === 'APARTAMENTO' ? 'checked' : '' ?>>
                                            <i class="fas fa-building text-gray-700"></i>
                                            <span class="text-sm text-gray-900">Apartamento</span>
                                        </label>
                                    </div>
                                    <p class="text-xs text-red-500 mt-1 hidden" id="subtipo-error">Selecione o tipo de imóvel</p>
                                </div>

                                <!-- CEP -->
                                <div class="mb-6">
                                    <label for="cep" class="block text-sm font-medium text-gray-700 mb-2">
                                        CEP <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <input type="text" id="cep" name="cep" required
                                               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               placeholder="00000-000"
                                               maxlength="9"
                                               value="<?= htmlspecialchars($dados['cep'] ?? '') ?>">
                                        <button type="button" id="btn-buscar-cep"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-search mr-2"></i>Buscar
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Informe o CEP para preenchimento automático</p>
                                </div>

                                <!-- Número do Contrato -->
                                <div class="mb-6">
                                    <label for="numero_contrato" class="block text-sm font-medium text-gray-700 mb-2">
                                    Número do seu contrato no sistema da imobiliária(KSI)                                    </label>
                                    <input type="text" id="numero_contrato" name="numero_contrato"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                           placeholder="Informe o Número do seu contrato no sistema da imobiliária(KSI)"
                                           value="<?= htmlspecialchars($dados['numero_contrato'] ?? '') ?>">
                                </div>

                                <!-- Endereço -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div class="md:col-span-2">
                                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-2">
                                            Endereço <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="endereco" name="endereco" required
                                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               placeholder="Rua, Avenida..."
                                               value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>">
                                    </div>
                                    <div>
                                        <label for="numero" class="block text-sm font-medium text-gray-700 mb-2">
                                            Número <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="numero" name="numero" required
                                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               placeholder="Nº"
                                               value="<?= htmlspecialchars($dados['numero'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Complemento -->
                                <div class="mb-6">
                                    <label for="complemento" class="block text-sm font-medium text-gray-700 mb-2">
                                        Complemento
                                    </label>
                                    <input type="text" id="complemento" name="complemento"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                           placeholder="Apto, Bloco, Sala..."
                                           value="<?= htmlspecialchars($dados['complemento'] ?? '') ?>">
                                </div>

                                <!-- Bairro, Cidade, Estado -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-2">
                                            Bairro <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="bairro" name="bairro" required
                                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               value="<?= htmlspecialchars($dados['bairro'] ?? '') ?>">
                                    </div>
                                    <div>
                                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-2">
                                            Cidade <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="cidade" name="cidade" required
                                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               value="<?= htmlspecialchars($dados['cidade'] ?? '') ?>">
                                    </div>
                                    <div>
                                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                            Estado <span class="text-red-500">*</span>
                                        </label>
                                        <select id="estado" name="estado" required
                                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                            <option value="">Selecione</option>
                                            <?php
                                            $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                            foreach ($estados as $uf): ?>
                                                <option value="<?= $uf ?>" <?= ($dados['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation -->
                            <div class="flex justify-between pt-6">
                                <a href="<?= url($instancia) ?>" 
                                   class="btn-cancelar-solicitacao px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </a>
                                <button type="submit"
                                        class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    Continuar <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($etapaAtual == 2): ?>
                    <!-- ETAPA 2: SERVIÇO -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-cog mr-2 text-green-600"></i>
                            Serviço Necessário
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Escolha a categoria do serviço que melhor representa sua necessidade</p>
                    </div>
                    
                    <div class="p-6">
                        <form method="POST" action="<?= $urlHelper('/etapa/2') ?>" class="space-y-6">
                            <?= \App\Core\View::csrfField() ?>
                            
                            <!-- Categorias -->
                            <div>
                                <?php
                                $tipoImovel = $dados['tipo_imovel'] ?? 'RESIDENCIAL';
                                $tipoTexto = $tipoImovel === 'RESIDENCIAL' ? 'Residencial' : 'Comercial';
                                ?>
                                <h3 class="text-sm font-medium text-gray-700 mb-3">
                                    Categoria do Serviço
                                    <?php if (!empty($tipoImovel) && $etapaAtual >= 2): ?>
                                        <span class="text-xs text-gray-500 font-normal">
                                            (Mostrando categorias para <?= strtolower($tipoTexto) ?>)
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <div class="space-y-3">
                                    <?php if (!empty($categorias)): ?>
                                        <?php foreach ($categorias as $categoriaPai): ?>
                                            <?php 
                                            // Verificar se a categoria pai tem filhas
                                            $temFilhas = !empty($categoriaPai['filhas']) && count($categoriaPai['filhas']) > 0;
                                            ?>
                                            
                                            <?php if ($temFilhas): ?>
                                                <?php 
                                                // Verificar se é "Manutenção e Prevenção" ou "Manutenção e Instalação"
                                                $isManutencaoPrevencao = stripos($categoriaPai['nome'], 'Manutenção') !== false && 
                                                                         (stripos($categoriaPai['nome'], 'Prevenção') !== false || 
                                                                          stripos($categoriaPai['nome'], 'Instalação') !== false);
                                                ?>
                                                
                                                <?php if ($isManutencaoPrevencao && count($categoriaPai['filhas']) <= 3): ?>
                                                    <!-- Categoria Pai "Manutenção e Prevenção" - Dropdown com Seleção de Subcategorias -->
                                                    <div class="categoria-pai-container" data-categoria-pai-id="<?= $categoriaPai['id'] ?>" data-tipo-selecao-multipla-subcategorias="true">
                                                        <!-- Card da categoria pai -->
                                                        <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                            <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-xl text-gray-600 flex-shrink-0"></i>
                                                            <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaPai['nome']) ?></span>
                                                            <?php if (!empty($categoriaPai['condicoes_gerais'])): ?>
                                                                <button type="button" 
                                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                        data-nome="<?= htmlspecialchars($categoriaPai['nome'], ENT_QUOTES) ?>"
                                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaPai['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                        title="Ver condições gerais">
                                                                    <i class="fas fa-info-circle text-sm"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" 
                                                                    class="btn-toggle-categoria-pai text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                    data-categoria-pai="<?= $categoriaPai['id'] ?>"
                                                                    onclick="toggleCategoriaPaiManual(<?= $categoriaPai['id'] ?>)"
                                                                    title="Expandir/Recolher">
                                                                <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-pai-<?= $categoriaPai['id'] ?>"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Categorias Filhas (ocultas por padrão) -->
                                                        <div class="categoria-filhas-container hidden mt-0 border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-gray-50" id="filhas-<?= $categoriaPai['id'] ?>" style="display: none;">
                                                            <div class="px-4 pt-3 pb-4 space-y-3">
                                                                <p class="text-xs text-gray-500 mb-3">Selecione até 3 subcategorias: <span id="contador-subcategorias-<?= $categoriaPai['id'] ?>">0</span>/3</p>
                                                                <?php 
                                                                // Buscar subcategorias já selecionadas
                                                                $subcategoriasSelecionadas = [];
                                                                if (!empty($dados['categorias_selecionadas']) && is_array($dados['categorias_selecionadas'])) {
                                                                    foreach ($dados['categorias_selecionadas'] as $cat) {
                                                                        if (!empty($cat['subcategoria_id'])) {
                                                                            $subcategoriasSelecionadas[] = (int)$cat['subcategoria_id'];
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <?php foreach ($categoriaPai['filhas'] as $categoriaFilha): ?>
                                                                <!-- Cada categoria filha como card expansível -->
                                                                <div class="categoria-container" data-categoria-id="<?= $categoriaFilha['id'] ?>">
                                                                    <!-- Card da Categoria Filha -->
                                                                    <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 bg-white cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                                        <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-lg text-gray-600 flex-shrink-0"></i>
                                                                        <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaFilha['nome']) ?></span>
                                                                        <?php if (!empty($categoriaFilha['condicoes_gerais'])): ?>
                                                                            <button type="button" 
                                                                                    class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                                    data-nome="<?= htmlspecialchars($categoriaFilha['nome'], ENT_QUOTES) ?>"
                                                                                    data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaFilha['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                                    title="Ver condições gerais">
                                                                                <i class="fas fa-info-circle text-sm"></i>
                                                                            </button>
                                                                        <?php endif; ?>
                                                                        <button type="button" 
                                                                                class="btn-toggle-categoria text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                                data-categoria="<?= $categoriaFilha['id'] ?>"
                                                                                onclick="toggleCategoriaManual(<?= $categoriaFilha['id'] ?>)"
                                                                                title="Expandir/Recolher">
                                                                            <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-<?= $categoriaFilha['id'] ?>"></i>
                                                                        </button>
                                                                    </div>
                                                                    
                                                                    <!-- Subcategorias (ocultas por padrão) -->
                                                                    <div class="categoria-descricao-container hidden border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-white" id="descricao-cat-<?= $categoriaFilha['id'] ?>">
                                                                        <!-- Subcategorias com checkboxes -->
                                                                        <div class="px-4 pb-4 bg-gray-50">
                                                                            <div class="space-y-3 pt-3">
                                                                                <?php if (!empty($categoriaFilha['subcategorias'])): ?>
                                                                                    <?php foreach ($categoriaFilha['subcategorias'] as $subcategoria): ?>
                                                                                        <div class="subcategoria-item-wrapper" 
                                                                                               data-categoria-filha-id="<?= $categoriaFilha['id'] ?>"
                                                                                               data-subcategoria-id="<?= $subcategoria['id'] ?>">
                                                                                            <div class="border border-gray-200 rounded-lg p-3 bg-white subcategoria-card-manutencao cursor-pointer hover:border-blue-300 transition-colors <?= in_array((int)$subcategoria['id'], $subcategoriasSelecionadas) ? 'border-blue-500 bg-blue-50' : '' ?> <?= (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) ? 'border-red-300 bg-red-50' : '' ?>">
                                                                                                <div class="flex items-start justify-between">
                                                                                                    <div class="flex-1">
                                                                                                        <div class="flex items-center gap-1 mb-1">
                                                                                                            <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-sm text-gray-600"></i>
                                                                                                            <h5 class="text-sm font-medium text-gray-900">
                                                                                                                <?= htmlspecialchars($subcategoria['nome']) ?>
                                                                                                            </h5>
                                                                                                            <?php if (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)): ?>
                                                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200 ml-2">
                                                                                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                                                                    Emergencial
                                                                                                                </span>
                                                                                                            <?php endif; ?>
                                                                                                        </div>
                                                                                                        <?php if (!empty($subcategoria['descricao'])): ?>
                                                                                                            <p class="text-xs text-gray-600 mt-1">
                                                                                                                <?= htmlspecialchars($subcategoria['descricao']) ?>
                                                                                                            </p>
                                                                                                        <?php endif; ?>
                                                                                                    </div>
                                                                                                    <div class="ml-3 flex items-center gap-2 flex-shrink-0">
                                                                                                        <?php if (!empty($subcategoria['condicoes_gerais'])): ?>
                                                                                                            <button type="button" 
                                                                                                                    class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                                                                    data-nome="<?= htmlspecialchars($subcategoria['nome'], ENT_QUOTES) ?>"
                                                                                                                    data-condicoes="<?= base64_encode(mb_convert_encoding($subcategoria['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                                                                    title="Ver condições gerais">
                                                                                                                <i class="fas fa-info-circle text-xs"></i>
                                                                                                            </button>
                                                                                                        <?php endif; ?>
                                                                                                        <label class="cursor-pointer">
                                                                                                            <input type="checkbox" 
                                                                                                                   name="subcategorias_selecionadas[]" 
                                                                                                                   value="<?= $subcategoria['id'] ?>"
                                                                                                                   class="sr-only subcategoria-checkbox-manutencao"
                                                                                                                   data-categoria-filha="<?= $categoriaFilha['id'] ?>"
                                                                                                                   data-subcategoria="<?= $subcategoria['id'] ?>"
                                                                                                                   data-categoria-pai="<?= $categoriaPai['id'] ?>"
                                                                                                                   <?= in_array((int)$subcategoria['id'], $subcategoriasSelecionadas) ? 'checked' : '' ?>>
                                                                                                        <div class="w-5 h-5 border-2 <?= in_array((int)$subcategoria['id'], $subcategoriasSelecionadas) ? 'border-blue-500 bg-blue-500' : 'border-gray-300' ?> rounded flex items-center justify-center subcategoria-check-manutencao">
                                                                                                            <?php if (in_array((int)$subcategoria['id'], $subcategoriasSelecionadas)): ?>
                                                                                                                <i class="fas fa-check text-white text-xs"></i>
                                                                                                            <?php endif; ?>
                                                                                                        </div>
                                                                                                        </label>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                <?php else: ?>
                                                                                    <p class="text-sm text-gray-500">Nenhum serviço disponível.</p>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Categoria Pai COM Filhas (Separadora Expansível) - Comportamento padrão -->
                                                    <div class="categoria-pai-container" data-categoria-pai-id="<?= $categoriaPai['id'] ?>">
                                                        <!-- Card da categoria pai -->
                                                        <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                            <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-xl text-gray-600 flex-shrink-0"></i>
                                                            <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaPai['nome']) ?></span>
                                                            <?php if (!empty($categoriaPai['condicoes_gerais'])): ?>
                                                                <button type="button" 
                                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                        data-nome="<?= htmlspecialchars($categoriaPai['nome'], ENT_QUOTES) ?>"
                                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaPai['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                        title="Ver condições gerais">
                                                                    <i class="fas fa-info-circle text-sm"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" 
                                                                    class="btn-toggle-categoria-pai text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                    data-categoria-pai="<?= $categoriaPai['id'] ?>"
                                                                    onclick="toggleCategoriaPaiManual(<?= $categoriaPai['id'] ?>)"
                                                                    title="Expandir/Recolher">
                                                                <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-pai-<?= $categoriaPai['id'] ?>"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Categorias Filhas (ocultas por padrão) -->
                                                        <div class="categoria-filhas-container hidden mt-0 border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-gray-50" id="filhas-<?= $categoriaPai['id'] ?>" style="display: none;">
                                                            <div class="px-4 pt-3 pb-4 space-y-3">
                                                                <?php foreach ($categoriaPai['filhas'] as $categoriaFilha): ?>
                                                                <!-- Cada categoria filha como card expansível -->
                                                                <div class="categoria-container" data-categoria-id="<?= $categoriaFilha['id'] ?>">
                                                                    <!-- Card da Categoria Filha -->
                                                                    <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 bg-white cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                                        <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-lg text-gray-600 flex-shrink-0"></i>
                                                                        <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaFilha['nome']) ?></span>
                                                                        <?php if (!empty($categoriaFilha['condicoes_gerais'])): ?>
                                                                            <button type="button" 
                                                                                    class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                                    data-nome="<?= htmlspecialchars($categoriaFilha['nome'], ENT_QUOTES) ?>"
                                                                                    data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaFilha['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                                    title="Ver condições gerais">
                                                                                <i class="fas fa-info-circle text-sm"></i>
                                                                            </button>
                                                                        <?php endif; ?>
                                                                        <button type="button" 
                                                                                class="btn-toggle-categoria text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                                data-categoria="<?= $categoriaFilha['id'] ?>"
                                                                                onclick="toggleCategoriaManual(<?= $categoriaFilha['id'] ?>)"
                                                                                title="Expandir/Recolher">
                                                                            <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-<?= $categoriaFilha['id'] ?>"></i>
                                                                        </button>
                                                                    </div>
                                                                    
                                                                    <!-- Subcategorias (ocultas por padrão) -->
                                                                    <div class="categoria-descricao-container hidden border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-white" id="descricao-cat-<?= $categoriaFilha['id'] ?>">
                                                                        <!-- Subcategorias -->
                                                                        <div class="px-4 pb-4 bg-gray-50">
                                                                            <div class="space-y-3 pt-3">
                                                                                <?php if (!empty($categoriaFilha['subcategorias'])): ?>
                                                                                    <?php foreach ($categoriaFilha['subcategorias'] as $subcategoria): ?>
                                                                                        <label class="relative block cursor-pointer">
                                                                                            <input type="radio" name="categoria_id" value="<?= $categoriaFilha['id'] ?>" 
                                                                                                   class="sr-only categoria-radio" data-categoria="<?= $categoriaFilha['id'] ?>"
                                                                                                   <?= ($dados['categoria_id'] ?? '') == $categoriaFilha['id'] ? 'checked' : '' ?>>
                                                                                            <input type="radio" name="subcategoria_id" value="<?= $subcategoria['id'] ?>" 
                                                                                                   class="sr-only subcategoria-radio"
                                                                                                   <?= ($dados['subcategoria_id'] ?? '') == $subcategoria['id'] ? 'checked' : '' ?>>
                                                                                            <div class="border border-gray-200 rounded-lg p-3 bg-white hover:border-blue-300 transition-colors subcategoria-card cursor-pointer <?= (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) ? 'border-red-300 bg-red-50' : '' ?>">
                                                                                                <div class="flex items-start justify-between">
                                                                                                    <div class="flex-1">
                                                                                                        <div class="flex items-center gap-2 mb-1">
                                                                                                            <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-sm text-gray-600"></i>
                                                                                                            <h5 class="text-sm font-medium text-gray-900">
                                                                                                                <?= htmlspecialchars($subcategoria['nome']) ?>
                                                                                                            </h5>
                                                                                                            <?php if (!empty($subcategoria['condicoes_gerais'])): ?>
                                                                                                                <button type="button" 
                                                                                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                                                                        data-nome="<?= htmlspecialchars($subcategoria['nome'], ENT_QUOTES) ?>"
                                                                                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($subcategoria['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                                                                        title="Ver condições gerais"
                                                                                                                        onclick="event.stopPropagation();">
                                                                                                                    <i class="fas fa-info-circle text-xs"></i>
                                                                                                                </button>
                                                                                                            <?php endif; ?>
                                                                                                            <?php if (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)): ?>
                                                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                                                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                                                                    Emergencial
                                                                                                                </span>
                                                                                                            <?php endif; ?>
                                                                                                        </div>
                                                                                                        <?php if (!empty($subcategoria['descricao'])): ?>
                                                                                                            <p class="text-xs text-gray-600 mt-1">
                                                                                                                <?= htmlspecialchars($subcategoria['descricao']) ?>
                                                                                                            </p>
                                                                                                        <?php endif; ?>
                                                                                                    </div>
                                                                                                    <div class="ml-3 flex-shrink-0">
                                                                                                        <div class="w-5 h-5 border-2 border-gray-300 rounded-full subcategoria-check"></div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </label>
                                                                                    <?php endforeach; ?>
                                                                                <?php else: ?>
                                                                                    <p class="text-sm text-gray-500">Nenhum serviço disponível.</p>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Categoria SEM Filhas (Categoria Normal) -->
                                                <div class="categoria-container" data-categoria-id="<?= $categoriaPai['id'] ?>">
                                                    <!-- Card da Categoria -->
                                                    <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4">
                                                        <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-xl text-gray-600 flex-shrink-0"></i>
                                                        <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaPai['nome']) ?></span>
                                                        <?php if (!empty($categoriaPai['condicoes_gerais'])): ?>
                                                            <button type="button" 
                                                                    class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                    data-nome="<?= htmlspecialchars($categoriaPai['nome'], ENT_QUOTES) ?>"
                                                                    data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaPai['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                    title="Ver condições gerais">
                                                                <i class="fas fa-info-circle text-sm"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" 
                                                                class="btn-toggle-categoria text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                data-categoria="<?= $categoriaPai['id'] ?>"
                                                                onclick="toggleCategoriaManual(<?= $categoriaPai['id'] ?>)"
                                                                title="Expandir/Recolher">
                                                            <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-<?= $categoriaPai['id'] ?>"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Subcategorias (ocultas por padrão) -->
                                                    <div class="categoria-descricao-container hidden border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-gray-50" id="descricao-cat-<?= $categoriaPai['id'] ?>">
                                                        <!-- Subcategorias -->
                                                        <div class="px-4 pb-4">
                                                            <div class="space-y-3 pt-3">
                                                                <?php if (!empty($categoriaPai['subcategorias'])): ?>
                                                                    <?php foreach ($categoriaPai['subcategorias'] as $subcategoria): ?>
                                                                        <label class="relative block cursor-pointer">
                                                                            <input type="radio" name="categoria_id" value="<?= $categoriaPai['id'] ?>" 
                                                                                   class="sr-only categoria-radio" data-categoria="<?= $categoriaPai['id'] ?>"
                                                                                   <?= ($dados['categoria_id'] ?? '') == $categoriaPai['id'] ? 'checked' : '' ?>>
                                                                            <input type="radio" name="subcategoria_id" value="<?= $subcategoria['id'] ?>" 
                                                                                   class="sr-only subcategoria-radio"
                                                                                   <?= ($dados['subcategoria_id'] ?? '') == $subcategoria['id'] ? 'checked' : '' ?>>
                                                                            <div class="border border-gray-200 rounded-lg p-3 bg-white hover:border-blue-300 transition-colors subcategoria-card cursor-pointer <?= (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) ? 'border-red-300 bg-red-50' : '' ?>">
                                                                                <div class="flex items-start justify-between">
                                                                                    <div class="flex-1">
                                                                                        <div class="flex items-center gap-2 mb-1">
                                                                                            <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-sm text-gray-600"></i>
                                                                                            <h5 class="text-sm font-medium text-gray-900">
                                                                                                <?= htmlspecialchars($subcategoria['nome']) ?>
                                                                                            </h5>
                                                                                            <?php if (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)): ?>
                                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                                                    Emergencial
                                                                                                </span>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                        <?php if (!empty($subcategoria['descricao'])): ?>
                                                                                            <p class="text-xs text-gray-600 mt-1">
                                                                                                <?= htmlspecialchars($subcategoria['descricao']) ?>
                                                                                            </p>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div class="ml-3 flex-shrink-0">
                                                                                        <div class="w-5 h-5 border-2 border-gray-300 rounded-full subcategoria-check"></div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </label>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <p class="text-sm text-gray-500">Nenhum serviço disponível.</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-center py-8 text-gray-500">Nenhuma categoria disponível</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Navigation -->
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 sm:justify-between pt-6">
                                <div class="flex flex-col sm:flex-row gap-3 flex-1">
                                <a href="<?= $urlHelper('') ?>" 
                                       class="btn-voltar-etapa w-full sm:w-auto px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-center sm:text-left"
                                   data-etapa="1">
                                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                                </a>
                                    <a href="/condicoes-gerais?return=<?= urlencode($urlHelper('/etapa/2')) ?>" target="_blank" 
                                       class="w-full sm:w-auto px-6 py-3 border border-blue-300 bg-white text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-colors text-center">
                                        <i class="fas fa-info-circle mr-2"></i>Condições Gerais
                                    </a>
                                </div>
                                <button type="submit"
                                        class="w-full sm:w-auto px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    Continuar <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($etapaAtual == 3): ?>
                    <!-- ETAPA 3: DESCRIÇÃO -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-file-alt mr-2 text-green-600"></i>
                            Descrição do Problema
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Forneça detalhes sobre o serviço necessário. Essas informações nos ajudam a direcionar o técnico certo.</p>
                    </div>
                    
                    <div class="p-6">
                        <form method="POST" action="<?= $urlHelper('/etapa/3') ?>" enctype="multipart/form-data" class="space-y-6" onsubmit="combinarFotosAntesEnvio(event)">
                            <?= \App\Core\View::csrfField() ?>
                            
                            <div class="space-y-5">
                                <div>
                                    <label for="local_manutencao" class="block text-sm font-medium text-gray-700 mb-2">
                                        Local no imóvel onde será feita a manutenção
                                    </label>
                                    <input type="text" id="local_manutencao" name="local_manutencao"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                           placeholder="Ex.: Fechadura do portão da rua, Banheiro social, Sala principal"
                                           value="<?= htmlspecialchars($dados['local_manutencao'] ?? '') ?>">
                                </div>

                                <div>
                                    <label for="descricao_problema" class="block text-sm font-medium text-gray-700 mb-2">
                                        Descrição do Problema <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="descricao_problema" name="descricao_problema" rows="6" required
                                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                              placeholder="Descreva detalhadamente o problema que precisa ser resolvido..."><?= htmlspecialchars($dados['descricao_problema'] ?? '') ?></textarea>
                                    <p class="text-xs text-gray-500 mt-2">Quanto mais detalhes você informar, mais rápido encontraremos a solução ideal.</p>
                                </div>

                                <!-- Upload de Fotos (Opcional) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fotos (Opcional)
                                    </label>
                                    <p class="text-sm text-gray-500 mb-3">Adicione até 5 fotos (máx. 5MB cada)</p>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-green-400 transition-colors cursor-pointer" 
                                         onclick="abrirModalFoto()">
                                        <i class="fas fa-camera text-4xl text-gray-400 mb-3"></i>
                                        <p class="text-sm text-gray-600 font-medium">Clique para adicionar uma foto</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG até 10MB</p>
                                    </div>
                                    <input type="file" id="fotos" name="fotos[]" multiple accept="image/*" class="hidden" onchange="previewPhotos(this)">
                                    <input type="file" id="fotos-camera" name="fotos[]" multiple accept="image/*" capture="environment" class="hidden">
                                    <div id="fotos-preview-container" class="mt-4 relative">
                                        <div id="fotos-preview" class="grid grid-cols-2 md:grid-cols-5 gap-4 hidden">
                                            <!-- Fotos serão inseridas aqui via JavaScript -->
                                        </div>
                                        <!-- Loading overlay -->
                                        <div id="fotos-loading" class="hidden absolute inset-0 bg-white bg-opacity-95 z-50 flex items-center justify-center rounded-lg" style="min-height: 200px;">
                                            <div class="text-center">
                                                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-4 border-green-600 mb-3"></div>
                                                <p class="text-sm text-gray-600 font-medium">Processando fotos...</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal para escolher entre câmera ou arquivos -->
                                    <div id="modal-foto" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                                        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Adicionar Foto</h3>
                                            <div class="space-y-3">
                                                <button type="button" onclick="escolherCamera()" 
                                                        class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                    <i class="fas fa-camera text-xl"></i>
                                                    <span>Abrir Câmera</span>
                                                </button>
                                                <button type="button" onclick="escolherArquivo()" 
                                                        class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                    <i class="fas fa-folder-open text-xl"></i>
                                                    <span>Carregar Arquivos do Dispositivo</span>
                                                </button>
                                                <button type="button" onclick="fecharModalFoto()" 
                                                        class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                                    Cancelar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Navigation -->
                            <div class="flex justify-between pt-6">
                                <a href="<?= $urlHelper('/etapa/2') ?>" 
                                   class="btn-voltar-etapa px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                                   data-etapa="2">
                                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                                </a>
                                <button type="submit"
                                        class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    Continuar <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($etapaAtual == 4): ?>
                    <!-- ETAPA 4: AGENDAMENTO OU ATENDIMENTO EMERGENCIAL -->
                    <?php
                    // Verificar se é emergencial E se o CPF está validado pelo bolsão
                    $isEmergencialEtapa4 = false;
                    $subcategoriaEmergencialEtapa4 = null;
                    $categoriaEmergencialEtapa4 = null;
                    $descricaoCategoriaEtapa4 = '';
                    
                    // Verificar validação do bolsão - só mostra tela emergencial se CPF validado
                    $cpfValidadoBolsao = !empty($dados['validacao_bolsao']) && $dados['validacao_bolsao'] == 1;
                    
                    if (!empty($dados['subcategoria_id'])) {
                        $subcategoriaModel = new \App\Models\Subcategoria();
                        $subcategoriaEmergencialEtapa4 = $subcategoriaModel->find($dados['subcategoria_id']);
                        
                        // Só considera emergencial se a subcategoria for emergencial E o CPF estiver validado no bolsão
                        if ($subcategoriaEmergencialEtapa4 && !empty($subcategoriaEmergencialEtapa4['is_emergencial']) && ($subcategoriaEmergencialEtapa4['is_emergencial'] == 1 || $subcategoriaEmergencialEtapa4['is_emergencial'] === true) && $cpfValidadoBolsao) {
                            $isEmergencialEtapa4 = true;
                            
                            // Buscar categoria e descrição
                            if (!empty($subcategoriaEmergencialEtapa4['categoria_id'])) {
                                $categoriaModel = new \App\Models\Categoria();
                                $categoriaEmergencialEtapa4 = $categoriaModel->find($subcategoriaEmergencialEtapa4['categoria_id']);
                                if ($categoriaEmergencialEtapa4) {
                                    $descricaoCategoriaEtapa4 = $categoriaEmergencialEtapa4['nome'] ?? '';
                                }
                            }
                        }
                    }
                    
                    // Verificar tipo de atendimento emergencial escolhido
                    $tipoAtendimentoEmergencialEtapa4 = $dados['tipo_atendimento_emergencial'] ?? '';
                    
                    // Se for emergencial e já escolheu 120_minutos, pular para confirmação
                    if ($isEmergencialEtapa4 && $tipoAtendimentoEmergencialEtapa4 === '120_minutos') {
                        header('Location: ' . $urlHelper('/etapa/5'));
                        exit;
                    }
                    
                    // Calcular data mínima para agendamento baseado no prazo_minimo
                    $dataMinimaAgendamento = null;
                    $subcategoriaParaPrazo = null;
                    if (!empty($dados['subcategoria_id'])) {
                        $subcategoriaModel = new \App\Models\Subcategoria();
                        $subcategoriaParaPrazo = $subcategoriaModel->find($dados['subcategoria_id']);
                        if ($subcategoriaParaPrazo && empty($subcategoriaParaPrazo['is_emergencial'])) {
                            $dataMinimaAgendamento = $subcategoriaModel->calcularDataLimiteAgendamento($dados['subcategoria_id']);
                        }
                    }
                    ?>
                    
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <?php if ($isEmergencialEtapa4): ?>
                                <i class="fas fa-calendar-alt mr-2 text-green-600"></i>
                                <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                                Atendimento Emergencial
                            <?php else: ?>
                                <i class="fas fa-calendar-alt mr-2 text-green-600"></i>
                                Agendamento
                            <?php endif; ?>
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php if ($isEmergencialEtapa4): ?>
                                Escolha como deseja prosseguir com o atendimento emergencial
                            <?php else: ?>
                                Escolha até três horários de preferência para o atendimento
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="p-6">
                        <form method="POST" action="<?= $urlHelper('/etapa/4') ?>" class="space-y-6">
                            <?= \App\Core\View::csrfField() ?>
                            
                            <?php if ($isEmergencialEtapa4): ?>
                                <!-- Interface de Atendimento Emergencial -->
                                
                                <!-- Descrição da Categoria (SEMPRE VISÍVEL) -->
                                <?php if ($subcategoriaEmergencialEtapa4 || $categoriaEmergencialEtapa4): ?>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                        <div class="flex items-start">
                                            <i class="fas fa-info-circle text-blue-600 mr-3 mt-0.5"></i>
                                            <div class="flex-1">
                                                <?php if ($categoriaEmergencialEtapa4 && !empty($descricaoCategoriaEtapa4)): ?>
                                                    <h4 class="text-sm font-medium text-blue-900 mb-1">Categoria</h4>
                                                    <p class="text-sm text-blue-800 mb-2"><?= htmlspecialchars($descricaoCategoriaEtapa4) ?></p>
                                                <?php endif; ?>
                                                <?php if ($subcategoriaEmergencialEtapa4 && !empty($subcategoriaEmergencialEtapa4['nome'])): ?>
                                                    <h4 class="text-sm font-medium text-blue-900 mb-1"><?= htmlspecialchars($subcategoriaEmergencialEtapa4['nome']) ?></h4>
                                                <?php endif; ?>
                                                <?php if ($subcategoriaEmergencialEtapa4 && !empty($subcategoriaEmergencialEtapa4['descricao'])): ?>
                                                    <p class="text-xs text-blue-700 mt-1"><?= htmlspecialchars($subcategoriaEmergencialEtapa4['descricao']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Avisos -->
                                <div class="space-y-4 mb-6">
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Condomínio</p>
                                                <p class="text-sm">Se o serviço for realizado em apartamento ou condomínio, é obrigatório comunicar previamente a administração ou portaria sobre a visita técnica agendada.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Responsável no Local</p>
                                                <p class="text-sm">É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Opções de atendimento -->
                                <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-4 mb-6">
                                    <p class="font-semibold mb-2">Escolha como deseja prosseguir:</p>
                                </div>
                                
                                <!-- Opção 1: 120 minutos -->
                                <label class="relative block cursor-pointer mb-4">
                                    <input type="radio" name="tipo_atendimento_emergencial" value="120_minutos" 
                                           class="sr-only tipo-atendimento-radio" id="opcao_120_minutos"
                                           <?= ($tipoAtendimentoEmergencialEtapa4 === '120_minutos' || empty($tipoAtendimentoEmergencialEtapa4)) ? 'checked' : '' ?>>
                                    <div class="border-2 <?= ($tipoAtendimentoEmergencialEtapa4 === '120_minutos' || empty($tipoAtendimentoEmergencialEtapa4)) ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white' ?> rounded-lg p-4 hover:border-green-300 hover:bg-green-50 transition-colors tipo-atendimento-card">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="w-5 h-5 border-2 <?= ($tipoAtendimentoEmergencialEtapa4 === '120_minutos' || empty($tipoAtendimentoEmergencialEtapa4)) ? 'border-green-600 bg-green-600 flex items-center justify-center rounded-full' : 'border-gray-300 rounded-full' ?> tipo-atendimento-check">
                                                    <?php if (($tipoAtendimentoEmergencialEtapa4 === '120_minutos' || empty($tipoAtendimentoEmergencialEtapa4))): ?>
                                                        <i class="fas fa-check text-white text-xs"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                                    Solicitar Atendimento em 120 minutos
                                                </h4>
                                                <p class="text-xs text-gray-600">
                                                    Sua solicitação será processada imediatamente. O atendimento será agendado automaticamente e você receberá retorno em até 120 minutos.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                
                                <!-- Opção 2: Agendar -->
                                <label class="relative block cursor-pointer mb-6">
                                    <input type="radio" name="tipo_atendimento_emergencial" value="agendar" 
                                           class="sr-only tipo-atendimento-radio" id="opcao_agendar"
                                           <?= $tipoAtendimentoEmergencialEtapa4 === 'agendar' ? 'checked' : '' ?>>
                                    <div class="border-2 <?= $tipoAtendimentoEmergencialEtapa4 === 'agendar' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white' ?> rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50 transition-colors tipo-atendimento-card">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="w-5 h-5 border-2 <?= $tipoAtendimentoEmergencialEtapa4 === 'agendar' ? 'border-blue-600 bg-blue-600 flex items-center justify-center rounded-full' : 'border-gray-300 rounded-full' ?> tipo-atendimento-check">
                                                    <?php if ($tipoAtendimentoEmergencialEtapa4 === 'agendar'): ?>
                                                        <i class="fas fa-check text-white text-xs"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                                                    Agendar
                                                </h4>
                                                <p class="text-xs text-gray-600">
                                                    Se preferir, você pode agendar um horário específico para o atendimento.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                
                                <!-- Seção de Agendamento (aparece quando selecionar "Agendar") -->
                                <div id="secao-agendamento-emergencial" class="<?= $tipoAtendimentoEmergencialEtapa4 === 'agendar' ? '' : 'hidden' ?> space-y-4 pt-4 border-t border-gray-200">
                                    <!-- Instruções -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <p class="font-semibold mb-2">Selecione até 3 datas e horários preferenciais</p>
                                        <p class="text-sm mb-1">Após sua escolha, o prestador verificará a disponibilidade. Caso algum dos horários não esteja livre, poderão ser sugeridas novas opções.</p>
                                        <p class="text-sm">Você receberá uma notificação confirmando a data e o horário final definidos (via WhatsApp e aplicativo).</p>
                                    </div>
                                    
                                    <!-- Horários Preferenciais -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Horários Preferenciais <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-sm text-gray-500 mb-3">Selecione até 3 opções de data e horário</p>
                                        
                                        <!-- Data -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Selecione uma Data
                                            </label>
                                            <!-- Botão para abrir modal -->
                                            <button type="button" id="btn-selecionar-data-emergencial" 
                                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm text-left font-medium text-gray-700 bg-white hover:border-green-500 hover:bg-green-50 transition-colors flex items-center justify-between">
                                                <span class="flex items-center">
                                                    <i class="fas fa-calendar-alt text-gray-400 mr-3"></i>
                                                    <span id="texto-botao-data-emergencial">Selecione uma Data</span>
                                                </span>
                                                <i class="fas fa-chevron-down text-gray-400"></i>
                                            </button>
                                            <!-- Input hidden para armazenar o valor -->
                                            <input type="hidden" id="data_selecionada_emergencial" name="data_selecionada">
                                            <!-- Modal para selecionar data -->
                                            <div id="modal-data-emergencial" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                                                <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                                                    <div class="flex justify-between items-center mb-4">
                                                        <h3 class="text-lg font-semibold text-gray-900">Selecione uma Data</h3>
                                                        <button type="button" id="fechar-modal-data-emergencial" class="text-gray-400 hover:text-gray-600">
                                                            <i class="fas fa-times text-xl"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mb-4" id="calendario-container-data-emergencial">
                                                        <!-- Calendário será gerado aqui via JavaScript -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <i class="fas fa-info-circle mr-1.5"></i>
                                                <span>Atendimentos disponíveis apenas em dias úteis (segunda a sexta-feira)</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Horário -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Horário
                                            </label>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                                <label class="relative">
                                                    <input type="radio" name="horario_temp_emergencial" value="08:00-11:00" class="sr-only horario-radio-emergencial">
                                                    <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card-emergencial">
                                                        <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                                                    </div>
                                                </label>
                                                <label class="relative">
                                                    <input type="radio" name="horario_temp_emergencial" value="11:00-14:00" class="sr-only horario-radio-emergencial">
                                                    <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card-emergencial">
                                                        <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                                                    </div>
                                                </label>
                                                <label class="relative">
                                                    <input type="radio" name="horario_temp_emergencial" value="14:00-17:00" class="sr-only horario-radio-emergencial">
                                                    <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card-emergencial">
                                                        <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                                                    </div>
                                                </label>
                                                <label class="relative">
                                                    <input type="radio" name="horario_temp_emergencial" value="17:00-20:00" class="sr-only horario-radio-emergencial">
                                                    <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card-emergencial">
                                                        <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Horários Selecionados -->
                                        <div id="horarios-selecionados-emergencial" class="hidden">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">
                                                Horários Selecionados (<span id="contador-horarios-emergencial">0</span>/3)
                                            </h4>
                                            <div id="lista-horarios-emergencial" class="space-y-2"></div>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- Agendamento Normal (não emergencial) -->
                                
                                <!-- Avisos -->
                                <div class="space-y-4 mb-6">
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Condomínio</p>
                                                <p class="text-sm">Se o serviço for realizado em apartamento ou condomínio, é obrigatório comunicar previamente a administração ou portaria sobre a visita técnica agendada.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Responsável no Local</p>
                                                <p class="text-sm">É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-4">
                                        <p class="font-semibold mb-2">Selecione até 3 datas e horários preferenciais</p>
                                        <p class="text-sm mb-1">Após sua escolha, o prestador verificará a disponibilidade. Caso algum dos horários não esteja livre, poderão ser sugeridas novas opções.</p>
                                        <p class="text-sm">Você receberá uma notificação confirmando a data e o horário final definidos (via WhatsApp e aplicativo).</p>
                                    </div>
                                </div>
                                
                                <!-- Horários Preferenciais -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Horários Preferenciais <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-sm text-gray-500 mb-3">Selecione até 3 opções de data e horário</p>
                                    
                                    <!-- Data -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Selecione uma Data
                                        </label>
                                        <!-- Botão para abrir modal -->
                                        <button type="button" id="btn-selecionar-data" 
                                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm text-left font-medium text-gray-700 bg-white hover:border-green-500 hover:bg-green-50 transition-colors flex items-center justify-between">
                                            <span class="flex items-center">
                                                <i class="fas fa-calendar-alt text-gray-400 mr-3"></i>
                                                <span id="texto-botao-data">Selecione uma Data</span>
                                            </span>
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </button>
                                        <!-- Input hidden para armazenar o valor -->
                                        <input type="hidden" id="data_selecionada" name="data_selecionada"
                                                   data-min-date="<?= $dataMinimaAgendamento ? $dataMinimaAgendamento->format('Y-m-d') : '' ?>">
                                        <!-- Modal para selecionar data -->
                                        <div id="modal-data" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                                            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                                                <div class="flex justify-between items-center mb-4">
                                                    <h3 class="text-lg font-semibold text-gray-900">Selecione uma Data</h3>
                                                    <button type="button" id="fechar-modal-data" class="text-gray-400 hover:text-gray-600">
                                                        <i class="fas fa-times text-xl"></i>
                                                    </button>
                                                </div>
                                                <div class="mb-4" id="calendario-container-data">
                                                    <!-- Calendário será gerado aqui via JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex items-center text-xs text-gray-500">
                                            <i class="fas fa-info-circle mr-1.5"></i>
                                            <span>
                                                Atendimentos disponíveis apenas em dias úteis (segunda a sexta-feira)
                                                <?php if ($dataMinimaAgendamento && $subcategoriaParaPrazo): ?>
                                                    <?php
                                                    $prazoMinimo = $subcategoriaParaPrazo['prazo_minimo'] ?? 1;
                                                    $dataMinimaFormatada = $dataMinimaAgendamento->format('d/m/Y');
                                                    ?>
                                                    <br>
                                                    <strong>Data mínima para agendamento: <?= $dataMinimaFormatada ?></strong>
                                                    <?php if ($prazoMinimo > 0): ?>
                                                        (prazo mínimo de <?= $prazoMinimo ?> dia<?= $prazoMinimo > 1 ? 's' : '' ?>)
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Horário -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Horário
                                        </label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <label class="relative">
                                                <input type="radio" name="horario_temp" value="08:00-11:00" class="sr-only horario-radio">
                                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card">
                                                    <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                                                </div>
                                            </label>
                                            <label class="relative">
                                                <input type="radio" name="horario_temp" value="11:00-14:00" class="sr-only horario-radio">
                                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card">
                                                    <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                                                </div>
                                            </label>
                                            <label class="relative">
                                                <input type="radio" name="horario_temp" value="14:00-17:00" class="sr-only horario-radio">
                                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card">
                                                    <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                                                </div>
                                            </label>
                                            <label class="relative">
                                                <input type="radio" name="horario_temp" value="17:00-20:00" class="sr-only horario-radio">
                                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-all horario-card">
                                                    <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Horários Selecionados -->
                                    <div id="horarios-selecionados" class="hidden">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            Horários Selecionados (<span id="contador-horarios">0</span>/3)
                                        </h4>
                                        <div id="lista-horarios" class="space-y-2"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Navigation -->
                            <div class="flex justify-between pt-6">
                                <a href="<?= $urlHelper('/etapa/3') ?>" 
                                   class="btn-voltar-etapa px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                                   data-etapa="3">
                                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                                </a>
                                <button type="submit" id="btn-continuar" 
                                        class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    Continuar <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($etapaAtual == 5): ?>
                    <!-- ETAPA 5: CONFIRMAÇÃO -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-check mr-2 text-green-600"></i>
                            Confirmação
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Revise os dados e confirme sua solicitação</p>
                    </div>
                    
                    <div class="p-6">
                        <!-- Resumo -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumo da Solicitação</h3>
                            
                            <div class="space-y-4">
                                <!-- Dados Pessoais -->
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Nome:</span>
                                    <p class="text-sm text-gray-900"><?= htmlspecialchars($dados['nome_completo'] ?? '') ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">CPF:</span>
                                    <p class="text-sm text-gray-900"><?= htmlspecialchars($dados['cpf'] ?? '') ?></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">WhatsApp:</span>
                                    <p class="text-sm text-gray-900"><?= htmlspecialchars($dados['whatsapp'] ?? '') ?></p>
                                </div>
                                
                                <!-- Endereço -->
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Endereço:</span>
                                    <p class="text-sm text-gray-900">
                                        <?= htmlspecialchars($dados['endereco'] ?? '') ?>, <?= htmlspecialchars($dados['numero'] ?? '') ?>
                                        <?= !empty($dados['complemento']) ? ' - ' . htmlspecialchars($dados['complemento']) : '' ?><br>
                                        <?= htmlspecialchars($dados['bairro'] ?? '') ?>, <?= htmlspecialchars($dados['cidade'] ?? '') ?> - <?= htmlspecialchars($dados['estado'] ?? '') ?><br>
                                        CEP: <?= htmlspecialchars($dados['cep'] ?? '') ?>
                                    </p>
                                </div>
                                
                                <!-- Serviço(s) -->
                                <div>
                                    <span class="text-sm font-medium text-gray-500"><?= (!empty($dados['categorias_selecionadas']) && count($dados['categorias_selecionadas']) > 1) ? 'Serviços:' : 'Serviço:' ?></span>
                                    <p class="text-sm text-gray-900">
                                        <?php
                                        // Verificar se há múltiplas categorias selecionadas
                                        $categoriasSelecionadas = $dados['categorias_selecionadas'] ?? [];
                                        $temMultiplasCategorias = !empty($categoriasSelecionadas) && is_array($categoriasSelecionadas) && count($categoriasSelecionadas) > 1;
                                        
                                        if ($temMultiplasCategorias) {
                                            // Exibir todas as subcategorias selecionadas
                                            $subcategoriaModel = new \App\Models\Subcategoria();
                                            $nomesServicos = [];
                                            
                                            foreach ($categoriasSelecionadas as $cat) {
                                                if (!empty($cat['subcategoria_id'])) {
                                                    try {
                                                        $subcategoria = $subcategoriaModel->find($cat['subcategoria_id']);
                                                        if ($subcategoria && !empty($subcategoria['nome'])) {
                                                            $nomesServicos[] = htmlspecialchars($subcategoria['nome']);
                                                        }
                                                    } catch (\Exception $e) {
                                                        // Ignorar erro
                                                    }
                                                }
                                            }
                                            
                                            if (!empty($nomesServicos)) {
                                                echo '<ul class="list-disc list-inside space-y-1">';
                                                foreach ($nomesServicos as $nome) {
                                                    echo '<li>' . $nome . '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo 'Não informado';
                                            }
                                        } elseif (!empty($dados['subcategoria_id'])) {
                                            // Seleção única
                                            $subcategoriaModel = new \App\Models\Subcategoria();
                                            $subcategoria = $subcategoriaModel->find($dados['subcategoria_id']);
                                            if ($subcategoria && !empty($subcategoria['nome'])) {
                                                echo htmlspecialchars($subcategoria['nome']);
                                            } else {
                                                echo 'Não informado';
                                            }
                                        } else {
                                            echo 'Não informado';
                                        }
                                        ?>
                                    </p>
                                </div>

                                <?php if (!empty($dados['numero_contrato'])): ?>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Número do Contrato:</span>
                                        <p class="text-sm text-gray-900"><?= htmlspecialchars($dados['numero_contrato']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($dados['local_manutencao'])): ?>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Local da Manutenção:</span>
                                        <p class="text-sm text-gray-900"><?= htmlspecialchars($dados['local_manutencao']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Descrição -->
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Descrição:</span>
                                    <p class="text-sm text-gray-900"><?= nl2br(htmlspecialchars($dados['descricao_problema'] ?? '')) ?></p>
                                </div>
                                
                                <!-- Horários -->
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Horários Preferenciais:</span>
                                    <?php if (!empty($dados['horarios_preferenciais'])): ?>
                                        <ul class="text-sm text-gray-900 list-disc list-inside">
                                            <?php foreach ($dados['horarios_preferenciais'] as $horario): 
                                                // Formatar horário: "2025-12-23 08:00:00-11:00:00" -> "23/12/2025 08:00 - 11:00"
                                                $horarioFormatado = $horario;
                                                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):\d{2}-(\d{2}):(\d{2}):\d{2}$/', $horario, $matches)) {
                                                    // Formato: YYYY-MM-DD HH:MM:SS-HH:MM:SS
                                                    $horarioFormatado = $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5] . ' - ' . $matches[6] . ':' . $matches[7];
                                                } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $horario, $matches)) {
                                                    // Formato: YYYY-MM-DD HH:MM (sem intervalo)
                                                    $horarioFormatado = $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5];
                                                }
                                            ?>
                                                <li><?= htmlspecialchars($horarioFormatado) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">Nenhum horário informado</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Termos -->
                        <form method="POST" action="<?= $urlHelper('/etapa/5') ?>" class="space-y-6">
                            <?= \App\Core\View::csrfField() ?>
                            
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox" name="termo_aceite" value="1" required
                                           class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <span class="ml-3 text-sm text-gray-700">
                                        Li e aceito os <a href="#" onclick="abrirModalTermos(); return false;" class="underline font-medium">termos e condições</a> de prestação de serviços. Estou ciente de que devo comunicar a administração/portaria quando necessário e garantir a presença de um responsável maior de idade durante o atendimento. <span class="text-red-600">*</span>
                                    </span>
                                </label>
                            </div>
                            
                            <!-- Termo de LGPD - SEPARADO -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox" name="lgpd_aceite" id="lgpd_aceite" value="1" required
                                           class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <span class="ml-3 text-sm text-blue-900">
                                        Li e aceito o tratamento de meus dados pessoais conforme a <a href="#" onclick="abrirModalLGPD(); return false;" class="underline font-medium text-blue-700 hover:text-blue-900">Lei Geral de Proteção de Dados (LGPD)</a>. Autorizo o uso de meus dados exclusivamente para o gerenciamento desta solicitação de serviço.
                                        <span class="text-red-600">*</span>
                                    </span>
                                </label>
                            </div>
                            
                            <!-- Navigation -->
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 sm:justify-between pt-6">
                                <a href="<?= $urlHelper('/etapa/4') ?>" 
                                   class="btn-voltar-etapa w-full sm:w-auto px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-center sm:text-left"
                                   data-etapa="4">
                                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                                </a>
                                <button type="submit" id="btn-finalizar"
                                        class="w-full sm:w-auto flex-1 sm:flex-none px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-check mr-2"></i>Finalizar Solicitação
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php endif; ?>
                
            </div>
            
        </div>
    </div>
    
    <!-- Modal de Termos -->
    <div id="modal-termos" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Termos e Condições de Prestação de Serviços</h3>
                <button onclick="fecharModalTermos()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4 text-sm text-gray-700">
                <p><strong>1. Prestação de Serviços:</strong> Os serviços de assistência técnica serão prestados por profissionais qualificados e devidamente autorizados pela SEGURADORA.</p>
                
                <p><strong>2. Emergências:</strong> Em emergência ligar para <strong>16 98124-1689</strong> (horário comercial de seg a sex).</p>
                
                <p><strong>3. Privacidade:</strong> Seus dados pessoais serão tratados de acordo com a Lei Geral de Proteção de Dados (LGPD) e utilizados exclusivamente para o gerenciamento da solicitação.</p>
                
                <p><strong>4. Responsabilidades:</strong> É obrigatória a presença de uma pessoa maior de 18 anos durante todo o período de execução do serviço. Em casos de condomínio, o solicitante deve comunicar previamente a administração/portaria.</p>
                
                <p><strong>5. Cancelamento:</strong> O cancelamento pode ser realizado até 24 horas antes do horário agendado sem custos adicionais.</p>
                
                <p><strong>6. Peças e Materiais:</strong> Caso seja necessária a compra de peças, o locatário será informado previamente e terá até 10 dias para providenciar os materiais.</p>
                
                <p><strong>7. Visita Técnica:</strong> Se o prestador não encontrar ninguém no imóvel no horário agendado, a visita será contabilizada normalmente.</p>
            </div>
            <div class="border-t border-gray-200 px-6 py-4">
                <button onclick="fecharModalTermos()" 
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de LGPD -->
    <div id="modal-lgpd" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Política de Privacidade (LGPD)</h3>
                <button onclick="fecharModalLGPD()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4 text-sm text-gray-700">
                <p>A sua privacidade é importante para nós. Esta Política de Privacidade descreve como coletamos, usamos, armazenamos e protegemos os dados pessoais dos usuários, em conformidade com a <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018 – LGPD)</strong>.</p>
                
                <p><strong>1. Dados Coletados:</strong> Podemos coletar os seguintes dados pessoais: nome completo, e-mail, telefone/WhatsApp, CPF/CNPJ, endereço completo (CEP, rua, número, complemento, bairro, cidade, estado), endereço IP, dados de acesso e navegação, informações fornecidas voluntariamente pelo usuário em formulários, fotos e documentos enviados.</p>
                
                <p><strong>2. Finalidade do Tratamento:</strong> Os dados pessoais são utilizados para: identificação e autenticação do usuário, prestação dos serviços oferecidos pela aplicação, gerenciamento de solicitações de assistência técnica, comunicação com o usuário (notificações, atualizações de status), cumprimento de obrigações legais e regulatórias, melhoria da experiência do usuário, análise estatística e desenvolvimento de novos serviços.</p>
                
                <p><strong>3. Base Legal:</strong> O tratamento dos dados é realizado com base em: <strong>consentimento do titular</strong> (quando você aceita esta política e autoriza o uso de seus dados), <strong>execução de contrato</strong> (para cumprimento de obrigações contratuais), <strong>cumprimento de obrigação legal</strong> (quando exigido por lei ou autoridade competente), <strong>legítimo interesse</strong> (para melhorias de serviço e segurança, quando aplicável).</p>
                
                <p><strong>4. Compartilhamento de Dados:</strong> Os dados pessoais <strong>não são vendidos</strong> a terceiros. Poderão ser compartilhados apenas quando: exigido por lei ou autoridade competente, necessário para a execução do serviço (ex: provedores de hospedagem, serviços de comunicação), com prestadores de serviços que atuam como processadores de dados, sob contrato de confidencialidade.</p>
                
                <p><strong>5. Armazenamento e Segurança:</strong> Adotamos medidas técnicas e administrativas para proteger os dados pessoais contra acessos não autorizados, vazamentos, alterações indevidas e destruição. Utilizamos tecnologias de criptografia, controle de acesso e monitoramento contínuo para garantir a segurança das informações.</p>
                
                <p><strong>6. Direitos do Titular:</strong> O titular dos dados pode, a qualquer momento: confirmar a existência de tratamento de dados, acessar seus dados pessoais, corrigir dados incompletos, inexatos ou desatualizados, solicitar anonimização, bloqueio ou eliminação de dados desnecessários, solicitar portabilidade dos dados, revogar o consentimento, obter informações sobre compartilhamento de dados. Solicitações podem ser feitas pelo e-mail: <a href="mailto:contato@ksssolucoes.com.br" class="text-blue-600 hover:text-blue-800 underline">contato@ksssolucoes.com.br</a></p>
                
                <p><strong>7. Retenção dos Dados:</strong> Os dados serão mantidos apenas pelo tempo necessário para cumprir as finalidades descritas nesta política, para cumprimento de obrigações legais ou regulatórias, ou conforme período estabelecido em lei. Após esse período, os dados serão eliminados ou anonimizados de forma segura.</p>
                
                <p><strong>8. Alterações:</strong> Esta Política de Privacidade pode ser atualizada a qualquer momento para refletir mudanças em nossas práticas ou por requisitos legais. Recomendamos a revisão periódica desta política. A data da última atualização será indicada no documento.</p>
            </div>
            <div class="border-t border-gray-200 px-6 py-4">
                <button onclick="fecharModalLGPD()" 
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Seleção de Endereços (Múltiplos Endereços do Bolsão) -->
    <div id="modal-selecionar-endereco" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-3xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>
                    Selecione o Endereço
                </h3>
                <button onclick="fecharModalSelecionarEndereco()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-gray-600 mb-4">Foram encontrados múltiplos endereços cadastrados para este CPF. Por favor, selecione o endereço onde será realizado o serviço:</p>
                <div id="lista-enderecos-bolsao" class="space-y-3">
                    <!-- Endereços serão inseridos aqui via JavaScript -->
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                <button onclick="fecharModalSelecionarEndereco()" 
                        class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Descrição da Categoria -->
    <div id="modal-condicoes-gerais" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900" id="modal-condicoes-titulo"></h3>
                <button onclick="fecharModalCondicoesGerais()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <div class="text-sm text-gray-700" id="modal-condicoes-conteudo"></div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4">
                <button onclick="fecharModalCondicoesGerais()" 
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de descrição (mantido para compatibilidade) -->
    <div id="modal-descricao-categoria" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900" id="modal-descricao-titulo"></h3>
                <button onclick="fecharModalDescricaoCategoria()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-gray-700 whitespace-pre-line" id="modal-descricao-conteudo"></p>
            </div>
            <div class="border-t border-gray-200 px-6 py-4">
                <button onclick="fecharModalDescricaoCategoria()" 
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
            <div class="mb-4">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-green-600"></div>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Enviando solicitação...</h3>
            <p class="text-gray-600">Por favor, aguarde.</p>
        </div>
    </div>
    
    <script>
    // Máscaras de input
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
        
        // Buscar dados automaticamente quando o CPF for preenchido
        let timeoutBusca;
        cpfInput.addEventListener('blur', function() {
            const cpf = this.value.replace(/\D/g, '');
            
            // Validar se tem 11 ou 14 dígitos
            if (cpf.length === 11 || cpf.length === 14) {
                clearTimeout(timeoutBusca);
                timeoutBusca = setTimeout(async function() {
                    await buscarDadosPorCPF(cpf);
                }, 500);
            } else {
                // Se CPF inválido, reabilitar campos
                habilitarCamposBolsao();
            }
        });
        
        // Reabilitar campos quando CPF for alterado manualmente
        cpfInput.addEventListener('input', function() {
            // Se o usuário começar a digitar um novo CPF, reabilitar campos
            const cpf = this.value.replace(/\D/g, '');
            if (cpf.length < 11) {
                habilitarCamposBolsao();
            }
        });
    }
    
    // Função para desabilitar campos quando bolsão é validado
    function desabilitarCamposBolsaoValidado() {
        // Campos que devem permanecer editáveis: CPF, WhatsApp, tipo_imovel, subtipo_imovel
        // Todos os outros campos devem ser desabilitados
        
        // Nome completo - desabilitar
        const nomeCompleto = document.getElementById('nome_completo');
        if (nomeCompleto) {
            nomeCompleto.disabled = true;
            nomeCompleto.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // CEP - desabilitar
        const cep = document.getElementById('cep');
        if (cep) {
            cep.disabled = true;
            cep.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Botão buscar CEP - desabilitar
        const btnBuscarCep = document.getElementById('btn-buscar-cep');
        if (btnBuscarCep) {
            btnBuscarCep.disabled = true;
            btnBuscarCep.classList.add('opacity-50', 'cursor-not-allowed');
        }
        
        // Endereço - desabilitar
        const endereco = document.getElementById('endereco');
        if (endereco) {
            endereco.disabled = true;
            endereco.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Número - desabilitar
        const numero = document.getElementById('numero');
        if (numero) {
            numero.disabled = true;
            numero.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Complemento - desabilitar
        const complemento = document.getElementById('complemento');
        if (complemento) {
            complemento.disabled = true;
            complemento.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Bairro - desabilitar
        const bairro = document.getElementById('bairro');
        if (bairro) {
            bairro.disabled = true;
            bairro.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Cidade - desabilitar
        const cidade = document.getElementById('cidade');
        if (cidade) {
            cidade.disabled = true;
            cidade.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Estado - desabilitar
        const estado = document.getElementById('estado');
        if (estado) {
            estado.disabled = true;
            estado.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Unidade - desabilitar
        const unidade = document.getElementById('unidade');
        if (unidade) {
            unidade.disabled = true;
            unidade.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Número do contrato - desabilitar
        const numeroContrato = document.getElementById('numero_contrato');
        if (numeroContrato) {
            numeroContrato.readOnly = true;
            numeroContrato.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
        
        // Mostrar mensagem informativa
        mostrarMensagemBolsaoValidado();
    }
    
    // Função para habilitar todos os campos (quando bolsão não validado)
    function habilitarCamposBolsao() {
        const campos = [
            'nome_completo', 'cep', 'endereco', 'numero', 'complemento', 
            'bairro', 'cidade', 'estado', 'unidade', 'numero_contrato'
        ];
        
        campos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.disabled = false;
                campo.readOnly = false;
                campo.classList.remove('bg-gray-100', 'cursor-not-allowed');
            }
        });
        
        // Habilitar botão buscar CEP
        const btnBuscarCep = document.getElementById('btn-buscar-cep');
        if (btnBuscarCep) {
            btnBuscarCep.disabled = false;
            btnBuscarCep.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        
        // Remover mensagem informativa
        removerMensagemBolsaoValidado();
    }
    
    // Função para mostrar mensagem de bolsão validado
    function mostrarMensagemBolsaoValidado() {
        // Remover mensagem anterior se existir
        removerMensagemBolsaoValidado();
        
        // Criar mensagem
        const mensagem = document.createElement('div');
        mensagem.id = 'mensagem-bolsao-validado';
        mensagem.className = 'mb-4 p-3 bg-green-50 border border-green-200 rounded-lg';
        mensagem.innerHTML = `
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mt-0.5 mr-2"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-800">Bolsão Validado</p>
                    <p class="text-xs text-green-700 mt-1">CPF encontrado na base de dados. Apenas os campos CPF, WhatsApp e Tipo de Imóvel podem ser editados.</p>
                </div>
            </div>
        `;
        
        // Inserir após o container do CPF
        const cpfContainer = document.getElementById('cpf-container');
        if (cpfContainer && cpfContainer.parentNode) {
            cpfContainer.parentNode.insertBefore(mensagem, cpfContainer.nextSibling);
        } else {
            // Fallback: inserir após o campo CPF
            const cpfDiv = document.getElementById('cpf')?.closest('div');
            if (cpfDiv && cpfDiv.parentNode) {
                cpfDiv.parentNode.insertBefore(mensagem, cpfDiv.nextSibling);
            }
        }
    }
    
    // Função para remover mensagem de bolsão validado
    function removerMensagemBolsaoValidado() {
        const mensagem = document.getElementById('mensagem-bolsao-validado');
        if (mensagem) {
            mensagem.remove();
        }
    }
    
    // Função para preencher campos com dados de um endereço
    function preencherCamposEndereco(dados, validacaoBolsao) {
                if (dados.nome_completo) {
                    document.getElementById('nome_completo').value = dados.nome_completo;
                }
                
                if (dados.tipo_imovel) {
                    const selectTipo = document.getElementById('finalidade_locacao');
                    if (selectTipo) {
                        // Mapear tipo_imovel para finalidade_locacao
                        let finalidade = dados.tipo_imovel.toUpperCase();
                        
                        // Se for CASA ou APARTAMENTO, mapear para RESIDENCIAL
                        if (finalidade === 'CASA' || finalidade === 'APARTAMENTO') {
                            finalidade = 'RESIDENCIAL';
                            
                            // Mostrar container de subtipo
                            const subtipoContainer = document.getElementById('subtipo-container');
                            if (subtipoContainer) {
                                subtipoContainer.classList.remove('hidden');
                            }
                            
                            // Definir o subtipo_imovel correspondente
                            const subtipoCasa = document.querySelector('input[name="subtipo_imovel"][value="CASA"]');
                            const subtipoApto = document.querySelector('input[name="subtipo_imovel"][value="APARTAMENTO"]');
                            
                            if (dados.tipo_imovel.toUpperCase() === 'CASA' && subtipoCasa) {
                                subtipoCasa.checked = true;
                            } else if (dados.tipo_imovel.toUpperCase() === 'APARTAMENTO' && subtipoApto) {
                                subtipoApto.checked = true;
                            }
                        } else if (finalidade === 'RESIDENCIAL') {
                            // Se for RESIDENCIAL, mostrar container mas não marcar subtipo
                            const subtipoContainer = document.getElementById('subtipo-container');
                            if (subtipoContainer) {
                                subtipoContainer.classList.remove('hidden');
                            }
                        }
                        
                        // Se for COMERCIAL, manter COMERCIAL (container já estará escondido)
                        selectTipo.value = finalidade;
                        selectTipo.dispatchEvent(new Event('change'));
                    }
                }
                
                if (dados.cep) {
                    document.getElementById('cep').value = dados.cep;
                }
                
                if (dados.endereco) {
                    document.getElementById('endereco').value = dados.endereco;
                }
                
                if (dados.numero) {
                    document.getElementById('numero').value = dados.numero;
                }
                
        // Se houver unidade, preencher complemento com a unidade
        // Caso contrário, usar o complemento original se existir
        if (dados.unidade) {
            document.getElementById('complemento').value = dados.unidade;
            const unidadeInput = document.getElementById('unidade');
            if (unidadeInput) {
                unidadeInput.value = dados.unidade;
            }
        } else if (dados.complemento) {
                    document.getElementById('complemento').value = dados.complemento;
                }
                
                if (dados.bairro) {
                    document.getElementById('bairro').value = dados.bairro;
                }
                
                if (dados.cidade) {
                    document.getElementById('cidade').value = dados.cidade;
                }
                
                if (dados.estado) {
                    document.getElementById('estado').value = dados.estado;
                }
                
                if (dados.numero_contrato) {
                    document.getElementById('numero_contrato').value = dados.numero_contrato;
                }
                
                // Se bolsão validado, desabilitar campos (exceto CPF, WhatsApp e tipo de imóvel)
                if (validacaoBolsao) {
                    desabilitarCamposBolsaoValidado();
                } else {
                    habilitarCamposBolsao();
                }
    }
    
    // Função para mostrar modal de seleção de endereços
    function mostrarModalSelecionarEndereco(enderecos, validacaoBolsao) {
        const modal = document.getElementById('modal-selecionar-endereco');
        const listaEnderecos = document.getElementById('lista-enderecos-bolsao');
        
        if (!modal || !listaEnderecos) {
            console.error('Modal ou lista de endereços não encontrada');
            return;
        }
        
        // Limpar lista anterior
        listaEnderecos.innerHTML = '';
        
        // Criar cards para cada endereço
        enderecos.forEach((endereco, index) => {
            const enderecoCompleto = [
                endereco.endereco || '',
                endereco.numero || '',
                endereco.complemento || '',
                endereco.bairro || '',
                endereco.cidade || '',
                endereco.estado || ''
            ].filter(Boolean).join(', ');
            
            const card = document.createElement('div');
            card.className = 'border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-500 hover:bg-green-50 transition-all';
            card.dataset.index = index;
            card.onclick = () => selecionarEnderecoBolsao(endereco, validacaoBolsao);
            
            card.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>
                            <span class="font-medium text-gray-900">Endereço ${index + 1}</span>
                        </div>
                        <p class="text-sm text-gray-700 mb-1">${enderecoCompleto || 'Endereço não informado'}</p>
                        ${endereco.cep ? `<p class="text-xs text-gray-500">CEP: ${endereco.cep}</p>` : ''}
                        ${endereco.numero_contrato ? `<p class="text-xs text-gray-500 mt-1">Contrato: ${endereco.numero_contrato}</p>` : ''}
                        ${endereco.unidade ? `<p class="text-xs text-gray-500 mt-1">Unidade: ${endereco.unidade}</p>` : ''}
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 ml-4"></i>
                </div>
            `;
            
            listaEnderecos.appendChild(card);
        });
        
        // Mostrar modal
        modal.classList.remove('hidden');
    }
    
    // Função para selecionar um endereço do modal
    function selecionarEnderecoBolsao(endereco, validacaoBolsao) {
        // Preencher campos com o endereço selecionado
        preencherCamposEndereco(endereco, validacaoBolsao);
        
        // Fechar modal
        window.fecharModalSelecionarEndereco();
    }
    
    // Função para buscar dados por CPF
    async function buscarDadosPorCPF(cpf) {
        const instancia = '<?= $instancia ?? "" ?>';
        if (!instancia) {
            console.error('Instância não encontrada');
            return;
        }
        
        // Mostrar indicador de carregamento
        const cpfInput = document.getElementById('cpf');
        const originalBorder = cpfInput.style.borderColor;
        cpfInput.style.borderColor = '#3b82f6';
        
        try {
            const formData = new FormData();
            formData.append('cpf', cpf);
            
            const urlBuscarCPF = '<?= $modoToken && $token ? url("solicitacao-manual/{$token}/buscar-cpf") : url($instancia . "/solicitacao-manual/buscar-cpf") ?>';
            const response = await fetch(urlBuscarCPF, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            // Verificar validação do bolsão
            const validacaoBolsao = data.validacao_bolsao === 1 || data.validacao_bolsao === true;
            
            // Verificar se há múltiplos endereços
            if (data.success && data.tem_multiplos_enderecos && data.enderecos && data.enderecos.length > 1) {
                // Múltiplos endereços encontrados - mostrar modal de seleção
                mostrarModalSelecionarEndereco(data.enderecos, validacaoBolsao);
                cpfInput.style.borderColor = '#10b981';
                setTimeout(() => {
                    cpfInput.style.borderColor = originalBorder;
                }, 2000);
            } else if (data.success && data.dados) {
                // Apenas 1 endereço encontrado - preencher automaticamente (comportamento antigo)
                preencherCamposEndereco(data.dados, validacaoBolsao);
                
                // Mostrar mensagem de sucesso
                cpfInput.style.borderColor = '#10b981';
                setTimeout(() => {
                    cpfInput.style.borderColor = originalBorder;
                }, 2000);
            } else {
                // CPF não encontrado - bolsão recusado, habilitar todos os campos
                habilitarCamposBolsao();
                cpfInput.style.borderColor = originalBorder;
            }
        } catch (error) {
            console.error('Erro ao buscar dados por CPF:', error);
            cpfInput.style.borderColor = originalBorder;
        }
    }
    
    // Formatação automática de CPF/CNPJ
    document.getElementById('cpf')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Se tiver 11 dígitos ou menos, formatar como CPF
        if (value.length <= 11) {
            value = value.replace(/^(\d{3})(\d)/, '$1.$2');
            value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1-$2');
        } else {
            // Se tiver mais de 11 dígitos, formatar como CNPJ
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }
        
        e.target.value = value;
    });
    
    document.getElementById('whatsapp')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        e.target.value = value;
    });
    
    document.getElementById('cep')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/^(\d{5})(\d)/, '$1-$2');
        e.target.value = value;
    });
    
    // Buscar CEP
    document.getElementById('btn-buscar-cep')?.addEventListener('click', async function() {
        const cep = document.getElementById('cep').value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            alert('CEP inválido');
            return;
        }
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Buscando...';
        this.disabled = true;
        
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();
            
            if (data.erro) {
                alert('CEP não encontrado');
            } else {
                document.getElementById('endereco').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('estado').value = data.uf || '';
            }
        } catch (error) {
            alert('Erro ao buscar CEP');
        } finally {
            this.innerHTML = '<i class="fas fa-search mr-2"></i>Buscar';
            this.disabled = false;
        }
    });
    
    // Validação do formulário da etapa 1
    function validarFormularioEtapa1(event) {
        const finalidade = document.getElementById('finalidade_locacao');
        const subtipoRadios = document.querySelectorAll('input[name="subtipo_imovel"]');
        const subtipoError = document.getElementById('subtipo-error');
        
        // Obter referências aos campos
        const campoCpf = document.getElementById('cpf');
        const campoNome = document.getElementById('nome_completo');
        const campoWhatsapp = document.getElementById('whatsapp');
        const campoCep = document.getElementById('cep');
        const campoEndereco = document.getElementById('endereco');
        const campoNumero = document.getElementById('numero');
        const campoBairro = document.getElementById('bairro');
        const campoCidade = document.getElementById('cidade');
        const campoEstado = document.getElementById('estado');
        
        // Garantir que todos os campos estejam habilitados antes do submit
        // Campos desabilitados não são enviados no POST
        if (campoCpf) campoCpf.disabled = false;
        if (campoNome) campoNome.disabled = false;
        if (campoWhatsapp) campoWhatsapp.disabled = false;
        if (campoCep) campoCep.disabled = false;
        if (campoEndereco) campoEndereco.disabled = false;
        if (campoNumero) campoNumero.disabled = false;
        if (campoBairro) campoBairro.disabled = false;
        if (campoCidade) campoCidade.disabled = false;
        if (campoEstado) campoEstado.disabled = false;
        
        const cpf = campoCpf?.value?.trim() || '';
        const nome = campoNome?.value?.trim() || '';
        const whatsapp = campoWhatsapp?.value?.trim() || '';
        const cep = campoCep?.value?.trim() || '';
        const endereco = campoEndereco?.value?.trim() || '';
        const numero = campoNumero?.value?.trim() || '';
        const bairro = campoBairro?.value?.trim() || '';
        const cidade = campoCidade?.value?.trim() || '';
        const estado = campoEstado?.value?.trim() || '';
        
        // 🔍 DEBUG: logar no console e no error.log todos os campos obrigatórios antes de enviar
        const debugInfo = {
            cpf,
            nome,
            whatsapp,
            finalidade: finalidade ? finalidade.value : null,
            cep,
            endereco,
            numero,
            bairro,
            cidade,
            estado,
            campos_habilitados: {
                cpf: campoCpf ? !campoCpf.disabled : 'N/A',
                nome: campoNome ? !campoNome.disabled : 'N/A',
                whatsapp: campoWhatsapp ? !campoWhatsapp.disabled : 'N/A',
                cep: campoCep ? !campoCep.disabled : 'N/A',
                endereco: campoEndereco ? !campoEndereco.disabled : 'N/A',
                numero: campoNumero ? !campoNumero.disabled : 'N/A',
                bairro: campoBairro ? !campoBairro.disabled : 'N/A',
                cidade: campoCidade ? !campoCidade.disabled : 'N/A',
                estado: campoEstado ? !campoEstado.disabled : 'N/A'
            }
        };
        
        console.log('[DEBUG Etapa 1] Campos obrigatórios antes do submit:', debugInfo);
        
        // Validar campos obrigatórios
        if (!cpf || !nome || !whatsapp || !cep || !endereco || !numero || !bairro || !cidade || !estado) {
            event.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios antes de continuar.');
            return false;
        }
        
        // Se for RESIDENCIAL, verificar se um subtipo foi selecionado
        if (finalidade && finalidade.value === 'RESIDENCIAL') {
            let subtipoSelecionado = false;
            subtipoRadios.forEach(radio => {
                if (radio.checked) {
                    subtipoSelecionado = true;
                }
            });
            
            if (!subtipoSelecionado) {
                event.preventDefault();
                if (subtipoError) {
                    subtipoError.classList.remove('hidden');
                }
                // Scroll para o campo de subtipo
                const subtipoContainer = document.getElementById('subtipo-container');
                if (subtipoContainer) {
                    subtipoContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
        }
        
        if (subtipoError) {
            subtipoError.classList.add('hidden');
        }
        return true;
    }
    
    // Finalidade (RESIDENCIAL/COMERCIAL) - mostrar/ocultar "Tipo de Imóvel"
    (function() {
        const selectFinalidade = document.getElementById('finalidade_locacao');
        const subtipoContainer = document.getElementById('subtipo-container');
        if (!selectFinalidade) return;
        function atualizarVisibilidadeSubtipo() {
            if (selectFinalidade.value === 'RESIDENCIAL') {
                subtipoContainer.classList.remove('hidden');
                // Tornar os radio buttons obrigatórios
                document.querySelectorAll('.subtipo-imovel-radio').forEach(r => {
                    r.setAttribute('required', 'required');
                });
            } else {
                subtipoContainer.classList.add('hidden');
                document.querySelectorAll('.subtipo-imovel-radio').forEach(r => {
                    r.checked = false;
                    r.removeAttribute('required');
                });
                // Ocultar mensagem de erro se estiver visível
                const subtipoError = document.getElementById('subtipo-error');
                if (subtipoError) {
                    subtipoError.classList.add('hidden');
                }
            }
        }
        selectFinalidade.addEventListener('change', atualizarVisibilidadeSubtipo);
        atualizarVisibilidadeSubtipo();
    })();
    
    // Adicionar validação no submit do formulário da etapa 1
    document.addEventListener('DOMContentLoaded', function() {
        const formEtapa1 = document.querySelector('form[action*="solicitacao-manual"]:not([action*="etapa"])');
        if (formEtapa1) {
            formEtapa1.addEventListener('submit', function(e) {
                if (!validarFormularioEtapa1(e)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
    
    // Subtipo de imóvel - radios simples (sem estilização de cards)
    
    // Inicializar estados visuais do subtipo e dos cards de subtipo
    document.addEventListener('DOMContentLoaded', function() {
        const subtipoSelecionado = document.querySelector('.subtipo-imovel-radio:checked');
        if (subtipoSelecionado) {
            subtipoSelecionado.dispatchEvent(new Event('change'));
        }
    });
    
    // === ETAPA 2: Função para seleção múltipla de subcategorias (Manutenção e Prevenção) ===
    window.toggleSubcategoriaManual = function(categoriaPaiId, categoriaFilhaId, subcategoriaId, checked) {
        // Buscar checkbox usando múltiplos seletores para garantir que encontre
        let checkbox = document.querySelector(`input[data-subcategoria="${subcategoriaId}"][data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao`);
        if (!checkbox) {
            checkbox = document.querySelector(`input[data-subcategoria="${subcategoriaId}"].subcategoria-checkbox-manutencao`);
        }
        
        if (!checkbox) {
            console.error('Checkbox não encontrado para subcategoria:', subcategoriaId);
            return;
        }
        
        // Garantir que o estado do checkbox está correto
        checkbox.checked = checked;
        
        const wrapper = checkbox.closest('.subcategoria-item-wrapper');
        if (!wrapper) {
            console.error('Wrapper não encontrado para subcategoria:', subcategoriaId);
            return;
        }
        
        const card = wrapper.querySelector('.subcategoria-card-manutencao');
        const checkIcon = wrapper.querySelector('.subcategoria-check-manutencao');
        const contador = document.getElementById('contador-subcategorias-' + categoriaPaiId);
        
        // Contar subcategorias marcadas no total (todas as categorias filhas)
        const todasSubcategoriasMarcadas = document.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`);
        const totalMarcadas = todasSubcategoriasMarcadas.length;
        
        // Limitar a 3 seleções (verificar antes de atualizar visual)
        if (checked && totalMarcadas > 3) {
            checkbox.checked = false;
            alert('Você pode selecionar no máximo 3 subcategorias.');
            return;
        }
        
        // Atualizar estilo visual
        if (card && checkIcon) {
            if (checked) {
                card.classList.add('border-blue-500', 'bg-blue-50');
                card.classList.remove('border-gray-200');
                checkIcon.classList.add('border-blue-500', 'bg-blue-500');
                checkIcon.classList.remove('border-gray-300');
                checkIcon.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
            } else {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
                checkIcon.classList.remove('border-blue-500', 'bg-blue-500');
                checkIcon.classList.add('border-gray-300');
                checkIcon.innerHTML = '';
            }
        }
        
        // Atualizar contador
        if (contador) {
            const totalAtual = document.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`).length;
            contador.textContent = totalAtual;
        }
    };
    
    // Função para atualizar contador de subcategorias
    window.atualizarContadorSubcategorias = function(categoriaPaiId) {
        const contador = document.getElementById('contador-subcategorias-' + categoriaPaiId);
        if (contador) {
            const totalAtual = document.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`).length;
            contador.textContent = totalAtual;
        }
    };
    
    // Função para validar seleção de subcategorias antes de enviar
    window.validarSelecaoSubcategorias = function() {
        // Verificar se há container de seleção múltipla de subcategorias
        const containers = document.querySelectorAll('[data-tipo-selecao-multipla-subcategorias="true"]');
        
        if (containers.length === 0) {
            // Se não há container de seleção múltipla, não precisa validar
            return true;
        }
        
        for (const container of containers) {
            const categoriaPaiId = container.dataset.categoriaPaiId;
            const subcategoriasMarcadas = container.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`);
            
            // Verificar se o container está visível (expandido)
            const filhasContainer = document.getElementById('filhas-' + categoriaPaiId);
            if (filhasContainer && (filhasContainer.classList.contains('hidden') || filhasContainer.style.display === 'none')) {
                // Se o container não está expandido, não precisa validar
                continue;
            }
            
            if (subcategoriasMarcadas.length === 0) {
                alert('Por favor, selecione pelo menos uma subcategoria.');
                return false;
            }
            
            if (subcategoriasMarcadas.length > 3) {
                alert('Você pode selecionar no máximo 3 subcategorias.');
                return false;
            }
        }
        
        return true;
    };
    
    // Inicializar contadores e estados ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Contar subcategorias já marcadas para seleção múltipla de subcategorias
        document.querySelectorAll('[data-tipo-selecao-multipla-subcategorias="true"]').forEach(container => {
            const categoriaPaiId = container.dataset.categoriaPaiId;
            
            // Atualizar contador
            atualizarContadorSubcategorias(categoriaPaiId);
            
            // Atualizar estado visual das subcategorias já selecionadas
            const checkboxesMarcados = container.querySelectorAll('.subcategoria-checkbox-manutencao:checked');
            checkboxesMarcados.forEach(checkbox => {
                const categoriaFilhaId = checkbox.dataset.categoriaFilha;
                const subcategoriaId = checkbox.dataset.subcategoria;
                const wrapper = checkbox.closest('.subcategoria-item-wrapper');
                const card = wrapper?.querySelector('.subcategoria-card-manutencao');
                const checkIcon = wrapper?.querySelector('.subcategoria-check-manutencao');
                
                if (card && checkIcon) {
                    card.classList.add('border-blue-500', 'bg-blue-50');
                    card.classList.remove('border-gray-200');
                    checkIcon.classList.add('border-blue-500', 'bg-blue-500');
                    checkIcon.classList.remove('border-gray-300');
                    checkIcon.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                }
            });
        });
        
        // Adicionar listener direto nos checkboxes para garantir que funcione
        // Usar event delegation para elementos que podem ser adicionados dinamicamente
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('subcategoria-checkbox-manutencao')) {
                const checkbox = e.target;
                const categoriaPaiId = checkbox.dataset.categoriaPai;
                const categoriaFilhaId = checkbox.dataset.categoriaFilha;
                const subcategoriaId = checkbox.dataset.subcategoria;
                if (categoriaPaiId && categoriaFilhaId && subcategoriaId) {
                    toggleSubcategoriaManual(categoriaPaiId, categoriaFilhaId, subcategoriaId, checkbox.checked);
                }
            }
        });
        
        // Checkbox agora é clicável apenas diretamente, não precisa de código adicional
        
        // Adicionar validação ao formulário antes de enviar
        const form = document.querySelector('form[action*="etapa/2"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validarSelecaoSubcategorias()) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
    
    // === ETAPA 2: Função para expandir/colapsar categoria pai (com filhas) ===
    window.toggleCategoriaPaiManual = function(categoriaPaiId) {
        const container = document.getElementById('filhas-' + categoriaPaiId);
        const chevron = document.getElementById('chevron-toggle-pai-' + categoriaPaiId);
        
        if (!container) return;
        
        if (container.classList.contains('hidden') || container.style.display === 'none') {
            // Expandir
            container.classList.remove('hidden');
            container.style.display = 'block';
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        } else {
            // Colapsar
            container.classList.add('hidden');
            container.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }
    };
    
    // === ETAPA 2: Função para expandir/colapsar categoria (simples ou filha) ===
    window.toggleCategoriaManual = function(categoriaId) {
        const container = document.getElementById('descricao-cat-' + categoriaId);
        const chevron = document.getElementById('chevron-toggle-' + categoriaId);
        
        if (!container) return;
        
        if (container.classList.contains('hidden')) {
            // Expandir
            container.classList.remove('hidden');
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        } else {
            // Colapsar
            container.classList.add('hidden');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }
    };
    
    // === ETAPA 2: Click no card da categoria expande/colapsa ===
    document.addEventListener('click', function(e) {
        // Ignorar cliques em botões de condições gerais e botões de toggle
        if (e.target.closest('.btn-condicoes-gerais') || e.target.closest('.btn-toggle-categoria-pai') || e.target.closest('.btn-toggle-categoria')) {
            return;
        }
        
        // Click no card da categoria pai
        const categoriaPaiContainer = e.target.closest('.categoria-pai-container');
        if (categoriaPaiContainer) {
            const cardPai = categoriaPaiContainer.querySelector(':scope > div.border-2');
            if (cardPai && cardPai.contains(e.target)) {
                const categoriaPaiId = categoriaPaiContainer.dataset.categoriaPaiId;
                if (categoriaPaiId) {
                    toggleCategoriaPaiManual(categoriaPaiId);
                }
                return;
            }
        }
        
        // Click no card da categoria filha
        const categoriaContainer = e.target.closest('.categoria-container');
        if (categoriaContainer) {
            const cardFilha = categoriaContainer.querySelector(':scope > div.border-2');
            if (cardFilha && cardFilha.contains(e.target)) {
                const categoriaId = categoriaContainer.dataset.categoriaId;
                if (categoriaId) {
                    toggleCategoriaManual(categoriaId);
                }
            }
        }
    });
    
    // === ETAPA 2: Seleção de Subcategoria ===
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('subcategoria-radio')) {
            const allSubCards = document.querySelectorAll('.subcategoria-card');
            const allSubChecks = document.querySelectorAll('.subcategoria-check');
            
            // Remover seleção de todos
            allSubCards.forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });
            allSubChecks.forEach(check => {
                check.classList.remove('bg-blue-500', 'border-blue-500');
                check.classList.add('border-gray-300');
            });
            
            // Adicionar seleção ao card pai do radio selecionado
            const selectedCard = e.target.closest('label').querySelector('.subcategoria-card');
            const selectedCheck = e.target.closest('label').querySelector('.subcategoria-check');
            
            if (selectedCard) {
                selectedCard.classList.remove('border-gray-200');
                selectedCard.classList.add('border-blue-500', 'bg-blue-50');
            }
            if (selectedCheck) {
                selectedCheck.classList.remove('border-gray-300');
                selectedCheck.classList.add('bg-blue-500', 'border-blue-500');
            }
            
            // Marcar também a categoria pai
            const categoriaRadio = e.target.closest('label').querySelector('.categoria-radio');
            if (categoriaRadio) {
                categoriaRadio.checked = true;
            }
        }
    });
    
    // Click no card da subcategoria seleciona o radio
    document.addEventListener('click', function(e) {
        // Ignorar cliques em botões de condições gerais
        if (e.target.closest('.btn-condicoes-gerais')) {
            return;
        }
        
        const subCard = e.target.closest('.subcategoria-card');
        if (subCard) {
            e.preventDefault();
            e.stopPropagation();
            const label = subCard.closest('label');
            const subcategoriaRadio = label ? label.querySelector('.subcategoria-radio') : null;
            const categoriaRadio = label ? label.querySelector('.categoria-radio') : null;
            if (subcategoriaRadio) {
                subcategoriaRadio.checked = true;
                subcategoriaRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (categoriaRadio) {
                categoriaRadio.checked = true;
            }
        }
        
        // Também tratar cards de Manutenção e Prevenção
        const manutencaoCard = e.target.closest('.subcategoria-card-manutencao');
        if (manutencaoCard) {
            const wrapper = manutencaoCard.closest('.subcategoria-item-wrapper');
            if (wrapper) {
                const subcategoriaId = wrapper.dataset.subcategoriaId;
                const categoriaFilhaId = wrapper.dataset.categoriaFilhaId;
                if (subcategoriaId && categoriaFilhaId) {
                    toggleSubcategoriaManutencao(categoriaFilhaId, subcategoriaId);
                }
            }
        }
    }, true);
    
    // Inicializar estados das subcategorias selecionadas (sem expandir automaticamente)
    document.addEventListener('DOMContentLoaded', function() {
        // Atualizar visual das subcategorias selecionadas sem expandir as categorias
        document.querySelectorAll('.subcategoria-radio:checked').forEach(radio => {
            // Disparar evento para atualizar visual da seleção
            radio.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    
    // Armazenar referências dos arquivos
    let fotosArmazenadasManual = [];

    /**
     * Módulo para captura de fotos pelo celular
     * Gerencia upload de fotos via câmera do dispositivo ou seleção de arquivos
     */
    class CameraUpload {
        constructor(options = {}) {
            this.inputFileId = options.inputFileId || 'fotos';
            this.inputCameraId = options.inputCameraId || 'fotos-camera';
            this.previewId = options.previewId || 'fotos-preview';
            this.modalId = options.modalId || 'modal-foto';
            this.loadingId = options.loadingId || 'fotos-loading';
            this.maxFiles = options.maxFiles || 5;
            this.maxSize = options.maxSize || 10 * 1024 * 1024; // 10MB padrão
            this.fotosArmazenadas = options.fotosArmazenadas || [];
            
            // Tornar métodos disponíveis globalmente IMEDIATAMENTE
            window.abrirModalFoto = () => this.abrirModal();
            window.fecharModalFoto = () => this.fecharModal();
            window.escolherCamera = () => this.escolherCamera();
            window.escolherArquivo = () => this.escolherArquivo();
            window.previewPhotos = (input) => this.previewPhotos(input);
            window.removePhoto = (fotoId) => this.removePhoto(fotoId);
            window.combinarFotosAntesEnvio = (event) => this.combinarFotosAntesEnvio(event);
            
            this.init();
        }

        init() {
            // Fechar modal ao clicar fora
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupListeners());
            } else {
                this.setupListeners();
            }
        }
        
        setupListeners() {
            const modal = document.getElementById(this.modalId);
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.fecharModal();
                    }
                });
            }
            
            // Adicionar listeners nos inputs
            const inputFotos = document.getElementById(this.inputFileId);
            const inputCamera = document.getElementById(this.inputCameraId);
            
            if (inputFotos && !inputFotos.dataset.listenerAdicionado) {
                inputFotos.addEventListener('change', (e) => {
                    this.previewPhotos(e.target);
                    this.fecharModal();
                });
                inputFotos.dataset.listenerAdicionado = 'true';
            }
            
            if (inputCamera && !inputCamera.dataset.listenerAdicionado) {
                inputCamera.addEventListener('change', (e) => {
                    if (e.target.files && e.target.files.length > 0) {
                        this.previewPhotos(e.target);
                        this.fecharModal();
                    }
                });
                inputCamera.dataset.listenerAdicionado = 'true';
            }
            
            // Inicializar estado do botão
            this.atualizarBotaoAdicionarFotos();
        }

        /**
         * Abrir modal para escolher entre câmera ou arquivos
         */
        abrirModal() {
            if (this.fotosArmazenadas.length >= this.maxFiles) {
                alert(`Você já adicionou o máximo de ${this.maxFiles} fotos`);
                return;
            }
            
            const modal = document.getElementById(this.modalId);
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        /**
         * Fechar modal
         */
        fecharModal() {
            const modal = document.getElementById(this.modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        /**
         * Escolher câmera para capturar foto
         */
        escolherCamera() {
            const inputCamera = document.getElementById(this.inputCameraId);
            if (inputCamera) {
                inputCamera.click();
            }
        }

        /**
         * Escolher arquivo do dispositivo
         */
        escolherArquivo() {
            const inputArquivo = document.getElementById(this.inputFileId);
            if (inputArquivo) {
                inputArquivo.click();
            }
        }

        /**
         * Preview das fotos selecionadas
         */
        previewPhotos(input) {
            const preview = document.getElementById(this.previewId);
            const loadingOverlay = document.getElementById(this.loadingId);
            const inputArquivo = document.getElementById(this.inputFileId);
            const inputCamera = document.getElementById(this.inputCameraId);

            // Usar apenas os arquivos do input que foi alterado
            let allFiles = [];
            if (input && input.files && input.files.length > 0) {
                allFiles = Array.from(input.files);
            }

            // Filtrar apenas imagens
            const todasFotos = allFiles.filter(f => f.type.startsWith('image/'));

            // Detectar apenas as NOVAS fotos
            const novasFotos = todasFotos.filter(novaFoto => {
                return !this.fotosArmazenadas.some(fotoArmazenada => {
                    const mesmoTamanho = fotoArmazenada.file.size === novaFoto.size;
                    const mesmoTimestamp = fotoArmazenada.file.lastModified === novaFoto.lastModified;
                    if (input === inputCamera) {
                        return mesmoTamanho && mesmoTimestamp;
                    } else {
                        return mesmoTamanho && mesmoTimestamp && fotoArmazenada.file.name === novaFoto.name;
                    }
                });
            });

            if (novasFotos.length > 0) {
                // Verificar limite
                const totalAposAdicao = this.fotosArmazenadas.length + novasFotos.length;
                const fotosParaAdicionar = totalAposAdicao > this.maxFiles 
                    ? novasFotos.slice(0, this.maxFiles - this.fotosArmazenadas.length)
                    : novasFotos;
                
                if (fotosParaAdicionar.length === 0) {
                    alert(`Você já adicionou o máximo de ${this.maxFiles} fotos`);
                    return;
                }

                if (preview) {
                    preview.classList.remove('hidden');
                }

                // Mostrar loading
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                }

                let fotosProcessadas = 0;
                const totalFotos = fotosParaAdicionar.length;

                const verificarConclusao = () => {
                    if (fotosProcessadas === totalFotos) {
                        if (loadingOverlay) {
                            loadingOverlay.classList.add('hidden');
                        }
                        
                        // Limpar inputs
                        if (input === inputArquivo && inputArquivo) {
                            inputArquivo.value = '';
                        }
                        if (input === inputCamera && inputCamera) {
                            inputCamera.value = '';
                        }
                        
                        this.atualizarBotaoAdicionarFotos();
                    }
                };

                fotosParaAdicionar.forEach((file, index) => {
                    // Validar tamanho
                    if (file.size > this.maxSize) {
                        alert(`Arquivo ${file.name} excede o tamanho máximo de ${this.maxSize / 1024 / 1024}MB`);
                        fotosProcessadas++;
                        verificarConclusao();
                        return;
                    }

                    const fotoId = Date.now() + '_' + index + '_' + Math.random().toString(36).substr(2, 9);
                    
                    // Armazenar referência do arquivo
                    this.fotosArmazenadas.push({
                        id: fotoId,
                        file: file,
                        input: input === inputArquivo ? 'arquivo' : 'camera'
                    });

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const fotoAindaExiste = this.fotosArmazenadas.some(f => f.id === fotoId);
                        if (!fotoAindaExiste) {
                            fotosProcessadas++;
                            verificarConclusao();
                            return;
                        }

                        const fotoExisteDOM = document.querySelector(`[data-foto-id="${fotoId}"]`);
                        if (fotoExisteDOM) {
                            fotosProcessadas++;
                            verificarConclusao();
                            return;
                        }

                        const div = document.createElement('div');
                        div.className = 'relative';
                        div.setAttribute('data-foto-id', fotoId);
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                            <button type="button" onclick="window.removePhoto('${fotoId}')" 
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 z-10">
                                ×
                            </button>
                        `;
                        if (preview) {
                            preview.appendChild(div);
                        }

                        fotosProcessadas++;
                        verificarConclusao();
                    };
                    reader.onerror = () => {
                        this.fotosArmazenadas = this.fotosArmazenadas.filter(f => f.id !== fotoId);
                        fotosProcessadas++;
                        verificarConclusao();
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                if (preview && this.fotosArmazenadas.length === 0) {
                    preview.classList.add('hidden');
                }
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden');
                }
            }
        }

        /**
         * Remover foto do preview
         */
        removePhoto(fotoId) {
            const fotoIndex = this.fotosArmazenadas.findIndex(f => f.id === fotoId);
            if (fotoIndex === -1) return;

            this.fotosArmazenadas.splice(fotoIndex, 1);

            const fotoElement = document.querySelector(`[data-foto-id="${fotoId}"]`);
            if (fotoElement && fotoElement.parentNode) {
                fotoElement.remove();
            }

            const preview = document.getElementById(this.previewId);
            if (this.fotosArmazenadas.length === 0 && preview) {
                preview.classList.add('hidden');
            }

            this.atualizarBotaoAdicionarFotos();
        }

        /**
         * Atualizar estado do botão de adicionar fotos
         */
        atualizarBotaoAdicionarFotos() {
            const botaoAdicionar = document.querySelector('div[onclick="abrirModalFoto()"]');
            if (!botaoAdicionar) return;

            if (this.fotosArmazenadas.length >= this.maxFiles) {
                botaoAdicionar.style.opacity = '0.5';
                botaoAdicionar.style.cursor = 'not-allowed';
                botaoAdicionar.style.pointerEvents = 'none';
                botaoAdicionar.onclick = null;
            } else {
                botaoAdicionar.style.opacity = '1';
                botaoAdicionar.style.cursor = 'pointer';
                botaoAdicionar.style.pointerEvents = 'auto';
                botaoAdicionar.onclick = this.abrirModal.bind(this);
            }
        }

        /**
         * Combinar fotos de ambos os inputs antes de enviar o formulário
         */
        combinarFotosAntesEnvio(event) {
            const loadingOverlay = document.getElementById(this.loadingId);
            if (loadingOverlay) {
                loadingOverlay.classList.remove('hidden');
            }

            const btnContinuar = document.getElementById('btn-continuar');
            if (btnContinuar) {
                btnContinuar.disabled = true;
                btnContinuar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
            }

            const inputArquivo = document.getElementById(this.inputFileId);

            if (inputArquivo && this.fotosArmazenadas.length > 0) {
                const dt = new DataTransfer();

                this.fotosArmazenadas.forEach(foto => {
                    dt.items.add(foto.file);
                });

                inputArquivo.files = dt.files;
            }
        }
    }

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('fotos') || document.getElementById('fotos-camera')) {
                window.cameraUpload = new CameraUpload({
                    inputFileId: 'fotos',
                    inputCameraId: 'fotos-camera',
                    previewId: 'fotos-preview',
                    modalId: 'modal-foto',
                    loadingId: 'fotos-loading',
                    maxFiles: 5,
                    maxSize: 10 * 1024 * 1024,
                    fotosArmazenadas: fotosArmazenadasManual
                });
            }
        });
    } else {
        // DOM já está pronto, inicializar imediatamente
        if (document.getElementById('fotos') || document.getElementById('fotos-camera')) {
            window.cameraUpload = new CameraUpload({
                inputFileId: 'fotos',
                inputCameraId: 'fotos-camera',
                previewId: 'fotos-preview',
                modalId: 'modal-foto',
                loadingId: 'fotos-loading',
                maxFiles: 5,
                maxSize: 10 * 1024 * 1024,
                fotosArmazenadas: fotosArmazenadasManual
            });
        }
    }
    
    // Função para toggle do resumo das etapas
    function toggleResumoEtapas() {
        const conteudo = document.getElementById('resumo-conteudo');
        const chevron = document.getElementById('resumo-chevron');
        
        if (conteudo && chevron) {
            const isHidden = conteudo.classList.contains('hidden');
            if (isHidden) {
                conteudo.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                conteudo.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }
    }
    
    // Adicionar event listener ao botão do resumo
    function inicializarResumoEtapas() {
        const btnResumo = document.getElementById('btn-resumo-etapas');
        if (btnResumo) {
            // Remover event listeners anteriores se existirem
            const novoBtn = btnResumo.cloneNode(true);
            btnResumo.parentNode.replaceChild(novoBtn, btnResumo);
            
            // Adicionar novo event listener
            novoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleResumoEtapas();
                return false;
            });
            
            // Também adicionar via onclick como fallback
            novoBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleResumoEtapas();
                return false;
            };
        }
    }
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarResumoEtapas);
    } else {
        inicializarResumoEtapas();
    }
    
    // Também disponibilizar globalmente para compatibilidade
    window.toggleResumoEtapas = toggleResumoEtapas;
    
    
    // Sistema de horários
    let horariosEscolhidos = [];
    
    // === Sistema de seleção de data com modal e calendário visual (NORMAL) ===
    function formatarDataParaExibicao(dataStr) {
        if (!dataStr) return 'Selecione uma Data';
        const [ano, mes, dia] = dataStr.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    function criarCalendarioVisual(containerId, minDate, maxDate, dataMinimaAttr, onSelect) {
        const container = document.getElementById(containerId);
        if (!container) return null;
        
        let currentMonth = new Date();
        let selectedDate = null;
        
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        
        function renderizarCalendario() {
            const ano = currentMonth.getFullYear();
            const mes = currentMonth.getMonth();
            
            const primeiroDia = new Date(ano, mes, 1);
            const ultimoDia = new Date(ano, mes + 1, 0);
            const primeiroDiaSemana = primeiroDia.getDay();
            const totalDias = ultimoDia.getDate();
            
            let html = `
                <div class="calendar-header flex items-center justify-between mb-4">
                    <button type="button" class="calendar-nav-btn p-2 hover:bg-gray-100 rounded-lg transition-colors" data-direction="-1">
                        <i class="fas fa-chevron-left text-gray-600"></i>
                    </button>
                    <div class="text-lg font-semibold text-gray-800">${meses[mes]} ${ano}</div>
                    <button type="button" class="calendar-nav-btn p-2 hover:bg-gray-100 rounded-lg transition-colors" data-direction="1">
                        <i class="fas fa-chevron-right text-gray-600"></i>
                    </button>
                </div>
                <div class="calendar-grid">
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        ${diasSemana.map(dia => `<div class="text-center text-xs font-semibold text-gray-600 py-1">${dia}</div>`).join('')}
                    </div>
                    <div class="grid grid-cols-7 gap-1" id="calendar-days-${containerId}">
            `;
            
            // Espaços vazios antes do primeiro dia
            for (let i = 0; i < primeiroDiaSemana; i++) {
                html += '<div class="p-2"></div>';
            }
            
            // Dias do mês
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            
            for (let dia = 1; dia <= totalDias; dia++) {
                const dataAtual = new Date(ano, mes, dia);
                const dataAtualStr = dataAtual.toISOString().split('T')[0];
                const diaSemana = dataAtual.getDay();
                const isFimDeSemana = diaSemana === 0 || diaSemana === 6;
                const isToday = dataAtual.getTime() === hoje.getTime();
                const isSelected = selectedDate && selectedDate.toISOString().split('T')[0] === dataAtualStr;
                
                let disabled = false;
                let disabledClass = '';
                
                if (minDate && dataAtual < new Date(minDate)) disabled = true;
                if (maxDate && dataAtual > new Date(maxDate)) disabled = true;
                if (isFimDeSemana) disabled = true;
                
                if (dataMinimaAttr) {
                    const dataMinima = new Date(dataMinimaAttr + 'T12:00:00');
                    dataMinima.setHours(0, 0, 0, 0);
                    if (dataAtual < dataMinima) disabled = true;
                }
                
                if (disabled) {
                    disabledClass = 'text-gray-300 bg-gray-50 cursor-not-allowed';
                } else if (isSelected) {
                    disabledClass = 'bg-green-600 text-white font-semibold';
                } else if (isToday) {
                    disabledClass = 'bg-green-100 text-green-700 font-semibold hover:bg-green-200';
            } else {
                    disabledClass = 'text-gray-700 hover:bg-gray-100';
                }
                
                html += `
                    <button type="button" 
                            class="calendar-day p-2 text-sm rounded-lg transition-colors ${disabledClass}" 
                            data-date="${dataAtualStr}"
                            ${disabled ? 'disabled' : ''}>
                        ${dia}
                    </button>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
            
            // Event listeners
            container.querySelectorAll('.calendar-nav-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const direction = parseInt(this.getAttribute('data-direction'));
                    currentMonth.setMonth(currentMonth.getMonth() + direction);
                    renderizarCalendario();
                });
            });
            
            container.querySelectorAll('.calendar-day:not(:disabled)').forEach(btn => {
                btn.addEventListener('click', function() {
                    const dateStr = this.getAttribute('data-date');
                    selectedDate = new Date(dateStr + 'T12:00:00');
                    
                    // Atualizar visual
                    container.querySelectorAll('.calendar-day').forEach(b => {
                        b.classList.remove('bg-green-600', 'text-white');
                        if (!b.disabled && !b.classList.contains('bg-green-100')) {
                            b.classList.add('text-gray-700');
                        }
                    });
                    
                    this.classList.add('bg-green-600', 'text-white', 'font-semibold');
                    this.classList.remove('text-gray-700', 'bg-green-100', 'text-green-700');
                    
                    if (onSelect) {
                        onSelect(dateStr);
                    }
                });
            });
        }
        
        renderizarCalendario();
        
        return {
            getSelectedDate: () => selectedDate ? selectedDate.toISOString().split('T')[0] : null,
            setSelectedDate: (dateStr) => {
                if (dateStr) {
                    selectedDate = new Date(dateStr + 'T12:00:00');
                    const dataObj = new Date(dateStr);
                    currentMonth = new Date(dataObj.getFullYear(), dataObj.getMonth(), 1);
                }
                renderizarCalendario();
            }
        };
    }
    
    const btnSelecionarData = document.getElementById('btn-selecionar-data');
    const modalData = document.getElementById('modal-data');
    const dataSelecionadaHidden = document.getElementById('data_selecionada');
    const textoBotaoData = document.getElementById('texto-botao-data');
    const btnFecharModalData = document.getElementById('fechar-modal-data');
    const calendarioContainer = document.getElementById('calendario-container-data');
    
    let calendarioInstance = null;
    
    function resetarBotaoData() {
        if (dataSelecionadaHidden) {
            dataSelecionadaHidden.value = '';
        }
        if (textoBotaoData) {
            textoBotaoData.textContent = 'Selecione uma Data';
        }
    }
    
    if (btnSelecionarData && modalData && calendarioContainer) {
        const dataMinimaAttr = dataSelecionadaHidden ? dataSelecionadaHidden.getAttribute('data-min-date') : null;
        const minDate = dataMinimaAttr || '<?= date('Y-m-d', strtotime('+1 day')) ?>';
        const maxDate = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
        
        btnSelecionarData.addEventListener('click', function() {
            modalData.classList.remove('hidden');
            
            if (!calendarioInstance) {
                calendarioInstance = criarCalendarioVisual(
                    'calendario-container-data',
                    minDate,
                    maxDate,
                    dataMinimaAttr,
                    function(dateStr) {
                        const dataObj = new Date(dateStr + 'T12:00:00');
                        const diaDaSemana = dataObj.getDay();
                        
                        if (diaDaSemana === 0 || diaDaSemana === 6) {
                            const nomeDia = diaDaSemana === 0 ? 'domingo' : 'sábado';
            alert('⚠️ Atendimentos não são realizados aos fins de semana.\n\nA data selecionada é um ' + nomeDia + '.\nPor favor, selecione um dia útil (segunda a sexta-feira).');
            return;
        }
        
                        // Atualizar valor imediatamente e garantir que está salvo
                        const inputHidden = document.getElementById('data_selecionada');
                        if (inputHidden) {
                            inputHidden.value = dateStr;
                            inputHidden.setAttribute('value', dateStr);
                            // Garantir que o valor está realmente salvo
                            console.log('Data salva no input hidden:', inputHidden.value);
                        } else {
                            console.error('Input hidden data_selecionada não encontrado!');
                        }
                        
                        // Também atualizar a variável de escopo se existir
                        if (dataSelecionadaHidden) {
                            dataSelecionadaHidden.value = dateStr;
                        }
                        
                        if (textoBotaoData) {
                            textoBotaoData.textContent = formatarDataParaExibicao(dateStr);
                        }
                        
                        // Fechar modal imediatamente após atualizar
                        fecharModal();
                    }
                );
            } else {
                if (dataSelecionadaHidden && dataSelecionadaHidden.value) {
                    calendarioInstance.setSelectedDate(dataSelecionadaHidden.value);
            }
        }
    });

        function fecharModal() {
            modalData.classList.add('hidden');
        }
        
        if (btnFecharModalData) btnFecharModalData.addEventListener('click', fecharModal);
        
        modalData.addEventListener('click', function(e) {
            if (e.target === modalData) {
                fecharModal();
            }
        });
    }

    // Função para obter a data selecionada de múltiplas fontes
    function obterDataSelecionada() {
        // 1. Tentar do input hidden
        const inputDataHidden = document.getElementById('data_selecionada');
        if (inputDataHidden && inputDataHidden.value) {
            return inputDataHidden.value;
        }
        
        // 2. Tentar do texto do botão
        const textoBotao = document.getElementById('texto-botao-data');
        if (textoBotao && textoBotao.textContent && textoBotao.textContent !== 'Selecione uma Data') {
            // Converter formato DD/MM/YYYY para YYYY-MM-DD
            const partes = textoBotao.textContent.trim().split('/');
            if (partes.length === 3) {
                const dia = partes[0].padStart(2, '0');
                const mes = partes[1].padStart(2, '0');
                const ano = partes[2];
                const dataFormatada = `${ano}-${mes}-${dia}`;
                
                // Atualizar o input hidden também
                if (inputDataHidden) {
                    inputDataHidden.value = dataFormatada;
                    inputDataHidden.setAttribute('value', dataFormatada);
                }
                
                return dataFormatada;
            }
        }
        
        return null;
    }
    
    // Adicionar event listeners aos horários quando o DOM estiver pronto
    function inicializarHorarios() {
    document.querySelectorAll('.horario-radio').forEach(radio => {
            // Remover listeners anteriores se existirem
            const novoRadio = radio.cloneNode(true);
            radio.parentNode.replaceChild(novoRadio, radio);
            
            novoRadio.addEventListener('change', function() {
                // Usar setTimeout para garantir que qualquer atualização de data tenha sido processada
                setTimeout(() => {
                    const data = obterDataSelecionada();
            const horario = this.value;
                    
                    console.log('Verificando horário - Data:', data, 'Horário:', horario);
            
            if (data && horario) {
                const horarioCompleto = `${formatarData(data)} - ${horario}`;
                
                if (!horariosEscolhidos.includes(horarioCompleto) && horariosEscolhidos.length < 3) {
                    horariosEscolhidos.push(horarioCompleto);
                    horariosEscolhidos = ordenarHorarios(horariosEscolhidos);
                    atualizarListaHorarios();
                    // Salvar automaticamente após adicionar horário
                    if (window.formPersistence) {
                        window.formPersistence.salvarEtapa();
                    }
                            
                            // Resetar botão de data após selecionar horário
                            resetarBotaoData();
                }
                
                // Limpar seleção de radio
                this.checked = false;
            } else if (!data) {
                alert('Selecione uma data primeiro');
                this.checked = false;
            }
                }, 100);
        });
    });
    }
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarHorarios);
    } else {
        inicializarHorarios();
    }
    
    document.querySelectorAll('.horario-card').forEach(card => {
        card.addEventListener('click', function() {
            const label = this.closest('label');
            const radio = label ? label.querySelector('.horario-radio') : null;
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    function atualizarListaHorarios() {
        const container = document.getElementById('horarios-selecionados');
        const lista = document.getElementById('lista-horarios');
        const contador = document.getElementById('contador-horarios');
        const btnContinuar = document.getElementById('btn-continuar');
        
        if (horariosEscolhidos.length > 0) {
            container.classList.remove('hidden');
            contador.textContent = horariosEscolhidos.length;
            
            lista.innerHTML = '';
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
                lista.appendChild(div);
            });
            
            btnContinuar.disabled = false;
            btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
            btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
            } else {
                container.classList.add('hidden');
                btnContinuar.disabled = true;
                btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
            }
            
            // Salvar automaticamente após atualizar lista
            if (window.formPersistence) {
                window.formPersistence.salvarEtapa();
            }
        }
    
    window.removerHorario = function(index) {
        horariosEscolhidos.splice(index, 1);
        atualizarListaHorarios();
        // Salvar automaticamente após remover horário
        if (window.formPersistence) {
            window.formPersistence.salvarEtapa();
        }
    };
    
    function formatarData(data) {
        const [ano, mes, dia] = data.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Função para ordenar horários por data (crescente) e hora (crescente)
    function ordenarHorarios(horarios) {
        return horarios.sort((a, b) => {
            // Formato: "DD/MM/YYYY - HH:MM-HH:MM"
            const [dataA, faixaA] = a.split(' - ');
            const [dataB, faixaB] = b.split(' - ');
            
            // Converter data DD/MM/YYYY para YYYY-MM-DD para comparação
            const [diaA, mesA, anoA] = dataA.split('/');
            const [diaB, mesB, anoB] = dataB.split('/');
            
            const dataComparavelA = `${anoA}-${mesA}-${diaA}`;
            const dataComparavelB = `${anoB}-${mesB}-${diaB}`;
            
            // Primeiro compara por data
            if (dataComparavelA !== dataComparavelB) {
                return dataComparavelA.localeCompare(dataComparavelB);
            }
            
            // Se mesma data, compara pelo horário inicial
            const horaInicialA = faixaA.split('-')[0].trim();
            const horaInicialB = faixaB.split('-')[0].trim();
            
            return horaInicialA.localeCompare(horaInicialB);
        });
    }
    
    // Salvar horários antes de enviar
    const formEtapa4 = document.querySelector('form[action*="etapa/4"]');
    if (formEtapa4) {
        formEtapa4.addEventListener('submit', function(e) {
            const horariosFormatados = horariosEscolhidos.map(horario => {
                const [dataStr, faixaHorario] = horario.split(' - ');
                const [dia, mes, ano] = dataStr.split('/');
                const [horarioInicial, horarioFinal] = faixaHorario.split('-');
                // Formato: "2025-10-29 08:00:00-11:00:00"
                return `${ano}-${mes}-${dia} ${horarioInicial.trim()}:00-${horarioFinal.trim()}:00`;
            });
            
            const inputHorarios = document.createElement('input');
            inputHorarios.type = 'hidden';
            inputHorarios.name = 'horarios_opcoes';
            inputHorarios.value = JSON.stringify(horariosFormatados);
            this.appendChild(inputHorarios);
        });
    }
    
    // Modal de termos
    function abrirModalTermos() {
        document.getElementById('modal-termos').classList.remove('hidden');
    }
    
    function fecharModalTermos() {
        document.getElementById('modal-termos').classList.add('hidden');
    }
    
    // Modal de LGPD
    function abrirModalLGPD() {
        document.getElementById('modal-lgpd').classList.remove('hidden');
    }
    
    function fecharModalLGPD() {
        document.getElementById('modal-lgpd').classList.add('hidden');
    }
    
    // Fechar modal ao clicar fora dele
    document.addEventListener('DOMContentLoaded', function() {
        const modalLGPD = document.getElementById('modal-lgpd');
        if (modalLGPD) {
            modalLGPD.addEventListener('click', function(e) {
                if (e.target === modalLGPD) {
                    fecharModalLGPD();
                }
            });
        }
        
        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modalLGPD.classList.contains('hidden')) {
                fecharModalLGPD();
            }
        });
    });
    
    // Função global para fechar modal de seleção de endereços
    window.fecharModalSelecionarEndereco = function() {
        const modal = document.getElementById('modal-selecionar-endereco');
        if (modal) {
            modal.classList.add('hidden');
        }
    };
    
    // Fechar modal de seleção de endereços ao clicar fora
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modal-selecionar-endereco');
        if (modal && e.target === modal) {
            fecharModalSelecionarEndereco();
        }
    });
    
    // Funções do modal de descrição da categoria
    // Função para decodificar base64 preservando UTF-8
    function base64DecodeUTF8(str) {
        try {
            // Decodificar base64
            const decoded = atob(str);
            // Converter bytes para string UTF-8
            const bytes = new Uint8Array(decoded.length);
            for (let i = 0; i < decoded.length; i++) {
                bytes[i] = decoded.charCodeAt(i);
            }
            // Usar TextDecoder para converter para UTF-8
            const decoder = new TextDecoder('utf-8');
            return decoder.decode(bytes);
        } catch (e) {
            // Se TextDecoder não estiver disponível, usar método alternativo
            try {
                const decoded = atob(str);
                return decodeURIComponent(
                    decoded.split('').map(function(c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join('')
                );
            } catch (e2) {
                // Última tentativa: decodificação simples
                try {
                    return atob(str);
                } catch (e3) {
                    return str;
                }
            }
        }
    }
    
    // Modal de Condições Gerais
    window.abrirModalCondicoesGerais = function(nome, condicoesGerais) {
        document.getElementById('modal-condicoes-titulo').textContent = nome;
        const conteudo = document.getElementById('modal-condicoes-conteudo');
        let html = condicoesGerais;
        
        // Se for base64, decodificar preservando UTF-8
        if (typeof condicoesGerais === 'string' && condicoesGerais.length > 0) {
            try {
                // Tentar decodificar base64 com suporte UTF-8
                html = base64DecodeUTF8(condicoesGerais);
            } catch (e) {
                // Se não for base64, usar como está
                html = condicoesGerais;
            }
        }
        
        conteudo.innerHTML = html || '';
        document.getElementById('modal-condicoes-gerais').classList.remove('hidden');
    };
    
    window.fecharModalCondicoesGerais = function() {
        document.getElementById('modal-condicoes-gerais').classList.add('hidden');
    };
    
    // Event listener para botões de condições gerais
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-condicoes-gerais');
        if (btn) {
            e.stopPropagation();
            const nome = btn.getAttribute('data-nome');
            const condicoes = btn.getAttribute('data-condicoes');
            if (nome && condicoes) {
                abrirModalCondicoesGerais(nome, condicoes);
            }
        }
    });
    
    // Fechar modal de condições gerais ao clicar fora dele
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modal-condicoes-gerais');
        if (e.target === modal) {
            fecharModalCondicoesGerais();
        }
    });
    
    // Modal de Descrição (mantido para compatibilidade)
    function abrirModalDescricaoCategoria(nome, descricao) {
        document.getElementById('modal-descricao-titulo').textContent = nome;
        document.getElementById('modal-descricao-conteudo').textContent = descricao;
        document.getElementById('modal-descricao-categoria').classList.remove('hidden');
    }
    
    function fecharModalDescricaoCategoria() {
        document.getElementById('modal-descricao-categoria').classList.add('hidden');
    }
    
    // Fechar modal ao clicar fora dele
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modal-descricao-categoria');
        if (e.target === modal) {
            fecharModalDescricaoCategoria();
        }
    });
    
    // Loading overlay no envio final
    const btnFinalizar = document.getElementById('btn-finalizar');
    if (btnFinalizar) {
        const formFinalizar = btnFinalizar.closest('form');
        if (formFinalizar) {
            formFinalizar.addEventListener('submit', function(e) {
                // Verificar se o termo foi aceito
                const termoAceite = formFinalizar.querySelector('input[name="termo_aceite"]');
                const lgpdAceite = formFinalizar.querySelector('input[name="lgpd_aceite"]');
                
                if (!termoAceite || !termoAceite.checked) {
                    e.preventDefault();
                    alert('Por favor, aceite os termos para continuar.');
                    return;
                }
                
                if (!lgpdAceite || !lgpdAceite.checked) {
                    e.preventDefault();
                    alert('Por favor, aceite o termo de LGPD para continuar.');
                    return;
                }
                
                // Limpar dados do sessionStorage ao finalizar
                if (window.formPersistence) {
                    window.formPersistence.limparDados();
                }
                
                document.getElementById('loading-overlay').classList.remove('hidden');
                btnFinalizar.disabled = true;
                btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
            });
        }
    }
    
    // Sistema de seleção de tipo de atendimento emergencial
    document.addEventListener('DOMContentLoaded', function() {
        const tipoAtendimentoRadios = document.querySelectorAll('.tipo-atendimento-radio');
        const tipoAtendimentoCards = document.querySelectorAll('.tipo-atendimento-card');
        
        tipoAtendimentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const allCards = document.querySelectorAll('.tipo-atendimento-card');
                const allChecks = document.querySelectorAll('.tipo-atendimento-check');
                
                // Remover seleção de todos
                allCards.forEach(card => {
                    card.classList.remove('border-green-500', 'bg-green-50', 'border-blue-500', 'bg-blue-50');
                    card.classList.add('border-gray-200', 'bg-white');
                });
                allChecks.forEach(check => {
                    check.classList.remove('border-green-600', 'bg-green-600', 'border-blue-600', 'bg-blue-600', 'flex', 'items-center', 'justify-center');
                    check.classList.add('border-gray-300');
                    check.innerHTML = '';
                });
                
                // Adicionar seleção ao card selecionado
                const selectedCard = this.closest('label').querySelector('.tipo-atendimento-card');
                const selectedCheck = this.closest('label').querySelector('.tipo-atendimento-check');
                
                const btnContinuar = document.getElementById('btn-continuar');
                
                if (this.value === '120_minutos') {
                    if (selectedCard) {
                        selectedCard.classList.remove('border-gray-200', 'bg-white');
                        selectedCard.classList.add('border-green-500', 'bg-green-50');
                    }
                    if (selectedCheck) {
                        selectedCheck.classList.remove('border-gray-300');
                        selectedCheck.classList.add('border-green-600', 'bg-green-600', 'flex', 'items-center', 'justify-center', 'rounded-full');
                        selectedCheck.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                    }
                    // Ocultar seção de agendamento
                    const secaoAgendamento = document.getElementById('secao-agendamento-emergencial');
                    if (secaoAgendamento) {
                        secaoAgendamento.classList.add('hidden');
                    }
                    // Habilitar botão continuar (120 minutos não precisa de horários)
                    if (btnContinuar) {
                        btnContinuar.disabled = false;
                        btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
                        btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
                    }
                } else if (this.value === 'agendar') {
                    if (selectedCard) {
                        selectedCard.classList.remove('border-gray-200', 'bg-white');
                        selectedCard.classList.add('border-blue-500', 'bg-blue-50');
                    }
                    if (selectedCheck) {
                        selectedCheck.classList.remove('border-gray-300');
                        selectedCheck.classList.add('border-blue-600', 'bg-blue-600', 'flex', 'items-center', 'justify-center', 'rounded-full');
                        selectedCheck.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                    }
                    // Mostrar seção de agendamento
                    const secaoAgendamento = document.getElementById('secao-agendamento-emergencial');
                    if (secaoAgendamento) {
                        secaoAgendamento.classList.remove('hidden');
                    }
                    // Desabilitar botão até ter horários selecionados
                    if (btnContinuar) {
                        btnContinuar.disabled = true;
                        btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
                        btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
                    }
                }
            });
            
            // Se já estiver selecionado, disparar o evento
            if (radio.checked) {
                radio.dispatchEvent(new Event('change'));
            }
        });
        
        // Click no card também seleciona o radio
        tipoAtendimentoCards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const label = this.closest('label');
                const radio = label ? label.querySelector('.tipo-atendimento-radio') : null;
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            });
        });
        
        // Sistema de horários para atendimento emergencial
        let horariosEscolhidosEmergencial = [];
        
        // === Sistema de seleção de data com modal e calendário visual (EMERGENCIAL) ===
        const btnSelecionarDataEmergencial = document.getElementById('btn-selecionar-data-emergencial');
        const modalDataEmergencial = document.getElementById('modal-data-emergencial');
        const dataSelecionadaHiddenEmergencial = document.getElementById('data_selecionada_emergencial');
        const textoBotaoDataEmergencial = document.getElementById('texto-botao-data-emergencial');
        const btnFecharModalDataEmergencial = document.getElementById('fechar-modal-data-emergencial');
        const calendarioContainerEmergencial = document.getElementById('calendario-container-data-emergencial');
        
        let calendarioInstanceEmergencial = null;
        
        function resetarBotaoDataEmergencial() {
            if (dataSelecionadaHiddenEmergencial) {
                dataSelecionadaHiddenEmergencial.value = '';
            }
            if (textoBotaoDataEmergencial) {
                textoBotaoDataEmergencial.textContent = 'Selecione uma Data';
            }
        }
        
        if (btnSelecionarDataEmergencial && modalDataEmergencial && calendarioContainerEmergencial) {
            const minDate = '<?= date('Y-m-d', strtotime('+1 day')) ?>';
            const maxDate = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
            
            btnSelecionarDataEmergencial.addEventListener('click', function() {
                modalDataEmergencial.classList.remove('hidden');
                
                if (!calendarioInstanceEmergencial) {
                    calendarioInstanceEmergencial = criarCalendarioVisual(
                        'calendario-container-data-emergencial',
                        minDate,
                        maxDate,
                        null,
                        function(dateStr) {
                            const dataObj = new Date(dateStr + 'T12:00:00');
                            const diaDaSemana = dataObj.getDay();
                            
                            if (diaDaSemana === 0 || diaDaSemana === 6) {
                                const nomeDia = diaDaSemana === 0 ? 'domingo' : 'sábado';
                    alert('⚠️ Atendimentos não são realizados aos fins de semana.\n\nA data selecionada é um ' + nomeDia + '.\nPor favor, selecione um dia útil (segunda a sexta-feira).');
                    return;
                            }
                            
                            // Atualizar valor imediatamente e garantir que está salvo
                            const inputHiddenEmergencial = document.getElementById('data_selecionada_emergencial');
                            if (inputHiddenEmergencial) {
                                inputHiddenEmergencial.value = dateStr;
                                inputHiddenEmergencial.setAttribute('value', dateStr);
                                console.log('Data emergencial salva no input hidden:', inputHiddenEmergencial.value);
                            } else {
                                console.error('Input hidden data_selecionada_emergencial não encontrado!');
                            }
                            
                            // Também atualizar a variável de escopo se existir
                            if (dataSelecionadaHiddenEmergencial) {
                                dataSelecionadaHiddenEmergencial.value = dateStr;
                            }
                            
                            if (textoBotaoDataEmergencial) {
                                textoBotaoDataEmergencial.textContent = formatarDataParaExibicao(dateStr);
                            }
                            
                            // Fechar modal imediatamente após atualizar
                            fecharModalEmergencial();
                        }
                    );
                } else {
                    if (dataSelecionadaHiddenEmergencial && dataSelecionadaHiddenEmergencial.value) {
                        calendarioInstanceEmergencial.setSelectedDate(dataSelecionadaHiddenEmergencial.value);
                    }
                }
            });
            
            function fecharModalEmergencial() {
                modalDataEmergencial.classList.add('hidden');
            }
            
            if (btnFecharModalDataEmergencial) btnFecharModalDataEmergencial.addEventListener('click', fecharModalEmergencial);
            
            modalDataEmergencial.addEventListener('click', function(e) {
                if (e.target === modalDataEmergencial) {
                    fecharModalEmergencial();
                }
            });
        }

        // Função para obter a data selecionada emergencial
        function obterDataSelecionadaEmergencial() {
            // 1. Tentar do input hidden
            const inputDataHiddenEmergencial = document.getElementById('data_selecionada_emergencial');
            if (inputDataHiddenEmergencial && inputDataHiddenEmergencial.value) {
                return inputDataHiddenEmergencial.value;
            }
            
            // 2. Tentar do texto do botão
            const textoBotaoEmergencial = document.getElementById('texto-botao-data-emergencial');
            if (textoBotaoEmergencial && textoBotaoEmergencial.textContent && textoBotaoEmergencial.textContent !== 'Selecione uma Data') {
                // Converter formato DD/MM/YYYY para YYYY-MM-DD
                const partes = textoBotaoEmergencial.textContent.trim().split('/');
                if (partes.length === 3) {
                    const dia = partes[0].padStart(2, '0');
                    const mes = partes[1].padStart(2, '0');
                    const ano = partes[2];
                    const dataFormatada = `${ano}-${mes}-${dia}`;
                    
                    // Atualizar o input hidden também
                    if (inputDataHiddenEmergencial) {
                        inputDataHiddenEmergencial.value = dataFormatada;
                        inputDataHiddenEmergencial.setAttribute('value', dataFormatada);
                    }
                    
                    return dataFormatada;
                }
            }
            
            return null;
        }
        
        // Adicionar event listeners aos horários emergenciais
        function inicializarHorariosEmergenciais() {
        document.querySelectorAll('.horario-radio-emergencial').forEach(radio => {
                // Remover listeners anteriores se existirem
                const novoRadio = radio.cloneNode(true);
                radio.parentNode.replaceChild(novoRadio, radio);
                
                novoRadio.addEventListener('change', function() {
                    // Usar setTimeout para garantir que qualquer atualização de data tenha sido processada
                    setTimeout(() => {
                        const data = obterDataSelecionadaEmergencial();
                const horario = this.value;
                        
                        console.log('Verificando horário emergencial - Data:', data, 'Horário:', horario);
                
                if (data && horario) {
                    const horarioCompleto = `${formatarData(data)} - ${horario}`;
                    
                    if (!horariosEscolhidosEmergencial.includes(horarioCompleto) && horariosEscolhidosEmergencial.length < 3) {
                        horariosEscolhidosEmergencial.push(horarioCompleto);
                        horariosEscolhidosEmergencial = ordenarHorarios(horariosEscolhidosEmergencial);
                        // Salvar automaticamente após adicionar horário emergencial
                        if (window.formPersistence) {
                            window.formPersistence.salvarEtapa();
                        }
                        atualizarListaHorariosEmergencial();
                                
                                // Resetar botão de data após selecionar horário
                                resetarBotaoDataEmergencial();
                    }
                    
                    // Limpar seleção de radio
                    this.checked = false;
                } else if (!data) {
                    alert('Selecione uma data primeiro');
                    this.checked = false;
                }
                    }, 100);
            });
        });
        }
        
        // Inicializar quando o DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarHorariosEmergenciais);
        } else {
            inicializarHorariosEmergenciais();
        }
        
        document.querySelectorAll('.horario-card-emergencial').forEach(card => {
            card.addEventListener('click', function() {
                const label = this.closest('label');
                const radio = label ? label.querySelector('.horario-radio-emergencial') : null;
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            });
        });
        
        function atualizarListaHorariosEmergencial() {
            const container = document.getElementById('horarios-selecionados-emergencial');
            const lista = document.getElementById('lista-horarios-emergencial');
            const contador = document.getElementById('contador-horarios-emergencial');
            const btnContinuar = document.getElementById('btn-continuar');
            
            if (horariosEscolhidosEmergencial.length > 0) {
                container.classList.remove('hidden');
                contador.textContent = horariosEscolhidosEmergencial.length;
                
                lista.innerHTML = '';
                horariosEscolhidosEmergencial.forEach((horario, index) => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3';
                    div.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-clock text-green-600 mr-2"></i>
                            <span class="text-sm text-green-800">${horario}</span>
                        </div>
                        <button type="button" onclick="removerHorarioEmergencial(${index})" 
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    lista.appendChild(div);
                });
                
                if (btnContinuar) {
                    btnContinuar.disabled = false;
                    btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
                }
            } else {
                container.classList.add('hidden');
                if (btnContinuar) {
                    btnContinuar.disabled = true;
                    btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
                    btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
                }
            }
            
            // Salvar automaticamente após atualizar lista
            if (window.formPersistence) {
                window.formPersistence.salvarEtapa();
            }
        }
        
        window.removerHorarioEmergencial = function(index) {
            horariosEscolhidosEmergencial.splice(index, 1);
            atualizarListaHorariosEmergencial();
            // Salvar automaticamente após remover horário
            if (window.formPersistence) {
                window.formPersistence.salvarEtapa();
            }
        };
        
        // Salvar horários emergenciais antes de enviar
        const formEtapa4Emergencial = document.querySelector('form[action*="etapa/4"]');
        if (formEtapa4Emergencial) {
            formEtapa4Emergencial.addEventListener('submit', function(e) {
                // Se for emergencial e escolheu agendar, processar horários
                const tipoAtendimento = document.querySelector('input[name="tipo_atendimento_emergencial"]:checked');
                if (tipoAtendimento && tipoAtendimento.value === 'agendar' && horariosEscolhidosEmergencial.length > 0) {
                    const horariosFormatados = horariosEscolhidosEmergencial.map(horario => {
                        const [dataStr, faixaHorario] = horario.split(' - ');
                        const [dia, mes, ano] = dataStr.split('/');
                        const [horarioInicial, horarioFinal] = faixaHorario.split('-');
                        // Formato: "2025-10-29 08:00:00-11:00:00"
                        return `${ano}-${mes}-${dia} ${horarioInicial.trim()}:00-${horarioFinal.trim()}:00`;
                    });
                    
                    const inputHorarios = document.createElement('input');
                    inputHorarios.type = 'hidden';
                    inputHorarios.name = 'horarios_opcoes';
                    inputHorarios.value = JSON.stringify(horariosFormatados);
                    this.appendChild(inputHorarios);
                }
            });
        }
    });
    
    // ============================================================
    // CLASSE: FormPersistence - Salvamento automático com sessionStorage
    // ============================================================
    class FormPersistence {
        constructor(storageKey = 'solicitacao_manual_form') {
            this.storageKey = storageKey;
            this.currentEtapa = <?= $etapaAtual ?>;
            this.init();
        }
        
        init() {
            // Restaurar dados ao carregar a página
            this.restaurarDados();
            
            // Configurar salvamento automático
            this.configurarAutoSave();
            
            // Interceptar navegação
            this.configurarNavegacao();
        }
        
        // Coletar todos os dados do formulário atual
        coletarDadosFormulario() {
            const form = this.getFormularioAtual();
            if (!form) return {};
            
            const dados = {};
            
            // Coletar inputs, selects e textareas
            form.querySelectorAll('input, select, textarea').forEach(field => {
                const name = field.name;
                if (!name) return;
                
                if (field.type === 'radio' || field.type === 'checkbox') {
                    if (field.checked) {
                        if (field.type === 'checkbox') {
                            // Para checkboxes, criar array se necessário
                            if (!dados[name]) dados[name] = [];
                            dados[name].push(field.value);
                        } else {
                            dados[name] = field.value;
                        }
                    }
                } else {
                    dados[name] = field.value;
                }
            });
            
            // Para etapa 4, coletar horários do JavaScript se existirem
            if (this.currentEtapa === 4) {
                // Verificar se há horários emergenciais
                if (typeof horariosEscolhidosEmergencial !== 'undefined' && horariosEscolhidosEmergencial.length > 0) {
                    const tipoAtendimento = form.querySelector('input[name="tipo_atendimento_emergencial"]:checked');
                    if (tipoAtendimento && tipoAtendimento.value === 'agendar') {
                        const horariosFormatados = horariosEscolhidosEmergencial.map(horario => {
                            const [dataStr, faixaHorario] = horario.split(' - ');
                            const [dia, mes, ano] = dataStr.split('/');
                            const [horarioInicial, horarioFinal] = faixaHorario.split('-');
                            return `${ano}-${mes}-${dia} ${horarioInicial.trim()}:00-${horarioFinal.trim()}:00`;
                        });
                        dados['horarios_opcoes'] = JSON.stringify(horariosFormatados);
                    }
                }
                // Verificar se há horários normais
                if (typeof horariosEscolhidos !== 'undefined' && horariosEscolhidos.length > 0) {
                    const horariosFormatados = horariosEscolhidos.map(horario => {
                        const [dataStr, faixaHorario] = horario.split(' - ');
                        const [dia, mes, ano] = dataStr.split('/');
                        const [horarioInicial, horarioFinal] = faixaHorario.split('-');
                        return `${ano}-${mes}-${dia} ${horarioInicial.trim()}:00-${horarioFinal.trim()}:00`;
                    });
                    dados['horarios_opcoes'] = JSON.stringify(horariosFormatados);
                }
            }
            
            return dados;
        }
        
        // Obter formulário atual
        getFormularioAtual() {
            if (this.currentEtapa === 1) {
                return document.querySelector('form[action*="solicitacao-manual"]:not([action*="etapa"])');
            } else {
                return document.querySelector('form[action*="etapa/' + this.currentEtapa + '"]');
            }
        }
        
        // Salvar dados da etapa atual (com debounce para evitar múltiplos salvamentos)
        salvarEtapa() {
            // Limpar timer anterior se existir
            if (this.salvarEtapaTimer) {
                clearTimeout(this.salvarEtapaTimer);
            }
            
            // Aguardar 300ms antes de salvar (debounce)
            this.salvarEtapaTimer = setTimeout(() => {
            const dados = this.coletarDadosFormulario();
            const todosDados = this.obterTodosDados();
            todosDados[`etapa_${this.currentEtapa}`] = dados;
            
            try {
                sessionStorage.setItem(this.storageKey, JSON.stringify(todosDados));
                console.log(`✅ Dados da etapa ${this.currentEtapa} salvos automaticamente`);
            } catch (e) {
                console.error('Erro ao salvar no sessionStorage:', e);
            }
                
                this.salvarEtapaTimer = null;
            }, 300);
        }
        
        // Obter todos os dados salvos
        obterTodosDados() {
            try {
                const dados = sessionStorage.getItem(this.storageKey);
                return dados ? JSON.parse(dados) : {};
            } catch (e) {
                console.error('Erro ao ler sessionStorage:', e);
                return {};
            }
        }
        
        // Restaurar dados da etapa atual
        restaurarDados() {
            const todosDados = this.obterTodosDados();
            const dadosEtapa = todosDados[`etapa_${this.currentEtapa}`];
            
            if (!dadosEtapa || Object.keys(dadosEtapa).length === 0) {
                return;
            }
            
            const form = this.getFormularioAtual();
            if (!form) return;
            
            console.log(`🔄 Restaurando dados da etapa ${this.currentEtapa}`);
            
            // Restaurar cada campo
            Object.keys(dadosEtapa).forEach(name => {
                const value = dadosEtapa[name];
                
                // Para checkboxes (arrays)
                if (Array.isArray(value)) {
                    value.forEach(val => {
                        const field = form.querySelector(`input[name="${name}"][value="${val}"]`);
                        if (field && field.type === 'checkbox') {
                            field.checked = true;
                        }
                    });
                } else {
                    const field = form.querySelector(`[name="${name}"]`);
                    if (field) {
                        if (field.type === 'radio') {
                            const radio = form.querySelector(`input[name="${name}"][value="${value}"]`);
                            if (radio) radio.checked = true;
                        } else if (field.type === 'checkbox') {
                            field.checked = value === 'on' || value === '1' || value === true;
                        } else {
                            field.value = value;
                            // Disparar evento change para campos que dependem dele
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                }
            });
            
            // Para etapa 1, atualizar visibilidade do subtipo
            if (this.currentEtapa === 1) {
                const tipoImovel = form.querySelector('select[name="tipo_imovel"]');
                if (tipoImovel) {
                    tipoImovel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            
            // Para etapa 4, restaurar horários se existirem
            if (this.currentEtapa === 4) {
                const horariosOpcoes = dadosEtapa['horarios_opcoes'];
                if (horariosOpcoes) {
                    try {
                        const horarios = typeof horariosOpcoes === 'string' ? JSON.parse(horariosOpcoes) : horariosOpcoes;
                        if (Array.isArray(horarios) && horarios.length > 0) {
                            // Converter horários do formato salvo para o formato exibido
                            horarios.forEach(horario => {
                                // Formato salvo: "2025-10-29 08:00:00-11:00:00"
                                // Formato exibido: "29/10/2025 - 08:00-11:00"
                                const match = horario.match(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):\d{2}-(\d{2}):(\d{2}):\d{2}/);
                                if (match) {
                                    const [, ano, mes, dia, h1, m1, h2, m2] = match;
                                    const horarioFormatado = `${dia}/${mes}/${ano} - ${h1}:${m1}-${h2}:${m2}`;
                                    
                                    // Verificar se é emergencial ou normal
                                    const tipoAtendimento = form.querySelector('input[name="tipo_atendimento_emergencial"]:checked');
                                    if (tipoAtendimento && tipoAtendimento.value === 'agendar') {
                                        if (typeof horariosEscolhidosEmergencial !== 'undefined' && !horariosEscolhidosEmergencial.includes(horarioFormatado)) {
                                            horariosEscolhidosEmergencial.push(horarioFormatado);
                                        }
                                    } else {
                                        if (typeof horariosEscolhidos !== 'undefined' && !horariosEscolhidos.includes(horarioFormatado)) {
                                            horariosEscolhidos.push(horarioFormatado);
                                        }
                                    }
                                }
                            });
                            
                            // Atualizar listas de horários
                            if (typeof atualizarListaHorariosEmergencial === 'function') {
                                atualizarListaHorariosEmergencial();
                            }
                            if (typeof atualizarListaHorarios === 'function') {
                                atualizarListaHorarios();
                            }
                        }
                    } catch (e) {
                        console.error('Erro ao restaurar horários:', e);
                    }
                }
            }
            
            console.log(`✅ Dados da etapa ${this.currentEtapa} restaurados`);
        }
        
        // Configurar salvamento automático quando campos mudam
        configurarAutoSave() {
            const form = this.getFormularioAtual();
            if (!form) return;
            
            // Salvar quando qualquer campo mudar
            form.addEventListener('input', () => {
                this.salvarEtapa();
            });
            
            form.addEventListener('change', () => {
                this.salvarEtapa();
            });
            
            // Salvar também quando campos são alterados programaticamente
            // Mas ignorar mudanças no preview de fotos para evitar múltiplos salvamentos
            const observer = new MutationObserver((mutations) => {
                // Verificar se a mudança é no preview de fotos
                let ignorarMudanca = false;
                mutations.forEach(mutation => {
                    if (mutation.target && (
                        mutation.target.id === 'fotos-preview' ||
                        mutation.target.closest('#fotos-preview') ||
                        mutation.target.classList.contains('fotos-preview')
                    )) {
                        ignorarMudanca = true;
                    }
                });
                
                // Só salvar se não for mudança no preview de fotos
                if (!ignorarMudanca) {
                this.salvarEtapa();
                }
            });
            
            observer.observe(form, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['value', 'checked', 'selected']
            });
        }
        
        // Configurar interceptação de navegação
        configurarNavegacao() {
            // Interceptar cliques nos botões "Voltar"
            document.addEventListener('click', (e) => {
                const link = e.target.closest('.btn-voltar-etapa');
                if (!link) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                // Salvar antes de navegar
                this.salvarEtapa();
                
                // Navegar
                const urlDestino = link.href;
                setTimeout(() => {
                    window.location.href = urlDestino;
                }, 100);
                
                return false;
            });
            
            // Interceptar submit do formulário (ao continuar)
            const form = this.getFormularioAtual();
            if (form) {
                form.addEventListener('submit', () => {
                    this.salvarEtapa();
                });
            }
        }
        
        // Limpar todos os dados salvos (chamar ao finalizar)
        limparDados() {
            try {
                sessionStorage.removeItem(this.storageKey);
                console.log('🗑️ Dados do formulário limpos');
            } catch (e) {
                console.error('Erro ao limpar sessionStorage:', e);
            }
        }
    }
    
    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        window.formPersistence = new FormPersistence('solicitacao_manual_form');
        
        // Restaurar dados automaticamente sem perguntar
        // Os dados serão restaurados automaticamente pela classe FormPersistence
        
        // Limpar dados quando clicar em "Cancelar" (sair da solicitação)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.btn-cancelar-solicitacao');
            if (!link) return;
            
            // Limpar dados do sessionStorage antes de navegar
            if (window.formPersistence) {
                window.formPersistence.limparDados();
                console.log('🗑️ Dados limpos ao cancelar solicitação');
            }
            // Permitir navegação normal (não prevenir default)
        });
    });
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
echo $content;
?>

