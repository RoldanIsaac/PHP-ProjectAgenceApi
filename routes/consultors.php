<?php
class Consultors {
    private $conn;
    private $table_usuario = "CAO_USUARIO";
    private $table_permissao = "PERMISSAO_SISTEMA";

    public function __construct($db) {
        $this->conn = $db;
    }

    // GET - Get all valid consultors
    public function getConsultors() {
        try {
           $query = "
                SELECT u.CO_USUARIO, u.NO_USUARIO, u.DS_SENHA
                FROM " . $this->table_usuario . " u
                INNER JOIN " . $this->table_permissao . " p
                    ON u.CO_USUARIO = p.CO_USUARIO
                WHERE p.CO_SISTEMA = 1
                AND p.IN_ATIVO = 'S'
                AND p.CO_TIPO_USUARIO IN (0, 1, 2)
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $consultors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $consultors,
                "count" => count($consultors)
            ]);
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error" => "Error al obtener consultores: " . $exception->getMessage()
            ]);
        }
    }
}
?>
