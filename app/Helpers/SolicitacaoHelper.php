<?php

/**
 * Helper functions para processamento de solicitações
 */

if (!function_exists('getHorariosLocatario')) {
    /**
     * Busca horários informados pelo locatário de forma padronizada
     * 
     * @param array $solicitacao Dados da solicitação
     * @return array Array de horários
     */
    function getHorariosLocatario(array $solicitacao): array
    {
        $horarios = [];
        
        // Quando horarios_indisponiveis = 1, horários originais do locatário estão em datas_opcoes
        if (!empty($solicitacao['horarios_indisponiveis'])) {
            if (!empty($solicitacao['datas_opcoes'])) {
                try {
                    $horarios = is_string($solicitacao['datas_opcoes']) 
                        ? json_decode($solicitacao['datas_opcoes'], true) 
                        : $solicitacao['datas_opcoes'];
                    if (!is_array($horarios)) {
                        $horarios = [];
                    }
                } catch (\Exception $e) {
                    $horarios = [];
                }
            } else {
                // Fallback: se datas_opcoes não existir, tentar buscar de horarios_opcoes (caso antigo)
                if (!empty($solicitacao['horarios_opcoes'])) {
                    try {
                        $horarios = is_string($solicitacao['horarios_opcoes']) 
                            ? json_decode($solicitacao['horarios_opcoes'], true) 
                            : $solicitacao['horarios_opcoes'];
                        if (!is_array($horarios)) {
                            $horarios = [];
                        }
                    } catch (\Exception $e) {
                        $horarios = [];
                    }
                }
            }
        } else {
            // Quando horarios_indisponiveis = 0, horários do locatário estão em horarios_opcoes
            if (!empty($solicitacao['horarios_opcoes'])) {
                try {
                    $horarios = is_string($solicitacao['horarios_opcoes']) 
                        ? json_decode($solicitacao['horarios_opcoes'], true) 
                        : $solicitacao['horarios_opcoes'];
                    if (!is_array($horarios)) {
                        $horarios = [];
                    }
                } catch (\Exception $e) {
                    $horarios = [];
                }
            }
        }
        
        return $horarios;
    }
}

if (!function_exists('getHorariosPrestador')) {
    /**
     * Busca horários informados pela seguradora/prestador
     * 
     * @param array $solicitacao Dados da solicitação
     * @return array Array de horários
     */
    function getHorariosPrestador(array $solicitacao): array
    {
        $horarios = [];
        
        // Horários do prestador só existem quando horarios_indisponiveis = 1
        if (!empty($solicitacao['horarios_indisponiveis']) && !empty($solicitacao['horarios_opcoes'])) {
            try {
                $horarios = is_string($solicitacao['horarios_opcoes']) 
                    ? json_decode($solicitacao['horarios_opcoes'], true) 
                    : $solicitacao['horarios_opcoes'];
                if (!is_array($horarios)) {
                    $horarios = [];
                }
            } catch (\Exception $e) {
                $horarios = [];
            }
        }
        
        return $horarios;
    }
}

if (!function_exists('normalizarHorario')) {
    /**
     * Normaliza um horário para o formato padrão "dd/mm/yyyy - HH:00-HH:00"
     * 
     * @param string $horario Horário em qualquer formato
     * @return string|null Horário normalizado ou null se inválido
     */
    function normalizarHorario(string $horario): ?string
    {
        if (empty($horario)) {
            return null;
        }
        
        $horario = trim($horario);
        
        // Se já está no formato esperado "dd/mm/yyyy - HH:00-HH:00", retornar
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s*-\s*\d{2}:\d{2}-\d{2}:\d{2}$/', $horario)) {
            return $horario;
        }
        
        // Converter "às" para "-"
        $horario = preg_replace('/\s+às\s+/', '-', $horario);
        
        // Tentar diferentes formatos
        $dt = null;
        
        // Formato 1: "dd/mm/yyyy - HH:00-HH:00" ou "dd/mm/yyyy HH:00-HH:00"
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})[ -](\d{2}):(\d{2})/', $horario, $matches)) {
            try {
                $dt = \DateTime::createFromFormat('d/m/Y H:i', $matches[1] . '/' . $matches[2] . '/' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5]);
                if ($dt && $dt !== false) {
                    $hora = (int)$matches[4];
                    $horaFim = $hora + 3;
                    if ($horaFim > 23) {
                        $horaFim = 23;
                    }
                    return sprintf('%02d/%02d/%04d - %02d:00-%02d:00', 
                        $dt->format('d'), $dt->format('m'), $dt->format('Y'), 
                        $hora, $horaFim);
                }
            } catch (\Exception $e) {
                // Continuar tentando outros formatos
            }
        }
        
        // Formato 2: ISO "2025-11-25 08:00:00" ou "2025-11-25T08:00:00"
        if (preg_match('/(\d{4}-\d{2}-\d{2})[T ](\d{2}):(\d{2})/', $horario, $matches)) {
            try {
                $dt = new \DateTime($matches[1] . ' ' . $matches[2] . ':' . $matches[3]);
                if ($dt && $dt !== false) {
                    $hora = (int)$dt->format('H');
                    $horaFim = $hora + 3;
                    if ($horaFim > 23) {
                        $horaFim = 23;
                    }
                    return sprintf('%02d/%02d/%04d - %02d:00-%02d:00', 
                        $dt->format('d'), $dt->format('m'), $dt->format('Y'), 
                        $hora, $horaFim);
                }
            } catch (\Exception $e) {
                // Continuar tentando outros formatos
            }
        }
        
        // Formato 3: Timestamp ou string que strtotime aceita
        if (is_numeric(strtotime($horario))) {
            try {
                $dt = new \DateTime($horario);
                if ($dt && $dt !== false) {
                    $hora = (int)$dt->format('H');
                    $horaFim = $hora + 3;
                    if ($horaFim > 23) {
                        $horaFim = 23;
                    }
                    return sprintf('%02d/%02d/%04d - %02d:00-%02d:00', 
                        $dt->format('d'), $dt->format('m'), $dt->format('Y'), 
                        $hora, $horaFim);
                }
            } catch (\Exception $e) {
                // Falhou
            }
        }
        
        // Se não conseguiu normalizar, retornar null
        return null;
    }
}

if (!function_exists('compararHorarios')) {
    /**
     * Compara dois horários normalizados
     * 
     * @param string $horario1 Primeiro horário
     * @param string $horario2 Segundo horário
     * @return bool True se são iguais
     */
    function compararHorarios(string $horario1, string $horario2): bool
    {
        $norm1 = normalizarHorario($horario1);
        $norm2 = normalizarHorario($horario2);
        
        if ($norm1 === null || $norm2 === null) {
            return false;
        }
        
        // Comparação exata
        if ($norm1 === $norm2) {
            return true;
        }
        
        // Normalizar espaços e comparar novamente
        $norm1Clean = preg_replace('/\s+/', ' ', trim($norm1));
        $norm2Clean = preg_replace('/\s+/', ' ', trim($norm2));
        
        if ($norm1Clean === $norm2Clean) {
            return true;
        }
        
        // Comparação por regex - extrair data e hora inicial E FINAL
        $regex = '/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})\s*(?:às|-)\s*(\d{2}:\d{2})/';
        $match1 = preg_match($regex, $norm1Clean, $m1);
        $match2 = preg_match($regex, $norm2Clean, $m2);
        
        if ($match1 && $match2) {
            // Comparar data, hora inicial E hora final EXATAS
            return ($m1[1] === $m2[1] && $m1[2] === $m2[2] && $m1[3] === $m2[3]);
        }
        
        return false;
    }
}

if (!function_exists('isHorarioConfirmado')) {
    /**
     * Verifica se um horário está confirmado na solicitação
     * 
     * @param string $horario Horário a verificar
     * @param array $solicitacao Dados da solicitação
     * @return bool True se está confirmado
     */
    function isHorarioConfirmado(string $horario, array $solicitacao): bool
    {
        $horarioNorm = normalizarHorario($horario);
        if ($horarioNorm === null) {
            return false;
        }
        
        // 1. Verificar em confirmed_schedules (prioridade)
        if (!empty($solicitacao['confirmed_schedules'])) {
            $confirmed = $solicitacao['confirmed_schedules'];
            
            // Se for string, parsear
            if (is_string($confirmed)) {
                $confirmed = json_decode($confirmed, true);
            }
            
            if (is_array($confirmed)) {
                foreach ($confirmed as $schedule) {
                    if (!is_array($schedule)) {
                        continue;
                    }
                    
                    // Comparar por raw (prioridade)
                    if (!empty($schedule['raw'])) {
                        if (compararHorarios($schedule['raw'], $horarioNorm)) {
                            return true;
                        }
                    }
                    
                    // Comparar por date + time se raw não funcionar
                    if (!empty($schedule['date']) && !empty($schedule['time'])) {
                        try {
                            $scheduleDate = new \DateTime($schedule['date']);
                            $horarioDate = new \DateTime($horarioNorm);
                            
                            if ($scheduleDate->format('Y-m-d') === $horarioDate->format('Y-m-d')) {
                                // Comparar hora
                                $scheduleTime = trim((string)$schedule['time']);
                                $horaAtual = $horarioDate->format('H:i');
                                $horaFimAtual = date('H:i', strtotime('+3 hours', $horarioDate->getTimestamp()));
                                $timeEsperado = $horaAtual . '-' . $horaFimAtual;
                                
                                if ($scheduleTime === $timeEsperado) {
                                    return true;
                                }
                            }
                        } catch (\Exception $e) {
                            // Ignorar erro
                        }
                    }
                }
            }
        }
        
        // 2. Verificar em horario_confirmado_raw
        if (!empty($solicitacao['horario_confirmado_raw'])) {
            if (compararHorarios($solicitacao['horario_confirmado_raw'], $horarioNorm)) {
                return true;
            }
        }
        
        // 3. Verificar em data_agendamento + horario_agendamento
        if (!empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento'])) {
            try {
                $dataAg = new \DateTime($solicitacao['data_agendamento']);
                $horarioDate = new \DateTime($horarioNorm);
                
                if ($dataAg->format('Y-m-d') === $horarioDate->format('Y-m-d')) {
                    $horaAg = trim((string)$solicitacao['horario_agendamento']);
                    $horaAtual = $horarioDate->format('H');
                    
                    if (strpos($horaAg, $horaAtual) !== false) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Ignorar erro
            }
        }
        
        return false;
    }
}

if (!function_exists('formatarHorarioParaExibicao')) {
    /**
     * Formata horário para exibição (converte hífen para "às")
     * 
     * @param string $horario Horário normalizado
     * @return string Horário formatado para exibição
     */
    function formatarHorarioParaExibicao(string $horario): string
    {
        // Converter hífen entre horários para "às"
        return preg_replace('/(\d{2}:\d{2})-(\d{2}:\d{2})/', '$1 às $2', $horario);
    }
}

if (!function_exists('getSubcategorias')) {
    /**
     * Extrai todas as subcategorias de uma solicitação (incluindo múltiplas das observações)
     * 
     * @param array $solicitacao Dados da solicitação
     * @return array Array com nomes das subcategorias
     */
    function getSubcategorias(array $solicitacao): array
    {
        $subcategorias = [];
        $subcategoriaModel = new \App\Models\Subcategoria();
        
        // Verificar se há múltiplas subcategorias nas observações (prioridade)
        if (!empty($solicitacao['observacoes'])) {
            // 1. Tentar extrair IDs das subcategorias do formato [SUBCATEGORIAS_IDS: [...]]
            // Melhorar regex para capturar JSON completo mesmo com quebras de linha e espaços
            $patterns = [
                '/\[SUBCATEGORIAS_IDS:\s*(\[[^\]]*(?:\][^\]]*)*\])\]/s',  // Padrão original
                '/\[SUBCATEGORIAS_IDS:\s*(\[.*?\])\]/s',  // Padrão mais flexível
                '/SUBCATEGORIAS_IDS:\s*(\[.*?\])/s',  // Sem colchetes externos
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $solicitacao['observacoes'], $matches)) {
                    try {
                        // Limpar o match removendo espaços extras
                        $jsonStr = trim($matches[1]);
                        $subcategoriasIds = json_decode($jsonStr, true);
                        
                        if (is_array($subcategoriasIds) && count($subcategoriasIds) > 0) {
                            foreach ($subcategoriasIds as $subId) {
                                if (empty($subId) && $subId !== 0) continue;
                                $sub = $subcategoriaModel->find((int)$subId);
                                if ($sub && !empty($sub['nome'])) {
                                    // Adicionar apenas se não estiver já na lista
                                    if (!in_array($sub['nome'], $subcategorias)) {
                                        $subcategorias[] = $sub['nome'];
                                    }
                                }
                            }
                            // Se encontrou IDs, usar eles e não tentar outros métodos
                            if (!empty($subcategorias)) {
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        // Continuar tentando outros padrões
                        continue;
                    }
                }
            }
            
            // 2. Alternativa: extrair da lista formatada "Serviços solicitados (X):"
            // Melhorar regex para ser mais flexível
            if (empty($subcategorias) || count($subcategorias) <= 1) {
                $patternsTexto = [
                    '/Serviços solicitados\s*\(\d+\)\s*:\s*\n((?:\d+\.\s*[^\n]+\n?)+)/s',  // Padrão original
                    '/Serviços solicitados[^\n]*:\s*\n((?:\d+\.\s*[^\n]+\n?)+)/s',  // Mais flexível
                    '/Serviços[^\n]*:\s*\n((?:\d+\.\s*[^\n]+\n?)+)/s',  // Ainda mais flexível
                ];
                
                foreach ($patternsTexto as $patternTexto) {
                    if (preg_match($patternTexto, $solicitacao['observacoes'], $matches)) {
                        $linhas = explode("\n", trim($matches[1]));
                        foreach ($linhas as $linha) {
                            $linha = trim($linha);
                            if (empty($linha)) continue;
                            
                            // Tentar padrão "1. Nome da subcategoria"
                            if (preg_match('/^\d+\.\s*(.+)$/', $linha, $linhaMatch)) {
                                $nome = trim($linhaMatch[1]);
                                // Adicionar apenas se não estiver já na lista
                                if (!empty($nome) && !in_array($nome, $subcategorias)) {
                                    $subcategorias[] = $nome;
                                }
                            }
                        }
                        // Se encontrou pelo menos uma, parar de tentar outros padrões
                        if (!empty($subcategorias)) {
                            break;
                        }
                    }
                }
            }
        }
        
        // 3. Se não encontrou nas observações ou encontrou apenas uma, adicionar subcategoria principal
        // Mas apenas se não estiver já na lista (para evitar duplicatas)
        if (!empty($solicitacao['subcategoria_nome'])) {
            // Verificar se a subcategoria principal já está na lista (comparação case-insensitive)
            $jaExiste = false;
            foreach ($subcategorias as $subExistente) {
                if (strcasecmp(trim($subExistente), trim($solicitacao['subcategoria_nome'])) === 0) {
                    $jaExiste = true;
                    break;
                }
            }
            
            if (!$jaExiste) {
                // Se já temos subcategorias das observações, adicionar no final
                // Se não temos nenhuma, adicionar como primeira
                if (empty($subcategorias)) {
                    array_unshift($subcategorias, $solicitacao['subcategoria_nome']);
                } else {
                    $subcategorias[] = $solicitacao['subcategoria_nome'];
                }
            }
        }
        
        // Garantir que retornamos pelo menos a subcategoria principal se existir
        if (empty($subcategorias) && !empty($solicitacao['subcategoria_nome'])) {
            $subcategorias[] = $solicitacao['subcategoria_nome'];
        }
        
        // Remover duplicatas (case-insensitive) e valores vazios
        $subcategoriasLimpa = [];
        foreach ($subcategorias as $sub) {
            $sub = trim($sub);
            if (empty($sub)) continue;
            
            $jaExiste = false;
            foreach ($subcategoriasLimpa as $subExistente) {
                if (strcasecmp($sub, $subExistente) === 0) {
                    $jaExiste = true;
                    break;
                }
            }
            
            if (!$jaExiste) {
                $subcategoriasLimpa[] = $sub;
            }
        }
        
        return $subcategoriasLimpa;
    }
}

