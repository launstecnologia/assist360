-- Script para limpar conexões antigas em estado Sleep
-- Execute este script no MySQL/MariaDB para matar conexões inativas

-- 1. Ver todas as conexões do usuário launs_kss
SELECT 
    ID,
    USER,
    HOST,
    DB,
    COMMAND,
    TIME,
    STATE,
    LEFT(INFO, 50) as QUERY
FROM 
    INFORMATION_SCHEMA.PROCESSLIST
WHERE 
    USER = 'launs_kss'
    AND COMMAND = 'Sleep'
    AND TIME > 60  -- Conexões dormindo há mais de 60 segundos
ORDER BY 
    TIME DESC;

-- 2. Matar conexões em Sleep há mais de 5 minutos (300 segundos)
-- ATENÇÃO: Execute com cuidado! Isso vai desconectar usuários ativos.
SET @kill_query = CONCAT(
    'KILL ',
    GROUP_CONCAT(ID SEPARATOR '; KILL ')
)
FROM INFORMATION_SCHEMA.PROCESSLIST
WHERE USER = 'launs_kss'
  AND COMMAND = 'Sleep'
  AND TIME > 300;  -- Mais de 5 minutos

-- Para executar o KILL, você precisa fazer manualmente ou usar um script PHP
-- Veja o arquivo scripts/limpar_conexoes_antigas.php

-- 3. Verificar limite de conexões
SHOW VARIABLES LIKE 'max_user_connections';
SHOW VARIABLES LIKE 'wait_timeout';
SHOW VARIABLES LIKE 'interactive_timeout';

-- 4. Aumentar timeout (opcional, requer privilégios)
-- SET GLOBAL wait_timeout = 300;  -- 5 minutos
-- SET GLOBAL interactive_timeout = 300;  -- 5 minutos

