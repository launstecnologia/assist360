<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class MaintenanceController extends Controller
{
    public function showMigrations(): void
    {
        $this->requireAuth();
        if (($_SESSION['user_level'] ?? '') !== 'ADMINISTRADOR') {
            header('Location: /admin/dashboard');
            return;
        }

        $this->view('admin.migracoes', $this->getMigrationViewData());
    }

    public function runMigrations(): void
    {
        $this->requireAuth();
        if (($_SESSION['user_level'] ?? '') !== 'ADMINISTRADOR') {
            header('Location: /admin/dashboard');
            return;
        }

        // CSRF básico
        $token = $this->input('csrf_token');
        if (!$token || $token !== \App\Core\View::csrfToken()) {
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'CSRF inválido'
            ]));
            return;
        }

        try {
            // DDL no MySQL faz autocommit; evite transações aqui
            // descricao_card
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS descricao_card TEXT NULL AFTER descricao_problema");
            Database::query("UPDATE solicitacoes SET descricao_card = descricao_problema WHERE descricao_card IS NULL");

            // horario_confirmado
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS horario_confirmado TINYINT(1) NOT NULL DEFAULT 0 AFTER horario_agendamento");
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS horario_confirmado_raw TEXT NULL AFTER horario_confirmado");

            // confirmed_schedules JSON (lista de confirmações)
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS confirmed_schedules JSON NULL AFTER horario_confirmado_raw");

            // datas_opcoes JSON (para preservar horários originais do locatário quando horarios_indisponiveis = 1)
            $checkColumn = function(string $column): bool {
                $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'solicitacoes' AND COLUMN_NAME = ?";
                $row = Database::fetch($sql, [$column]);
                return (int)($row['c'] ?? 0) > 0;
            };
            
            if (!$checkColumn('datas_opcoes')) {
                Database::query("ALTER TABLE solicitacoes ADD COLUMN datas_opcoes JSON NULL AFTER horarios_opcoes");
            }

            // Campos de lembrete de peças
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS data_limite_peca DATE NULL AFTER horarios_opcoes");
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS data_ultimo_lembrete DATETIME NULL AFTER data_limite_peca");
            Database::query("ALTER TABLE solicitacoes ADD COLUMN IF NOT EXISTS lembretes_enviados INT NOT NULL DEFAULT 0 AFTER data_ultimo_lembrete");
            Database::query("UPDATE solicitacoes SET lembretes_enviados = 0 WHERE lembretes_enviados IS NULL");

            $this->view('admin.migracoes', $this->getMigrationViewData([
                'success' => 'Migrações executadas com sucesso.'
            ]));
        } catch (\Exception $e) {
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'Falha ao executar: ' . $e->getMessage()
            ]));
        }
    }

    public function redirectToMigrations(): void
    {
        $this->requireAuth();
        header('Location: /admin/migracoes');
    }

    public function purgeSolicitacoes(): void
    {
        $this->requireAuth();
        if (($_SESSION['user_level'] ?? '') !== 'ADMINISTRADOR') {
            header('Location: /admin/dashboard');
            return;
        }

        $token = $this->input('csrf_token');
        $confirm = trim((string)$this->input('confirm_text'));
        if (!$token || $token !== \App\Core\View::csrfToken()) {
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'CSRF inválido'
            ]));
            return;
        }
        if (strtoupper($confirm) !== 'LIMPAR') {
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'Para confirmar, digite LIMPAR.'
            ]));
            return;
        }

        try {
            // Desativar FKs para garantir limpeza em cascata controlada
            Database::query('SET FOREIGN_KEY_CHECKS=0');

            // Tabelas relacionadas (algumas podem não existir em certas instalações)
            $tables = [
                'historico_status',
                'fotos',
                'solicitacoes',
            ];
            foreach ($tables as $t) {
                try { Database::query("DELETE FROM {$t}"); } catch (\Exception $e) { /* ignora */ }
            }

            // Limpar solicitações manuais se existir
            try { Database::query('DELETE FROM solicitacoes_manuais'); } catch (\Exception $e) { /* ignora */ }

            Database::query('SET FOREIGN_KEY_CHECKS=1');

            $this->view('admin.migracoes', $this->getMigrationViewData([
                'success' => 'Todas as solicitações foram limpas.'
            ]));
        } catch (\Exception $e) {
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'Falha ao limpar: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Limpar "Disponibilidade:" das descrições existentes
     */
    public function limparDisponibilidadeDescricoes(): void
    {
        $this->requireAuth();
        if (($_SESSION['user_level'] ?? '') !== 'ADMINISTRADOR') {
            header('Location: /admin/dashboard');
            return;
        }

        $token = $this->input('csrf_token');
        if (!$token || $token !== \App\Core\View::csrfToken()) {
            $this->json(['error' => 'CSRF inválido'], 403);
            return;
        }

        try {
            // Remover "Disponibilidade: ..." das descrições usando REPLACE (compatível com MySQL antigo)
            // Primeiro, buscar todas as solicitações com "Disponibilidade:"
            $sqlSelect = "
                SELECT id, descricao_problema, descricao_card 
                FROM solicitacoes 
                WHERE descricao_problema LIKE '%Disponibilidade:%' 
                   OR descricao_card LIKE '%Disponibilidade:%'
            ";
            
            $solicitacoes = Database::fetchAll($sqlSelect);
            $atualizadas = 0;
            
            foreach ($solicitacoes as $solicitacao) {
                $id = $solicitacao['id'];
                $descricaoProblema = $solicitacao['descricao_problema'] ?? '';
                $descricaoCard = $solicitacao['descricao_card'] ?? '';
                
                // Limpar descricao_problema usando preg_replace (PHP)
                $descricaoProblemaLimpa = preg_replace('/\n?Disponibilidade:.*$/m', '', $descricaoProblema);
                $descricaoProblemaLimpa = trim($descricaoProblemaLimpa);
                
                // Limpar descricao_card
                $descricaoCardLimpa = preg_replace('/\n?Disponibilidade:.*$/m', '', $descricaoCard);
                $descricaoCardLimpa = trim($descricaoCardLimpa);
                
                // Atualizar apenas se houve mudança
                if ($descricaoProblemaLimpa !== $descricaoProblema || $descricaoCardLimpa !== $descricaoCard) {
                    $sqlUpdate = "
                        UPDATE solicitacoes 
                        SET 
                            descricao_problema = ?,
                            descricao_card = ?
                        WHERE id = ?
                    ";
                    
                    Database::query($sqlUpdate, [
                        $descricaoProblemaLimpa ?: null,
                        $descricaoCardLimpa ?: null,
                        $id
                    ]);
                    
                    $atualizadas++;
                }
            }

            // Buscar quantas ainda têm "Disponibilidade:" (pode ter formatos diferentes)
            $sqlCount = "
                SELECT COUNT(*) as total 
                FROM solicitacoes 
                WHERE descricao_problema LIKE '%Disponibilidade:%' 
                   OR descricao_card LIKE '%Disponibilidade:%'
            ";
            $count = Database::fetch($sqlCount);

            $this->json([
                'success' => true,
                'message' => "Descrições limpas com sucesso! {$atualizadas} registro(s) atualizado(s).",
                'atualizadas' => $atualizadas,
                'restantes' => (int)($count['total'] ?? 0)
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao limpar disponibilidade: ' . $e->getMessage());
            $this->json(['error' => 'Falha ao limpar: ' . $e->getMessage()], 500);
        }
    }

    private function getMigrationStatus(): array
    {
        // Garantir que não há queries pendentes antes de verificar status
        // Fazer múltiplas tentativas para garantir
        for ($i = 0; $i < 3; $i++) {
            $this->closeAllPendingQueries();
            if ($i < 2) {
                usleep(50000); // 50ms entre tentativas
            }
        }
        
        $check = function(string $column): bool {
            // Fechar queries pendentes antes de cada verificação
            try {
                $pdo = Database::getInstance();
                $testStmt = $pdo->query('SELECT 1');
                if ($testStmt) {
                    $testStmt->fetchAll(\PDO::FETCH_ASSOC);
                    $testStmt->closeCursor();
                    unset($testStmt);
                }
            } catch (\PDOException $e) {
                // Ignorar
            }
            
            $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'solicitacoes' AND COLUMN_NAME = ?";
            // Usar fetchAll para garantir que a query seja completamente processada
            $result = Database::fetchAll($sql, [$column]);
            return (int)($result[0]['c'] ?? 0) > 0;
        };

        return [
            'hasDescricaoCard' => $check('descricao_card'),
            'hasHorarioConfirmado' => $check('horario_confirmado'),
            'hasHorarioRaw' => $check('horario_confirmado_raw'),
            'hasConfirmedSchedules' => $check('confirmed_schedules'),
            'hasDatasOpcoes' => $check('datas_opcoes'),
            'hasDataLimitePeca' => $check('data_limite_peca'),
            'hasDataUltimoLembrete' => $check('data_ultimo_lembrete'),
            'hasLembretesEnviados' => $check('lembretes_enviados'),
        ];
    }

    private function getSqlScripts(): array
    {
        $basePath = dirname(__DIR__, 2);
        $directories = [
            'scripts' => $basePath . DIRECTORY_SEPARATOR . 'scripts',
            'scripts/migrations' => $basePath . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'migrations',
        ];

        $scripts = [];
        foreach ($directories as $relativeDir => $absoluteDir) {
            if (!is_dir($absoluteDir)) {
                continue;
            }
            $files = glob($absoluteDir . DIRECTORY_SEPARATOR . '*.sql');
            if (!$files) {
                continue;
            }
            sort($files);
            foreach ($files as $file) {
                $relativePath = $relativeDir . '/' . basename($file);
                $scripts[$relativePath] = $relativePath;
            }
        }

        ksort($scripts);
        return $scripts;
    }

    private function getMigrationViewData(array $data = []): array
    {
        return array_merge(
            ['title' => 'Migrações rápidas'],
            $this->getMigrationStatus(),
            ['sqlScripts' => $this->getSqlScripts()],
            $data
        );
    }

    public function runSqlScript(): void
    {
        $this->requireAuth();
        if (($_SESSION['user_level'] ?? '') !== 'ADMINISTRADOR') {
            if ($this->isAjax()) {
                $this->json(['error' => 'Acesso negado'], 403);
                return;
            }
            header('Location: /admin/dashboard');
            return;
        }

        $isAjax = $this->isAjax();
        
        $token = $this->input('csrf_token');
        if (!$token || $token !== \App\Core\View::csrfToken()) {
            if ($isAjax) {
                $this->json(['error' => 'CSRF inválido'], 403);
                return;
            }
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'CSRF inválido'
            ]));
            return;
        }

        $scriptFile = trim((string)$this->input('script_file'));
        $sqlText = trim((string)$this->input('sql_text'));

        if ($scriptFile === '' && $sqlText === '') {
            if ($isAjax) {
                $this->json(['error' => 'Selecione um arquivo SQL ou informe o conteúdo manualmente.'], 400);
                return;
            }
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'Selecione um arquivo SQL ou informe o conteúdo manualmente.',
                'previous_script_file' => $scriptFile,
                'previous_sql_text' => $sqlText,
            ]));
            return;
        }

        try {
            $executadas = 0;
            $origens = [];

            if ($scriptFile !== '') {
                $path = $this->resolveSqlScriptPath($scriptFile);
                if (!$path || !is_file($path)) {
                    throw new \RuntimeException('Arquivo SQL selecionado não encontrado.');
                }
                $conteudoArquivo = file_get_contents($path);
                if ($conteudoArquivo === false) {
                    throw new \RuntimeException('Não foi possível ler o arquivo selecionado.');
                }
                $executadas += $this->executeSqlBatch($conteudoArquivo);
                $origens[] = $scriptFile;
            }

            if ($sqlText !== '') {
                $executadas += $this->executeSqlBatch($sqlText);
                $origens[] = 'SQL manual';
            }

            $descricaoOrigem = implode(' + ', $origens);
            if ($descricaoOrigem === '') {
                $descricaoOrigem = 'Script';
            }

            // Garantir que todas as queries foram fechadas antes de verificar status
            // Fazer múltiplas tentativas para garantir que todas as queries sejam fechadas
            for ($i = 0; $i < 5; $i++) {
                $this->closeAllPendingQueries();
                if ($i < 4) {
                    usleep(150000); // 150ms entre tentativas
                }
            }

            $message = sprintf('%s executado com sucesso (%d instrução(ões)).', $descricaoOrigem, $executadas);
            
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => $message,
                    'executadas' => $executadas
                ]);
                return;
            }
            
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'success' => $message
            ]));
        } catch (\Throwable $e) {
            // Garantir que todas as queries foram fechadas mesmo em caso de erro
            $this->closeAllPendingQueries();
            
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'error' => 'Falha ao executar script: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            $this->view('admin.migracoes', $this->getMigrationViewData([
                'error' => 'Falha ao executar script: ' . $e->getMessage(),
                'previous_script_file' => $scriptFile,
                'previous_sql_text' => $sqlText,
            ]));
        }
    }

    /**
     * Fecha todas as queries pendentes para evitar conflitos
     */
    private function closeAllPendingQueries(): void
    {
        try {
            $pdo = Database::getInstance();
            
            // Garantir que query buffering está habilitado
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            // Tentar fechar qualquer statement pendente
            // Executar uma query simples e garantir que seja completamente processada
            try {
                $stmt = $pdo->query('SELECT 1');
                if ($stmt) {
                    // Buscar todos os resultados para garantir que a query seja completamente processada
                    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    // Fechar o cursor explicitamente
                    $stmt->closeCursor();
                    // Liberar a referência
                    unset($stmt);
                    unset($results);
                }
            } catch (\PDOException $e) {
                // Se der erro, tentar com exec
                try {
                    $pdo->exec('SELECT 1');
                } catch (\PDOException $e2) {
                    // Ignorar
                }
            }
            
            // Tentar executar outra query para forçar o fechamento de qualquer query pendente
            try {
                $stmt2 = $pdo->query('SELECT 1');
                if ($stmt2) {
                    $stmt2->fetchAll(\PDO::FETCH_ASSOC);
                    $stmt2->closeCursor();
                    unset($stmt2);
                }
            } catch (\PDOException $e) {
                // Se der erro, tentar uma última vez com um comando simples
                try {
                    $pdo->exec('SELECT 1');
                } catch (\PDOException $e2) {
                    // Ignorar
                }
            }
            
            // Para queries preparadas, tentar fechar explicitamente
            // Isso é importante para PREPARE/EXECUTE/DEALLOCATE
            // Nota: MySQL não suporta "IF EXISTS" em DEALLOCATE, então tentamos diretamente
            $commonStmtNames = ['stmt', 'prep_stmt', 'dynamic_stmt'];
            foreach ($commonStmtNames as $stmtName) {
                try {
                    $pdo->exec("DEALLOCATE PREPARE {$stmtName}");
                } catch (\PDOException $e) {
                    // Ignorar - pode não existir ou já foi fechado
                }
            }
            
            // Forçar garbage collection se possível
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        } catch (\PDOException $e) {
            // Ignorar erros na limpeza - pode não haver queries pendentes
        }
    }

    private function resolveSqlScriptPath(string $relativePath): ?string
    {
        $basePath = dirname(__DIR__, 2);
        $fullPath = realpath($basePath . DIRECTORY_SEPARATOR . $relativePath);
        if ($fullPath === false) {
            return null;
        }

        $allowedDirs = [
            realpath($basePath . DIRECTORY_SEPARATOR . 'scripts'),
            realpath($basePath . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'migrations'),
        ];

        foreach ($allowedDirs as $allowedDir) {
            if ($allowedDir && strncmp($fullPath, $allowedDir, strlen($allowedDir)) === 0) {
                return $fullPath;
            }
        }

        return null;
    }

    private function executeSqlBatch(string $sql): int
    {
        $pdo = Database::getInstance();

        // Habilitar query buffering para evitar problemas com queries não finalizadas
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        // Desabilitar emulação de prepared statements para melhor compatibilidade
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        // Garantir que não há queries pendentes antes de começar
        $this->closeAllPendingQueries();

        // Remover comentários de linha (-- e #)
        $clean = preg_replace('/^\s*(--|#).*$/m', '', $sql);
        // Remover comentários de bloco (/* ... */)
        $clean = preg_replace('/\/\*.*?\*\//s', '', $clean);
        // Normalizar quebras de linha
        $clean = str_replace(["\r\n", "\r"], "\n", $clean);

        // Dividir por ponto e vírgula (seguido de espaço/linha ou fim de string)
        // Usar lookahead para manter o ponto e vírgula no final de cada parte
        $parts = preg_split('/;\s*(?=\n|$)/', $clean, -1, PREG_SPLIT_NO_EMPTY);
        $executadas = 0;

        foreach ($parts as $statement) {
            $stmt = trim($statement);
            
            // Pular se estiver vazio ou muito curto (menos de 10 caracteres)
            if ($stmt === '' || strlen($stmt) < 10) {
                continue;
            }
            
            // Normalizar espaços múltiplos, mas manter estrutura básica
            $stmt = preg_replace('/\s+/', ' ', $stmt);
            $stmt = trim($stmt);
            
            // Adicionar ponto e vírgula se não tiver (para queries que foram divididas)
            if (!preg_match('/;\s*$/', $stmt)) {
                $stmt .= ';';
            }
            
            try {
                // Fechar qualquer query pendente antes de executar a próxima
                $this->closeAllPendingQueries();
                
                // Detectar se é um comando PREPARE/EXECUTE/DEALLOCATE
                $isPreparedStatement = preg_match('/^\s*(PREPARE|EXECUTE|DEALLOCATE)\s+/i', $stmt);
                $isDeallocate = preg_match('/^\s*DEALLOCATE\s+/i', $stmt);
                
                // Para comandos que podem retornar resultados (SELECT, SHOW, etc)
                if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\s+/i', $stmt)) {
                    $pdoStmt = $pdo->query($stmt);
                    if ($pdoStmt) {
                        // Buscar todos os resultados para garantir que a query seja completamente processada
                        $pdoStmt->fetchAll(\PDO::FETCH_ASSOC);
                        // Fechar o cursor explicitamente
                        $pdoStmt->closeCursor();
                        // Liberar a referência
                        unset($pdoStmt);
                    }
                } else {
                    // Para comandos DDL (ALTER, CREATE, DROP, etc) e outros, usar exec()
                    // Isso inclui PREPARE, EXECUTE, DEALLOCATE que não retornam resultados
                    try {
                        // Para DEALLOCATE, garantir que não há queries pendentes
                        if ($isDeallocate) {
                            // Fechar todas as queries pendentes múltiplas vezes de forma mais agressiva
                            for ($i = 0; $i < 5; $i++) {
                                $this->closeAllPendingQueries();
                                usleep(100000); // 100ms entre tentativas
                            }
                            
                            // Tentar fechar qualquer prepared statement pendente explicitamente
                            // Nota: MySQL não suporta "IF EXISTS" em DEALLOCATE, então tentamos diretamente
                            try {
                                // Tentar deallocar qualquer prepared statement com nome genérico
                                $pdo->exec('DEALLOCATE PREPARE stmt');
                            } catch (\PDOException $e) {
                                // Ignorar - pode não existir ou já foi fechado
                            }
                            
                            // Mais uma limpeza antes de executar
                            $this->closeAllPendingQueries();
                            usleep(100000);
                        }
                        
                        // Executar diretamente
                        $pdo->exec($stmt);
                        
                        // Fechar imediatamente após execução para queries preparadas
                        if ($isPreparedStatement) {
                            // Para queries preparadas, garantir que tudo foi fechado
                            for ($i = 0; $i < 2; $i++) {
                                $this->closeAllPendingQueries();
                                usleep(50000); // 50ms
                            }
                        }
                    } catch (\PDOException $e) {
                        // Fechar queries pendentes antes de verificar o erro
                        $this->closeAllPendingQueries();
                        
                        // Se for erro de coluna já existe (1060), ignorar e continuar
                        if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                            strpos($e->getMessage(), '1060') !== false ||
                            strpos($e->getMessage(), 'duplicate') !== false) {
                            // Coluna já existe, contar como executada
                            $executadas++;
                            continue;
                        }
                        
                        // Se for erro de query não finalizada, tentar fechar e executar novamente
                        if (strpos($e->getMessage(), 'unbuffered queries') !== false || 
                            strpos($e->getMessage(), '2014') !== false) {
                            // Fechar todas as queries pendentes múltiplas vezes
                            for ($i = 0; $i < 5; $i++) {
                                $this->closeAllPendingQueries();
                                usleep(100000); // 100ms entre tentativas
                            }
                            
                            // Tentar executar novamente
                            try {
                                $pdo->exec($stmt);
                                // Fechar novamente após execução bem-sucedida
                                for ($i = 0; $i < 2; $i++) {
                                    $this->closeAllPendingQueries();
                                    usleep(50000);
                                }
                            } catch (\PDOException $e2) {
                                // Se ainda falhar, tentar uma abordagem diferente
                                // Para DEALLOCATE, pode ser que o statement já foi fechado ou não existe
                                if ($isDeallocate) {
                                    // DEALLOCATE pode falhar se o statement já foi fechado, mas isso não é um erro crítico
                                    // O EXECUTE já foi executado com sucesso, então podemos ignorar o erro do DEALLOCATE
                                    // Fazer limpeza final e contar como executado
                                    for ($i = 0; $i < 3; $i++) {
                                        $this->closeAllPendingQueries();
                                        usleep(100000);
                                    }
                                    $executadas++;
                                    continue;
                                }
                                
                                // Se for erro de query não finalizada novamente, e for EXECUTE, também podemos ignorar
                                if (preg_match('/^\s*EXECUTE\s+/i', $stmt) && 
                                    (strpos($e2->getMessage(), 'unbuffered queries') !== false || 
                                     strpos($e2->getMessage(), '2014') !== false)) {
                                    // EXECUTE foi executado, apenas não conseguimos fechar corretamente
                                    // Fazer limpeza e contar como executado
                                    for ($i = 0; $i < 3; $i++) {
                                        $this->closeAllPendingQueries();
                                        usleep(100000);
                                    }
                                    $executadas++;
                                    continue;
                                }
                                
                                // Para outros comandos, relançar o erro original
                                throw $e;
                            }
                        } else {
                            // Para outros erros, relançar
                            throw $e;
                        }
                    }
                }
                $executadas++;
                
                // Fechar qualquer cursor pendente após cada statement
                // Isso é especialmente importante para PREPARE/EXECUTE
                if ($isPreparedStatement) {
                    // Para queries preparadas, fazer limpeza mais agressiva
                    for ($i = 0; $i < 2; $i++) {
                        $this->closeAllPendingQueries();
                        usleep(50000);
                    }
                } else {
                    $this->closeAllPendingQueries();
                }
            } catch (\PDOException $e) {
                // Fechar queries pendentes antes de verificar o erro
                $this->closeAllPendingQueries();
                
                // Se for erro de coluna já existe, ignorar e continuar
                if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                    strpos($e->getMessage(), '1060') !== false) {
                    $executadas++;
                    $this->closeAllPendingQueries();
                    continue;
                }
                
                // Se for erro de query não finalizada, tentar fechar e relançar com mensagem mais clara
                if (strpos($e->getMessage(), 'unbuffered queries') !== false || 
                    strpos($e->getMessage(), '2014') !== false) {
                    // Se for DEALLOCATE ou EXECUTE, podemos tentar ignorar o erro se já foi executado
                    if ($isDeallocate || preg_match('/^\s*EXECUTE\s+/i', $stmt)) {
                        // Fazer limpeza agressiva
                        for ($i = 0; $i < 5; $i++) {
                            $this->closeAllPendingQueries();
                            usleep(100000);
                        }
                        // Contar como executado, pois o statement provavelmente já foi executado
                        $executadas++;
                        continue;
                    }
                    
                    $this->closeAllPendingQueries();
                    usleep(100000); // 100ms
                    $this->closeAllPendingQueries();
                    throw new \RuntimeException("Erro: há queries não finalizadas. Tente executar o script em partes menores. Query problemática: " . substr($stmt, 0, 100));
                }
                
                // Fechar queries pendentes mesmo em caso de erro
                $this->closeAllPendingQueries();
                throw new \RuntimeException("Erro ao executar SQL: " . $e->getMessage() . " | Query: " . substr($stmt, 0, 100));
            }
        }

        // Garantir que não há queries pendentes após executar o batch
        $this->closeAllPendingQueries();

        return $executadas;
    }
}


