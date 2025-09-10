<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'student_db';
    private $username = 'root';
    private $password = '11111111';
    private $conn = null;

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }
        return $this->conn;
    }

    public function disconnect() {
        $this->conn = null;
    }
}

// Initialize database connection
$db = new Database();
$pdo = $db->connect();
?>