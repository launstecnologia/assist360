<?php
/**
 * Script para atualizar o template "Nova Solicitação" com as variáveis corretas
 * 
 * Execute: php scripts/atualizar_template_nova_solicitacao.php
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
} catch (PDOException $e) {
    die("❌ Erro na conexão com o banco de dados: " . $e->getMessage() . "\n");
}

$templateCorpo = '🏠 Nova Solicitação - Seguro Imobiliário KSS e {{imobiliaria_nome}}



📋 Nº Atendimento: {{protocol}}

🏷 Contrato: {{contrato_numero}}

🔖 Protocolo Seguradora: {{protocolo_seguradora}}

👤 Nome: {{cliente_nome}}

📄 CPF: {{cliente_cpf}}

📞 Telefone: {{cliente_telefone}}

🏢 Imobiliária: {{imobiliaria_nome}}

📍 Endereço: {{endereco_completo}}

📝 Descrição do Problema:
{{descricao_problema}}

📅 Agendamento: Horário à Confirmar

🔗 Acompanhe sua solicitação em:
{{link_rastreamento}}

🚫 Caso deseje cancelar sua solicitação, acesse:
{{link_cancelamento_solicitacao}}

⚠ OBSERVAÇÕES IMPORTANTES:

🏢 Condomínio: Se o serviço for realizado em apartamento ou condomínio, é obrigatório comunicar previamente a administração ou portaria sobre a visita técnica agendada.

👥 Responsável no Local: É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.

Caso não tiver ninguém no local, será considerado assistência perdida.

⏳ Próximos Passos: Aguarde a confirmação das opções de horários informadas para realização da assistência. Caso nenhuma das opções tenha disponibilidade, novas opções serão oferecidas.

---

Solicitação criada automaticamente pelo sistema

Não responda essa mensagem';

try {
    // Primeiro, tentar atualizar o template padrão
    $sql = "
        UPDATE whatsapp_templates 
        SET corpo = ?,
            updated_at = NOW()
        WHERE tipo = 'Nova Solicitação' 
        AND ativo = 1
        AND padrao = 1
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$templateCorpo]);
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        echo "✅ Template padrão 'Nova Solicitação' atualizado com sucesso!\n";
    } else {
        // Se não houver template padrão, atualizar qualquer template ativo
        $sql = "
            UPDATE whatsapp_templates 
            SET corpo = ?,
                updated_at = NOW()
            WHERE tipo = 'Nova Solicitação' 
            AND ativo = 1
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$templateCorpo]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "✅ Template ativo 'Nova Solicitação' atualizado com sucesso!\n";
        } else {
            echo "⚠️ Nenhum template 'Nova Solicitação' ativo encontrado.\n";
            echo "   Crie um novo template no admin: /admin/templates-whatsapp\n";
        }
    }
    
    echo "\n📋 Variáveis incluídas no template:\n";
    echo "   - {{contrato_numero}} - Número do contrato\n";
    echo "   - {{descricao_problema}} - Descrição do problema\n";
    echo "   - {{link_cancelamento_solicitacao}} - Link para cancelar solicitação\n";
    echo "\n✅ Script executado com sucesso!\n";
    
} catch (\Exception $e) {
    echo "❌ Erro ao atualizar template: " . $e->getMessage() . "\n";
    exit(1);
}

