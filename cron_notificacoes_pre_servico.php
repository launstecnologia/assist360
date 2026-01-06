<?php
/**
 * Script para executar via cron job
 * Verifica e envia notificações 1 hora antes do prestador chegar
 * 
 * Configurar no crontab para executar a cada 5 minutos:
 * */5 * * * * /usr/bin/php /caminho/para/kss/cron_notificacoes_pre_servico.php
 * 
 * Ou via wget/curl a cada 5 minutos:
 * */5 * * * * wget -q -O - https://seu-dominio.com/cron/notificacoes-pre-servico?token=SECRET_TOKEN
 */

// Carregar autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Carregar configurações
require_once __DIR__ . '/app/Config/bootstrap.php';

// Criar instância do controller e processar
$controller = new \App\Controllers\SolicitacoesController();

// Usar reflexão para chamar método privado
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('processarNotificacoesPreServico');
$method->setAccessible(true);
$method->invoke($controller);

