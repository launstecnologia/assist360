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
    
    // Criar tabela condicoes
    $sql1 = "CREATE TABLE IF NOT EXISTS `condicoes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nome` varchar(255) NOT NULL,
      `cor` varchar(7) NOT NULL DEFAULT '#3B82F6',
      `icone` varchar(50) DEFAULT NULL,
      `ordem` int(11) DEFAULT 1,
      `status` enum('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO',
      `created_at` datetime DEFAULT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_status` (`status`),
      KEY `idx_ordem` (`ordem`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql1);
    echo "✅ Tabela 'condicoes' criada com sucesso!\n";
    
    // Verificar se a coluna já existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `solicitacoes` LIKE 'condicao_id'");
    if ($checkColumn->rowCount() == 0) {
        // Adicionar coluna condicao_id na tabela solicitacoes
        $sql2 = "ALTER TABLE `solicitacoes` 
                 ADD COLUMN `condicao_id` int(11) DEFAULT NULL";
        $pdo->exec($sql2);
        echo "✅ Coluna 'condicao_id' adicionada à tabela 'solicitacoes'!\n";
        
        // Adicionar índice
        $sql3 = "ALTER TABLE `solicitacoes` ADD KEY `idx_condicao_id` (`condicao_id`)";
        $pdo->exec($sql3);
        echo "✅ Índice 'idx_condicao_id' criado!\n";
        
        // Adicionar foreign key
        try {
            $sql4 = "ALTER TABLE `solicitacoes` 
                     ADD CONSTRAINT `fk_solicitacoes_condicao` 
                     FOREIGN KEY (`condicao_id`) REFERENCES `condicoes` (`id`) 
                     ON DELETE SET NULL ON UPDATE CASCADE";
            $pdo->exec($sql4);
            echo "✅ Foreign key 'fk_solicitacoes_condicao' criada!\n";
        } catch (PDOException $e) {
            echo "⚠️ Aviso ao criar foreign key: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ Coluna 'condicao_id' já existe na tabela 'solicitacoes'.\n";
    }
    
    echo "\n✅ Processo concluído com sucesso!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

