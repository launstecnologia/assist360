<?php

namespace App\Models;

use App\Core\Database;

class Locatario extends Model
{
    protected string $table = 'locatarios';
    
    protected array $fillable = [
        'imobiliaria_id', 'ksi_cliente_id', 'nome', 'cpf', 'email', 
        'telefone', 'whatsapp', 'endereco_logradouro', 'endereco_numero',
        'endereco_complemento', 'endereco_bairro', 'endereco_cidade',
        'endereco_estado', 'endereco_cep', 'status', 'ultima_sincronizacao'
    ];

    protected array $casts = [
        'ultima_sincronizacao' => 'datetime'
    ];

    /**
     * Buscar locatário por CPF e imobiliária
     * Retorna o registro mais recente (prioriza updated_at, depois created_at)
     */
    public function findByCpfAndImobiliaria(string $cpf, int $imobiliariaId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE cpf = ? AND imobiliaria_id = ? AND status = 'ATIVO' 
                ORDER BY updated_at DESC, created_at DESC 
                LIMIT 1";
        return Database::fetch($sql, [$cpf, $imobiliariaId]);
    }

    /**
     * Buscar locatário por ID do KSI e imobiliária
     * Retorna o registro mais recente (prioriza updated_at, depois created_at)
     */
    public function findByKsiIdAndImobiliaria(string $ksiId, int $imobiliariaId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ksi_cliente_id = ? AND imobiliaria_id = ? AND status = 'ATIVO' 
                ORDER BY updated_at DESC, created_at DESC 
                LIMIT 1";
        return Database::fetch($sql, [$ksiId, $imobiliariaId]);
    }

    /**
     * Criar ou atualizar locatário
     */
    public function createOrUpdate(array $data): array
    {
        $existing = $this->findByKsiIdAndImobiliaria($data['ksi_cliente_id'], $data['imobiliaria_id']);
        
        if ($existing) {
            // Atualizar dados existentes
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['ultima_sincronizacao'] = date('Y-m-d H:i:s');
            
            $this->update($existing['id'], $data);
            return $existing;
        } else {
            // Criar novo locatário
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['ultima_sincronizacao'] = date('Y-m-d H:i:s');
            
            $id = $this->create($data);
            return $this->find($id);
        }
    }

    /**
     * Buscar imóveis do locatário
     */
    public function getImoveis(int $locatarioId): array
    {
        $sql = "
            SELECT * FROM imoveis_locatarios 
            WHERE locatario_id = ? AND status = 'ATIVO' 
            ORDER BY created_at DESC
        ";
        return Database::fetchAll($sql, [$locatarioId]);
    }

    /**
     * Adicionar imóvel ao locatário
     */
    public function addImovel(int $locatarioId, array $imovelData): int
    {
        $imovelData['locatario_id'] = $locatarioId;
        $imovelData['created_at'] = date('Y-m-d H:i:s');
        $imovelData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($imovelData, 'imoveis_locatarios');
    }

    /**
     * Atualizar WhatsApp do locatário
     */
    public function updateWhatsapp(int $locatarioId, string $whatsapp): bool
    {
        $sql = "UPDATE {$this->table} SET whatsapp = ?, updated_at = NOW() WHERE id = ?";
        try {
            $stmt = Database::query($sql, [$whatsapp, $locatarioId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log('Erro ao atualizar WhatsApp: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualizar dados pessoais do locatário
     */
    public function updateDadosPessoais(int $locatarioId, array $dados): bool
    {
        $allowedFields = ['nome', 'email', 'telefone', 'whatsapp', 'endereco_logradouro', 
                         'endereco_numero', 'endereco_complemento', 'endereco_bairro', 
                         'endereco_cidade', 'endereco_estado', 'endereco_cep'];
        
        $updateFields = [];
        $values = [];
        $nomeAtualizado = null;
        
        foreach ($dados as $field => $value) {
            if (in_array($field, $allowedFields) && $value !== null) {
                $updateFields[] = "{$field} = ?";
                $values[] = $value;
                
                // Se o nome foi atualizado, guardar para atualizar nas solicitações
                if ($field === 'nome') {
                    $nomeAtualizado = $value;
                }
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $values[] = $locatarioId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
        
        try {
            $stmt = Database::query($sql, $values);
            $sucesso = $stmt->rowCount() > 0;
            
            // ✅ Se o nome foi atualizado, atualizar em TODAS as solicitações relacionadas
            if ($sucesso && $nomeAtualizado !== null) {
                $this->atualizarNomeNasSolicitacoes($locatarioId, $nomeAtualizado);
            }
            
            return $sucesso;
        } catch (\Exception $e) {
            error_log('Erro ao atualizar dados pessoais: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar nome do locatário em todas as solicitações relacionadas
     */
    private function atualizarNomeNasSolicitacoes(int $locatarioId, string $novoNome): void
    {
        try {
            // Buscar o locatário para pegar o ksi_cliente_id se existir
            $locatario = $this->find($locatarioId);
            if (!$locatario) {
                return;
            }
            
            // Atualizar solicitações onde locatario_id = locatarioId
            $sql1 = "UPDATE solicitacoes SET locatario_nome = ?, updated_at = NOW() WHERE locatario_id = ?";
            Database::query($sql1, [$novoNome, $locatarioId]);
            
            // Se tiver ksi_cliente_id, também atualizar solicitações onde locatario_id = ksi_cliente_id
            if (!empty($locatario['ksi_cliente_id'])) {
                $sql2 = "UPDATE solicitacoes SET locatario_nome = ?, updated_at = NOW() WHERE locatario_id = ?";
                Database::query($sql2, [$novoNome, $locatario['ksi_cliente_id']]);
            }
            
            error_log("✅ Nome do locatário atualizado em todas as solicitações [Locatario ID: {$locatarioId}] -> {$novoNome}");
        } catch (\Exception $e) {
            error_log('Erro ao atualizar nome nas solicitações: ' . $e->getMessage());
        }
    }
}
