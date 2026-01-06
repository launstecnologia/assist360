<?php

namespace App\Models;

use App\Core\Database;

class Configuracao extends Model
{
    protected string $table = 'configuracoes';
    protected array $fillable = [
        'chave', 'valor', 'tipo', 'descricao', 'created_at', 'updated_at'
    ];
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obter valor de uma configuração por chave
     */
    public function getValor(string $chave, $default = null)
    {
        $config = $this->findByChave($chave);
        return $config ? $config['valor'] : $default;
    }

    /**
     * Definir valor de uma configuração
     */
    public function setValor(string $chave, $valor, string $tipo = 'string', string $descricao = ''): bool
    {
        $config = $this->findByChave($chave);
        
        if ($config) {
            return $this->update($config['id'], [
                'valor' => is_array($valor) ? json_encode($valor) : $valor,
                'tipo' => $tipo,
                'descricao' => $descricao ?: $config['descricao']
            ]);
        } else {
            return $this->create([
                'chave' => $chave,
                'valor' => is_array($valor) ? json_encode($valor) : $valor,
                'tipo' => $tipo,
                'descricao' => $descricao
            ]) > 0;
        }
    }

    /**
     * Buscar configuração por chave
     */
    public function findByChave(string $chave): ?array
    {
        return $this->findAll(['chave' => $chave])[0] ?? null;
    }

    /**
     * Obter todas as configurações
     */
    public function getAll(): array
    {
        return $this->findAll([], 'chave ASC');
    }

    /**
     * Obter configurações agrupadas por tipo
     */
    public function getAgrupadas(): array
    {
        $configs = $this->getAll();
        $agrupadas = [];
        
        foreach ($configs as $config) {
            $tipo = $config['tipo'] ?? 'outros';
            if (!isset($agrupadas[$tipo])) {
                $agrupadas[$tipo] = [];
            }
            $agrupadas[$tipo][] = $config;
        }
        
        return $agrupadas;
    }

    /**
     * Obter configurações de emergência
     */
    public function getConfiguracoesEmergencia(): array
    {
        $configs = [
            'telefone_emergencia' => $this->getValor('telefone_emergencia', ''),
            'horario_comercial_inicio' => $this->getValor('horario_comercial_inicio', '08:00'),
            'horario_comercial_fim' => $this->getValor('horario_comercial_fim', '17:30'),
            'dias_semana_comerciais' => json_decode($this->getValor('dias_semana_comerciais', '[1,2,3,4,5]'), true) ?? [1,2,3,4,5]
        ];
        
        return $configs;
    }

    /**
     * Verificar se está fora do horário comercial
     */
    public function isForaHorarioComercial(): bool
    {
        $configs = $this->getConfiguracoesEmergencia();
        
        $agora = new \DateTime();
        $horaAtual = (int)$agora->format('H');
        $minutoAtual = (int)$agora->format('i');
        $diaSemana = (int)$agora->format('N'); // 1=Segunda, 7=Domingo
        
        // Verificar se é dia comercial
        $diasComerciais = $configs['dias_semana_comerciais'];
        if (!in_array($diaSemana, $diasComerciais)) {
            return true; // Final de semana ou dia não comercial
        }
        
        // Verificar horário
        $horarioInicio = explode(':', $configs['horario_comercial_inicio']);
        $horarioFim = explode(':', $configs['horario_comercial_fim']);
        
        $horaInicio = (int)$horarioInicio[0];
        $minutoInicio = (int)($horarioInicio[1] ?? 0);
        $horaFim = (int)$horarioFim[0];
        $minutoFim = (int)($horarioFim[1] ?? 0);
        
        $minutosAtual = ($horaAtual * 60) + $minutoAtual;
        $minutosInicio = ($horaInicio * 60) + $minutoInicio;
        $minutosFim = ($horaFim * 60) + $minutoFim;
        
        return $minutosAtual < $minutosInicio || $minutosAtual > $minutosFim;
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
}

