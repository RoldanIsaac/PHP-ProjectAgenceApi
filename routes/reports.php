<?php
class Reports {
    private $conn;
    private $table_fatura = "CAO_FATURA";
    private $table_os = "CAO_OS";
    private $table_salario = "CAO_SALARIO";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Generate months between two dates (formato d/m/Y)
    private function getMonthsInRange($startDate, $endDate) {
        $months = [];

        // Parse dates d/m/Y
        $current = DateTime::createFromFormat('d/m/Y', $startDate);
        $end = DateTime::createFromFormat('d/m/Y', $endDate);

        if (!$current || !$end) {
            throw new Exception("Invalid date forma. Must be d/m/Y");
        }

        // Normalice data
        $current->modify('first day of this month');
        $end->modify('first day of this month');

        while ($current <= $end) {
            $months[] = $current->format('Y-m');
            $current->modify('first day of next month');
        }

        return $months;
    }

    private function getMonthlyData($consultorCode, $month) {
        $startDate = $month . "-01";
        $endDate = date("Y-m-t", strtotime($startDate)); 

        $query = "
            SELECT 
                F.VALOR,
                F.TOTAL_IMP_INC,
                F.COMISSAO_CN
            FROM " . $this->table_fatura . " F
            INNER JOIN " . $this->table_os . " O ON F.CO_OS = O.CO_OS
            WHERE O.CO_USUARIO = :consultorCode
            AND F.DATA_EMISSAO BETWEEN :startDate AND :endDate
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':consultorCode', $consultorCode, PDO::PARAM_STR);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $receitaLiquida = 0;
        $comissao = 0;

        foreach ($rows as $row) {
            $valor = floatval($row['VALOR']);
            $impuestos = floatval($row['TOTAL_IMP_INC']);
            $comissao_cn = floatval($row['COMISSAO_CN']);

            // Receita líquida
            $receitaLiquida += $valor - $impuestos;

            // Comisión según la fórmula: (VALOR - VALOR*TOTAL_IMP_INC/100) * COMISSAO_CN/100
            $comissao += ($valor - ($valor * $impuestos / 100)) * ($comissao_cn / 100);
        }

        return [
            "receitaLiquida" => $receitaLiquida,
            "comissao" => $comissao
        ];
    }

    // 
    private function getFixedSalary($consultorCode) {
        $query = "SELECT BRUT_SALARIO FROM " . $this->table_salario . " WHERE CO_USUARIO = :consultorCode LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':consultorCode', $consultorCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? floatval($row['BRUT_SALARIO']) : 0;
    }
    

    // GET - Report for consultors in range date
    public function getReports($consultors, $dateStart, $dateEnd) {
        try {
            $resultsFinal = [];

            // For each consultor get required data
            foreach ($consultors as $consultor) {

                $monthsArray = [];
                foreach ($this->getMonthsInRange($dateStart, $dateEnd) as $month) {
                    $monthData = $this->getMonthlyData($consultor['CO_USUARIO'], $month);

                    $fixedSalary = $this->getFixedSalary($consultor['CO_USUARIO']);
                    $lucro = $monthData['receitaLiquida'] - ($fixedSalary + $monthData['comissao']);
                    
                    $monthsArray[] = [
                        "month" => $month,
                        "receitaLiquida" => $monthData['receitaLiquida'],
                        "fixedSalary" => $fixedSalary,
                        "comissao" => $monthData['comissao'],
                        "lucro" => $lucro
                    ];
                }

                $resultsFinal[] = [
                    "consultor" => $consultor['CO_USUARIO'],
                    "info" => $monthsArray
                ];
            }

            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $resultsFinal,
                "count" => count($resultsFinal)
            ]);

        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error" => "Error al obtener reportes: " . $exception->getMessage()
            ]);
        }
    }
}
?>
