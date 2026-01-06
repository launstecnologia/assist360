<?php
/**
 * Script para migrar tipo_imovel e local_manutencao de descricao_card e observacoes
 * para os campos individuais na tabela solicitacoes
 */

require __DIR__ . '/../app/Config/config.php';

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
    
    echo "Conectado ao banco de dados: {$database}\n\n";
    
    // Buscar todas as solicitações que precisam ser atualizadas
    $sql = "SELECT id, descricao_card, observacoes, local_manutencao, tipo_imovel 
            FROM solicitacoes 
            WHERE (local_manutencao IS NULL OR tipo_imovel IS NULL)
            AND (descricao_card IS NOT NULL OR observacoes IS NOT NULL)
            ORDER BY id DESC";
    
    $stmt = $pdo->query($sql);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($solicitacoes) . " solicitações para processar.\n\n";
    
    $atualizadas = 0;
    $erros = 0;
    
    foreach ($solicitacoes as $solicitacao) {
        $id = $solicitacao['id'];
        $descricaoCard = $solicitacao['descricao_card'] ?? '';
        $observacoes = $solicitacao['observacoes'] ?? '';
        $localManutencaoAtual = $solicitacao['local_manutencao'] ?? '';
        $tipoImovelAtual = $solicitacao['tipo_imovel'] ?? '';
        
        $localManutencao = $localManutencaoAtual;
        $tipoImovel = $tipoImovelAtual;
        
        // Tentar extrair de descricao_card primeiro
        if (empty($localManutencao) || empty($tipoImovel)) {
            if (!empty($descricaoCard)) {
                $linhas = explode("\n", $descricaoCard);
                foreach ($linhas as $linha) {
                    $linha = trim($linha);
                    if (empty($linha)) continue;
                    
                    // Ignorar linhas que começam com "Validação:"
                    if (stripos($linha, 'Validação:') === 0) {
                        continue;
                    }
                    
                    // Buscar Finalidade
                    if (stripos($linha, 'Finalidade:') !== false) {
                        // Não é tipo_imovel, continuar
                        continue;
                    }
                    
                    // Buscar Tipo
                    if (stripos($linha, 'Tipo:') !== false || stripos($linha, 'Tipo do Imóvel:') !== false) {
                        if (empty($tipoImovel)) {
                            $tipoImovel = trim(str_replace(['Tipo:', 'Tipo do Imóvel:', 'Tipo do imóvel:'], '', $linha));
                            // Limpar valores comuns
                            $tipoImovel = preg_replace('/^(RESIDENCIAL|COMERCIAL|CASA|APARTAMENTO)/i', '', $tipoImovel);
                            $tipoImovel = trim($tipoImovel);
                            // Se ainda estiver vazio, pegar o valor antes de "Tipo:"
                            if (empty($tipoImovel) && preg_match('/Tipo:\s*(.+)/i', $linha, $matches)) {
                                $tipoImovel = trim($matches[1]);
                            }
                        }
                        continue;
                    }
                    
                    // Se for a primeira linha útil e não for Finalidade ou Tipo, pode ser local_manutencao
                    if (empty($localManutencao) && stripos($linha, 'Finalidade:') === false && stripos($linha, 'Tipo:') === false) {
                        // Não deve ser muito longa (provavelmente não é local)
                        if (strlen($linha) < 100) {
                            $localManutencao = $linha;
                        }
                    }
                }
            }
        }
        
        // Se ainda estiver vazio, tentar de observacoes
        if (empty($localManutencao) || empty($tipoImovel)) {
            if (!empty($observacoes)) {
                $linhas = explode("\n", $observacoes);
                foreach ($linhas as $linha) {
                    $linha = trim($linha);
                    if (empty($linha)) continue;
                    
                    // Ignorar linhas que começam com "Validação:"
                    if (stripos($linha, 'Validação:') === 0) {
                        continue;
                    }
                    
                    // Buscar Tipo
                    if (stripos($linha, 'Tipo:') !== false || stripos($linha, 'Tipo do Imóvel:') !== false) {
                        if (empty($tipoImovel)) {
                            $tipoImovel = trim(str_replace(['Tipo:', 'Tipo do Imóvel:', 'Tipo do imóvel:'], '', $linha));
                            $tipoImovel = preg_replace('/^(RESIDENCIAL|COMERCIAL|CASA|APARTAMENTO)/i', '', $tipoImovel);
                            $tipoImovel = trim($tipoImovel);
                            if (empty($tipoImovel) && preg_match('/Tipo:\s*(.+)/i', $linha, $matches)) {
                                $tipoImovel = trim($matches[1]);
                            }
                        }
                        continue;
                    }
                    
                    // Buscar local (primeira linha útil que não seja Tipo ou Finalidade)
                    if (empty($localManutencao) && stripos($linha, 'Finalidade:') === false && stripos($linha, 'Tipo:') === false) {
                        if (strlen($linha) < 100 && !preg_match('/[.!?]{2,}/', $linha)) {
                            $localManutencao = $linha;
                        }
                    }
                }
            }
        }
        
        // Limpar valores
        $localManutencao = trim($localManutencao);
        $tipoImovel = trim($tipoImovel);
        
        // Remover "Validação:" se existir no início
        if (!empty($localManutencao)) {
            $localManutencao = preg_replace('/^Validação:\s*/i', '', $localManutencao);
            $localManutencao = trim($localManutencao);
        }
        
        // Atualizar apenas se encontrou valores e os campos estão vazios
        if ((!empty($localManutencao) && empty($localManutencaoAtual)) || 
            (!empty($tipoImovel) && empty($tipoImovelAtual))) {
            
            try {
                $updateSql = "UPDATE solicitacoes SET ";
                $params = [];
                $updates = [];
                
                if (!empty($localManutencao) && empty($localManutencaoAtual)) {
                    $updates[] = "local_manutencao = ?";
                    $params[] = $localManutencao;
                }
                
                if (!empty($tipoImovel) && empty($tipoImovelAtual)) {
                    $updates[] = "tipo_imovel = ?";
                    $params[] = $tipoImovel;
                }
                
                if (!empty($updates)) {
                    $updateSql .= implode(", ", $updates) . " WHERE id = ?";
                    $params[] = $id;
                    
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute($params);
                    
                    $atualizadas++;
                    
                    if ($atualizadas % 100 == 0) {
                        echo "Processadas {$atualizadas} solicitações...\n";
                    }
                }
            } catch (PDOException $e) {
                $erros++;
                echo "Erro ao atualizar solicitação #{$id}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migração concluída!\n";
    echo "   - Solicitações atualizadas: {$atualizadas}\n";
    echo "   - Erros: {$erros}\n";
    
    // Verificar resultado
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitacoes WHERE local_manutencao IS NOT NULL OR tipo_imovel IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Total de solicitações com dados: {$result['total']}\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

