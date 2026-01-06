<?php
/**
 * Script para migrar numero_contrato de solicitacoes_manuais para solicitacoes
 * Migra o número do contrato das solicitações manuais que já foram migradas
 */

require __DIR__ . '/../app/Config/config.php';

$config = require __DIR__ . '/../app/Config/config.php';
$dbConfig = $config['database'] ?? [];

$host = $dbConfig['host'] ?? 'localhost';
$database = $dbConfig['database'] ?? 'launs_kss';
$username = $dbConfig['username'] ?? 'root';
$password = $dbConfig['password'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Conectado ao banco de dados: {$database}\n\n";
    
    // Buscar solicitações manuais que foram migradas e têm numero_contrato
    $sql = "SELECT 
                sm.id as manual_id,
                sm.numero_contrato,
                sm.migrada_para_solicitacao_id,
                s.id as solicitacao_id,
                s.numero_contrato as solicitacao_numero_contrato
            FROM solicitacoes_manuais sm
            INNER JOIN solicitacoes s ON s.id = sm.migrada_para_solicitacao_id
            WHERE sm.numero_contrato IS NOT NULL 
            AND sm.numero_contrato != ''
            AND (s.numero_contrato IS NULL OR s.numero_contrato = '')
            ORDER BY sm.id DESC";
    
    $stmt = $pdo->query($sql);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($solicitacoes) . " solicitações para migrar numero_contrato.\n\n";
    
    if (empty($solicitacoes)) {
        echo "✅ Nenhuma solicitação precisa ser migrada.\n";
        exit(0);
    }
    
    $atualizadas = 0;
    $erros = 0;
    
    foreach ($solicitacoes as $solicitacao) {
        $manualId = $solicitacao['manual_id'];
        $solicitacaoId = $solicitacao['solicitacao_id'];
        $numeroContrato = trim($solicitacao['numero_contrato']);
        
        if (empty($numeroContrato)) {
            continue;
        }
        
        try {
            $updateSql = "UPDATE solicitacoes 
                         SET numero_contrato = ? 
                         WHERE id = ?";
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$numeroContrato, $solicitacaoId]);
            
            $atualizadas++;
            
            if ($atualizadas % 10 == 0) {
                echo "Processadas {$atualizadas} solicitações...\n";
            }
        } catch (PDOException $e) {
            $erros++;
            echo "Erro ao atualizar solicitação #{$solicitacaoId} (Manual #{$manualId}): " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Migração concluída!\n";
    echo "   - Solicitações atualizadas: {$atualizadas}\n";
    echo "   - Erros: {$erros}\n";
    
    // Verificar resultado
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitacoes WHERE numero_contrato IS NOT NULL AND numero_contrato != ''");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Total de solicitações com numero_contrato: {$result['total']}\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

