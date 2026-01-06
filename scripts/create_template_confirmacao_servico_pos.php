<?php
/**
 * Script para criar/atualizar template "Confirmação de Serviço" para notificação pós-serviço
 * 
 * Execute este script via linha de comando:
 * php scripts/create_template_confirmacao_servico_pos.php
 */

// Carregar configuração
$configFile = __DIR__ . '/../app/Config/config.php';
if (!file_exists($configFile)) {
    die("❌ Arquivo de configuração não encontrado: {$configFile}\n");
}

$config = require $configFile;
$dbConfig = $config['database'] ?? [];

// Conectar diretamente ao banco
$host = $dbConfig['host'] ?? 'localhost';
$database = $dbConfig['database'] ?? 'launs_kss';
$username = $dbConfig['username'] ?? 'root';
$password = $dbConfig['password'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $templateCorpo = 'Olá {{cliente_nome}}!

O horário agendado para o serviço foi finalizado. Por favor, nos informe como foi o atendimento clicando no link abaixo:

{{link_acoes_servico}}

📅 Data: {{data_agendamento}}
⏰ Horário: {{horario_agendamento}}

Protocolo: {{protocol}}

Atenciosamente,
Equipe KSS Assistência 360';

    // Verificar se já existe template padrão
    $sql = "SELECT id FROM whatsapp_templates WHERE tipo = 'Confirmação de Serviço' AND padrao = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $existe = $stmt->fetch();

    if ($existe) {
        // Atualizar template existente
        $sql = "
            UPDATE whatsapp_templates 
            SET corpo = ?,
                ativo = 1,
                padrao = 1,
                updated_at = NOW()
            WHERE tipo = 'Confirmação de Serviço' 
            AND padrao = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateCorpo]);
        echo "✅ Template 'Confirmação de Serviço' atualizado com sucesso!\n";
    } else {
        // Criar novo template
        $sql = "
            INSERT INTO whatsapp_templates (nome, tipo, corpo, ativo, padrao, created_at, updated_at)
            VALUES (?, ?, ?, 1, 1, NOW(), NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'Confirmação de Serviço - Padrão',
            'Confirmação de Serviço',
            $templateCorpo
        ]);
        echo "✅ Template 'Confirmação de Serviço' criado com sucesso!\n";
    }

    // Verificar resultado
    $sql = "SELECT id, nome, tipo, ativo, padrao FROM whatsapp_templates WHERE tipo = 'Confirmação de Serviço' AND padrao = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $template = $stmt->fetch();
    
    if ($template) {
        echo "\n📋 Template criado/atualizado:\n";
        echo "   ID: {$template['id']}\n";
        echo "   Nome: {$template['nome']}\n";
        echo "   Tipo: {$template['tipo']}\n";
        echo "   Ativo: " . ($template['ativo'] ? 'Sim' : 'Não') . "\n";
        echo "   Padrão: " . ($template['padrao'] ? 'Sim' : 'Não') . "\n";
        echo "\n✅ Pronto! O template está configurado para ser usado no cron de pós-serviço.\n";
    } else {
        echo "❌ Erro: Template não foi criado corretamente.\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Erro ao criar template: " . $e->getMessage() . "\n";
    exit(1);
}
