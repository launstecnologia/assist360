<?php

namespace App\Models;

use App\Core\Database;

class Categoria extends Model
{
    protected string $table = 'categorias';
    protected array $fillable = [
        'nome', 'descricao', 'condicoes_gerais', 'icone', 'cor', 'status', 'ordem', 'tipo_imovel', 'tipo_assistencia', 'prazo_minimo', 'limite_solicitacoes_12_meses', 'parent_id', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'prazo_minimo' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getAtivas(): array
    {
        return $this->findAll(['status' => 'ATIVA'], 'ordem ASC, nome ASC');
    }

    public function getByTipo(string $tipo): array
    {
        return $this->findAll(['tipo_assistencia' => $tipo, 'status' => 'ATIVA'], 'ordem ASC, nome ASC');
    }

    public function getByTipoImovel(string $tipoImovel): array
    {
        // Busca categorias que são do tipo especificado ou 'AMBOS'
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE status = 'ATIVA' 
            AND (tipo_imovel = ? OR tipo_imovel = 'AMBOS')
            ORDER BY ordem ASC, nome ASC
        ";
        return Database::fetchAll($sql, [$tipoImovel]);
    }

    /**
     * Busca categorias principais (sem parent_id) com suas categorias filhas organizadas
     * @param string|null $tipoImovel Tipo de imóvel (RESIDENCIAL, COMERCIAL) ou null para todos
     * @return array Categorias principais com array 'filhas' contendo categorias filhas
     */
    public function getHierarquicas(?string $tipoImovel = null): array
    {
        // Buscar categorias principais (sem parent_id)
        $sql = "
            SELECT DISTINCT * FROM {$this->table} 
            WHERE status = 'ATIVA' 
            AND (parent_id IS NULL OR parent_id = 0)
        ";
        
        $params = [];
        if ($tipoImovel) {
            $sql .= " AND (tipo_imovel = ? OR tipo_imovel = 'AMBOS')";
            $params[] = $tipoImovel;
        }
        
        $sql .= " ORDER BY ordem ASC, nome ASC";
        
        $categoriasPai = Database::fetchAll($sql, $params);
        
        // Para cada categoria pai, buscar suas categorias filhas
        foreach ($categoriasPai as &$categoriaPai) {
            $filhas = $this->getFilhas($categoriaPai['id'], $tipoImovel);
            $categoriaPai['filhas'] = !empty($filhas) ? $filhas : [];
        }
        
        return $categoriasPai;
    }

    /**
     * Busca categorias filhas de uma categoria pai
     * @param int $parentId ID da categoria pai
     * @param string|null $tipoImovel Tipo de imóvel (RESIDENCIAL, COMERCIAL) ou null para todos
     * @return array Categorias filhas
     */
    public function getFilhas(int $parentId, ?string $tipoImovel = null): array
    {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE status = 'ATIVA' 
            AND parent_id = ?
        ";
        
        $params = [$parentId];
        
        if ($tipoImovel) {
            $sql .= " AND (tipo_imovel = ? OR tipo_imovel = 'AMBOS')";
            $params[] = $tipoImovel;
        }
        
        $sql .= " ORDER BY ordem ASC, nome ASC";
        
        return Database::fetchAll($sql, $params);
    }

    public function getSubcategorias(int $categoriaId): array
    {
        $sql = "SELECT * FROM subcategorias WHERE categoria_id = ? AND status = 'ATIVA' ORDER BY ordem ASC, nome ASC";
        return Database::fetchAll($sql, [$categoriaId]);
    }

    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }

    public function getAll(): array
    {
        return $this->findAll([], 'ordem ASC, nome ASC');
    }

    public function getById(int $id): ?array
    {
        return $this->find($id);
    }

    public function countSolicitacoes(int $categoriaId): int
    {
        $sql = "SELECT COUNT(*) as total FROM solicitacoes WHERE categoria_id = ?";
        $result = Database::fetch($sql, [$categoriaId]);
        return $result['total'] ?? 0;
    }

    public function getEstatisticas(int $categoriaId, string $periodo = '30'): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_solicitacoes,
                COUNT(CASE WHEN st.nome = 'Concluído' THEN 1 END) as concluidas,
                AVG(TIMESTAMPDIFF(HOUR, s.created_at, s.updated_at)) as tempo_medio_resolucao
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            WHERE s.categoria_id = ? 
            AND s.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        return Database::fetch($sql, [$categoriaId, $periodo]) ?: [];
    }

    /**
     * Verifica se o contrato ultrapassou o limite de solicitações da categoria nos últimos 12 meses
     * @param int $categoriaId ID da categoria
     * @param string $numeroContrato Número do contrato
     * @return array ['permitido' => bool, 'limite' => int|null, 'total_atual' => int, 'mensagem' => string]
     */
    public function verificarLimiteSolicitacoes(int $categoriaId, string $numeroContrato): array
    {
        // Buscar categoria e seu limite
        $categoria = $this->find($categoriaId);
        if (!$categoria) {
            return [
                'permitido' => true,
                'limite' => null,
                'total_atual' => 0,
                'mensagem' => 'Categoria não encontrada'
            ];
        }

        $limite = $categoria['limite_solicitacoes_12_meses'] ?? null;
        
        // Se não houver limite definido, permitir
        if ($limite === null || $limite <= 0) {
            return [
                'permitido' => true,
                'limite' => null,
                'total_atual' => 0,
                'mensagem' => 'Sem limite definido'
            ];
        }

        // Calcular data de 12 meses atrás
        $dataInicio = date('Y-m-d', strtotime('-12 months'));
        
        // Contar solicitações do mesmo contrato e categoria nos últimos 12 meses
        $sql = "
            SELECT COUNT(*) as total
            FROM solicitacoes
            WHERE categoria_id = ?
            AND numero_contrato = ?
            AND DATE(created_at) >= ?
        ";
        
        $resultado = Database::fetch($sql, [$categoriaId, $numeroContrato, $dataInicio]);
        $totalAtual = (int) ($resultado['total'] ?? 0);
        
        $permitido = $totalAtual < $limite;
        
        return [
            'permitido' => $permitido,
            'limite' => $limite,
            'total_atual' => $totalAtual,
            'mensagem' => $permitido 
                ? "Limite disponível: {$totalAtual}/{$limite} solicitações"
                : "Limite atingido! Você já possui {$totalAtual} solicitação" . ($totalAtual > 1 ? 'ões' : '') . " desta categoria nos últimos 12 meses. O limite permitido é de {$limite} solicitação" . ($limite > 1 ? 'ões' : '') . "."
        ];
    }
}
