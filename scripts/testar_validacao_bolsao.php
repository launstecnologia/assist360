<?php
/**
 * Script de teste para verificar validação de Bolsão
 * Testa se o CPF está sendo encontrado corretamente na tabela locatarios_contratos
 */

require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$cpfTeste = '57093251803'; // CPF do usuário para teste
$cpfLimpo = preg_replace('/[^0-9]/', '', $cpfTeste);

echo "=== TESTE DE VALIDAÇÃO BOLSÃO ===\n\n";
echo "CPF para teste: {$cpfTeste}\n";
echo "CPF limpo: {$cpfLimpo}\n\n";

// Buscar todas as imobiliárias
$imobiliarias = Database::fetchAll("SELECT id, nome FROM imobiliarias");

echo "=== Verificando em todas as imobiliárias ===\n\n";

foreach ($imobiliarias as $imobiliaria) {
    $sql = "SELECT * FROM locatarios_contratos 
            WHERE imobiliaria_id = ? 
            AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
    
    $resultado = Database::fetch($sql, [$imobiliaria['id'], $cpfLimpo]);
    
    if ($resultado) {
        echo "✅ ENCONTRADO na imobiliária: {$imobiliaria['nome']} (ID: {$imobiliaria['id']})\n";
        echo "   CPF na tabela: " . ($resultado['cpf'] ?? 'N/A') . "\n";
        echo "   Contrato: " . ($resultado['numero_contrato'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ NÃO encontrado na imobiliária: {$imobiliaria['nome']} (ID: {$imobiliaria['id']})\n\n";
    }
}

echo "\n=== Verificando todas as ocorrências do CPF na tabela ===\n\n";
$todasOcorrencias = Database::fetchAll("SELECT * FROM locatarios_contratos 
                                        WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", 
                                        [$cpfLimpo]);

if (empty($todasOcorrencias)) {
    echo "❌ CPF {$cpfLimpo} NÃO encontrado em nenhuma imobiliária na tabela locatarios_contratos\n";
} else {
    echo "Encontrado " . count($todasOcorrencias) . " registro(s):\n\n";
    foreach ($todasOcorrencias as $reg) {
        echo "  - Imobiliária ID: {$reg['imobiliaria_id']}, CPF: {$reg['cpf']}, Contrato: {$reg['numero_contrato']}\n";
    }
}

echo "\n=== Verificando solicitações criadas com este CPF ===\n\n";
$solicitacoes = Database::fetchAll("SELECT id, numero_solicitacao, locatario_cpf, validacao_bolsao, imobiliaria_id, created_at 
                                     FROM solicitacoes 
                                     WHERE REPLACE(REPLACE(REPLACE(locatario_cpf, '.', ''), '-', ''), ' ', '') = ?
                                     ORDER BY created_at DESC 
                                     LIMIT 10", 
                                     [$cpfLimpo]);

if (empty($solicitacoes)) {
    echo "Nenhuma solicitação encontrada com este CPF\n";
} else {
    echo "Encontrado " . count($solicitacoes) . " solicitação(ões):\n\n";
    foreach ($solicitacoes as $sol) {
        $validacaoTexto = ($sol['validacao_bolsao'] ?? 0) == 1 ? 'SIM (Bolsão)' : 'NÃO';
        echo "  - ID: {$sol['id']}, Número: {$sol['numero_solicitacao']}, Validação Bolsão: {$validacaoTexto}, Imobiliária ID: {$sol['imobiliaria_id']}, Data: {$sol['created_at']}\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";

