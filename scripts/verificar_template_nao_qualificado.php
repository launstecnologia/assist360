<?php
/**
 * Verificar se o template WhatsApp "Não Qualificado" existe
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $sql = "SELECT * FROM whatsapp_templates WHERE tipo = 'Não Qualificado' AND ativo = 1";
    $template = Database::fetch($sql);
    
    if ($template) {
        echo "✅ Template 'Não Qualificado' encontrado:\n";
        echo "   - ID: {$template['id']}\n";
        echo "   - Nome: {$template['nome']}\n";
        echo "   - Tipo: {$template['tipo']}\n";
        echo "   - Ativo: " . ($template['ativo'] ? 'SIM' : 'NÃO') . "\n";
        echo "   - Variaveis: {$template['variaveis']}\n";
        echo "\n📝 Corpo do template:\n";
        echo $template['corpo'] . "\n";
    } else {
        echo "❌ Template 'Não Qualificado' NÃO encontrado!\n";
        echo "   Execute o script: scripts/criar_template_whatsapp_nao_qualificado.sql\n";
    }
    
    // Verificar todos os templates ativos
    echo "\n📋 Todos os templates ativos:\n";
    $sql = "SELECT id, nome, tipo FROM whatsapp_templates WHERE ativo = 1 ORDER BY tipo";
    $templates = Database::fetchAll($sql);
    foreach ($templates as $t) {
        echo "   - {$t['id']}: {$t['tipo']} ({$t['nome']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

