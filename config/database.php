<?php
class Database {
    private $host = "localhost";
    private $db_name = "caol";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo json_encode([
                "success" => false,
                "error" => "Error de conexión: " . $exception->getMessage()
            ]);
            exit;
        }
        
        return $this->conn;
    }
}
?>