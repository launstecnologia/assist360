<?php

namespace App\Models;

use App\Core\Database;

class HistoricoUpload extends Model
{
    protected string $table = 'historico_uploads';
    protected array $fillable = [
        'imobiliaria_id',
        'nome_arquivo',
        'caminho_arquivo',
        'tamanho_arquivo',
        'usuario_id',
        'total_registros',
        'registros_sucesso',
        'registros_erro',
        'detalhes_erros',
        'created_at',
        'updated_at'
    ];
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Buscar histórico filtrado por imobiliária
     */
    public function getByImobiliaria(int $imobiliariaId, int $limit = 50): array
    {
        $sql = "SELECT 
                    hu.*,
                    u.nome as usuario_nome,
                    u.email as usuario_email,
                    i.nome as imobiliaria_nome,
                    i.nome_fantasia as imobiliaria_nome_fantasia
                FROM {$this->table} hu
                INNER JOIN usuarios u ON hu.usuario_id = u.id
                INNER JOIN imobiliarias i ON hu.imobiliaria_id = i.id
                WHERE hu.imobiliaria_id = ?
                ORDER BY hu.created_at DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$imobiliariaId, $limit]);
    }

    /**
     * Buscar todos os históricos
     */
    public function getAll(int $limit = 100): array
    {
        $sql = "SELECT 
                    hu.*,
                    u.nome as usuario_nome,
                    u.email as usuario_email,
                    i.nome as imobiliaria_nome,
                    i.nome_fantasia as imobiliaria_nome_fantasia
                FROM {$this->table} hu
                INNER JOIN usuarios u ON hu.usuario_id = u.id
                INNER JOIN imobiliarias i ON hu.imobiliaria_id = i.id
                ORDER BY hu.created_at DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Criar novo registro de histórico
     */
    public function create(array $data): int
    {
        // Converter detalhes_erros para JSON se for array
        if (isset($data['detalhes_erros']) && is_array($data['detalhes_erros'])) {
            $data['detalhes_erros'] = json_encode($data['detalhes_erros'], JSON_UNESCAPED_UNICODE);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::create($data);
    }
}

