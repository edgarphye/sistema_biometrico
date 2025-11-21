<?php
class Database {
    private $conn;
    // Shared PDO instance so different Database instances see the same underlying connection
    private static $sharedConn = null;

    public function __construct() {
        if (self::$sharedConn instanceof \PDO) {
            $this->conn = self::$sharedConn;
            return;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            // store in shared static so subsequent Database instances reuse same PDO
            self::$sharedConn = $this->conn;
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
        // clear shared connection
        self::$sharedConn = null;
    }
}
?>
