<?php
/**
 * Script para corrigir a solicitação 249
 * Move de "Não Qualificado" para "Solicitação Manual"
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

\App\Core\Database::setConfig(require __DIR__ . '/../app/Config/config.php');

$solicitacaoId = 249;

echo "=== CORRIGINDO SOLICITAÇÃO KSS249 (ID: 249) ===\n\n";

// Buscar dados da solicitação
$sql = "SELECT * FROM solicitacoes WHERE id = ?";
$solicitacao = \App\Core\Database::fetch($sql, [$solicitacaoId]);

if (!$solicitacao) {
    echo "❌ Solicitação não encontrada!\n";
    exit(1);
}

echo "📋 Dados da solicitação:\n";
echo "   ID: {$solicitacao['id']}\n";
echo "   CPF: {$solicitacao['locatario_cpf']}\n";
echo "   Nome: {$solicitacao['locatario_nome']}\n";
echo "   Status ID: {$solicitacao['status_id']}\n";
echo "   Tipo Qualificação: " . ($solicitacao['tipo_qualificacao'] ?? 'NULL') . "\n";
echo "   Validação Utilização: " . ($solicitacao['validacao_utilizacao'] ?? 'NULL') . "\n";
echo "   Validação Bolsão: " . ($solicitacao['validacao_bolsao'] ?? 'NULL') . "\n\n";

// Verificar se já existe solicitação manual para este CPF/imobiliária/categoria
$sqlManual = "SELECT id FROM solicitacoes_manuais 
               WHERE cpf = ? 
               AND imobiliaria_id = ? 
               AND categoria_id = ?
               AND DATE(created_at) = DATE(?)";
$solicitacaoManualExistente = \App\Core\Database::fetch($sqlManual, [
    $solicitacao['locatario_cpf'],
    $solicitacao['imobiliaria_id'],
    $solicitacao['categoria_id'],
    $solicitacao['created_at']
]);

if ($solicitacaoManualExistente) {
    echo "⚠️  Já existe uma solicitação manual para este CPF/categoria/data.\n";
    echo "   ID da solicitação manual: {$solicitacaoManualExistente['id']}\n\n";
    
    // Atualizar a solicitação manual existente com os dados da solicitação
    echo "🔄 Atualizando solicitação manual existente...\n";
    
    $dadosAtualizacao = [
        'nome_completo' => $solicitacao['locatario_nome'],
        'whatsapp' => $solicitacao['locatario_telefone'],
        'endereco' => $solicitacao['imovel_endereco'],
        'numero' => $solicitacao['imovel_numero'],
        'complemento' => $solicitacao['imovel_complemento'],
        'bairro' => $solicitacao['imovel_bairro'],
        'cidade' => $solicitacao['imovel_cidade'],
        'estado' => $solicitacao['imovel_estado'],
        'cep' => $solicitacao['imovel_cep'],
        'descricao_problema' => $solicitacao['descricao_problema'],
        'numero_contrato' => $solicitacao['numero_contrato'],
        'validacao_bolsao' => $solicitacao['validacao_bolsao'] ?? 0,
        'tipo_qualificacao' => null, // Resetar para admin decidir
        'observacao_qualificacao' => null, // Limpar observação
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $updateSql = "UPDATE solicitacoes_manuais SET ";
    $updateParams = [];
    $updateFields = [];
    
    foreach ($dadosAtualizacao as $campo => $valor) {
        if ($valor !== null) {
            $updateFields[] = "{$campo} = ?";
            $updateParams[] = $valor;
        }
    }
    
    $updateSql .= implode(', ', $updateFields) . " WHERE id = ?";
    $updateParams[] = $solicitacaoManualExistente['id'];
    
    \App\Core\Database::query($updateSql, $updateParams);
    echo "✅ Solicitação manual atualizada!\n\n";
    
    $solicitacaoManualId = $solicitacaoManualExistente['id'];
} else {
    // Criar nova solicitação manual
    echo "📝 Criando nova solicitação manual...\n";
    
    // Extrair tipo_imovel e local_manutencao das observações se possível
    $tipoImovel = 'RESIDENCIAL';
    $localManutencao = null;
    
    if (!empty($solicitacao['observacoes'])) {
        $observacoes = $solicitacao['observacoes'];
        if (preg_match('/Tipo:\s*(\w+)/i', $observacoes, $matches)) {
            $tipoImovel = strtoupper($matches[1]);
        }
        $linhas = explode("\n", $observacoes);
        if (!empty($linhas[0])) {
            $localManutencao = trim($linhas[0]);
        }
    }
    
    $dadosManual = [
        'imobiliaria_id' => $solicitacao['imobiliaria_id'],
        'nome_completo' => $solicitacao['locatario_nome'],
        'cpf' => $solicitacao['locatario_cpf'],
        'whatsapp' => $solicitacao['locatario_telefone'],
        'tipo_imovel' => $tipoImovel,
        'subtipo_imovel' => null,
        'cep' => $solicitacao['imovel_cep'],
        'endereco' => $solicitacao['imovel_endereco'],
        'numero' => $solicitacao['imovel_numero'],
        'complemento' => $solicitacao['imovel_complemento'],
        'bairro' => $solicitacao['imovel_bairro'],
        'cidade' => $solicitacao['imovel_cidade'],
        'estado' => $solicitacao['imovel_estado'],
        'categoria_id' => $solicitacao['categoria_id'],
        'subcategoria_id' => $solicitacao['subcategoria_id'],
        'numero_contrato' => $solicitacao['numero_contrato'],
        'local_manutencao' => $localManutencao,
        'descricao_problema' => $solicitacao['descricao_problema'],
        'observacoes' => $solicitacao['observacoes'],
        'validacao_bolsao' => $solicitacao['validacao_bolsao'] ?? 0,
        'tipo_qualificacao' => null, // Resetar para admin decidir
        'observacao_qualificacao' => null, // Limpar observação
        'termos_aceitos' => 1,
        'created_at' => $solicitacao['created_at'],
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Verificar quais colunas existem
    $colunasExistentes = [];
    $sqlColunas = "SHOW COLUMNS FROM solicitacoes_manuais";
    $colunas = \App\Core\Database::fetchAll($sqlColunas);
    foreach ($colunas as $col) {
        $colunasExistentes[] = $col['Field'];
    }
    
    // Filtrar apenas campos que existem na tabela
    $dadosManualFiltrado = [];
    foreach ($dadosManual as $campo => $valor) {
        if (in_array($campo, $colunasExistentes)) {
            $dadosManualFiltrado[$campo] = $valor;
        }
    }
    
    // Criar INSERT
    $campos = array_keys($dadosManualFiltrado);
    $valores = array_values($dadosManualFiltrado);
    $placeholders = array_fill(0, count($valores), '?');
    
    $insertSql = "INSERT INTO solicitacoes_manuais (" . implode(', ', $campos) . ") 
                  VALUES (" . implode(', ', $placeholders) . ")";
    
    \App\Core\Database::query($insertSql, $valores);
    $solicitacaoManualId = \App\Core\Database::lastInsertId();
    
    echo "✅ Solicitação manual criada! ID: {$solicitacaoManualId}\n\n";
}

// Atualizar a solicitação original: remover tipo_qualificacao e observacao_qualificacao
echo "🔄 Atualizando solicitação original (removendo marcação de não qualificado)...\n";

$updateSolicitacao = "UPDATE solicitacoes SET 
    tipo_qualificacao = NULL,
    observacao_qualificacao = NULL
    WHERE id = ?";

\App\Core\Database::query($updateSolicitacao, [$solicitacaoId]);

// Buscar status "Nova Solicitação" para atualizar
$statusModel = new \App\Models\Status();
$statusNova = $statusModel->findByNome('Nova Solicitação') 
           ?? $statusModel->findByNome('Nova') 
           ?? $statusModel->findByNome('NOVA');

if ($statusNova) {
    $updateStatus = "UPDATE solicitacoes SET status_id = ? WHERE id = ?";
    \App\Core\Database::query($updateStatus, [$statusNova['id'], $solicitacaoId]);
    echo "✅ Status atualizado para '{$statusNova['nome']}'\n";
}

echo "✅ Solicitação original atualizada!\n\n";

echo "=== CORREÇÃO CONCLUÍDA ===\n";
echo "✅ Solicitação 249 corrigida:\n";
echo "   - Tipo qualificação removido (era NAO_QUALIFICADA)\n";
echo "   - Observação de qualificação removida\n";
echo "   - Status atualizado para 'Nova Solicitação'\n";
echo "   - Dados copiados para solicitação manual ID: {$solicitacaoManualId}\n";
echo "\n";
echo "📝 A solicitação agora aparece em:\n";
echo "   - Solicitações Manuais (ID: {$solicitacaoManualId})\n";
echo "   - O admin pode decidir se é CORTESIA ou NAO_QUALIFICADA\n";

