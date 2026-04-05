<?php
/**
 * Database.php
 * Gerenciador centralizado de conexões com banco de dados
 * Utiliza prepared statements para prevenir SQL injection
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $user;
    private $password;
    private $database;

    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->user = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->database = $_ENV['DB_NAME'] ?? 'mini_erp';

        $this->connect();
    }

    /**
     * Implementa Singleton para gerenciar única conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Estabelece conexão com banco de dados
     */
    private function connect() {
        $this->connection = new mysqli(
            $this->host,
            $this->user,
            $this->password,
            $this->database
        );

        if ($this->connection->connect_error) {
            throw new Exception('Erro ao conectar com banco de dados: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    /**
     * Retorna a conexão MySQLi
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Executa query com prepared statement
     * @param string $query Query com placeholders (?)
     * @param array $params Parâmetros para bind
     * @param string $types String com tipos (s=string, i=integer, d=double, b=blob)
     * @return mysqli_result|bool Resultado da query ou false em caso de erro
     */
    public function execute($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception('Erro ao preparar query: ' . $this->connection->error);
            }

            // Se há parâmetros, faz bind
            if (!empty($params)) {
                if (empty($types)) {
                    // Auto-detecta tipos se não fornecido
                    $types = $this->detectTypes($params);
                }
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception('Erro ao executar query: ' . $stmt->error);
            }

            return $stmt->get_result();
        } catch (Exception $e) {
            Logger::error('Database Execute Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Executa query sem retorno (INSERT, UPDATE, DELETE)
     * @return int Número de linhas afetadas
     */
    public function executeUpdate($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception('Erro ao preparar query: ' . $this->connection->error);
            }

            if (!empty($params)) {
                if (empty($types)) {
                    $types = $this->detectTypes($params);
                }
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception('Erro ao executar update: ' . $stmt->error);
            }

            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
        } catch (Exception $e) {
            Logger::error('Database Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retorna insert ID da última query
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * Auto-detecta tipos de parâmetros para bind_param
     */
    private function detectTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /**
     * Inicia transação
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    /**
     * Confirma transação
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * Reverte transação
     */
    public function rollback() {
        $this->connection->rollback();
    }

    /**
     * Escapa string (fallback para casos especiais, não recomendado)
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Fecha conexão
     */
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Retorna última erro do banco
     */
    public function getError() {
        return $this->connection->error;
    }
}
