<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');


define('DB_HOST', 'sql113.infinityfree.com');
define('DB_USER', 'if0_40791412');
define('DB_PASS', 'fW96u5Ke4ZF4');
define('DB_NAME', 'if0_40791412_salon_booking');
define('DB_PORT', 3306);


function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME , DB_PORT );
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]);
        exit();
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}


function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}


function validateFields($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            return "Field '$field' is required";
        }
    }
    return null;
}
?>