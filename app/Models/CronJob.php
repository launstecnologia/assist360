<?php

namespace App\Models;

use App\Core\Database;

class CronJob extends Model
{
    protected string $table = 'cron_jobs';
    protected array $fillable = [
        'nome', 'descricao', 'classe_controller', 'metodo', 'frequencia_minutos',
        'ativo', 'ultima_execucao', 'proxima_execucao', 'total_execucoes',
        'total_erros', 'ultimo_erro', 'configuracao'
    ];

    /**
     * Busca todos os cron jobs
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY nome ASC";
        return Database::fetchAll($sql);
    }

    /**
     * Busca um cron job por ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return Database::fetch($sql, [$id]);
    }

    /**
     * Atualiza um cron job
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $fields[] = "{$key} = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        Database::query($sql, $values);
        return true;
    }

    /**
     * Busca todos os cron jobs ativos que precisam ser executados
     */
    public function getCronJobsParaExecutar(): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE ativo = 1
            AND (proxima_execucao IS NULL OR proxima_execucao <= NOW())
            ORDER BY proxima_execucao ASC
        ";
        
        return Database::fetchAll($sql);
    }

    /**
     * Atualiza informações após execução
     */
    public function atualizarAposExecucao(int $id, bool $sucesso, ?string $erro = null, int $tempoExecucao = 0): void
    {
        $sql = "
            UPDATE {$this->table}
            SET ultima_execucao = NOW(),
                proxima_execucao = DATE_ADD(NOW(), INTERVAL frequencia_minutos MINUTE),
                total_execucoes = total_execucoes + 1,
                total_erros = total_erros + ?,
                ultimo_erro = ?,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        Database::query($sql, [
            $sucesso ? 0 : 1,
            $erro,
            $id
        ]);
    }

    /**
     * Registra execução no histórico
     */
    public function registrarExecucao(int $cronJobId, string $status, string $mensagem, ?array $dados = null, ?int $tempoExecucao = null): void
    {
        $sql = "
            INSERT INTO cron_job_execucoes 
            (cron_job_id, status, mensagem, dados_execucao, tempo_execucao, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        
        Database::query($sql, [
            $cronJobId,
            $status,
            $mensagem,
            $dados ? json_encode($dados) : null,
            $tempoExecucao
        ]);
    }

    /**
     * Busca histórico de execuções
     */
    public function getHistoricoExecucoes(int $cronJobId, int $limit = 50): array
    {
        $sql = "
            SELECT * FROM cron_job_execucoes
            WHERE cron_job_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ";
        
        return Database::fetchAll($sql, [$cronJobId, $limit]);
    }

    /**
     * Toggle ativo/inativo
     */
    public function toggleAtivo(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET ativo = NOT ativo WHERE id = ?";
        Database::query($sql, [$id]);
        return true;
    }

    /**
     * Atualiza frequência
     */
    public function atualizarFrequencia(int $id, int $frequenciaMinutos): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET frequencia_minutos = ?,
                proxima_execucao = DATE_ADD(NOW(), INTERVAL ? MINUTE)
            WHERE id = ?
        ";
        
        Database::query($sql, [$frequenciaMinutos, $frequenciaMinutos, $id]);
        return true;
    }
}

