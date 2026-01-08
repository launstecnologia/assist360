<?php

namespace App\Models;

use App\Core\Database;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return Database::fetch($sql, [$id]);
    }

    public function findAll(array $conditions = [], string $orderBy = null, int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        return Database::fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        $fillableData = $this->filterFillable($data);
        
        // Log para debug se status_id estiver presente
        if (isset($fillableData['status_id'])) {
            error_log("Model::create [{$this->table}] - status_id antes do INSERT: " . ($fillableData['status_id'] ?? 'NULL'));
            error_log("Model::create [{$this->table}] - Tipo: " . gettype($fillableData['status_id']));
        } else {
            error_log("Model::create [{$this->table}] - status_id NÃO está presente no fillableData!");
        }
        
        $fields = array_keys($fillableData);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        // Preparar valores garantindo que status_id nunca seja null
        $values = [];
        foreach ($fields as $field) {
            $value = $fillableData[$field];
            // Se for status_id e for null, usar 1 como fallback
            if ($field === 'status_id' && ($value === null || $value === '' || (is_numeric($value) && $value <= 0))) {
                error_log("Model::create [{$this->table}] - ⚠️ ATENÇÃO: status_id estava inválido, usando fallback 1");
                $value = 1;
            }
            $values[] = $value;
        }
        
        error_log("Model::create [{$this->table}] - SQL: " . $sql);
        error_log("Model::create [{$this->table}] - Valores: " . json_encode($values));
        
        try {
            Database::query($sql, $values);
            $lastId = Database::lastInsertId();
            $lastIdInt = (int) $lastId;
            
            if ($lastIdInt <= 0) {
                error_log("⚠️ Model::create [{$this->table}] - lastInsertId retornou valor inválido: '{$lastId}' (int: {$lastIdInt})");
                // Tentar buscar o último ID inserido manualmente
                $result = Database::fetch("SELECT LAST_INSERT_ID() as id");
                if ($result && isset($result['id']) && $result['id'] > 0) {
                    $lastIdInt = (int) $result['id'];
                    error_log("✅ Model::create [{$this->table}] - ID recuperado via SELECT: {$lastIdInt}");
                } else {
                    error_log("❌ Model::create [{$this->table}] - Não foi possível recuperar o ID inserido");
                    throw new \Exception("Não foi possível obter o ID da solicitação criada");
                }
            } else {
                error_log("✅ Model::create [{$this->table}] - Solicitação criada com ID: {$lastIdInt}");
            }
            
            return $lastIdInt;
        } catch (\PDOException $e) {
            error_log("❌ Model::create [{$this->table}] - Erro PDO: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Valores: " . json_encode($values));
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        } catch (\Exception $e) {
            error_log("❌ Model::create [{$this->table}] - Erro: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $fillableData = $this->filterFillable($data);
        
        // Verificar se a tabela tem coluna updated_at ANTES de verificar se está vazio
        $hasUpdatedAt = $this->hasUpdatedAtColumn();
        
        // Sempre atualizar updated_at automaticamente se a coluna existir
        // Isso garante que mesmo com array vazio, updated_at será atualizado
        if ($hasUpdatedAt) {
            $fillableData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // Se não há dados para atualizar (e não tem updated_at), retorna true
        if (empty($fillableData)) {
            return true;
        }
        
        $fields = array_keys($fillableData);
        $setClause = array_map(fn($field) => "$field = ?", $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($fillableData), [$id]);
        
        try {
            Database::query($sql, $params);
            // Retorna true se a query foi executada sem erros
            // Não importa se linhas foram afetadas ou não
            return true;
        } catch (\PDOException $e) {
            error_log("Erro no update [Table: {$this->table}, ID: {$id}]: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            error_log("Stack trace: " . $e->getTraceAsString());
            // Relançar a exceção para que o controller possa tratá-la
            throw $e;
        }
    }
    
    /**
     * Verifica se a tabela tem coluna updated_at
     */
    private function hasUpdatedAtColumn(): bool
    {
        static $columnCache = [];
        
        if (!isset($columnCache[$this->table])) {
            try {
                $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'updated_at'";
                $result = Database::fetch($sql);
                $columnCache[$this->table] = !empty($result);
            } catch (\Exception $e) {
                // Se der erro, assumir que não tem
                $columnCache[$this->table] = false;
            }
        }
        
        return $columnCache[$this->table] ?? false;
    }

    public function delete(int $id): bool
    {
        // Primeiro verificar se o registro existe
        if (!$this->exists($id)) {
            return false;
        }
        
        // Deletar o registro
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        Database::query($sql, [$id]);
        
        // Verificar se foi realmente deletado
        return !$this->exists($id);
    }

    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return Database::fetch($sql, [$id]) !== null;
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }

        $result = Database::fetch($sql, $params);
        return (int) $result['count'];
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function cast(array $data): array
    {
        foreach ($this->casts as $field => $type) {
            if (isset($data[$field])) {
                switch ($type) {
                    case 'int':
                    case 'integer':
                        $data[$field] = (int) $data[$field];
                        break;
                    case 'float':
                    case 'double':
                        $data[$field] = (float) $data[$field];
                        break;
                    case 'bool':
                    case 'boolean':
                        $data[$field] = (bool) $data[$field];
                        break;
                    case 'json':
                        $data[$field] = json_decode($data[$field], true);
                        break;
                }
            }
        }

        return $data;
    }

    protected function hide(array $data): array
    {
        return array_diff_key($data, array_flip($this->hidden));
    }
}
