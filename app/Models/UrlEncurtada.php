<?php

namespace App\Models;

use App\Core\Database;

class UrlEncurtada extends Model
{
    protected string $table = 'urls_encurtadas';
    protected array $fillable = [
        'codigo', 'solicitacao_id', 'tipo', 'url_original', 'url_encurtada', 'acessos'
    ];

    /**
     * Gera um código único para URL encurtada
     */
    private function gerarCodigo(): string
    {
        do {
            // Gerar código numérico de 8 dígitos
            $codigo = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            
            // Verificar se já existe
            $existe = $this->findByCodigo($codigo);
        } while ($existe);
        
        return $codigo;
    }

    /**
     * Busca URL encurtada por código
     */
    public function findByCodigo(string $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE codigo = ? LIMIT 1";
        return Database::fetch($sql, [$codigo]);
    }

    /**
     * Cria ou retorna URL encurtada para uma solicitação
     */
    public function criarOuBuscar(int $solicitacaoId, string $urlOriginal, string $tipo = 'rastreamento'): string
    {
        // Verificar se já existe URL encurtada para esta solicitação e tipo
        $sql = "SELECT * FROM {$this->table} WHERE solicitacao_id = ? AND tipo = ? LIMIT 1";
        $existente = Database::fetch($sql, [$solicitacaoId, $tipo]);
        
        if ($existente) {
            return $existente['url_encurtada'];
        }
        
        // Criar nova URL encurtada
        $codigo = $this->gerarCodigo();
        $baseUrl = $this->getBaseUrl();
        $urlEncurtada = $baseUrl . '/' . $codigo;
        
        $this->create([
            'codigo' => $codigo,
            'solicitacao_id' => $solicitacaoId,
            'tipo' => $tipo,
            'url_original' => $urlOriginal,
            'url_encurtada' => $urlEncurtada
        ]);
        
        return $urlEncurtada;
    }

    /**
     * Incrementa contador de acessos
     */
    public function incrementarAcesso(string $codigo): void
    {
        $sql = "UPDATE {$this->table} SET acessos = acessos + 1, updated_at = NOW() WHERE codigo = ?";
        Database::query($sql, [$codigo]);
    }

    /**
     * Obtém URL base do sistema
     */
    private function getBaseUrl(): string
    {
        // Prioridade 1: Banco de dados
        try {
            $configuracaoModel = new \App\Models\Configuracao();
            $urlBase = $configuracaoModel->getValor('whatsapp_links_base_url');
            if (!empty($urlBase)) {
                return rtrim($urlBase, '/');
            }
        } catch (\Exception $e) {
            // Continuar com fallbacks
        }
        
        // Prioridade 2: Config
        $configFile = __DIR__ . '/../Config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $whatsappConfig = $config['whatsapp'] ?? [];
            $urlBase = $whatsappConfig['links_base_url'] ?? null;
            if (!empty($urlBase)) {
                return rtrim($urlBase, '/');
            }
        }
        
        // Fallback
        return 'https://kss.launs.com.br';
    }
}

