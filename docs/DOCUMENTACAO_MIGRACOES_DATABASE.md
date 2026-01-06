# 📊 Documentação de Migrações e Alterações de Banco de Dados

## 📋 Visão Geral

Este documento consolida **todas as migrações e alterações de tabelas** realizadas no sistema KSS Assistência 360. Todos os scripts SQL de migração foram consolidados aqui e removidos do projeto.

---

## 🗄️ Estrutura Base

### Arquivos Principais (NÃO MIGRAÇÕES)
- `database.sql` - Estrutura base do banco de dados
- `setup_banco_local.sql` - Script de setup para banco local
- `database_whatsapp_infrastructure.sql` - Infraestrutura WhatsApp (tabelas base)
- `database_whatsapp_templates.sql` - Templates padrão WhatsApp (dados iniciais)

---

## 📝 Migrações e Alterações

### 1. Tabelas de Locatários

**Arquivo Original:** `criar_tabelas_locatarios.sql`

#### Tabela: `locatarios`
```sql
CREATE TABLE IF NOT EXISTS locatarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    imobiliaria_id INT NOT NULL,
    ksi_cliente_id VARCHAR(50) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(15),
    whatsapp VARCHAR(15),
    endereco_logradouro VARCHAR(255),
    endereco_numero VARCHAR(20),
    endereco_complemento VARCHAR(100),
    endereco_bairro VARCHAR(100),
    endereco_cidade VARCHAR(100),
    endereco_estado VARCHAR(2),
    endereco_cep VARCHAR(9),
    status ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    ultima_sincronizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_locatario_imobiliaria (imobiliaria_id, ksi_cliente_id),
    UNIQUE KEY unique_cpf_imobiliaria (imobiliaria_id, cpf)
);
```

#### Tabela: `imoveis_locatarios`
```sql
CREATE TABLE IF NOT EXISTS imoveis_locatarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    locatario_id INT NOT NULL,
    ksi_imovel_cod VARCHAR(50) NOT NULL,
    endereco_logradouro VARCHAR(255) NOT NULL,
    endereco_numero VARCHAR(20),
    endereco_complemento VARCHAR(100),
    endereco_bairro VARCHAR(100),
    endereco_cidade VARCHAR(100),
    endereco_estado VARCHAR(2),
    endereco_cep VARCHAR(9),
    contrato_cod VARCHAR(50),
    contrato_dv VARCHAR(10),
    status ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (locatario_id) REFERENCES locatarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_imovel_locatario (locatario_id, ksi_imovel_cod)
);
```

**Descrição:** Cria tabelas para armazenar dados dos locatários e seus imóveis sincronizados da API KSI.

---

### 2. Coluna: `descricao_card`

**Arquivo Original:** `adicionar_coluna_descricao_card.sql`

```sql
ALTER TABLE solicitacoes
  ADD COLUMN descricao_card TEXT NULL AFTER descricao_problema;

-- Opcional: preencher inicialmente com a própria descricao_problema
UPDATE solicitacoes SET descricao_card = descricao_problema WHERE descricao_card IS NULL;
```

**Descrição:** Adiciona coluna para descrição do card nas solicitações (usado no Kanban).

---

### 3. Coluna: `horario_confirmado`

**Arquivo Original:** `adicionar_coluna_horario_confirmado.sql`

```sql
ALTER TABLE solicitacoes
  ADD COLUMN horario_confirmado TINYINT(1) NOT NULL DEFAULT 0 AFTER horario_agendamento;
```

**Descrição:** Adiciona flag booleana para indicar se o horário foi confirmado.

---

### 4. Migração Combinada: `descricao_card` + `horario_confirmado`

**Arquivo Original:** `scripts/migracao_descricao_card_e_horario_confirmado.sql`

```sql
START TRANSACTION;

-- 1) Adiciona coluna descricao_card se não existir
ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS descricao_card TEXT NULL AFTER descricao_problema;

-- Preenche descricao_card com descricao_problema quando nulo
UPDATE solicitacoes SET descricao_card = descricao_problema WHERE descricao_card IS NULL;

-- 2) Adiciona flag horario_confirmado se não existir
ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS horario_confirmado TINYINT(1) NOT NULL DEFAULT 0 AFTER horario_agendamento;

-- 3) Guarda o valor bruto selecionado para comparação nas views
ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS horario_confirmado_raw TEXT NULL AFTER horario_confirmado;

-- 4) Lista de confirmações (JSON)
ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS confirmed_schedules JSON NULL AFTER horario_confirmado_raw;

COMMIT;
```

**Descrição:** Migração combinada que adiciona múltiplas colunas relacionadas a horários confirmados e descrição do card.

---

### 5. Campos Adicionais para Solicitações

**Arquivo Original:** `adicionar_campos_solicitacoes.sql`

```sql
-- Adicionar campo para CPF do locatário
ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS locatario_cpf VARCHAR(14) NULL AFTER locatario_email;

-- Adicionar campo para horários preferenciais (JSON)
ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS horarios_opcoes JSON NULL AFTER prioridade;

-- Adicionar índice para busca por CPF
ALTER TABLE solicitacoes 
ADD INDEX IF NOT EXISTS idx_locatario_cpf (locatario_cpf);
```

**Descrição:** Adiciona campos necessários para exibição completa de solicitações manuais.

---

### 6. Tabela: `solicitacoes_manuais`

**Arquivo Original:** `criar_tabela_solicitacoes_manuais.sql`

```sql
CREATE TABLE IF NOT EXISTS solicitacoes_manuais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relacionamento
    imobiliaria_id INT NOT NULL,
    
    -- Dados Pessoais
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    
    -- Endereço
    tipo_imovel ENUM('RESIDENCIAL', 'COMERCIAL') NOT NULL,
    subtipo_imovel ENUM('CASA', 'APARTAMENTO') NULL,
    cep VARCHAR(10) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL,
    
    -- Serviço
    categoria_id INT NOT NULL,
    subcategoria_id INT NOT NULL,
    descricao_problema TEXT NOT NULL,
    
    -- Horários e Fotos
    horarios_preferenciais JSON NULL,
    fotos JSON NULL,
    
    -- Termos e Controle
    termos_aceitos BOOLEAN DEFAULT FALSE,
    status_id INT NOT NULL DEFAULT 1,
    
    -- Migração
    migrada_para_solicitacao_id INT NULL,
    migrada_em DATETIME NULL,
    migrada_por_usuario_id INT NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_imobiliaria (imobiliaria_id),
    INDEX idx_cpf (cpf),
    INDEX idx_status (status_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_subcategoria (subcategoria_id),
    INDEX idx_migrada (migrada_para_solicitacao_id),
    INDEX idx_created (created_at),
    
    -- Foreign Keys
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (subcategoria_id) REFERENCES subcategorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (status_id) REFERENCES status(id) ON DELETE RESTRICT,
    FOREIGN KEY (migrada_para_solicitacao_id) REFERENCES solicitacoes(id) ON DELETE SET NULL,
    FOREIGN KEY (migrada_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Descrição:** Tabela para armazenar solicitações criadas manualmente por usuários não logados.

---

### 7. Correção de Estrutura: `imobiliarias`

**Arquivo Original:** `corrigir_estrutura.sql`

```sql
-- 1. Remover campo duplicado api_instancia (se existir)
ALTER TABLE imobiliarias DROP COLUMN IF EXISTS api_instancia;

-- 2. Atualizar dados existentes para usar a estrutura correta
UPDATE imobiliarias SET 
    api_url = 'https://www.lagoimobiliaria.com.br',
    api_id = '42',
    api_token = 'bccbe9c743bd0e8edc809012f5a1234567890abcdef'
WHERE instancia IN ('demo', 'topx');
```

**Descrição:** Remove campos duplicados e organiza a estrutura da tabela `imobiliarias`.

---

### 8. Rollback: Remover Campos Adicionados

**Arquivo Original:** `rollback_last_changes.sql`

```sql
START TRANSACTION;

-- Drop added columns if they exist
ALTER TABLE solicitacoes
  DROP COLUMN IF EXISTS locatario_cpf,
  DROP COLUMN IF EXISTS horarios_opcoes,
  DROP COLUMN IF EXISTS horarios_sugestoes;

-- Drop the manual requests table if it exists
DROP TABLE IF EXISTS solicitacoes_manuais;

COMMIT;
```

**Descrição:** Script de rollback para reverter alterações anteriores (remover campos e tabela de solicitações manuais).

---

## 📊 Infraestrutura WhatsApp

### Tabela: `whatsapp_templates`

**Arquivo Original:** `database_whatsapp_infrastructure.sql`

```sql
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL COMMENT 'Nome descritivo do template',
    tipo VARCHAR(100) NOT NULL COMMENT 'Tipo de mensagem (ex: Nova Solicitação, Horário Confirmado)',
    corpo TEXT NOT NULL COMMENT 'Corpo do template com variáveis {{variavel}}',
    variaveis JSON NULL COMMENT 'Array JSON das variáveis disponíveis no template',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1 = ativo, 0 = inativo',
    padrao TINYINT(1) DEFAULT 0 COMMENT '1 = template padrão do tipo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo),
    INDEX idx_padrao (padrao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Templates customizáveis para mensagens WhatsApp';
```

### Tabela: `schedule_confirmation_tokens`

**Arquivo Original:** `database_whatsapp_infrastructure.sql`

```sql
CREATE TABLE IF NOT EXISTS schedule_confirmation_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token único gerado (64 caracteres hex)',
    solicitacao_id INT UNSIGNED NOT NULL COMMENT 'ID da solicitação relacionada',
    protocol VARCHAR(50) NOT NULL COMMENT 'Protocolo da solicitação (ex: KS2025-001)',
    scheduled_date DATE NULL COMMENT 'Data sugerida/confirmada',
    scheduled_time VARCHAR(20) NULL COMMENT 'Horário sugerido (ex: 14:00-17:00)',
    expires_at TIMESTAMP NOT NULL COMMENT 'Data de expiração do token (48 horas)',
    used_at TIMESTAMP NULL COMMENT 'Data/hora em que o token foi usado',
    action_type ENUM('confirm', 'cancel', 'reschedule') NULL COMMENT 'Ação realizada pelo cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_solicitacao (solicitacao_id),
    INDEX idx_used (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tokens para confirmação de horários via WhatsApp (expiração 48h)';
```

**Descrição:** Infraestrutura completa para sistema de notificações WhatsApp com templates customizáveis e tokens de confirmação.

---

## 📋 Ordem de Execução Recomendada

1. **Estrutura Base:**
   - `database.sql` (estrutura principal)
   - `database_whatsapp_infrastructure.sql` (infraestrutura WhatsApp)

2. **Tabelas de Locatários:**
   - `criar_tabelas_locatarios.sql`

3. **Alterações na Tabela `solicitacoes`:**
   - `adicionar_coluna_descricao_card.sql`
   - `adicionar_coluna_horario_confirmado.sql`
   - OU `scripts/migracao_descricao_card_e_horario_confirmado.sql` (combinação)
   - `adicionar_campos_solicitacoes.sql`

4. **Tabela de Solicitações Manuais:**
   - `criar_tabela_solicitacoes_manuais.sql`

5. **Correções:**
   - `corrigir_estrutura.sql` (se necessário)

6. **Dados Iniciais:**
   - `database_whatsapp_templates.sql` (templates padrão WhatsApp)
   - `setup_banco_local.sql` (dados de exemplo para desenvolvimento)

---

## ⚠️ Notas Importantes

1. **Compatibilidade:** Alguns scripts usam `ADD COLUMN IF NOT EXISTS` que requer MySQL 8.0+ ou MariaDB 10.5+
2. **Rollback:** Use `rollback_last_changes.sql` apenas se precisar reverter alterações
3. **Backup:** Sempre faça backup do banco antes de executar migrações
4. **Transações:** Scripts que usam `START TRANSACTION` devem ser executados completamente ou revertidos

---

## 🔄 Scripts de Migração Combinados

### Script Completo Recomendado (MySQL 8+)

```sql
START TRANSACTION;

-- Tabelas de Locatários
CREATE TABLE IF NOT EXISTS locatarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    imobiliaria_id INT NOT NULL,
    ksi_cliente_id VARCHAR(50) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(15),
    whatsapp VARCHAR(15),
    endereco_logradouro VARCHAR(255),
    endereco_numero VARCHAR(20),
    endereco_complemento VARCHAR(100),
    endereco_bairro VARCHAR(100),
    endereco_cidade VARCHAR(100),
    endereco_estado VARCHAR(2),
    endereco_cep VARCHAR(9),
    status ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    ultima_sincronizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_locatario_imobiliaria (imobiliaria_id, ksi_cliente_id),
    UNIQUE KEY unique_cpf_imobiliaria (imobiliaria_id, cpf)
);

CREATE TABLE IF NOT EXISTS imoveis_locatarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    locatario_id INT NOT NULL,
    ksi_imovel_cod VARCHAR(50) NOT NULL,
    endereco_logradouro VARCHAR(255) NOT NULL,
    endereco_numero VARCHAR(20),
    endereco_complemento VARCHAR(100),
    endereco_bairro VARCHAR(100),
    endereco_cidade VARCHAR(100),
    endereco_estado VARCHAR(2),
    endereco_cep VARCHAR(9),
    contrato_cod VARCHAR(50),
    contrato_dv VARCHAR(10),
    status ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (locatario_id) REFERENCES locatarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_imovel_locatario (locatario_id, ksi_imovel_cod)
);

-- Alterações na tabela solicitacoes
ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS descricao_card TEXT NULL AFTER descricao_problema;

UPDATE solicitacoes SET descricao_card = descricao_problema WHERE descricao_card IS NULL;

ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS horario_confirmado TINYINT(1) NOT NULL DEFAULT 0 AFTER horario_agendamento;

ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS horario_confirmado_raw TEXT NULL AFTER horario_confirmado;

ALTER TABLE solicitacoes
  ADD COLUMN IF NOT EXISTS confirmed_schedules JSON NULL AFTER horario_confirmado_raw;

ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS locatario_cpf VARCHAR(14) NULL AFTER locatario_email;

ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS horarios_opcoes JSON NULL AFTER prioridade;

ALTER TABLE solicitacoes 
ADD INDEX IF NOT EXISTS idx_locatario_cpf (locatario_cpf);

-- Tabela de solicitações manuais
CREATE TABLE IF NOT EXISTS solicitacoes_manuais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imobiliaria_id INT NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    tipo_imovel ENUM('RESIDENCIAL', 'COMERCIAL') NOT NULL,
    subtipo_imovel ENUM('CASA', 'APARTAMENTO') NULL,
    cep VARCHAR(10) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL,
    categoria_id INT NOT NULL,
    subcategoria_id INT NOT NULL,
    descricao_problema TEXT NOT NULL,
    horarios_preferenciais JSON NULL,
    fotos JSON NULL,
    termos_aceitos BOOLEAN DEFAULT FALSE,
    status_id INT NOT NULL DEFAULT 1,
    migrada_para_solicitacao_id INT NULL,
    migrada_em DATETIME NULL,
    migrada_por_usuario_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_imobiliaria (imobiliaria_id),
    INDEX idx_cpf (cpf),
    INDEX idx_status (status_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_subcategoria (subcategoria_id),
    INDEX idx_migrada (migrada_para_solicitacao_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (subcategoria_id) REFERENCES subcategorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (status_id) REFERENCES status(id) ON DELETE RESTRICT,
    FOREIGN KEY (migrada_para_solicitacao_id) REFERENCES solicitacoes(id) ON DELETE SET NULL,
    FOREIGN KEY (migrada_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
```

---

## 🔧 Scripts PHP de Migração

### 1. Script: Adicionar Campos à Tabela `solicitacoes`

**Arquivo Original:** `adicionar_campos_script.php`

```php
<?php
/**
 * Script para adicionar campos necessários na tabela solicitacoes
 * Execute acessando: http://localhost:8000/adicionar_campos_script.php
 * DEPOIS DE EXECUTAR, DELETE ESTE ARQUIVO POR SEGURANÇA!
 */

require_once __DIR__ . '/app/Config/config.php';

try {
    $config = require_once __DIR__ . '/app/Config/config.php';
    $dbConfig = $config['database'];
    
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options']
    );
    
    // Adicionar campo locatario_cpf
    try {
        $pdo->exec("ALTER TABLE solicitacoes ADD COLUMN locatario_cpf VARCHAR(14) NULL AFTER locatario_email");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
    }
    
    // Adicionar campo horarios_opcoes
    try {
        $pdo->exec("ALTER TABLE solicitacoes ADD COLUMN horarios_opcoes JSON NULL AFTER prioridade");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
    }
    
    // Adicionar índice
    try {
        $pdo->exec("ALTER TABLE solicitacoes ADD INDEX idx_locatario_cpf (locatario_cpf)");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
```

**Descrição:** Script PHP para adicionar campos `locatario_cpf` e `horarios_opcoes` à tabela `solicitacoes` com interface web.

---

### 2. Script: Criar Tabela `solicitacoes_manuais`

**Arquivo Original:** `criar_tabela_manual.php`

```php
<?php
/**
 * Script temporário para criar a tabela solicitacoes_manuais
 * Execute acessando: http://localhost:8000/criar_tabela_manual.php
 * DEPOIS DE EXECUTAR, DELETE ESTE ARQUIVO POR SEGURANÇA!
 */

$config = require_once __DIR__ . '/app/Config/config.php';
$dbConfig = $config['database'];

$pdo = new PDO(
    "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['options']
);

// SQL para criar a tabela (mesmo SQL do arquivo SQL)
$sql = "CREATE TABLE IF NOT EXISTS solicitacoes_manuais (...);";

$pdo->exec($sql);
```

**Descrição:** Script PHP para criar a tabela `solicitacoes_manuais` com interface web e verificação de estrutura.

---

### 3. Script: Rollback de Alterações

**Arquivo Original:** `rollback_last_changes.php`

```php
<?php
/**
 * Script para reverter alterações recentes
 * Remove colunas e tabela de solicitações manuais
 */

$pdo->beginTransaction();

// Drop columns if they exist
$columns = ['locatario_cpf', 'horarios_opcoes', 'horarios_sugestoes'];

foreach ($columns as $column) {
    $check = $pdo->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'solicitacoes' AND COLUMN_NAME = ?");
    $check->execute([$dbName, $column]);
    $exists = (int)$check->fetchColumn() > 0;
    if ($exists) {
        $pdo->exec("ALTER TABLE solicitacoes DROP COLUMN {$column}");
    }
}

// Drop table solicitacoes_manuais if exists
$check = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = 'solicitacoes_manuais'");
$exists = (int)$check->fetchColumn() > 0;
if ($exists) {
    $pdo->exec("DROP TABLE solicitacoes_manuais");
}

$pdo->commit();
```

**Descrição:** Script PHP para reverter alterações, removendo colunas e tabela criadas anteriormente.

---

## 📝 Changelog

### Versão 1.0 - 2024
- Criação de tabelas de locatários e imóveis
- Adição de colunas `descricao_card` e `horario_confirmado`
- Criação de tabela `solicitacoes_manuais`
- Adição de campos adicionais para solicitações
- Correção de estrutura da tabela `imobiliarias`
- Infraestrutura completa de WhatsApp

---

**Última atualização:** 2024  
**Documentação criada para consolidar todas as migrações do projeto**

