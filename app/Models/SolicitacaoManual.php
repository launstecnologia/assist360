<?php

namespace App\Models;

use App\Core\Database;

class SolicitacaoManual extends Model
{
    protected string $table = 'solicitacoes_manuais';
    
    protected array $fillable = [
        'imobiliaria_id', 'nome_completo', 'cpf', 'whatsapp',
        'tipo_imovel', 'subtipo_imovel', 'cep', 'endereco', 'numero', 
        'complemento', 'bairro', 'cidade', 'estado', 'numero_contrato',
        'categoria_id', 'subcategoria_id', 'descricao_problema',
        // Campo de observações (usado para armazenar múltiplas subcategorias, quando houver)
        'observacoes',
        'validacao_bolsao', 'tipo_qualificacao', 'observacao_qualificacao',
        'horarios_preferenciais', 'fotos', 'termos_aceitos',
        'status_id', 'migrada_para_solicitacao_id', 'migrada_em', 
        'migrada_por_usuario_id', 'token_acesso', 'created_at', 'updated_at'
    ];
    
    protected array $casts = [
        'horarios_preferenciais' => 'json',
        'fotos' => 'json',
        'termos_aceitos' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'migrada_em' => 'datetime'
    ];

    private static array $colunaCache = [];

    /**
     * Verifica se uma coluna existe na tabela (cacheada)
     */
    public function colunaExisteBanco(string $coluna): bool
    {
        if (!array_key_exists($coluna, self::$colunaCache)) {
            $sql = "DESCRIBE {$this->table}";
            $resultado = Database::fetchAll($sql);

            self::$colunaCache = [];
            foreach ($resultado as $colunaInfo) {
                $nome = $colunaInfo['Field'] ?? '';
                if ($nome !== '') {
                    self::$colunaCache[$nome] = true;
                }
            }
        }

        return self::$colunaCache[$coluna] ?? false;
    }

    /**
     * Buscar todas as solicitações manuais com filtros
     */
    public function getAll(array $filtros = []): array
    {
        $sql = "
            SELECT 
                sm.*,
                st.nome as status_nome,
                st.cor as status_cor,
                st.icone as status_icone,
                c.nome as categoria_nome,
                sc.nome as subcategoria_nome,
                i.nome as imobiliaria_nome,
                i.logo as imobiliaria_logo,
                u.nome as migrada_por_nome,
                CASE WHEN sm.migrada_para_solicitacao_id IS NOT NULL THEN 1 ELSE 0 END as migrada
            FROM solicitacoes_manuais sm
            LEFT JOIN status st ON sm.status_id = st.id
            LEFT JOIN categorias c ON sm.categoria_id = c.id
            LEFT JOIN subcategorias sc ON sm.subcategoria_id = sc.id
            LEFT JOIN imobiliarias i ON sm.imobiliaria_id = i.id
            LEFT JOIN usuarios u ON sm.migrada_por_usuario_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Removido filtro que exclui NAO_QUALIFICADA - agora tudo aparece em Solicitações Manuais
        
        // Filtro por imobiliária
        if (!empty($filtros['imobiliaria_id'])) {
            $sql .= " AND sm.imobiliaria_id = ?";
            $params[] = $filtros['imobiliaria_id'];
        }
        
        // Filtro por status
        if (!empty($filtros['status_id'])) {
            $sql .= " AND sm.status_id = ?";
            $params[] = $filtros['status_id'];
        }
        
        // Filtro por CPF
        if (!empty($filtros['cpf'])) {
            $sql .= " AND sm.cpf = ?";
            $params[] = $filtros['cpf'];
        }
        
        // Filtro por migrada
        if (isset($filtros['migrada'])) {
            if ($filtros['migrada']) {
                $sql .= " AND sm.migrada_para_solicitacao_id IS NOT NULL";
            } else {
                $sql .= " AND sm.migrada_para_solicitacao_id IS NULL";
            }
        }
        
        // Busca por texto
        if (!empty($filtros['busca'])) {
            $sql .= " AND (sm.nome_completo LIKE ? OR sm.cpf LIKE ? OR sm.descricao_problema LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        
        $sql .= " ORDER BY sm.created_at DESC";
        
        // Limit
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limit'];
        }
        
        return Database::fetchAll($sql, $params);
    }

    /**
     * Buscar detalhes de uma solicitação manual por ID
     */
    public function getDetalhes(int $id): ?array
    {
        $sql = "
            SELECT 
                sm.*,
                st.nome as status_nome,
                st.cor as status_cor,
                st.icone as status_icone,
                c.nome as categoria_nome,
                c.icone as categoria_icone,
                sc.nome as subcategoria_nome,
                sc.descricao as subcategoria_descricao,
                i.nome as imobiliaria_nome,
                i.telefone as imobiliaria_telefone,
                i.api_id as imobiliaria_api_id,
                i.integracao_ativa as imobiliaria_integracao_ativa,
                u.nome as migrada_por_nome
            FROM solicitacoes_manuais sm
            LEFT JOIN status st ON sm.status_id = st.id
            LEFT JOIN categorias c ON sm.categoria_id = c.id
            LEFT JOIN subcategorias sc ON sm.subcategoria_id = sc.id
            LEFT JOIN imobiliarias i ON sm.imobiliaria_id = i.id
            LEFT JOIN usuarios u ON sm.migrada_por_usuario_id = u.id
            WHERE sm.id = ?
        ";
        
        $solicitacao = Database::fetch($sql, [$id]);
        
        if (!$solicitacao) {
            return null;
        }
        
        // Se a coluna observacoes não existir, mas houver informações de múltiplas subcategorias
        // no descricao_problema, extrair e colocar em observacoes para compatibilidade
        $colunaObservacoesExiste = $this->colunaExisteBanco('observacoes');
        if (!$colunaObservacoesExiste && !empty($solicitacao['descricao_problema'])) {
            $descricao = $solicitacao['descricao_problema'];
            if (strpos($descricao, '[SUBCATEGORIAS_IDS:') !== false || strpos($descricao, 'Serviços solicitados') !== false) {
                // Extrair a parte das múltiplas subcategorias
                $partes = explode("\n\n", $descricao);
                if (count($partes) > 1) {
                    $ultimaParte = end($partes);
                    if (strpos($ultimaParte, '[SUBCATEGORIAS_IDS:') !== false || strpos($ultimaParte, 'Serviços solicitados') !== false) {
                        // Adicionar como observacoes para compatibilidade com o código que espera esse campo
                        $solicitacao['observacoes'] = $ultimaParte;
                    }
                }
            }
        }
        
        return $solicitacao;
    }

    /**
     * Montar observações para migração
     */
    private function montarObservacoesMigracao(array $solicitacaoManual, int $id): string
    {
        $observacoes = "⚠️ SOLICITAÇÃO CRIADA MANUALMENTE (ID Manual: {$id})\n";
        $observacoes .= "Solicitação realizada por usuário não autenticado através do formulário público.\n";
        $observacoes .= "CPF informado: " . ($solicitacaoManual['cpf'] ?? 'N/A');
        
        // Verificar se a coluna observacoes existe
        $colunaObservacoesExiste = $this->colunaExisteBanco('observacoes');
        
        // Se a solicitação manual já possui observações (incluindo múltiplas subcategorias),
        // preservar essas informações para que o Kanban consiga exibir todos os serviços.
        if ($colunaObservacoesExiste && !empty($solicitacaoManual['observacoes'])) {
            $observacoes .= "\n\n--- Detalhes originais da solicitação manual ---\n";
            $observacoes .= $solicitacaoManual['observacoes'];
        } elseif (!$colunaObservacoesExiste && !empty($solicitacaoManual['descricao_problema'])) {
            // Se a coluna observacoes não existir, verificar se as informações de múltiplas subcategorias
            // foram salvas no descricao_problema como fallback
            $descricao = $solicitacaoManual['descricao_problema'];
            if (strpos($descricao, '[SUBCATEGORIAS_IDS:') !== false || strpos($descricao, 'Serviços solicitados') !== false) {
                // Extrair apenas a parte das múltiplas subcategorias do descricao_problema
                $partes = explode("\n\n", $descricao);
                if (count($partes) > 1) {
                    // A última parte geralmente contém as informações de múltiplas subcategorias
                    $ultimaParte = end($partes);
                    if (strpos($ultimaParte, '[SUBCATEGORIAS_IDS:') !== false || strpos($ultimaParte, 'Serviços solicitados') !== false) {
                        $observacoes .= "\n\n--- Detalhes originais da solicitação manual ---\n";
                        $observacoes .= $ultimaParte;
                    }
                }
            }
        }
        
        return $observacoes;
    }

    /**
     * Migrar solicitação manual para o sistema principal
     */
    public function migrarParaSistema(int $id, int $usuarioId): array
    {
        try {
            Database::beginTransaction();
            
            // Buscar dados da solicitação manual
            $solicitacaoManual = $this->getDetalhes($id);
            
            if (!$solicitacaoManual) {
                throw new \Exception('Solicitação manual não encontrada');
            }
            
            // Verificar se já foi migrada
            if ($solicitacaoManual['migrada_para_solicitacao_id']) {
                throw new \Exception('Esta solicitação já foi migrada');
            }
            
            // Determinar o status a ser usado na migração
            $statusModel = new Status();
            $statusIdParaMigracao = null;
            
            // Se a solicitação manual já tem um status_id definido e válido, usar esse status
            if (!empty($solicitacaoManual['status_id']) && is_numeric($solicitacaoManual['status_id']) && $solicitacaoManual['status_id'] > 0) {
                // Verificar se o status existe no sistema principal
                $statusExistente = $statusModel->find((int)$solicitacaoManual['status_id']);
                if ($statusExistente && !empty($statusExistente['id'])) {
                    $statusIdParaMigracao = (int)$statusExistente['id'];
                }
            }
            
            // Se não tiver status definido ou o status não existir, usar "Nova Solicitação" como padrão
            if (empty($statusIdParaMigracao) || !is_numeric($statusIdParaMigracao)) {
                $statusInicial = $statusModel->findByNome('Nova Solicitação');
                if (!$statusInicial || empty($statusInicial['id'])) {
                    $statusInicial = $statusModel->findByNome('Nova');
                }
                if (!$statusInicial || empty($statusInicial['id'])) {
                    // Último recurso: buscar qualquer status ativo
                    $sqlStatus = "SELECT id FROM status WHERE status = 'ATIVO' ORDER BY ordem ASC LIMIT 1";
                    $statusFallback = Database::fetch($sqlStatus);
                    $statusInicial = $statusFallback ?: ['id' => 1];
                }
                $statusIdParaMigracao = (int)$statusInicial['id'];
            }
            
            // Garantir que temos um status válido (nunca null ou zero)
            if (empty($statusIdParaMigracao) || !is_numeric($statusIdParaMigracao) || $statusIdParaMigracao <= 0) {
                // Fallback absoluto: buscar qualquer status ativo ou usar ID 1
                $sqlStatus = "SELECT id FROM status WHERE status = 'ATIVO' ORDER BY ordem ASC LIMIT 1";
                $statusFallback = Database::fetch($sqlStatus);
                if ($statusFallback && !empty($statusFallback['id'])) {
                    $statusIdParaMigracao = (int)$statusFallback['id'];
                } else {
                    $statusIdParaMigracao = 1; // Último recurso
                }
            }
            
            // Se tipo_qualificacao for NAO_QUALIFICADA, usar status "Não qualificado"
            if (!empty($solicitacaoManual['tipo_qualificacao']) && 
                $solicitacaoManual['tipo_qualificacao'] === 'NAO_QUALIFICADA') {
                
                // Tentar ambos os nomes possíveis (com e sem S)
                $statusNaoQualificado = $statusModel->findByNome('Não qualificado');
                if (!$statusNaoQualificado) {
                    $statusNaoQualificado = $statusModel->findByNome('Não Qualificados');
                }
                if ($statusNaoQualificado && !empty($statusNaoQualificado['id'])) {
                    $statusIdParaMigracao = (int)$statusNaoQualificado['id'];
                    error_log("Solicitação manual #{$id} marcada como NAO_QUALIFICADA - usando status '{$statusNaoQualificado['nome']}' (ID: {$statusIdParaMigracao})");
                }
            }
            
            // Garantir que é um inteiro válido e nunca null
            $statusIdParaMigracao = (int)$statusIdParaMigracao;
            if ($statusIdParaMigracao <= 0) {
                $statusIdParaMigracao = 1; // Fallback absoluto
            }
            
            // Log para debug
            error_log("=== MIGRAÇÃO SOLICITAÇÃO MANUAL #{$id} ===");
            error_log("Status ID da solicitação manual: " . ($solicitacaoManual['status_id'] ?? 'NULL'));
            error_log("Status ID determinado para migração: " . $statusIdParaMigracao);
            
            // Preparar dados para criar solicitação normal
            $solicitacaoModel = new Solicitacao();
            $dadosSolicitacao = [
                'imobiliaria_id' => $solicitacaoManual['imobiliaria_id'],
                'categoria_id' => $solicitacaoManual['categoria_id'],
                'subcategoria_id' => $solicitacaoManual['subcategoria_id'],
                'status_id' => $statusIdParaMigracao, // Garantido que nunca será null
                
                // Dados do locatário
                'locatario_id' => 0, // ID 0 indica que veio de solicitação manual
                'locatario_nome' => $solicitacaoManual['nome_completo'],
                'locatario_cpf' => $solicitacaoManual['cpf'],
                'locatario_telefone' => $solicitacaoManual['whatsapp'],
                'locatario_email' => null,
                
                // Dados do imóvel
                'imovel_endereco' => $solicitacaoManual['endereco'],
                'imovel_numero' => $solicitacaoManual['numero'],
                'imovel_complemento' => $solicitacaoManual['complemento'],
                'imovel_bairro' => $solicitacaoManual['bairro'],
                'imovel_cidade' => $solicitacaoManual['cidade'],
                'imovel_estado' => $solicitacaoManual['estado'],
                'imovel_cep' => $solicitacaoManual['cep'],
                
                // Número do contrato
                'numero_contrato' => $solicitacaoManual['numero_contrato'] ?? null,
                
                // Descrição e detalhes
                'descricao_problema' => $solicitacaoManual['descricao_problema'],
                'observacoes' => $this->montarObservacoesMigracao($solicitacaoManual, $id),
                'prioridade' => 'NORMAL',
                
                // Tipo de qualificação e validação do bolsão (transferir da solicitação manual)
                'tipo_qualificacao' => $solicitacaoManual['tipo_qualificacao'] ?? null,
                'validacao_bolsao' => $solicitacaoManual['validacao_bolsao'] ?? 0,
                'observacao_qualificacao' => $solicitacaoManual['observacao_qualificacao'] ?? null,
                
                // Horários preferenciais
                'horarios_opcoes' => is_string($solicitacaoManual['horarios_preferenciais']) 
                    ? $solicitacaoManual['horarios_preferenciais'] 
                    : json_encode($solicitacaoManual['horarios_preferenciais'])
            ];
            
            // ✅ VALIDAÇÃO DE UTILIZAÇÃO: Calcular se CPF está dentro do limite
            $validacaoUtilizacao = $this->calcularValidacaoUtilizacao(
                $solicitacaoManual['cpf'] ?? '',
                (int)($solicitacaoManual['imobiliaria_id'] ?? 0),
                (int)($solicitacaoManual['categoria_id'] ?? 0)
            );
            $dadosSolicitacao['validacao_utilizacao'] = $validacaoUtilizacao;
            
            // IMPORTANTE: Se validacao_utilizacao = 0 (excedido), NÃO marcar como NAO_QUALIFICADA automaticamente
            // A solicitação já foi criada como manual quando validacao_utilizacao = 0
            // O admin deve decidir se é CORTESIA ou NAO_QUALIFICADA na tela de solicitações manuais
            // O tipo_qualificacao já vem da solicitação manual (pode ser NULL, CORTESIA ou NAO_QUALIFICADA)
            
            // Log dos dados antes de criar
            error_log("Dados antes do create - status_id: " . ($dadosSolicitacao['status_id'] ?? 'NULL'));
            error_log("Tipo do status_id: " . gettype($dadosSolicitacao['status_id']));
            error_log("Validação Utilização calculada: " . ($validacaoUtilizacao === null ? 'NULL' : $validacaoUtilizacao));
            
            // Criar solicitação no sistema principal
            $solicitacaoId = $solicitacaoModel->create($dadosSolicitacao);
            
            if (!$solicitacaoId) {
                throw new \Exception('Erro ao criar solicitação no sistema principal');
            }
            
            // Gerar token de acesso para visualização pública (sem login)
            // Este token permite que a pessoa veja o status da solicitação sem precisar fazer login
            $tokenAcesso = bin2hex(random_bytes(32)); // 64 caracteres
            
            // Salvar token de acesso na solicitação (se a coluna existir)
            try {
                $sqlToken = "UPDATE solicitacoes SET token_acesso = ? WHERE id = ?";
                Database::query($sqlToken, [$tokenAcesso, $solicitacaoId]);
            } catch (\Exception $e) {
                // Se a coluna não existir, apenas logar o erro (não bloquear)
                error_log('Aviso: Coluna token_acesso não existe na tabela solicitacoes. Execute o script SQL para adicionar.');
            }
            
            // Se há fotos, copiar para a tabela de fotos
            if (!empty($solicitacaoManual['fotos'])) {
                $fotos = is_string($solicitacaoManual['fotos']) 
                    ? json_decode($solicitacaoManual['fotos'], true) 
                    : $solicitacaoManual['fotos'];
                
                if (is_array($fotos) && count($fotos) > 0) {
                    foreach ($fotos as $foto) {
                        // Extrair nome do arquivo do caminho
                        $nomeArquivo = basename($foto);
                        
                        $sqlFoto = "INSERT INTO fotos (solicitacao_id, nome_arquivo, url_arquivo, created_at) 
                                    VALUES (?, ?, ?, NOW())";
                        Database::query($sqlFoto, [$solicitacaoId, $nomeArquivo, $foto]);
                    }
                }
            }
            
            // Atualizar solicitação manual com ID da migração
            $this->update($id, [
                'migrada_para_solicitacao_id' => $solicitacaoId,
                'migrada_em' => date('Y-m-d H:i:s'),
                'migrada_por_usuario_id' => $usuarioId
            ]);
            
            // Verificar se o usuário existe antes de registrar no histórico
            $usuarioExiste = Database::fetch("SELECT id FROM usuarios WHERE id = ?", [$usuarioId]);
            $usuarioIdValido = $usuarioExiste ? $usuarioId : null;
            
            // Registrar no histórico de status
            $sqlHistorico = "
                INSERT INTO historico_status (solicitacao_id, status_id, usuario_id, observacoes, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            Database::query($sqlHistorico, [
                $solicitacaoId, 
                $statusIdParaMigracao, 
                $usuarioIdValido,
                'Solicitação migrada do sistema manual (ID: ' . $id . ')'
            ]);
            
            Database::commit();
            
            return [
                'success' => true,
                'solicitacao_id' => $solicitacaoId,
                'message' => 'Solicitação migrada com sucesso para o sistema principal'
            ];
            
        } catch (\Exception $e) {
            Database::rollback();
            error_log('Erro ao migrar solicitação manual: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar quantidade de solicitações manuais por CPF
     */
    public function verificarQuantidadePorCPF(string $cpf, int $imobiliariaId, ?int $categoriaId = null): array
    {
        // Limpar CPF
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        if (empty($cpfLimpo) || strlen($cpfLimpo) < 11) {
            return [
                'permitido' => true,
                'quantidade_total' => 0,
                'quantidade_12_meses' => 0,
                'mensagem' => 'CPF inválido'
            ];
        }
        
        // Verificar se a tabela de contagem existe
        $tabelaExiste = false;
        try {
            $sql = "SHOW TABLES LIKE 'solicitacoes_manuais_contagem_cpf'";
            $result = Database::fetch($sql);
            $tabelaExiste = !empty($result);
        } catch (\Exception $e) {
            // Tabela não existe ainda
        }
        
        // Se a tabela existe, usar ela (mais rápido)
        if ($tabelaExiste) {
            if ($categoriaId) {
                // Buscar contagem específica da categoria
                $sql = "SELECT quantidade_total, quantidade_12_meses 
                        FROM solicitacoes_manuais_contagem_cpf 
                        WHERE cpf = ? AND imobiliaria_id = ? AND categoria_id = ?";
                $contagem = Database::fetch($sql, [$cpfLimpo, $imobiliariaId, $categoriaId]);
            } else {
                // Buscar contagem total de todas as categorias
                $sql = "SELECT 
                            SUM(quantidade_total) as quantidade_total, 
                            SUM(quantidade_12_meses) as quantidade_12_meses 
                        FROM solicitacoes_manuais_contagem_cpf 
                        WHERE cpf = ? AND imobiliaria_id = ?";
                $contagem = Database::fetch($sql, [$cpfLimpo, $imobiliariaId]);
            }
            
            if ($contagem && ($contagem['quantidade_total'] ?? 0) > 0) {
                return [
                    'permitido' => true, // Por enquanto sempre permitir, mas retornar a quantidade
                    'quantidade_total' => (int)($contagem['quantidade_total'] ?? 0),
                    'quantidade_12_meses' => (int)($contagem['quantidade_12_meses'] ?? 0),
                    'mensagem' => "Total: {$contagem['quantidade_total']} solicitações" . ($categoriaId ? " nesta categoria" : "") . " | Últimos 12 meses: {$contagem['quantidade_12_meses']}"
                ];
            }
        }
        
        // Se a tabela não existe ou não encontrou registro, contar diretamente
        $sql = "
            SELECT 
                COUNT(*) as quantidade_total,
                SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) as quantidade_12_meses
            FROM solicitacoes_manuais
            WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
            AND imobiliaria_id = ?
        ";
        
        if ($categoriaId) {
            $sql .= " AND categoria_id = ?";
            $resultado = Database::fetch($sql, [$cpfLimpo, $imobiliariaId, $categoriaId]);
        } else {
            $resultado = Database::fetch($sql, [$cpfLimpo, $imobiliariaId]);
        }
        
        $quantidadeTotal = (int)($resultado['quantidade_total'] ?? 0);
        $quantidade12Meses = (int)($resultado['quantidade_12_meses'] ?? 0);
        
        return [
            'permitido' => true, // Por enquanto sempre permitir
            'quantidade_total' => $quantidadeTotal,
            'quantidade_12_meses' => $quantidade12Meses,
            'mensagem' => "Total: {$quantidadeTotal} solicitações | Últimos 12 meses: {$quantidade12Meses}"
        ];
    }
    
    /**
     * Atualizar contagem de CPF após criar solicitação manual
     */
    public function atualizarContagemCPF(string $cpf, int $imobiliariaId, ?int $categoriaId = null): void
    {
        // Limpar CPF
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        if (empty($cpfLimpo) || strlen($cpfLimpo) < 11) {
            return;
        }
        
        // Verificar se a tabela existe
        try {
            $sql = "SHOW TABLES LIKE 'solicitacoes_manuais_contagem_cpf'";
            $result = Database::fetch($sql);
            if (empty($result)) {
                // Tabela não existe, não fazer nada (será criada pelo trigger ou script SQL)
                return;
            }
            
            // Atualizar contagem
            $data12Meses = date('Y-m-d', strtotime('-12 months'));
            
            if ($categoriaId) {
                // Atualizar contagem específica da categoria
                $sql = "
                    INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
                    SELECT 
                        ? as cpf,
                        ? as imobiliaria_id,
                        ? as categoria_id,
                        COUNT(*) as quantidade_total,
                        SUM(CASE WHEN DATE(created_at) >= ? THEN 1 ELSE 0 END) as quantidade_12_meses,
                        MIN(DATE(created_at)) as primeira_solicitacao,
                        MAX(DATE(created_at)) as ultima_solicitacao
                    FROM solicitacoes_manuais
                    WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
                    AND imobiliaria_id = ?
                    AND categoria_id = ?
                    GROUP BY cpf, imobiliaria_id, categoria_id
                    ON DUPLICATE KEY UPDATE
                        quantidade_total = VALUES(quantidade_total),
                        quantidade_12_meses = VALUES(quantidade_12_meses),
                        ultima_solicitacao = VALUES(ultima_solicitacao),
                        updated_at = CURRENT_TIMESTAMP
                ";
                
                Database::query($sql, [$cpfLimpo, $imobiliariaId, $categoriaId, $data12Meses, $cpfLimpo, $imobiliariaId, $categoriaId]);
            } else {
                // Atualizar contagem de todas as categorias (para cada categoria)
                $sql = "
                    INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
                    SELECT 
                        REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') as cpf,
                        imobiliaria_id,
                        categoria_id,
                        COUNT(*) as quantidade_total,
                        SUM(CASE WHEN DATE(created_at) >= ? THEN 1 ELSE 0 END) as quantidade_12_meses,
                        MIN(DATE(created_at)) as primeira_solicitacao,
                        MAX(DATE(created_at)) as ultima_solicitacao
                    FROM solicitacoes_manuais
                    WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
                    AND imobiliaria_id = ?
                    AND categoria_id IS NOT NULL
                    GROUP BY cpf, imobiliaria_id, categoria_id
                    ON DUPLICATE KEY UPDATE
                        quantidade_total = VALUES(quantidade_total),
                        quantidade_12_meses = VALUES(quantidade_12_meses),
                        ultima_solicitacao = VALUES(ultima_solicitacao),
                        updated_at = CURRENT_TIMESTAMP
                ";
                
                Database::query($sql, [$data12Meses, $cpfLimpo, $imobiliariaId]);
            }
        } catch (\Exception $e) {
            error_log('Erro ao atualizar contagem CPF: ' . $e->getMessage());
        }
    }
    
    /**
     * Calcular validação de utilização (limite de CPF por categoria)
     * Retorna: 1 = aprovado (dentro do limite), 0 = recusado (limite excedido), null = não verificável
     */
    public function calcularValidacaoUtilizacao(string $cpf, int $imobiliariaId, int $categoriaId): ?int
    {
        // Se não tem CPF ou categoria, não pode verificar
        if (empty($cpf) || $categoriaId <= 0) {
            return 1; // Considera aprovado se não tiver dados para verificar
        }
        
        try {
            // Buscar limite da categoria
            $categoriaModel = new Categoria();
            $categoria = $categoriaModel->find($categoriaId);
            
            if (!$categoria) {
                return 1; // Sem categoria, considera aprovado
            }
            
            $limite = $categoria['limite_solicitacoes_12_meses'] ?? null;
            
            // Se não tem limite configurado, considera aprovado
            if ($limite === null || (int)$limite <= 0) {
                return 1;
            }
            
            // Verificar quantidade de solicitações do CPF nos últimos 12 meses
            $verificacao = $this->verificarQuantidadePorCPF($cpf, $imobiliariaId, $categoriaId);
            
            $quantidade12Meses = (int)($verificacao['quantidade_12_meses'] ?? 0);
            
            // Se quantidade atual é MENOR que o limite, está aprovado
            // (a solicitação atual ainda não foi contada, então usamos <)
            if ($quantidade12Meses < (int)$limite) {
                error_log("Validação Utilização [CPF: {$cpf}] - Quantidade 12 meses: {$quantidade12Meses}, Limite: {$limite} - APROVADO");
                return 1; // Aprovado
            } else {
                error_log("Validação Utilização [CPF: {$cpf}] - Quantidade 12 meses: {$quantidade12Meses}, Limite: {$limite} - RECUSADO");
                return 0; // Recusado - limite atingido
            }
            
        } catch (\Exception $e) {
            error_log('Erro ao calcular validação de utilização: ' . $e->getMessage());
            return 1; // Em caso de erro, considera aprovado para não bloquear
        }
    }
    
    /**
     * Validar CPF
     */
    public function validarCPF(string $cpf): bool
    {
        // Remover caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Se tiver 11 dígitos, validar como CPF
        if (strlen($cpf) == 11) {
        // Verificar se não é uma sequência de números iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        
        // Validar primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $cpf[$i] * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ($cpf[9] != $digito1) {
            return false;
        }
        
        // Validar segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $cpf[$i] * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ($cpf[10] != $digito2) {
            return false;
        }
        
        return true;
        }
        
        // Se tiver 14 dígitos, validar como CNPJ
        if (strlen($cpf) == 14) {
            // Verificar se não é uma sequência de números iguais
            if (preg_match('/^(\d)\1{13}$/', $cpf)) {
                return false;
            }
            
            // Validar primeiro dígito verificador
            $soma = 0;
            $pesos = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            for ($i = 0; $i < 12; $i++) {
                $soma += $cpf[$i] * $pesos[$i];
            }
            $resto = $soma % 11;
            $digito1 = ($resto < 2) ? 0 : 11 - $resto;
            
            if ($cpf[12] != $digito1) {
                return false;
            }
            
            // Validar segundo dígito verificador
            $soma = 0;
            $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            for ($i = 0; $i < 13; $i++) {
                $soma += $cpf[$i] * $pesos[$i];
            }
            $resto = $soma % 11;
            $digito2 = ($resto < 2) ? 0 : 11 - $resto;
            
            if ($cpf[13] != $digito2) {
                return false;
            }
            
            return true;
        }
        
        // Se não tiver 11 nem 14 dígitos, inválido
        return false;
    }

    /**
     * Buscar estatísticas de solicitações por CPF
     */
    public function getEstatisticasPorCPF(string $cpf, int $imobiliariaId): array
    {
        $verificacao = $this->verificarQuantidadePorCPF($cpf, $imobiliariaId);
        
        // Buscar detalhes das solicitações
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        $sql = "
            SELECT 
                id,
                nome_completo,
                categoria_id,
                subcategoria_id,
                created_at,
                migrada_para_solicitacao_id,
                tipo_qualificacao
            FROM solicitacoes_manuais
            WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
            AND imobiliaria_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ";
        
        $solicitacoes = Database::fetchAll($sql, [$cpfLimpo, $imobiliariaId]);
        
        return [
            'cpf' => $cpfLimpo,
            'quantidade_total' => $verificacao['quantidade_total'],
            'quantidade_12_meses' => $verificacao['quantidade_12_meses'],
            'solicitacoes' => $solicitacoes
        ];
    }
    
    /**
     * Verificar se CPF já existe
     */
    public function cpfExiste(string $cpf, int $imobiliariaId): bool
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        
        $sql = "SELECT COUNT(*) as total FROM solicitacoes_manuais 
                WHERE cpf = ? AND imobiliaria_id = ?";
        $result = Database::fetch($sql, [$cpfLimpo, $imobiliariaId]);
        
        return $result['total'] > 0;
    }

    /**
     * Contar solicitações manuais por status
     */
    public function contarPorStatus(): array
    {
        $sql = "
            SELECT 
                st.nome as status,
                st.cor as cor,
                COUNT(*) as total
            FROM solicitacoes_manuais sm
            LEFT JOIN status st ON sm.status_id = st.id
            WHERE sm.migrada_para_solicitacao_id IS NULL
            GROUP BY st.id, st.nome, st.cor
            ORDER BY total DESC
        ";
        
        return Database::fetchAll($sql);
    }

    /**
     * Buscar solicitações não migradas
     */
    public function getNaoMigradas(int $limit = 50): array
    {
        $sql = "
            SELECT 
                sm.*,
                st.nome as status_nome,
                st.cor as status_cor,
                c.nome as categoria_nome,
                sc.nome as subcategoria_nome,
                i.nome as imobiliaria_nome
            FROM solicitacoes_manuais sm
            LEFT JOIN status st ON sm.status_id = st.id
            LEFT JOIN categorias c ON sm.categoria_id = c.id
            LEFT JOIN subcategorias sc ON sm.subcategoria_id = sc.id
            LEFT JOIN imobiliarias i ON sm.imobiliaria_id = i.id
            WHERE sm.migrada_para_solicitacao_id IS NULL
        ";
        
        // Removido filtro que exclui NAO_QUALIFICADA - agora tudo aparece em Solicitações Manuais
        
        $sql .= " ORDER BY sm.created_at DESC LIMIT ?";
        
        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Buscar solicitação manual por token de acesso
     */
    public function findByToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token_acesso = ? LIMIT 1";
        return Database::fetch($sql, [$token]);
    }

    /**
     * Gerar token único para acesso público
     */
    private function gerarTokenAcesso(): string
    {
        do {
            $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimais
            $existe = $this->findByToken($token);
        } while ($existe !== null);
        
        return $token;
    }

    /**
     * Override do método create para adicionar validações
     */
    public function create(array $data): int
    {
        // Garantir timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Gerar token de acesso se não fornecido
        if (empty($data['token_acesso'])) {
            $data['token_acesso'] = $this->gerarTokenAcesso();
        }
        
        // Converter arrays para JSON se necessário
        if (isset($data['horarios_preferenciais']) && is_array($data['horarios_preferenciais'])) {
            $data['horarios_preferenciais'] = json_encode($data['horarios_preferenciais']);
        }
        
        if (isset($data['fotos']) && is_array($data['fotos'])) {
            $data['fotos'] = json_encode($data['fotos']);
        }
        
        // Limpar CPF (remover máscara)
        if (isset($data['cpf'])) {
            $data['cpf'] = preg_replace('/[^0-9]/', '', $data['cpf']);
        }
        
        // Definir status inicial se não fornecido
        if (!isset($data['status_id'])) {
            $sql = "SELECT id FROM status WHERE nome = 'Nova Solicitação' LIMIT 1";
            $status = Database::fetch($sql);
            $data['status_id'] = $status['id'] ?? 1;
        }
        
        // Definir tipo_qualificacao baseado em validacao_bolsao se não fornecido (apenas se a coluna existir)
        $colunaTipoQualificacaoExiste = $this->colunaExisteBanco('tipo_qualificacao');
        if (!isset($data['tipo_qualificacao']) && $colunaTipoQualificacaoExiste) {
            // Solicitações manuais SEM bolsão começam sem tipo_qualificacao (NULL)
            // Admin deve escolher CORTESIA ou NAO_QUALIFICADA no formulário
            // Solicitações COM bolsão não devem ser criadas como manuais (devem ir direto pro kanban como BOLSAO)
            // Deixar NULL - será definido pelo admin
            $data['tipo_qualificacao'] = null;
        }
        
        // Se a coluna não existir, remover do array para evitar erro
        if (!$colunaTipoQualificacaoExiste && isset($data['tipo_qualificacao'])) {
            unset($data['tipo_qualificacao']);
        }
        
        // Verificar se a coluna observacoes existe antes de tentar salvar
        $colunaObservacoesExiste = $this->colunaExisteBanco('observacoes');
        if (!$colunaObservacoesExiste && isset($data['observacoes'])) {
            // Se a coluna não existir, tentar salvar as múltiplas subcategorias no descricao_problema
            // como fallback temporário (será preservado na migração)
            if (!empty($data['observacoes']) && strpos($data['observacoes'], '[SUBCATEGORIAS_IDS:') !== false) {
                // Adicionar as informações de múltiplas subcategorias ao final do descricao_problema
                $data['descricao_problema'] = ($data['descricao_problema'] ?? '') . "\n\n" . $data['observacoes'];
            }
            // Remover observacoes dos dados para salvar
            unset($data['observacoes']);
        }
        
        return parent::create($data);
    }

    /**
     * Buscar todas as solicitações manuais não qualificadas
     */
    public function getNaoQualificadas(array $filtros = []): array
    {
        // Verificar se a coluna tipo_qualificacao existe
        $colunaExiste = $this->colunaExisteBanco('tipo_qualificacao');
        
        // Se a coluna não existir, retornar array vazio
        if (!$colunaExiste) {
            return [];
        }
        
        $sql = "
            SELECT 
                sm.*,
                st.nome as status_nome,
                st.cor as status_cor,
                st.icone as status_icone,
                c.nome as categoria_nome,
                sc.nome as subcategoria_nome,
                i.nome as imobiliaria_nome,
                i.logo as imobiliaria_logo,
                'MANUAL' as tipo_solicitacao,
                CASE WHEN sm.migrada_para_solicitacao_id IS NOT NULL THEN 1 ELSE 0 END as migrada
            FROM solicitacoes_manuais sm
            LEFT JOIN status st ON sm.status_id = st.id
            LEFT JOIN categorias c ON sm.categoria_id = c.id
            LEFT JOIN subcategorias sc ON sm.subcategoria_id = sc.id
            LEFT JOIN imobiliarias i ON sm.imobiliaria_id = i.id
            WHERE sm.tipo_qualificacao = 'NAO_QUALIFICADA'
        ";
        
        $params = [];
        
        // Filtro por imobiliária
        if (!empty($filtros['imobiliaria_id'])) {
            $sql .= " AND sm.imobiliaria_id = ?";
            $params[] = $filtros['imobiliaria_id'];
        }
        
        // Filtro por migrada
        if (isset($filtros['migrada'])) {
            if ($filtros['migrada']) {
                $sql .= " AND sm.migrada_para_solicitacao_id IS NOT NULL";
            } else {
                $sql .= " AND sm.migrada_para_solicitacao_id IS NULL";
            }
        }
        
        // Filtro por data
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND DATE(sm.created_at) >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND DATE(sm.created_at) <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        // Busca por texto
        if (!empty($filtros['busca'])) {
            $sql .= " AND (sm.nome_completo LIKE ? OR sm.cpf LIKE ? OR sm.descricao_problema LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        
        $sql .= " ORDER BY sm.created_at DESC";
        
        // Limit
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limit'];
        }
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Gerar link público com token para acesso sem login
     */
    public function getLinkPublico(int $id): ?string
    {
        $solicitacao = $this->find($id);
        if (!$solicitacao || empty($solicitacao['token_acesso'])) {
            return null;
        }
        
        // Buscar URL base configurada
        $config = require __DIR__ . '/../Config/config.php';
        $whatsappConfig = $config['whatsapp'] ?? [];
        $baseUrl = $whatsappConfig['links_base_url'] ?? \App\Core\Url::base();
        $baseUrl = rtrim($baseUrl, '/');
        
        return $baseUrl . '/solicitacao-manual/' . $solicitacao['token_acesso'];
    }
}

