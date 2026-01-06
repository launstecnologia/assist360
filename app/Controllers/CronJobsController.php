<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CronJob;

class CronJobsController extends Controller
{
    private CronJob $cronJobModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->cronJobModel = new CronJob();
    }

    /**
     * Lista todos os cron jobs
     */
    public function index(): void
    {
        $cronJobs = $this->cronJobModel->getAll();
        
        $this->view('admin.cron_jobs.index', [
            'title' => 'Gerenciar Cron Jobs',
            'pageTitle' => 'Gerenciar Cron Jobs',
            'currentPage' => 'cron-jobs',
            'cronJobs' => $cronJobs
        ]);
    }

    /**
     * Executa um cron job manualmente
     */
    public function executar(int $id): void
    {
        try {
            $cronJob = $this->cronJobModel->find($id);
            
            if (!$cronJob) {
                $this->json(['success' => false, 'message' => 'Cron job não encontrado'], 404);
                return;
            }

            $inicio = microtime(true);
            
            // Executar o método do controller
            $controllerClass = 'App\\Controllers\\' . $cronJob['classe_controller'];
            if (!class_exists($controllerClass)) {
                throw new \Exception("Classe {$controllerClass} não encontrada");
            }

            $controller = new $controllerClass();
            $metodo = $cronJob['metodo'];
            
            if (!method_exists($controller, $metodo)) {
                // Tentar método privado via reflexão
                $reflection = new \ReflectionClass($controller);
                if (!$reflection->hasMethod($metodo)) {
                    throw new \Exception("Método {$metodo} não encontrado em {$controllerClass}");
                }
                $method = $reflection->getMethod($metodo);
                $method->setAccessible(true);
                
                // Capturar output
                ob_start();
                $method->invoke($controller);
                $output = ob_get_clean();
            } else {
                ob_start();
                $controller->$metodo();
                $output = ob_get_clean();
            }

            $tempoExecucao = (microtime(true) - $inicio) * 1000; // em milissegundos
            
            // Atualizar informações
            $this->cronJobModel->atualizarAposExecucao($id, true, null, (int)$tempoExecucao);
            $this->cronJobModel->registrarExecucao($id, 'sucesso', 'Execução manual bem-sucedida', [
                'tempo_execucao_ms' => (int)$tempoExecucao,
                'output' => $output
            ], (int)$tempoExecucao);

            $this->json([
                'success' => true,
                'message' => 'Cron job executado com sucesso',
                'tempo_execucao_ms' => (int)$tempoExecucao,
                'output' => $output
            ]);

        } catch (\Exception $e) {
            $tempoExecucao = isset($inicio) ? (microtime(true) - $inicio) * 1000 : 0;
            
            if (isset($id) && isset($cronJob)) {
                $this->cronJobModel->atualizarAposExecucao($id, false, $e->getMessage(), (int)$tempoExecucao);
                $this->cronJobModel->registrarExecucao($id, 'erro', $e->getMessage(), [
                    'tempo_execucao_ms' => (int)$tempoExecucao
                ], (int)$tempoExecucao);
            }

            $this->json([
                'success' => false,
                'message' => 'Erro ao executar cron job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle ativo/inativo
     */
    public function toggleAtivo(int $id): void
    {
        try {
            $this->cronJobModel->toggleAtivo($id);
            $cronJob = $this->cronJobModel->find($id);
            
            $this->json([
                'success' => true,
                'message' => 'Status atualizado',
                'ativo' => (bool)$cronJob['ativo']
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza frequência
     */
    public function atualizarFrequencia(int $id): void
    {
        try {
            $frequencia = (int)$this->input('frequencia_minutos');
            
            if ($frequencia < 1) {
                $this->json(['success' => false, 'message' => 'Frequência deve ser no mínimo 1 minuto'], 400);
                return;
            }

            $this->cronJobModel->atualizarFrequencia($id, $frequencia);
            
            $this->json([
                'success' => true,
                'message' => 'Frequência atualizada com sucesso'
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Busca histórico de execuções
     */
    public function historico(int $id): void
    {
        try {
            $historico = $this->cronJobModel->getHistoricoExecucoes($id, 50);
            
            $this->json([
                'success' => true,
                'historico' => $historico
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Executa todos os cron jobs que estão na fila
     * Pode ser chamado via AJAX periodicamente
     */
    public function executarPendentes(): void
    {
        try {
            $cronJobs = $this->cronJobModel->getCronJobsParaExecutar();
            $executados = 0;
            $erros = 0;

            foreach ($cronJobs as $cronJob) {
                try {
                    $inicio = microtime(true);
                    
                    $controllerClass = 'App\\Controllers\\' . $cronJob['classe_controller'];
                    if (!class_exists($controllerClass)) {
                        throw new \Exception("Classe {$controllerClass} não encontrada");
                    }

                    $controller = new $controllerClass();
                    $metodo = $cronJob['metodo'];
                    
                    // Tentar método privado via reflexão
                    $reflection = new \ReflectionClass($controller);
                    if (!$reflection->hasMethod($metodo)) {
                        throw new \Exception("Método {$metodo} não encontrado");
                    }
                    $method = $reflection->getMethod($metodo);
                    $method->setAccessible(true);
                    
                    ob_start();
                    $method->invoke($controller);
                    $output = ob_get_clean();

                    $tempoExecucao = (microtime(true) - $inicio) * 1000;
                    
                    $this->cronJobModel->atualizarAposExecucao($cronJob['id'], true, null, (int)$tempoExecucao);
                    $this->cronJobModel->registrarExecucao($cronJob['id'], 'sucesso', 'Execução automática bem-sucedida', [
                        'tempo_execucao_ms' => (int)$tempoExecucao
                    ], (int)$tempoExecucao);
                    
                    $executados++;
                } catch (\Exception $e) {
                    $tempoExecucao = isset($inicio) ? (microtime(true) - $inicio) * 1000 : 0;
                    $this->cronJobModel->atualizarAposExecucao($cronJob['id'], false, $e->getMessage(), (int)$tempoExecucao);
                    $this->cronJobModel->registrarExecucao($cronJob['id'], 'erro', $e->getMessage(), [
                        'tempo_execucao_ms' => (int)$tempoExecucao
                    ], (int)$tempoExecucao);
                    $erros++;
                }
            }

            $this->json([
                'success' => true,
                'message' => 'Cron jobs processados',
                'executados' => $executados,
                'erros' => $erros,
                'total' => count($cronJobs)
            ]);

        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

