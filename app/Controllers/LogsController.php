<?php

namespace App\Controllers;

use App\Core\Controller;

class LogsController extends Controller
{
    public function __construct()
    {
        // Verificar autenticação e permissão de admin
        $this->requireAdmin();
    }

    /**
     * Exibe página de visualização de logs
     */
    public function index(): void
    {
        $availableLogs = $this->getAvailableLogs();
        
        // Se não foi especificado um arquivo, tentar usar o primeiro disponível
        $logFile = $this->input('file', '');
        if (empty($logFile) && !empty($availableLogs)) {
            // Tentar php_error_log primeiro, senão usar o primeiro disponível
            $phpLogExists = false;
            foreach ($availableLogs as $log) {
                if ($log['name'] === 'php_error_log') {
                    $phpLogExists = true;
                    $logFile = 'php_error_log';
                    break;
                }
            }
            if (!$phpLogExists) {
                $logFile = $availableLogs[0]['name'];
            }
        } elseif (empty($logFile)) {
            $logFile = 'php_error_log'; // Fallback padrão
        }
        
        $filter = $this->input('filter', '');
        $lines = (int)($this->input('lines', 100));
        
        // Limitar número de linhas
        if ($lines > 1000) {
            $lines = 1000;
        }
        if ($lines < 10) {
            $lines = 100;
        }

        $logs = $this->readLogs($logFile, $lines, $filter);

        $this->view('admin.logs.index', [
            'title' => 'Visualizador de Logs',
            'pageTitle' => 'Visualizador de Logs',
            'currentPage' => 'logs',
            'logs' => $logs,
            'logFile' => $logFile,
            'filter' => $filter,
            'lines' => $lines,
            'availableLogs' => $availableLogs
        ]);
    }

    /**
     * Lê logs do arquivo
     */
    private function readLogs(string $logFile, int $lines = 100, string $filter = ''): array
    {
        $logPath = $this->getLogPath($logFile);
        
        if (!file_exists($logPath)) {
            return [
                'content' => [],
                'error' => "Arquivo de log não encontrado: {$logFile}. O arquivo não existe no caminho especificado: {$logPath}",
                'file_exists' => false,
                'total_lines' => 0,
                'showing_lines' => 0,
                'file_size' => 0
            ];
        }

        try {
            // Ler arquivo
            $content = file_get_contents($logPath);
            
            if ($content === false) {
                return [
                    'content' => [],
                    'error' => "Erro ao ler arquivo de log: {$logFile}",
                    'file_exists' => true,
                    'total_lines' => 0,
                    'showing_lines' => 0,
                    'file_size' => 0
                ];
            }

            // Dividir em linhas
            $allLines = explode("\n", $content);
            
            // Aplicar filtro se fornecido
            if (!empty($filter)) {
                $allLines = array_filter($allLines, function($line) use ($filter) {
                    return stripos($line, $filter) !== false;
                });
            }

            // Pegar últimas N linhas
            $allLines = array_values($allLines);
            $totalLines = count($allLines);
            $startLine = max(0, $totalLines - $lines);
            $selectedLines = array_slice($allLines, $startLine);

            // Formatar linhas com numeração e destacar erros/debug
            $formattedLines = [];
            foreach ($selectedLines as $index => $line) {
                $lineNumber = $startLine + $index + 1;
                $formattedLines[] = [
                    'number' => $lineNumber,
                    'content' => $line,
                    'type' => $this->detectLogType($line),
                    'highlight' => $this->shouldHighlight($line)
                ];
            }

            return [
                'content' => array_reverse($formattedLines), // Mais recentes primeiro
                'total_lines' => $totalLines,
                'showing_lines' => count($formattedLines),
                'file_exists' => true,
                'file_size' => filesize($logPath),
                'file_modified' => filemtime($logPath)
            ];

        } catch (\Exception $e) {
            return [
                'content' => [],
                'error' => "Erro ao processar log: " . $e->getMessage(),
                'file_exists' => true,
                'total_lines' => 0,
                'showing_lines' => 0,
                'file_size' => 0
            ];
        }
    }

    /**
     * Detecta o tipo de log
     */
    private function detectLogType(string $line): string
    {
        if (stripos($line, 'ERROR') !== false || stripos($line, 'ERRO') !== false) {
            return 'error';
        }
        if (stripos($line, 'WARNING') !== false || stripos($line, 'AVISO') !== false) {
            return 'warning';
        }
        if (stripos($line, 'DEBUG') !== false) {
            return 'debug';
        }
        if (stripos($line, 'SUCCESS') !== false || stripos($line, 'SUCESSO') !== false) {
            return 'success';
        }
        return 'info';
    }

    /**
     * Verifica se a linha deve ser destacada
     */
    private function shouldHighlight(string $line): bool
    {
        $highlightKeywords = [
            'DEBUG confirmacaoHorario',
            'DEBUG confirmacao.php',
            'ERROR',
            'ERRO',
            'Exception',
            'Fatal error',
            'Warning'
        ];

        foreach ($highlightKeywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna o caminho completo do arquivo de log
     */
    private function getLogPath(string $logFile): string
    {
        // Detectar sistema operacional
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Lista de arquivos de log permitidos
        $allowedLogs = [
            'php_error_log' => $isWindows 
                ? 'C:/xampp/php/logs/php_error_log' 
                : (ini_get('error_log') ?: '/var/log/php_errors.log'),
            'whatsapp_evolution_api.log' => __DIR__ . '/../../storage/logs/whatsapp_evolution_api.log',
            'webhook_whatsapp.log' => __DIR__ . '/../../storage/logs/webhook_whatsapp.log',
            'error.log' => __DIR__ . '/../../storage/logs/error.log',
            'app.log' => __DIR__ . '/../../storage/logs/app.log'
        ];

        // Se o arquivo está na lista permitida, usar o caminho definido
        if (isset($allowedLogs[$logFile])) {
            $path = $allowedLogs[$logFile];
            
            // Para php_error_log, tentar múltiplos caminhos se o primeiro não existir
            if ($logFile === 'php_error_log' && !file_exists($path)) {
                // Tentar caminhos alternativos
                $alternativePaths = [
                    ini_get('error_log'),
                    '/var/log/php_errors.log',
                    '/var/log/php-fpm/error.log',
                    '/var/log/apache2/error.log',
                    sys_get_temp_dir() . '/php_errors.log'
                ];
                
                foreach ($alternativePaths as $altPath) {
                    if ($altPath && file_exists($altPath)) {
                        return $altPath;
                    }
                }
            }
            
            return $path;
        }

        // Caso contrário, tentar construir o caminho relativo
        $basePath = __DIR__ . '/../../storage/logs/';
        $fullPath = $basePath . basename($logFile);
        
        // Verificar se o arquivo existe no caminho relativo
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        // Se não encontrou, retornar o caminho do php_error_log como padrão
        return $allowedLogs['php_error_log'];
    }

    /**
     * Retorna lista de logs disponíveis
     */
    private function getAvailableLogs(): array
    {
        $logs = [];

        // PHP Error Log - tentar múltiplos caminhos
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $phpLogPaths = $isWindows 
            ? ['C:/xampp/php/logs/php_error_log']
            : [
                ini_get('error_log'),
                '/var/log/php_errors.log',
                '/var/log/php-fpm/error.log',
                '/var/log/apache2/error.log',
                sys_get_temp_dir() . '/php_errors.log'
            ];
        
        $phpLogPath = null;
        foreach ($phpLogPaths as $path) {
            if ($path && file_exists($path)) {
                $phpLogPath = $path;
                break;
            }
        }
        
        if ($phpLogPath) {
            $logs[] = [
                'name' => 'php_error_log',
                'label' => 'PHP Error Log',
                'path' => $phpLogPath,
                'size' => filesize($phpLogPath),
                'modified' => filemtime($phpLogPath)
            ];
        }

        // WhatsApp Log
        $whatsappLogPath = __DIR__ . '/../../storage/logs/whatsapp_evolution_api.log';
        if (file_exists($whatsappLogPath)) {
            $logs[] = [
                'name' => 'whatsapp_evolution_api.log',
                'label' => 'WhatsApp Evolution API',
                'path' => $whatsappLogPath,
                'size' => filesize($whatsappLogPath),
                'modified' => filemtime($whatsappLogPath)
            ];
        }

        // Error Log
        $errorLogPath = __DIR__ . '/../../storage/logs/error.log';
        if (file_exists($errorLogPath)) {
            $logs[] = [
                'name' => 'error.log',
                'label' => 'Error Log',
                'path' => $errorLogPath,
                'size' => filesize($errorLogPath),
                'modified' => filemtime($errorLogPath)
            ];
        }

        // App Log
        $appLogPath = __DIR__ . '/../../storage/logs/app.log';
        if (file_exists($appLogPath)) {
            $logs[] = [
                'name' => 'app.log',
                'label' => 'App Log',
                'path' => $appLogPath,
                'size' => filesize($appLogPath),
                'modified' => filemtime($appLogPath)
            ];
        }

        // Webhook WhatsApp Log
        $webhookLogPath = __DIR__ . '/../../storage/logs/webhook_whatsapp.log';
        if (file_exists($webhookLogPath)) {
            $logs[] = [
                'name' => 'webhook_whatsapp.log',
                'label' => 'Webhook WhatsApp',
                'path' => $webhookLogPath,
                'size' => filesize($webhookLogPath),
                'modified' => filemtime($webhookLogPath)
            ];
        }

        return $logs;
    }

    /**
     * Limpa o arquivo de log
     */
    public function limpar(): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }

        $logFile = $this->input('file', 'php_error_log');
        $logPath = $this->getLogPath($logFile);

        if (!file_exists($logPath)) {
            $this->json(['success' => false, 'error' => 'Arquivo não encontrado'], 404);
            return;
        }

        try {
            // Criar backup antes de limpar
            $backupPath = $logPath . '.backup.' . date('Y-m-d_H-i-s');
            copy($logPath, $backupPath);

            // Limpar arquivo
            file_put_contents($logPath, '');

            $this->json([
                'success' => true,
                'message' => 'Log limpo com sucesso. Backup criado em: ' . basename($backupPath)
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => 'Erro ao limpar log: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download do arquivo de log
     */
    public function download(): void
    {
        $logFile = $this->input('file', 'php_error_log');
        $logPath = $this->getLogPath($logFile);

        if (!file_exists($logPath)) {
            $this->view('admin.error', [
                'title' => 'Arquivo não encontrado',
                'message' => 'O arquivo de log solicitado não foi encontrado.'
            ]);
            return;
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . basename($logFile) . '_' . date('Y-m-d_H-i-s') . '.txt"');
        header('Content-Length: ' . filesize($logPath));
        
        readfile($logPath);
        exit;
    }
}

