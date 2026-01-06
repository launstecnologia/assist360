<?php

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
    
    // Verificar se a coluna existe
    $stmt = $pdo->query("SHOW COLUMNS FROM solicitacoes LIKE 'numero_contrato'");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($result)) {
        echo "⚠️ Coluna 'numero_contrato' não existe. Adicionando...\n";
        
        // Adicionar coluna
        $sql = "ALTER TABLE `solicitacoes` 
                ADD COLUMN `numero_contrato` varchar(50) DEFAULT NULL";
        $pdo->exec($sql);
        
        echo "✅ Coluna 'numero_contrato' adicionada com sucesso!\n";
    } else {
        echo "✅ Coluna 'numero_contrato' já existe.\n";
        print_r($result);
    }
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

