<?php
/**
 * Script para verificar e corrigir os status visíveis no Kanban
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configuração
$config = require __DIR__ . '/../app/Config/config.php';

// Configurar Database
use App\Core\Database;
Database::setConfig($config['database']);

try {
    // Listar todos os status
    $status = Database::fetchAll(
        "SELECT id, nome, visivel_kanban, ordem, status 
         FROM status 
         ORDER BY ordem ASC"
    );
    
    echo "📊 Status no banco de dados:\n\n";
    echo str_pad("ID", 5) . str_pad("Nome", 30) . str_pad("Visível Kanban", 15) . str_pad("Ordem", 8) . "Status\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($status as $s) {
        $visivel = $s['visivel_kanban'] ? '✅ Sim' : '❌ Não';
        echo str_pad($s['id'], 5) . 
             str_pad($s['nome'], 30) . 
             str_pad($visivel, 15) . 
             str_pad($s['ordem'], 8) . 
             $s['status'] . "\n";
    }
    
    echo "\n";
    
    // Verificar especificamente o status "Pendente"
    $pendente = Database::fetch(
        "SELECT id, nome, visivel_kanban, ordem, status 
         FROM status 
         WHERE nome = 'Pendente'"
    );
    
    if ($pendente) {
        echo "🔍 Status 'Pendente' encontrado:\n";
        echo "   ID: " . $pendente['id'] . "\n";
        echo "   Visível no Kanban: " . ($pendente['visivel_kanban'] ? 'Sim ✅' : 'Não ❌') . "\n";
        echo "   Ordem: " . $pendente['ordem'] . "\n";
        echo "   Status: " . $pendente['status'] . "\n";
        
        if (!$pendente['visivel_kanban']) {
            echo "\n⚠️  O status 'Pendente' NÃO está marcado como visível no Kanban!\n";
            echo "🔄 Deseja corrigir? (Execute: UPDATE status SET visivel_kanban = 1 WHERE nome = 'Pendente')\n";
        }
    } else {
        echo "❌ Status 'Pendente' NÃO encontrado no banco de dados!\n";
    }
    
    // Contar quantos status estão visíveis
    $countVisiveis = Database::fetch(
        "SELECT COUNT(*) as total 
         FROM status 
         WHERE visivel_kanban = 1 AND status = 'ATIVO'"
    );
    
    echo "\n📈 Total de status visíveis no Kanban: " . $countVisiveis['total'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

