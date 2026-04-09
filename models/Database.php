<?php
class Database {
    private $conn;
    // True singleton pattern - single instance for entire application
    private static $instance = null;
    private static $sharedConn = null;

    /**
     * Singleton pattern - get single database instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self(true);
        }
        return self::$instance;
    }

    public function __construct($useShared = true) {
        if ($useShared && self::$sharedConn instanceof \PDO) {
            $this->conn = self::$sharedConn;
            return;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            // store in shared static so subsequent Database instances reuse same PDO
            if ($useShared) {
                self::$sharedConn = $this->conn;
            }
        } catch(PDOException $e) {
            error_log("Error de conexión a base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    /**
     * Close connection and reset singleton
     */
    public function closeConnection() {
        $this->conn = null;
        self::$sharedConn = null;
        self::$instance = null;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>
