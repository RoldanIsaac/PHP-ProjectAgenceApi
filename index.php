<?php
// Headers 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include files
require_once 'config/database.php';
require_once 'routes/consultors.php';
require_once 'routes/reports.php';

// Get requested URL
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Get base path (ej: if is in /api/index.php clean it)
$base_path = str_replace('\\', '/', dirname($script_name));

// Clean url (remove base_path and GET params)
$clean_path = str_replace($base_path, '', $request_uri);
$clean_path = parse_url($clean_path, PHP_URL_PATH);

// Split
$path_segments = array_values(array_filter(explode('/', trim($clean_path, '/'))));

// Connect to db
$database = new Database();
$db = $database->getConnection();

// Instantiate classes
$consultors = new Consultors($db);
$reports = new Reports($db);

// Routing
$method = $_SERVER['REQUEST_METHOD'];

// Determine endpoint
$segment0 = $path_segments[0] ?? '';
$endpoint = $path_segments[1] ?? '';
$param1   = $path_segments[2] ?? '';
$param2   = $path_segments[3] ?? '';

if ($segment0 === 'api') {
    switch (true) {
        // Index route
        case ($endpoint === '' && $method === 'GET'):
            echo json_encode(["message" => "API funcionando correctamente"]);
            break;

        // Consultors endpoints
        case ($endpoint === 'consultors' && $method === 'GET'):
            if (empty($param1)) {
                $consultors->getConsultors();
            } 
            break;

        
        // Reports endpoint
        case ($endpoint === 'reports' && $method === 'POST'):
            // Read JSON body with consultors and date range
            $input = json_decode(file_get_contents("php://input"), true);

            $consultorsList = $input['consultors'] ?? [];
            $dateStart = $input['dateStart'] ?? null;
            $dateEnd   = $input['dateEnd'] ?? null;

            if (empty($consultorsList) || !$dateStart || !$dateEnd) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "Parámetros faltantes: consultors[], dateStart, dateEnd"
                ]);
                exit();
            }

            $reports->getReports($consultorsList, $dateStart, $dateEnd);
            break;

        // Endpoint not found
        default:
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Endpoint not found"
            ]);
            break;
    }
} else {
    // Welcome message
    if ($method === 'GET') {
        echo json_encode([
            "message" => "Welcome to AGENCE API. Use /api/ to access the endpoints"
        ]);
    }
}
