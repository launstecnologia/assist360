<?php

namespace App\Models;

use App\Core\Database;

class SolicitacaoMensagem extends Model
{
    protected string $table = 'solicitacao_mensagens';
    protected array $fillable = [
        'solicitacao_id', 'whatsapp_instance_id', 'instance_name',
        'numero_remetente', 'numero_destinatario', 'mensagem',
        'tipo', 'status', 'message_id', 'erro', 'metadata', 'is_lida',
        'created_at', 'updated_at'
    ];

    /**
     * Busca todas as mensagens de uma solicitação ordenadas por data
     */
    public function getBySolicitacao(int $solicitacaoId): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE solicitacao_id = ?
            ORDER BY created_at ASC
        ";
        return Database::fetchAll($sql, [$solicitacaoId]);
    }

    /**
     * Busca mensagens não lidas de uma solicitação
     */
    public function getNaoLidas(int $solicitacaoId): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE solicitacao_id = ?
              AND tipo = 'RECEBIDA'
              AND (is_lida = 0 OR is_lida IS NULL)
            ORDER BY created_at ASC
        ";
        return Database::fetchAll($sql, [$solicitacaoId]);
    }

    /**
     * Marca mensagens como lidas
     */
    public function marcarComoLidas(int $solicitacaoId): bool
    {
        try {
            $sql = "
                UPDATE {$this->table}
                SET is_lida = 1, status = 'LIDA', updated_at = NOW()
                WHERE solicitacao_id = ?
                  AND tipo = 'RECEBIDA'
                  AND (is_lida = 0 OR is_lida IS NULL)
            ";
            Database::query($sql, [$solicitacaoId]);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao marcar mensagens como lidas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conta mensagens não lidas de uma solicitação
     */
    public function countNaoLidas(int $solicitacaoId): int
    {
        $sql = "
            SELECT COUNT(*) as total FROM {$this->table}
            WHERE solicitacao_id = ?
              AND tipo = 'RECEBIDA'
              AND (is_lida = 0 OR is_lida IS NULL)
        ";
        $result = Database::fetch($sql, [$solicitacaoId]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Busca última mensagem de uma solicitação
     */
    public function getUltimaMensagem(int $solicitacaoId): ?array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE solicitacao_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ";
        return Database::fetch($sql, [$solicitacaoId]);
    }
}

