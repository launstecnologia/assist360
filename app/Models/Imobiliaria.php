<?php

namespace App\Models;

use App\Core\Database;

class Imobiliaria extends Model
{
    protected string $table = 'imobiliarias';
    protected array $fillable = [
        'cnpj', 'razao_social', 'nome_fantasia', 'nome',
        'endereco_logradouro', 'endereco_numero', 'endereco_complemento',
        'endereco_bairro', 'endereco_cidade', 'endereco_estado', 'endereco_cep',
        'telefone', 'email', 'logo', 'cor_primaria', 'cor_secundaria',
        'api_id',
        'url_base', 'token', 'instancia', 'status', 'cache_ttl', 
        'integração_ativa', 'integracao_ativa',
        'configuracoes', 'observacoes', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'configuracoes' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getAll(): array
    {
        return $this->findAll([], 'nome ASC');
    }

    public function getAtivas(): array
    {
        return $this->findAll(['status' => 'ATIVA'], 'nome ASC');
    }

    public function findByToken(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND status = 'ATIVA'";
        return Database::fetch($sql, [$token]);
    }

    public function findByInstancia(string $instancia): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE instancia = ? AND status = 'ATIVA'";
        return Database::fetch($sql, [$instancia]);
    }

    public function findByCnpj(string $cnpj): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE cnpj = ?";
        return Database::fetch($sql, [$cnpj]);
    }

    /**
     * Buscar estados únicos de imobiliárias ativas
     */
    public function getEstados(): array
    {
        $sql = "SELECT DISTINCT endereco_estado as estado 
                FROM {$this->table} 
                WHERE status = 'ATIVA' 
                AND endereco_estado IS NOT NULL 
                AND endereco_estado != ''
                ORDER BY endereco_estado ASC";
        return Database::fetchAll($sql);
    }

    /**
     * Buscar cidades por estado
     */
    public function getCidadesPorEstado(string $estado): array
    {
        $sql = "SELECT DISTINCT endereco_cidade as cidade 
                FROM {$this->table} 
                WHERE status = 'ATIVA' 
                AND endereco_estado = ?
                AND endereco_cidade IS NOT NULL 
                AND endereco_cidade != ''
                ORDER BY endereco_cidade ASC";
        return Database::fetchAll($sql, [$estado]);
    }

    /**
     * Buscar imobiliárias por estado e cidade
     */
    public function getImobiliariasPorLocalizacao(string $estado, string $cidade): array
    {
        $sql = "SELECT id, nome, nome_fantasia, instancia, logo, endereco_cidade, endereco_estado, integracao_ativa
                FROM {$this->table} 
                WHERE status = 'ATIVA' 
                AND endereco_estado = ?
                AND endereco_cidade = ?
                ORDER BY nome ASC";
        return Database::fetchAll($sql, [$estado, $cidade]);
    }

    /**
     * Buscar imobiliárias por nome/instância e cidade
     */
    public function search(?string $nomeInstancia = null, ?string $cidade = null): array
    {
        $conditions = [];
        $params = [];

        // Buscar por nome ou instância
        if (!empty($nomeInstancia)) {
            $conditions[] = "(nome_fantasia LIKE ? OR nome LIKE ? OR instancia LIKE ? OR razao_social LIKE ?)";
            $searchTerm = '%' . $nomeInstancia . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Buscar por cidade
        if (!empty($cidade)) {
            $conditions[] = "endereco_cidade LIKE ?";
            $params[] = '%' . $cidade . '%';
        }

        // Montar query
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY nome_fantasia ASC";

        return Database::fetchAll($sql, $params);
    }



    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($data['configuracoes']) && is_array($data['configuracoes'])) {
            $data['configuracoes'] = json_encode($data['configuracoes']);
        }
        
        return parent::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($data['configuracoes']) && is_array($data['configuracoes'])) {
            $data['configuracoes'] = json_encode($data['configuracoes']);
        }
        
        return parent::update($id, $data);
    }

    public function getConfiguracoes(int $id): array
    {
        $imobiliaria = $this->find($id);
        if (!$imobiliaria) {
            return [];
        }
        
        return json_decode($imobiliaria['configuracoes'] ?? '{}', true);
    }

    public function updateConfiguracoes(int $id, array $configuracoes): bool
    {
        return $this->update($id, ['configuracoes' => $configuracoes]);
    }

    public function getLocatarios(int $imobiliariaId): array
    {
        $sql = "
            SELECT DISTINCT 
                s.locatario_id,
                s.locatario_nome,
                s.locatario_telefone,
                s.locatario_email,
                s.imovel_endereco
            FROM solicitacoes s 
            WHERE s.imobiliaria_id = ?
            ORDER BY s.locatario_nome
        ";
        
        return Database::fetchAll($sql, [$imobiliariaId]);
    }

    public function getEstatisticas(int $imobiliariaId, string $periodo = '30'): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_solicitacoes,
                COUNT(CASE WHEN s.status_id = (SELECT id FROM status WHERE nome = 'Concluído') THEN 1 END) as concluidas,
                COUNT(CASE WHEN s.status_id = (SELECT id FROM status WHERE nome = 'Nova Solicitação') THEN 1 END) as novas,
                COUNT(CASE WHEN s.status_id = (SELECT id FROM status WHERE nome = 'Aguardando Peça') THEN 1 END) as aguardando_peca,
                AVG(TIMESTAMPDIFF(HOUR, s.created_at, s.updated_at)) as tempo_medio_resolucao
            FROM solicitacoes s 
            WHERE s.imobiliaria_id = ? 
            AND s.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        return Database::fetch($sql, [$imobiliariaId, $periodo]) ?: [];
    }
}
