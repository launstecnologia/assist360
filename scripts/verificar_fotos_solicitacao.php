<?php
/**
 * Script para verificar se as fotos estão sendo salvas corretamente
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

// Verificar se foi passado um ID de solicitação
$solicitacaoId = $argv[1] ?? null;

if (!$solicitacaoId) {
    echo "Uso: php scripts/verificar_fotos_solicitacao.php [ID_SOLICITACAO]\n";
    echo "Exemplo: php scripts/verificar_fotos_solicitacao.php 32\n";
    exit(1);
}

echo "=== Verificando Fotos da Solicitação #{$solicitacaoId} ===\n\n";

// 1. Verificar se a solicitação existe
$sqlSolicitacao = "SELECT id, numero_solicitacao, descricao_problema FROM solicitacoes WHERE id = ?";
$stmt = $pdo->prepare($sqlSolicitacao);
$stmt->execute([$solicitacaoId]);
$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    echo "❌ Solicitação #{$solicitacaoId} não encontrada!\n";
    exit(1);
}

echo "✅ Solicitação encontrada:\n";
echo "   ID: {$solicitacao['id']}\n";
echo "   Número: " . ($solicitacao['numero_solicitacao'] ?? 'N/A') . "\n";
echo "   Descrição: " . substr($solicitacao['descricao_problema'] ?? '', 0, 50) . "...\n\n";

// 2. Verificar fotos na tabela
$sqlFotos = "SELECT * FROM fotos WHERE solicitacao_id = ? ORDER BY created_at ASC";
$stmt = $pdo->prepare($sqlFotos);
$stmt->execute([$solicitacaoId]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Fotos na Tabela 'fotos' ===\n";
if (empty($fotos)) {
    echo "❌ Nenhuma foto encontrada na tabela 'fotos' para esta solicitação.\n\n";
} else {
    echo "✅ Encontradas " . count($fotos) . " foto(s):\n\n";
    foreach ($fotos as $index => $foto) {
        echo "Foto #" . ($index + 1) . ":\n";
        echo "   ID: {$foto['id']}\n";
        echo "   Nome Arquivo: {$foto['nome_arquivo']}\n";
        echo "   URL Arquivo: {$foto['url_arquivo']}\n";
        echo "   Criado em: {$foto['created_at']}\n";
        
        // Verificar se o arquivo físico existe
        $caminhoFisico = __DIR__ . '/../Public/uploads/solicitacoes/' . $foto['nome_arquivo'];
        if (file_exists($caminhoFisico)) {
            $tamanho = filesize($caminhoFisico);
            echo "   ✅ Arquivo físico existe: {$caminhoFisico}\n";
            echo "   Tamanho: " . number_format($tamanho / 1024, 2) . " KB\n";
        } else {
            echo "   ❌ Arquivo físico NÃO encontrado: {$caminhoFisico}\n";
        }
        echo "\n";
    }
}

// 3. Verificar diretório de uploads
$uploadDir = __DIR__ . '/../Public/uploads/solicitacoes/';
echo "=== Verificando Diretório de Uploads ===\n";
if (!is_dir($uploadDir)) {
    echo "❌ Diretório não existe: {$uploadDir}\n";
    echo "   Tentando criar...\n";
    if (mkdir($uploadDir, 0755, true)) {
        echo "   ✅ Diretório criado com sucesso!\n";
    } else {
        echo "   ❌ Erro ao criar diretório!\n";
    }
} else {
    echo "✅ Diretório existe: {$uploadDir}\n";
    $arquivos = glob($uploadDir . '*');
    echo "   Total de arquivos no diretório: " . count($arquivos) . "\n";
}

echo "\n=== Verificação Concluída ===\n";

