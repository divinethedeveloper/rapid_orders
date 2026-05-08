<?php
// backend/check.php
// Diagnostic page to check database connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$result = [
    'status' => 'checking',
    'php_version' => PHP_VERSION,
    'errors' => []
];

// Check if mysqli extension is loaded
if (!extension_loaded('mysqli')) {
    $result['errors'][] = 'MySQLi extension is not loaded';
    $result['status'] = 'error';
    echo json_encode($result);
    exit;
}

$result['mysqli_loaded'] = true;

// Try to connect to database
require_once 'db.php';

try {
    $db = Database::getInstance();
    if ($db->isConnected()) {
        $result['status'] = 'ok';
        $result['database'] = 'Connected successfully';
        
        // Test query
        $conn = $db->getConnection();
        $test = $conn->query("SELECT COUNT(*) as count FROM vendors");
        if ($test) {
            $row = $test->fetch_assoc();
            $result['vendors_count'] = $row['count'];
        }
    } else {
        $result['status'] = 'error';
        $result['errors'][] = 'Database connection failed';
    }
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
}

echo json_encode($result);
?>