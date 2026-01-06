<?php

namespace App\Models;

use App\Core\Database;

class Subcategoria extends Model
{
    protected string $table = 'subcategorias';
    protected array $fillable = [
        'categoria_id', 'nome', 'descricao', 'condicoes_gerais', 'prazo_minimo', 'status', 'ordem', 'is_emergencial', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'categoria_id' => 'int',
        'prazo_minimo' => 'int',
        'is_emergencial' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getByCategoria(int $categoriaId): array
    {
        return $this->findAll(['categoria_id' => $categoriaId], 'ordem ASC, nome ASC');
    }

    public function getAtivasAgrupadas(): array
    {
        $sql = "
            SELECT 
                sc.*, 
                c.nome as categoria_nome
            FROM subcategorias sc
            LEFT JOIN categorias c ON sc.categoria_id = c.id
            WHERE sc.status = 'ATIVA'
            ORDER BY c.ordem ASC, c.nome ASC, sc.ordem ASC, sc.nome ASC
        ";

        $dados = Database::fetchAll($sql);
        $agrupados = [];

        foreach ($dados as $subcategoria) {
            $categoriaId = $subcategoria['categoria_id'] ?? 0;
            if (!isset($agrupados[$categoriaId])) {
                $agrupados[$categoriaId] = [
                    'categoria_nome' => $subcategoria['categoria_nome'] ?? 'Sem categoria',
                    'itens' => []
                ];
            }

            $agrupados[$categoriaId]['itens'][] = $subcategoria;
        }

        return $agrupados;
    }

    public function getById(int $id): ?array
    {
        return $this->find($id);
    }

    public function countByCategoria(int $categoriaId): int
    {
        $sql = "SELECT COUNT(*) as total FROM subcategorias WHERE categoria_id = ?";
        $result = Database::fetch($sql, [$categoriaId]);
        return $result['total'] ?? 0;
    }

    public function countSolicitacoes(int $subcategoriaId): int
    {
        $sql = "SELECT COUNT(*) as total FROM solicitacoes WHERE subcategoria_id = ?";
        $result = Database::fetch($sql, [$subcategoriaId]);
        return $result['total'] ?? 0;
    }

    public function getAtivas(): array
    {
        return $this->findAll(['status' => 'ATIVA'], 'ordem ASC, nome ASC');
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

    public function getCategoria(int $subcategoriaId): ?array
    {
        $sql = "
            SELECT c.* 
            FROM categorias c
            INNER JOIN subcategorias sc ON c.id = sc.categoria_id
            WHERE sc.id = ?
        ";
        
        return Database::fetch($sql, [$subcategoriaId]);
    }

    public function getEstatisticas(int $subcategoriaId, string $periodo = '30'): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_solicitacoes,
                COUNT(CASE WHEN st.nome = 'Concluído' THEN 1 END) as concluidas,
                AVG(TIMESTAMPDIFF(HOUR, s.created_at, s.updated_at)) as tempo_medio_resolucao
            FROM solicitacoes s
            LEFT JOIN status st ON s.status_id = st.id
            WHERE s.subcategoria_id = ? 
            AND s.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        return Database::fetch($sql, [$subcategoriaId, $periodo]) ?: [];
    }

    /**
     * Calcula a próxima data útil (exclui sábado e domingo)
     */
    private function proximoDiaUtil(\DateTime $data): \DateTime
    {
        while (in_array($data->format('N'), [6, 7])) { // 6 = Sábado, 7 = Domingo
            $data->add(new \DateInterval('P1D'));
        }
        return $data;
    }

    /**
     * Calcula a data mínima para agendamento baseado no prazo mínimo
     * 
     * Lógica:
     * - Se prazo_minimo = 0: pode agendar hoje (se for dia útil, senão próximo dia útil)
     * - Se prazo_minimo = 1: pode agendar a partir de amanhã (se for dia útil, senão próximo dia útil)
     * - Se prazo_minimo = 2: conta hoje + amanhã, então pode agendar a partir de depois de amanhã
     * 
     * Exemplo: Se hoje é segunda e prazo_minimo = 2:
     * - Conta hoje (segunda) + amanhã (terça) = 2 dias
     * - Pode agendar a partir de quarta-feira (se for dia útil)
     */
    public function calcularDataLimiteAgendamento(int $subcategoriaId): \DateTime
    {
        $subcategoria = $this->find($subcategoriaId);
        
        // Se for emergencial, não precisa agendamento
        if ($subcategoria['is_emergencial'] ?? 0) {
            return new \DateTime(); // Pode agendar imediatamente
        }
        
        $prazoMinimo = (int)($subcategoria['prazo_minimo'] ?? 1);
        
        // Data base: hoje
        $dataLimite = new \DateTime();
        
        // Se prazo_minimo = 0, pode agendar hoje (mas precisa ser dia útil)
        if ($prazoMinimo === 0) {
            return $this->proximoDiaUtil($dataLimite);
        }
        
        // Adiciona o prazo mínimo (conta a partir de hoje)
        // Exemplo: se prazo_minimo = 2, conta hoje + amanhã, então pode agendar depois de amanhã
        $dataLimite->add(new \DateInterval("P{$prazoMinimo}D"));
        
        // Garantir que a data final seja um dia útil
        return $this->proximoDiaUtil($dataLimite);
    }

    public function getHorariosDisponiveis(int $subcategoriaId, string $data): array
    {
        $subcategoria = $this->find($subcategoriaId);
        
        // Se for emergencial, não precisa agendamento
        if ($subcategoria['is_emergencial'] ?? 0) {
            return [];
        }
        
        $dataSolicitada = new \DateTime($data);
        $dataLimite = $this->calcularDataLimiteAgendamento($subcategoriaId);
        
        // Comparar apenas a data (sem hora)
        $dataSolicitada->setTime(0, 0, 0);
        $dataLimite->setTime(0, 0, 0);
        
        // Verificar se a data está dentro do prazo mínimo
        if ($dataSolicitada < $dataLimite) {
            return [];
        }
        
        // Verificar se a data solicitada é um dia útil
        if (in_array($dataSolicitada->format('N'), [6, 7])) {
            return [];
        }
        
        // Horários comerciais (8h às 18h)
        $horarios = [];
        for ($hora = 8; $hora <= 17; $hora++) {
            $horarios[] = sprintf('%02d:00', $hora);
            $horarios[] = sprintf('%02d:30', $hora);
        }
        
        return $horarios;
    }
}
