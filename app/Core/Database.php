<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];
    private static int $retryAttempts = 0;
    private static ?int $lastActivityTime = null;
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MS = 100; // 100ms entre tentativas
    private const CONNECTION_IDLE_TIMEOUT = 300; // 5 minutos de inatividade antes de fechar conexão

    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Fecha a conexão atual e força uma nova conexão na próxima chamada.
     * Útil para limpar conexões em caso de erro.
     */
    public static function closeConnection(): void
    {
        if (self::$instance !== null) {
            self::$instance = null;
            self::$lastActivityTime = null;
        }
    }

    /**
     * Verifica se a conexão está ativa e válida.
     * Otimizado para evitar queries desnecessárias que podem criar conexões extras.
     * Usa verificação simples de atributo em vez de executar query.
     */
    private static function isConnectionValid(): bool
    {
        if (self::$instance === null) {
            return false;
        }

        try {
            // Verificar se a conexão está viva acessando um atributo simples
            // Isso não cria statement e é mais eficiente
            @self::$instance->getAttribute(PDO::ATTR_SERVER_INFO);
            return true;
        } catch (PDOException $e) {
            // Se houver erro ao acessar atributo, a conexão está morta
            self::$instance = null;
            return false;
        } catch (\Exception $e) {
            // Qualquer outro erro também indica conexão inválida
            self::$instance = null;
            return false;
        }
    }

    public static function getInstance(): PDO
    {
        $currentTime = time();
        
        // Se a conexão está inativa há muito tempo, fecha ela
        if (self::$instance !== null && self::$lastActivityTime !== null) {
            $idleTime = $currentTime - self::$lastActivityTime;
            if ($idleTime > self::CONNECTION_IDLE_TIMEOUT) {
                error_log("Fechando conexão inativa (idle por {$idleTime}s)");
                self::closeConnection();
            }
        }
        
        // Se não há instância, cria uma nova
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    self::$config['host'],
                    self::$config['database']
                );

                self::$instance = new PDO(
                    $dsn,
                    self::$config['username'],
                    self::$config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => false, // Evita conexões persistentes que mantêm estado
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    ]
                );
                
                // Reset retry attempts em caso de sucesso
                self::$retryAttempts = 0;
                self::$lastActivityTime = $currentTime;
            } catch (PDOException $e) {
                // Se for erro de max_user_connections, tenta aguardar e tentar novamente
                if (strpos($e->getMessage(), 'max_user_connections') !== false && self::$retryAttempts < self::MAX_RETRY_ATTEMPTS) {
                    self::$retryAttempts++;
                    // Fechar conexão antes de tentar novamente
                    self::closeConnection();
                    usleep(self::RETRY_DELAY_MS * 1000 * self::$retryAttempts); // Backoff exponencial
                    return self::getInstance(); // Tenta novamente
                }
                
                error_log('Erro na conexão com o banco de dados: ' . $e->getMessage());
                throw new PDOException('Erro na conexão com o banco de dados: ' . $e->getMessage(), 0, $e);
            }
        } else {
            // Verificar conexão apenas se estiver inativa há mais de 30 segundos (otimização)
            // Isso evita verificações desnecessárias em cada chamada
            if (self::$lastActivityTime !== null && ($currentTime - self::$lastActivityTime) > 30) {
                if (!self::isConnectionValid()) {
                    // Conexão inválida, criar nova
                    self::closeConnection();
                    return self::getInstance(); // Recursão para criar nova conexão
                }
            }
            
            // Atualiza o tempo da última atividade
            self::$lastActivityTime = $currentTime;
        }

        return self::$instance;
    }

    /**
     * Executa uma query SQL e fecha o cursor automaticamente.
     * NÃO retorna PDOStatement para evitar conexões em estado Sleep.
     * 
     * @param string $sql Query SQL a ser executada
     * @param array $params Parâmetros para a query preparada
     * @return void
     * @throws PDOException Em caso de erro na execução
     */
    public static function query(string $sql, array $params = []): void
    {
        $stmt = null;
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            // Fecha o cursor imediatamente após execute para liberar a conexão
            $stmt->closeCursor();
            $stmt = null;
        } catch (PDOException $e) {
            // Garante que o statement seja fechado mesmo em caso de erro
            if ($stmt !== null) {
                try {
                    $stmt->closeCursor();
                } catch (\Exception $closeException) {
                    // Ignora erros ao fechar cursor
                }
                $stmt = null;
            }
            
            // Se for erro de max_user_connections, tenta fechar conexão e aguardar
            if (strpos($e->getMessage(), 'max_user_connections') !== false) {
                self::closeConnection();
                // Aguarda um pouco antes de relançar o erro
                usleep(200000); // 200ms
            }
            
            error_log('Erro ao executar query: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params));
            throw new PDOException('Erro ao executar query: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Executa uma query SQL e retorna o número de linhas afetadas.
     * Útil para operações UPDATE, DELETE, INSERT que precisam verificar linhas afetadas.
     * 
     * @param string $sql Query SQL a ser executada
     * @param array $params Parâmetros para a query preparada
     * @return int Número de linhas afetadas
     * @throws PDOException Em caso de erro na execução
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = null;
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            // Fecha o cursor imediatamente após obter rowCount
            $stmt->closeCursor();
            $stmt = null;
            return $rowCount;
        } catch (PDOException $e) {
            // Garante que o statement seja fechado mesmo em caso de erro
            if ($stmt !== null) {
                try {
                    $stmt->closeCursor();
                } catch (\Exception $closeException) {
                    // Ignora erros ao fechar cursor
                }
                $stmt = null;
            }
            
            // Se for erro de max_user_connections, tenta fechar conexão e aguardar
            if (strpos($e->getMessage(), 'max_user_connections') !== false) {
                self::closeConnection();
                // Aguarda um pouco antes de relançar o erro
                usleep(200000); // 200ms
            }
            
            error_log('Erro ao executar query: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params));
            throw new PDOException('Erro ao executar query: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Executa uma query e retorna uma única linha.
     * Fecha o cursor automaticamente após o fetch.
     * 
     * @param string $sql Query SQL a ser executada
     * @param array $params Parâmetros para a query preparada
     * @return array|null Array associativo com os dados ou null se não houver resultado
     * @throws PDOException Em caso de erro na execução
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = null;
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            // Fecha o cursor imediatamente após fetch
            $stmt->closeCursor();
            $stmt = null;
            return $result ?: null;
        } catch (PDOException $e) {
            // Garante que o statement seja fechado mesmo em caso de erro
            if ($stmt !== null) {
                try {
                    $stmt->closeCursor();
                } catch (\Exception $closeException) {
                    // Ignora erros ao fechar cursor
                }
                $stmt = null;
            }
            
            // Se for erro de max_user_connections, tenta fechar conexão e aguardar
            if (strpos($e->getMessage(), 'max_user_connections') !== false) {
                self::closeConnection();
                // Aguarda um pouco antes de relançar o erro
                usleep(200000); // 200ms
            }
            
            error_log('Erro ao executar fetch: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params));
            throw new PDOException('Erro ao executar fetch: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Executa uma query e retorna todas as linhas.
     * Fecha o cursor automaticamente após o fetchAll.
     * 
     * @param string $sql Query SQL a ser executada
     * @param array $params Parâmetros para a query preparada
     * @return array Array de arrays associativos com os dados
     * @throws PDOException Em caso de erro na execução
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = null;
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            // Fecha o cursor imediatamente após fetchAll
            $stmt->closeCursor();
            $stmt = null;
            return $result;
        } catch (PDOException $e) {
            // Garante que o statement seja fechado mesmo em caso de erro
            if ($stmt !== null) {
                try {
                    $stmt->closeCursor();
                } catch (\Exception $closeException) {
                    // Ignora erros ao fechar cursor
                }
                $stmt = null;
            }
            
            // Se for erro de max_user_connections, tenta fechar conexão e aguardar
            if (strpos($e->getMessage(), 'max_user_connections') !== false) {
                self::closeConnection();
                // Aguarda um pouco antes de relançar o erro
                usleep(200000); // 200ms
            }
            
            error_log('Erro ao executar fetchAll: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('Params: ' . json_encode($params));
            throw new PDOException('Erro ao executar fetchAll: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    public static function rollback(): bool
    {
        return self::getInstance()->rollback();
    }
}
