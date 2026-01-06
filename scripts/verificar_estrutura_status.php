<?php
/**
 * Script para verificar a estrutura da tabela status
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configuração
$config = require __DIR__ . '/../app/Config/config.php';

// Configurar Database
use App\Core\Database;
Database::setConfig($config['database']);

try {
    $pdo = Database::getInstance();
    
    // Listar todas as colunas da tabela status
    $columns = Database::fetchAll(
        "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE
         FROM information_schema.COLUMNS 
         WHERE TABLE_SCHEMA = ? 
         AND TABLE_NAME = 'status'
         ORDER BY ORDINAL_POSITION",
        [$config['database']['database']]
    );
    
    echo "📊 Estrutura da tabela 'status':\n\n";
    echo str_pad("Coluna", 25) . str_pad("Tipo", 20) . str_pad("Nullable", 12) . "Default\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['COLUMN_NAME'], 25) . 
             str_pad($column['DATA_TYPE'], 20) . 
             str_pad($column['IS_NULLABLE'], 12) . 
             ($column['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
    }
    
    echo "\n";
    
    // Verificar especificamente a coluna visivel_kanban
    $visivelKanban = Database::fetch(
        "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
         FROM information_schema.COLUMNS 
         WHERE TABLE_SCHEMA = ? 
         AND TABLE_NAME = 'status' 
         AND COLUMN_NAME = 'visivel_kanban'",
        [$config['database']['database']]
    );
    
    if ($visivelKanban) {
        echo "✅ Coluna 'visivel_kanban' encontrada:\n";
        echo "   Tipo: " . $visivelKanban['DATA_TYPE'] . "\n";
        echo "   Nullable: " . $visivelKanban['IS_NULLABLE'] . "\n";
        echo "   Default: " . ($visivelKanban['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
    } else {
        echo "❌ Coluna 'visivel_kanban' NÃO encontrada na tabela 'status'!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

