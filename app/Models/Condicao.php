<?php

namespace App\Models;

use App\Core\Database;

class Condicao extends Model
{
    protected string $table = 'condicoes';
    protected array $fillable = [
        'nome', 'cor', 'icone', 'ordem', 'status', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'ordem' => 'int',
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
        
        return parent::create($data);
    }

    public function getProximaOrdem(): int
    {
        $sql = "SELECT MAX(ordem) as max_ordem FROM {$this->table}";
        $result = Database::fetch($sql);
        return ($result['max_ordem'] ?? 0) + 1;
    }

    public function isUsado(int $condicaoId): bool
    {
        $sql = "SELECT COUNT(*) as total FROM solicitacoes WHERE condicao_id = ?";
        $result = Database::fetch($sql, [$condicaoId]);
        return ($result['total'] ?? 0) > 0;
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }

    public function updateOrdem(array $condicaoIds): bool
    {
        Database::beginTransaction();
        
        try {
            foreach ($condicaoIds as $index => $condicaoId) {
                $this->update($condicaoId, ['ordem' => $index + 1]);
            }
            
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            return false;
        }
    }
}

