<?php
require __DIR__ . '/../vendor/autoload.php';

// Carregar bootstrap
require __DIR__ . '/../app/Core/bootstrap.php';

try {
    $sql = "ALTER TABLE locatarios_contratos
            ADD COLUMN IF NOT EXISTS inquilino_nome VARCHAR(255) NULL AFTER cpf,
            ADD COLUMN IF NOT EXISTS tipo_imovel VARCHAR(50) NULL AFTER numero_contrato,
            ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS estado VARCHAR(2) NULL,
            ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS cep VARCHAR(10) NULL,
            ADD COLUMN IF NOT EXISTS endereco VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS numero VARCHAR(20) NULL,
            ADD COLUMN IF NOT EXISTS complemento VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS unidade VARCHAR(50) NULL,
            ADD COLUMN IF NOT EXISTS empresa_fiscal VARCHAR(255) NULL";
    
    // Executar cada ADD COLUMN separadamente para evitar erro se a coluna já existir
    $colunas = [
        "ADD COLUMN IF NOT EXISTS inquilino_nome VARCHAR(255) NULL AFTER cpf",
        "ADD COLUMN IF NOT EXISTS tipo_imovel VARCHAR(50) NULL AFTER numero_contrato",
        "ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) NULL",
        "ADD COLUMN IF NOT EXISTS estado VARCHAR(2) NULL",
        "ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) NULL",
        "ADD COLUMN IF NOT EXISTS cep VARCHAR(10) NULL",
        "ADD COLUMN IF NOT EXISTS endereco VARCHAR(255) NULL",
        "ADD COLUMN IF NOT EXISTS numero VARCHAR(20) NULL",
        "ADD COLUMN IF NOT EXISTS complemento VARCHAR(100) NULL",
        "ADD COLUMN IF NOT EXISTS unidade VARCHAR(50) NULL",
        "ADD COLUMN IF NOT EXISTS empresa_fiscal VARCHAR(255) NULL"
    ];
    
    foreach ($colunas as $coluna) {
        try {
            $sqlAlter = "ALTER TABLE locatarios_contratos " . $coluna;
            \App\Core\Database::query($sqlAlter);
            echo "OK: Coluna adicionada\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "INFO: Coluna já existe\n";
            } else {
                echo "ERRO: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nProcesso concluído!\n";
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

