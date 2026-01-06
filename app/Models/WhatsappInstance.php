<?php

namespace App\Models;

use App\Core\Database;

class WhatsappInstance extends Model
{
    protected string $table = 'whatsapp_instances';
    protected array $fillable = [
        'nome', 'instance_name', 'numero_whatsapp', 'qrcode', 'status',
        'is_ativo', 'is_padrao', 'api_url', 'api_key', 'token', 'observacoes',
        'created_at', 'updated_at'
    ];

    /**
     * Busca a instância padrão (ativa e marcada como padrão)
     */
    public function getPadrao(): ?array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE is_padrao = 1
              AND is_ativo = 1
            ORDER BY (status = 'CONECTADO') DESC, updated_at DESC
            LIMIT 1
        ";
        return Database::fetch($sql);
    }

    /**
     * Busca todas as instâncias ativas
     */
    public function getAtivas(): array
    {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE is_ativo = 1 
            ORDER BY is_padrao DESC, nome ASC
        ";
        return Database::fetchAll($sql);
    }

    /**
     * Define uma instância como padrão (desmarca as outras)
     */
    public function setPadrao(int $id): bool
    {
        try {
            // Desmarcar todas
            Database::query("UPDATE {$this->table} SET is_padrao = 0");
            
            // Marcar a selecionada
            Database::query("UPDATE {$this->table} SET is_padrao = 1 WHERE id = ?", [$id]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao definir instância padrão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza o status da instância
     */
    public function atualizarStatus(int $id, string $status, ?string $numeroWhatsapp = null): bool
    {
        try {
            $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            if ($numeroWhatsapp) {
                $data['numero_whatsapp'] = $numeroWhatsapp;
            }
            $this->update($id, $data);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza o QR code da instância
     */
    public function atualizarQrcode(int $id, ?string $qrcode): bool
    {
        try {
            $this->update($id, [
                'qrcode' => $qrcode,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao atualizar QR code: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca uma instância pelo nome da instância (instance_name)
     */
    public function findByInstanceName(string $instanceName): ?array
    {
        $instances = $this->findAll(['instance_name' => $instanceName], null, 1);
        return !empty($instances) ? $instances[0] : null;
    }
}

