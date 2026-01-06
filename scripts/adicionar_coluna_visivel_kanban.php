<?php
/**
 * Script para adicionar a coluna visivel_kanban na tabela status
 * 
 * Execute este script via linha de comando:
 * php scripts/adicionar_coluna_visivel_kanban.php
 * 
 * Ou acesse via navegador:
 * http://localhost/kss/scripts/adicionar_coluna_visivel_kanban.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configuração
$config = require __DIR__ . '/../app/Config/config.php';

// Configurar Database
use App\Core\Database;
Database::setConfig($config['database']);

try {
    $pdo = Database::getInstance();
    
    // Verificar se a coluna já existe
    $checkColumn = Database::fetch(
        "SELECT COUNT(*) as count 
         FROM information_schema.COLUMNS 
         WHERE TABLE_SCHEMA = ? 
         AND TABLE_NAME = 'status' 
         AND COLUMN_NAME = 'visivel_kanban'",
        [$config['database']['database']]
    );
    
    if ($checkColumn && $checkColumn['count'] > 0) {
        echo "✅ A coluna 'visivel_kanban' já existe na tabela 'status'.\n";
        exit(0);
    }
    
    // Adicionar a coluna
    echo "🔄 Adicionando coluna 'visivel_kanban' na tabela 'status'...\n";
    
    $sql = "ALTER TABLE `status` 
            ADD COLUMN `visivel_kanban` TINYINT(1) DEFAULT 1 NOT NULL 
            AFTER `ordem`";
    
    $pdo->exec($sql);
    
    // Atualizar registros existentes
    echo "🔄 Atualizando registros existentes...\n";
    $pdo->exec("UPDATE `status` SET `visivel_kanban` = 1 WHERE `visivel_kanban` IS NULL");
    
    echo "✅ Coluna 'visivel_kanban' adicionada com sucesso!\n";
    echo "✅ Todos os status existentes foram marcados como visíveis no Kanban.\n";
    
} catch (PDOException $e) {
    echo "❌ Erro ao adicionar coluna: " . $e->getMessage() . "\n";
    exit(1);
}

