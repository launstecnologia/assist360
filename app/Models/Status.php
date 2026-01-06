<?php

namespace App\Models;

use App\Core\Database;

class Status extends Model
{
    protected string $table = 'status';
    protected array $fillable = [
        'nome', 'cor', 'icone', 'ordem', 'visivel_kanban', 'template_mensagem', 'notificar_automatico', 'status', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'ordem' => 'int',
        'notificar_automatico' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getAtivos(): array
    {
        return $this->findAll(['status' => 'ATIVO'], 'ordem ASC');
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY ordem ASC, created_at DESC";
        return Database::fetchAll($sql);
    }

    public function getKanban(): array
    {
        // Retornar apenas os status marcados como visíveis no Kanban
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE visivel_kanban = 1
            AND status = 'ATIVO'
            ORDER BY ordem ASC
        ";
        
        return Database::fetchAll($sql);
    }

    public function findByNome(string $nome): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nome = ? AND status = 'ATIVO'";
        return Database::fetch($sql, [$nome]);
    }

    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Se não foi definida ordem, usar a próxima disponível
        if (!isset($data['ordem']) || empty($data['ordem'])) {
            $data['ordem'] = $this->getProximaOrdem();
        }
        
        // Converter boolean para inteiro se necessário
        if (isset($data['visivel_kanban'])) {
            $data['visivel_kanban'] = $data['visivel_kanban'] ? 1 : 0;
        }
        
        return parent::create($data);
    }

    public function getProximaOrdem(): int
    {
        $sql = "SELECT MAX(ordem) as max_ordem FROM {$this->table}";
        $result = Database::fetch($sql);
        return ($result['max_ordem'] ?? 0) + 1;
    }

    public function isUsado(int $statusId): bool
    {
        $sql = "SELECT COUNT(*) as total FROM solicitacoes WHERE status_id = ?";
        $result = Database::fetch($sql, [$statusId]);
        return ($result['total'] ?? 0) > 0;
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Converter boolean para inteiro se necessário
        if (isset($data['visivel_kanban'])) {
            $data['visivel_kanban'] = $data['visivel_kanban'] ? 1 : 0;
        }
        
        return parent::update($id, $data);
    }

    public function updateOrdem(array $statusIds): bool
    {
        Database::beginTransaction();
        
        try {
            foreach ($statusIds as $index => $statusId) {
                $this->update($statusId, ['ordem' => $index + 1]);
            }
            
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            return false;
        }
    }

    public function getSolicitacoes(int $statusId): array
    {
        $sql = "
            SELECT 
                s.*,
                c.nome as categoria_nome,
                sc.nome as subcategoria_nome,
                i.nome as imobiliaria_nome
            FROM solicitacoes s
            LEFT JOIN categorias c ON s.categoria_id = c.id
            LEFT JOIN subcategorias sc ON s.subcategoria_id = sc.id
            LEFT JOIN imobiliarias i ON s.imobiliaria_id = i.id
            WHERE s.status_id = ?
            ORDER BY s.created_at DESC
        ";
        
        return Database::fetchAll($sql, [$statusId]);
    }

    public function getEstatisticas(int $statusId, string $periodo = '30'): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_solicitacoes,
                AVG(TIMESTAMPDIFF(HOUR, s.created_at, s.updated_at)) as tempo_medio_permanencia
            FROM solicitacoes s
            WHERE s.status_id = ? 
            AND s.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        return Database::fetch($sql, [$statusId, $periodo]) ?: [];
    }

    public function getProximosStatus(int $statusId): array
    {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE ordem > (SELECT ordem FROM {$this->table} WHERE id = ?) 
            AND status = 'ATIVO'
            ORDER BY ordem ASC
        ";
        
        return Database::fetchAll($sql, [$statusId]);
    }

    public function getStatusAnteriores(int $statusId): array
    {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE ordem < (SELECT ordem FROM {$this->table} WHERE id = ?) 
            AND status = 'ATIVO'
            ORDER BY ordem DESC
        ";
        
        return Database::fetchAll($sql, [$statusId]);
    }
}
