<?php
/**
 * Script para verificar a estrutura da tabela fotos e dados
 */

// Carregar configuração do banco
require_once __DIR__ . '/../app/Config/config.php';

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
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage() . "\n");
}

echo "=== Verificando Tabela 'fotos' ===\n\n";

// 1. Verificar se a tabela existe
$sql = "SHOW TABLES LIKE 'fotos'";
$stmt = $pdo->query($sql);
$tabelaExiste = $stmt->rowCount() > 0;

if (!$tabelaExiste) {
    echo "❌ Tabela 'fotos' NÃO existe!\n";
    echo "   Criando tabela...\n\n";
    
    $sqlCreate = "CREATE TABLE IF NOT EXISTS `fotos` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `solicitacao_id` INT(11) NOT NULL,
        `nome_arquivo` VARCHAR(255) NOT NULL,
        `url_arquivo` VARCHAR(500) NOT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `solicitacao_id` (`solicitacao_id`),
        FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $pdo->exec($sqlCreate);
        echo "✅ Tabela 'fotos' criada com sucesso!\n\n";
    } catch (PDOException $e) {
        echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "✅ Tabela 'fotos' existe\n\n";
}

// 2. Verificar estrutura da tabela
echo "=== Estrutura da Tabela 'fotos' ===\n";
$sql = "DESCRIBE fotos";
$stmt = $pdo->query($sql);
$colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($colunas as $coluna) {
    echo "  - {$coluna['Field']}: {$coluna['Type']} " . ($coluna['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
}
echo "\n";

// 3. Contar total de fotos
$sql = "SELECT COUNT(*) as total FROM fotos";
$stmt = $pdo->query($sql);
$total = $stmt->fetch(PDO::FETCH_ASSOC);
echo "=== Estatísticas ===\n";
echo "Total de fotos na tabela: {$total['total']}\n\n";

// 4. Listar últimas 10 fotos
if ($total['total'] > 0) {
    echo "=== Últimas 10 Fotos ===\n";
    $sql = "SELECT f.*, s.numero_solicitacao 
            FROM fotos f 
            LEFT JOIN solicitacoes s ON f.solicitacao_id = s.id 
            ORDER BY f.created_at DESC 
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fotos as $index => $foto) {
        echo "Foto #" . ($index + 1) . ":\n";
        echo "  ID: {$foto['id']}\n";
        echo "  Solicitação: {$foto['numero_solicitacao']} (ID: {$foto['solicitacao_id']})\n";
        echo "  Nome Arquivo: {$foto['nome_arquivo']}\n";
        echo "  URL Arquivo: {$foto['url_arquivo']}\n";
        echo "  Criado em: {$foto['created_at']}\n";
        
        // Verificar arquivo físico
        $caminhoFisico = __DIR__ . '/../Public/uploads/solicitacoes/' . $foto['nome_arquivo'];
        if (file_exists($caminhoFisico)) {
            $tamanho = filesize($caminhoFisico);
            echo "  ✅ Arquivo existe: " . number_format($tamanho / 1024, 2) . " KB\n";
        } else {
            echo "  ❌ Arquivo NÃO existe: {$caminhoFisico}\n";
        }
        echo "\n";
    }
}

echo "=== Verificação Concluída ===\n";

