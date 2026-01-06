<?php
/**
 * Script para limpar informações técnicas das observações e movê-las para descricao_card
 * 
 * Este script:
 * 1. Extrai Local da Manutenção, Finalidade e Tipo das observações
 * 2. Salva essas informações em descricao_card
 * 3. Remove essas informações das observações, deixando apenas observações reais
 * 
 * Uso: Execute via MaintenanceController ou diretamente via terminal
 */

require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

try {
    // Buscar todas as solicitações que têm informações técnicas nas observações
    $sql = "
        SELECT id, observacoes, descricao_card
        FROM solicitacoes
        WHERE observacoes IS NOT NULL 
          AND observacoes != ''
          AND (
              observacoes LIKE '%Finalidade:%' 
              OR observacoes LIKE '%Tipo:%'
          )
    ";
    
    $solicitacoes = Database::fetchAll($sql);
    $atualizadas = 0;
    
    foreach ($solicitacoes as $solicitacao) {
        $id = $solicitacao['id'];
        $observacoes = $solicitacao['observacoes'] ?? '';
        $descricaoCard = $solicitacao['descricao_card'] ?? '';
        
        // Extrair informações técnicas
        $localManutencao = '';
        $finalidade = '';
        $tipoImovel = '';
        
        $linhas = explode("\n", $observacoes);
        $observacoesLimpa = [];
        
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            
            if (empty($linha)) {
                continue;
            }
            
            // Verificar se é Finalidade
            if (preg_match('/^Finalidade:\s*(.+)$/i', $linha, $matches)) {
                $finalidade = trim($matches[1]);
                continue; // Não adicionar nas observações limpas
            }
            
            // Verificar se é Tipo
            if (preg_match('/^Tipo:\s*(.+)$/i', $linha, $matches)) {
                $tipoImovel = trim($matches[1]);
                continue; // Não adicionar nas observações limpas
            }
            
            // Se for a primeira linha e não for Finalidade ou Tipo, pode ser local_manutencao
            if (empty($localManutencao) && empty($finalidade) && empty($tipoImovel) && count($observacoesLimpa) === 0) {
                // Verificar se não parece ser uma observação real (muito curta ou sem contexto)
                if (strlen($linha) < 50 && !preg_match('/[.!?]/', $linha)) {
                    $localManutencao = $linha;
                    continue; // Não adicionar nas observações limpas
                }
            }
            
            // Adicionar à lista de observações limpas
            $observacoesLimpa[] = $linha;
        }
        
        // Montar descricao_card com informações técnicas
        $descricaoCardNova = '';
        if (!empty($localManutencao)) {
            $descricaoCardNova = $localManutencao;
        }
        if (!empty($finalidade)) {
            $descricaoCardNova .= ($descricaoCardNova ? "\n" : '') . "Finalidade: " . $finalidade;
        }
        if (!empty($tipoImovel)) {
            $descricaoCardNova .= ($descricaoCardNova ? "\n" : '') . "Tipo: " . $tipoImovel;
        }
        
        // Observações limpas (sem informações técnicas)
        $observacoesNova = trim(implode("\n", $observacoesLimpa));
        
        // Atualizar apenas se houve mudança
        if ($descricaoCardNova !== $descricaoCard || $observacoesNova !== $observacoes) {
            $sqlUpdate = "
                UPDATE solicitacoes 
                SET 
                    descricao_card = ?,
                    observacoes = ?
                WHERE id = ?
            ";
            
            Database::query($sqlUpdate, [
                $descricaoCardNova ?: null,
                $observacoesNova ?: null,
                $id
            ]);
            
            $atualizadas++;
        }
    }
    
    echo "Script executado com sucesso!\n";
    echo "Total de solicitações atualizadas: {$atualizadas}\n";
    
} catch (\Exception $e) {
    echo "Erro ao executar script: " . $e->getMessage() . "\n";
    exit(1);
}

