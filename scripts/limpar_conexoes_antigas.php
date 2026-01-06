<?php
/**
 * Script para limpar conexões antigas do MySQL/MariaDB
 * 
 * Este script mata conexões em estado "Sleep" há mais de X segundos
 * para liberar slots de max_user_connections
 * 
 * Uso: php scripts/limpar_conexoes_antigas.php [segundos]
 * Exemplo: php scripts/limpar_conexoes_antigas.php 300
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configurações
$config = require __DIR__ . '/../app/Config/config.php';
$dbConfig = $config['database'];

// Tempo mínimo em segundos para considerar uma conexão como "antiga" (padrão: 5 minutos)
$minTimeSeconds = isset($argv[1]) ? (int)$argv[1] : 300;

try {
    // Conectar ao MySQL usando uma conexão administrativa
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
        ]
    );

    echo "=== Limpeza de Conexões Antigas ===\n";
    echo "Usuário: {$dbConfig['username']}\n";
    echo "Tempo mínimo: {$minTimeSeconds} segundos\n\n";

    // Buscar conexões em Sleep
    $sql = "
        SELECT 
            ID,
            USER,
            HOST,
            DB,
            COMMAND,
            TIME,
            STATE
        FROM 
            INFORMATION_SCHEMA.PROCESSLIST
        WHERE 
            USER = :username
            AND COMMAND = 'Sleep'
            AND TIME > :min_time
        ORDER BY 
            TIME DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $dbConfig['username'],
        ':min_time' => $minTimeSeconds
    ]);

    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($connections)) {
        echo "✅ Nenhuma conexão antiga encontrada.\n";
        exit(0);
    }

    echo "Encontradas " . count($connections) . " conexão(ões) antiga(s):\n\n";
    
    $killed = 0;
    $failed = 0;

    foreach ($connections as $conn) {
        $timeFormatted = gmdate("H:i:s", $conn['TIME']);
        echo sprintf(
            "ID: %s | Host: %s | DB: %s | Tempo: %s (%d segundos)\n",
            $conn['ID'],
            $conn['HOST'],
            $conn['DB'] ?? 'NULL',
            $timeFormatted,
            $conn['TIME']
        );

        // Matar a conexão
        try {
            $killStmt = $pdo->prepare("KILL ?");
            $killStmt->execute([$conn['ID']]);
            $killStmt->closeCursor();
            echo "  ✅ Conexão {$conn['ID']} encerrada.\n";
            $killed++;
        } catch (PDOException $e) {
            echo "  ❌ Erro ao encerrar conexão {$conn['ID']}: " . $e->getMessage() . "\n";
            $failed++;
        }
    }

    echo "\n=== Resumo ===\n";
    echo "Total encontradas: " . count($connections) . "\n";
    echo "Encerradas com sucesso: {$killed}\n";
    echo "Falhas: {$failed}\n";

    // Verificar conexões restantes
    $sql = "
        SELECT COUNT(*) as total
        FROM INFORMATION_SCHEMA.PROCESSLIST
        WHERE USER = :username
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $dbConfig['username']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    echo "Conexões ativas restantes: {$result['total']}\n";

} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

