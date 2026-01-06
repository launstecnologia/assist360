<?php
/**
 * Script para migrar numero_contrato de locatarios_contratos para solicitacoes
 * Migra o número do contrato das solicitações que foram criadas a partir do bolsão
 * mas não têm o numero_contrato preenchido
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
    
    // Buscar solicitações que:
    // 1. Têm validacao_bolsao = 1 (vem do bolsão)
    // 2. Têm locatario_cpf preenchido
    // 3. NÃO têm numero_contrato preenchido
    // 4. E existe um registro em locatarios_contratos com esse CPF e imobiliaria_id
    $sql = "SELECT 
                s.id as solicitacao_id,
                s.locatario_cpf,
                s.imobiliaria_id,
                s.numero_contrato as solicitacao_numero_contrato,
                lc.numero_contrato as bolsao_numero_contrato
            FROM solicitacoes s
            INNER JOIN locatarios_contratos lc ON (
                s.imobiliaria_id = lc.imobiliaria_id
                AND REPLACE(REPLACE(REPLACE(s.locatario_cpf, '.', ''), '-', ''), '/', '') = 
                    REPLACE(REPLACE(REPLACE(lc.cpf, '.', ''), '-', ''), '/', '')
            )
            WHERE s.validacao_bolsao = 1
            AND s.locatario_cpf IS NOT NULL
            AND s.locatario_cpf != ''
            AND (s.numero_contrato IS NULL OR s.numero_contrato = '')
            AND lc.numero_contrato IS NOT NULL
            AND lc.numero_contrato != ''
            ORDER BY s.id DESC";
    
    $stmt = $pdo->query($sql);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($solicitacoes) . " solicitações para migrar numero_contrato do bolsão.\n\n";
    
    if (empty($solicitacoes)) {
        echo "✅ Nenhuma solicitação precisa ser migrada.\n";
        exit(0);
    }
    
    $atualizadas = 0;
    $erros = 0;
    
    foreach ($solicitacoes as $solicitacao) {
        $solicitacaoId = $solicitacao['solicitacao_id'];
        $numeroContrato = trim($solicitacao['bolsao_numero_contrato']);
        
        if (empty($numeroContrato)) {
            continue;
        }
        
        try {
            // Se houver múltiplos contratos para o mesmo CPF, usar o primeiro encontrado
            // (ou podemos melhorar isso depois para usar o mais recente)
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
            echo "Erro ao atualizar solicitação #{$solicitacaoId}: " . $e->getMessage() . "\n";
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

