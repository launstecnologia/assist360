<?php
/**
 * Script para criar a tabela whatsapp_instances
 * Execute: php scripts/criar_tabela_whatsapp_instances.php
 */

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
    
    $sql = file_get_contents(__DIR__ . '/criar_tabela_whatsapp_instances.sql');
    $pdo->exec($sql);
    
    echo "✅ Tabela 'whatsapp_instances' criada com sucesso!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

