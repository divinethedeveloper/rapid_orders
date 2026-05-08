<?php
// backend/categories.php
// Returns all categories for dropdown selection

error_reporting(1);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $result = $conn->query("
        SELECT id, name, slug, color_class 
        FROM categories 
        ORDER BY name ASC
    ");
    
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse(true, ['categories' => $categories], 'Categories loaded');
    
} catch (Exception $e) {
    sendResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}

$db->close();
?>