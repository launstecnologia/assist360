<?php

namespace App\Models;

class TelefoneEmergencia extends Model
{
    protected string $table = 'telefones_emergencia';
    protected array $fillable = [
        'numero', 'descricao', 'is_ativo', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'is_ativo' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getAtivos(): array
    {
        return $this->findAll(['is_ativo' => 1], 'numero ASC');
    }

    public function getPrincipal(): ?array
    {
        // Primeiro, tentar buscar da tabela de configurações
        $configuracaoModel = new \App\Models\Configuracao();
        $telefoneConfig = $configuracaoModel->getValor('telefone_emergencia');
        
        if (!empty($telefoneConfig)) {
            return [
                'numero' => $telefoneConfig,
                'descricao' => 'Telefone de emergência configurado',
                'is_ativo' => 1
            ];
        }
        
        // Se não tiver na configuração, buscar da tabela de telefones
        $telefones = $this->getAtivos();
        return !empty($telefones) ? $telefones[0] : null;
    }

    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_ativo'] = $data['is_ativo'] ?? 1;
        return parent::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}

