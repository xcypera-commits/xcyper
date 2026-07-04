<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'security_monitoring_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("خطأ في الاتصال: " . $e->getMessage());
        }
        return $this->conn;
    }
}

function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>