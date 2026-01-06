<?php
/**
 * Script PHP para adicionar campos de informações do serviço na tabela solicitacoes
 * 
 * Execute via: php scripts/adicionar_campos_info_servico_solicitacoes.php
 * Ou via navegador: http://localhost/kss/scripts/adicionar_campos_info_servico_solicitacoes.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configuração
$config = require __DIR__ . '/../app/Config/config.php';

// Configurar Database
use App\Core\Database;
Database::setConfig($config['database']);

try {
    $pdo = Database::getInstance();
    
    echo "🔄 Adicionando colunas na tabela solicitacoes...\n\n";
    
    // Função para verificar se coluna existe
    $colunaExiste = function($nomeColuna) use ($pdo, $config) {
        $sql = "SELECT COUNT(*) as count 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'solicitacoes' 
                AND COLUMN_NAME = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$config['database']['database'], $nomeColuna]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return (int)($result['count'] ?? 0) > 0;
    };
    
    // 1. Adicionar local_manutencao
    if (!$colunaExiste('local_manutencao')) {
        echo "✅ Adicionando coluna 'local_manutencao'...\n";
        $pdo->exec("ALTER TABLE `solicitacoes` 
                    ADD COLUMN `local_manutencao` VARCHAR(255) NULL DEFAULT NULL 
                    COMMENT 'Local onde será realizada a manutenção' 
                    AFTER `descricao_card`");
        echo "   ✓ Coluna 'local_manutencao' adicionada com sucesso!\n\n";
    } else {
        echo "ℹ️  Coluna 'local_manutencao' já existe.\n\n";
    }
    
    // 2. Adicionar finalidade_locacao
    if (!$colunaExiste('finalidade_locacao')) {
        echo "✅ Adicionando coluna 'finalidade_locacao'...\n";
        $pdo->exec("ALTER TABLE `solicitacoes` 
                    ADD COLUMN `finalidade_locacao` ENUM('RESIDENCIAL', 'COMERCIAL') NULL DEFAULT NULL 
                    COMMENT 'Finalidade da locação: Residencial ou Comercial' 
                    AFTER `local_manutencao`");
        echo "   ✓ Coluna 'finalidade_locacao' adicionada com sucesso!\n\n";
    } else {
        echo "ℹ️  Coluna 'finalidade_locacao' já existe.\n\n";
    }
    
    // 3. Adicionar tipo_imovel
    if (!$colunaExiste('tipo_imovel')) {
        echo "✅ Adicionando coluna 'tipo_imovel'...\n";
        $pdo->exec("ALTER TABLE `solicitacoes` 
                    ADD COLUMN `tipo_imovel` ENUM('CASA', 'APARTAMENTO') NULL DEFAULT NULL 
                    COMMENT 'Tipo do imóvel: Casa ou Apartamento' 
                    AFTER `finalidade_locacao`");
        echo "   ✓ Coluna 'tipo_imovel' adicionada com sucesso!\n\n";
    } else {
        echo "ℹ️  Coluna 'tipo_imovel' já existe.\n\n";
    }
    
    // Verificar resultado
    echo "📋 Verificando colunas criadas:\n";
    $sql = "SELECT 
                COLUMN_NAME,
                DATA_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = 'solicitacoes'
            AND COLUMN_NAME IN ('local_manutencao', 'finalidade_locacao', 'tipo_imovel')
            ORDER BY ORDINAL_POSITION";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$config['database']['database']]);
    $colunas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    if (empty($colunas)) {
        echo "⚠️  Nenhuma coluna encontrada.\n";
    } else {
        foreach ($colunas as $coluna) {
            echo "   ✓ {$coluna['COLUMN_NAME']} ({$coluna['DATA_TYPE']}) - {$coluna['COLUMN_COMMENT']}\n";
        }
    }
    
    echo "\n✅ Script executado com sucesso!\n";
    echo "✅ Todas as colunas foram verificadas/criadas.\n";
    
} catch (\Exception $e) {
    echo "❌ Erro ao executar script: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

