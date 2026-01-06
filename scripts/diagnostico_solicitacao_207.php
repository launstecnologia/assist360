<?php
/**
 * Script de diagnóstico para verificar por que a solicitação 207 não aparece no Kanban
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

// Conectar ao banco
$config = require __DIR__ . '/../app/Config/config.php';
$dbConfig = $config['database'];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== DIAGNÓSTICO SOLICITAÇÃO 207 ===\n\n";
    
    // 1. Buscar dados da solicitação 207
    $sql = "SELECT id, status_id, tipo_qualificacao, imobiliaria_id, created_at 
            FROM solicitacoes 
            WHERE id = 207";
    $solicitacao = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitacao) {
        echo "❌ Solicitação 207 não encontrada!\n";
        exit;
    }
    
    echo "📋 Dados da Solicitação 207:\n";
    echo "   - ID: {$solicitacao['id']}\n";
    echo "   - status_id: {$solicitacao['status_id']}\n";
    echo "   - tipo_qualificacao: " . ($solicitacao['tipo_qualificacao'] ?? 'NULL') . "\n";
    echo "   - imobiliaria_id: {$solicitacao['imobiliaria_id']}\n";
    echo "   - created_at: {$solicitacao['created_at']}\n\n";
    
    // 2. Buscar dados do status atual
    $sql = "SELECT id, nome, visivel_kanban, status 
            FROM status 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$solicitacao['status_id']]);
    $statusAtual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statusAtual) {
        echo "📊 Status Atual da Solicitação:\n";
        echo "   - ID: {$statusAtual['id']}\n";
        echo "   - Nome: {$statusAtual['nome']}\n";
        echo "   - Visível no Kanban: " . ($statusAtual['visivel_kanban'] ? 'SIM' : 'NÃO') . "\n";
        echo "   - Status: {$statusAtual['status']}\n\n";
    } else {
        echo "❌ Status não encontrado!\n\n";
    }
    
    // 3. Buscar status "Não qualificado"
    $sql = "SELECT id, nome, visivel_kanban, status 
            FROM status 
            WHERE nome = 'Não qualificado'";
    $statusNaoQualificado = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if ($statusNaoQualificado) {
        echo "✅ Status 'Não qualificado' encontrado:\n";
        echo "   - ID: {$statusNaoQualificado['id']}\n";
        echo "   - Nome: {$statusNaoQualificado['nome']}\n";
        echo "   - Visível no Kanban: " . ($statusNaoQualificado['visivel_kanban'] ? 'SIM' : 'NÃO') . "\n";
        echo "   - Status: {$statusNaoQualificado['status']}\n\n";
    } else {
        echo "❌ Status 'Não qualificado' NÃO encontrado!\n";
        echo "   Execute o script: scripts/criar_status_nao_qualificado.sql\n\n";
    }
    
    // 4. Verificar se a solicitação deveria aparecer no Kanban
    if ($solicitacao['tipo_qualificacao'] === 'NAO_QUALIFICADA') {
        echo "🔍 Análise:\n";
        echo "   - tipo_qualificacao = 'NAO_QUALIFICADA' ✓\n";
        
        if ($statusNaoQualificado && $solicitacao['status_id'] == $statusNaoQualificado['id']) {
            echo "   - status_id = {$statusNaoQualificado['id']} (Não qualificado) ✓\n";
            
            if ($statusNaoQualificado['visivel_kanban']) {
                echo "   - Status visível no Kanban ✓\n";
                echo "\n✅ A solicitação DEVERIA aparecer no Kanban!\n";
                echo "   Verifique se há filtros de imobiliária ou outros filtros aplicados.\n";
            } else {
                echo "   - Status NÃO visível no Kanban ✗\n";
                echo "\n❌ PROBLEMA: O status 'Não qualificado' não está visível no Kanban!\n";
                echo "   Execute: UPDATE status SET visivel_kanban = 1 WHERE nome = 'Não qualificado';\n";
            }
        } else {
            echo "   - status_id = {$solicitacao['status_id']} (NÃO é 'Não qualificado') ✗\n";
            echo "\n❌ PROBLEMA: A solicitação tem tipo_qualificacao = 'NAO_QUALIFICADA' mas o status_id não é 'Não qualificado'!\n";
            if ($statusNaoQualificado) {
                echo "   Execute: UPDATE solicitacoes SET status_id = {$statusNaoQualificado['id']} WHERE id = 207;\n";
            }
        }
    } else {
        echo "⚠️  A solicitação NÃO tem tipo_qualificacao = 'NAO_QUALIFICADA'\n";
        echo "   tipo_qualificacao atual: " . ($solicitacao['tipo_qualificacao'] ?? 'NULL') . "\n";
    }
    
    // 5. Verificar todos os status visíveis no Kanban
    echo "\n📋 Status visíveis no Kanban:\n";
    $sql = "SELECT id, nome, visivel_kanban 
            FROM status 
            WHERE visivel_kanban = 1 AND status = 'ATIVO'
            ORDER BY ordem ASC";
    $statusKanban = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusKanban as $status) {
        echo "   - {$status['id']}: {$status['nome']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

