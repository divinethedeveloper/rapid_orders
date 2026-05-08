<?php
// backend/vendors.php
// Handles vendor operations (GET, PUT for updates)

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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
        'message' => $message,
        'timestamp' => time()
    ]);
    exit;
}

function sendError($message, $code = 400) {
    sendResponse(false, null, $message, $code);
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    sendError('Database connection failed', 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$vendor_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

// GET vendor by ID or slug
if ($method === 'GET') {
    if ($vendor_id) {
        $stmt = $conn->prepare("
            SELECT v.*, c.name as category_name, c.slug as category_slug
            FROM vendors v
            LEFT JOIN categories c ON c.id = v.category_id
            WHERE v.id = ? AND v.active = 1
        ");
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vendor = $result->fetch_assoc();
        
        if ($vendor) {
            sendResponse(true, $vendor, 'Vendor found');
        } else {
            sendError('Vendor not found', 404);
        }
        $stmt->close();
    }
    elseif ($slug) {
        $stmt = $conn->prepare("
            SELECT v.*, c.name as category_name, c.slug as category_slug
            FROM vendors v
            LEFT JOIN categories c ON c.id = v.category_id
            WHERE v.slug = ? AND v.active = 1
        ");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $vendor = $result->fetch_assoc();
        
        if ($vendor) {
            // Get product count
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as count FROM products 
                WHERE vendor_id = ? AND active = 1
            ");
            $countStmt->bind_param("i", $vendor['id']);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $vendor['product_count'] = $countResult->fetch_assoc()['count'];
            $countStmt->close();
            
            // Get categories for filtering
            $catStmt = $conn->prepare("
                SELECT DISTINCT c.id, c.name, c.slug
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE p.vendor_id = ? AND p.active = 1
                ORDER BY c.name
            ");
            $catStmt->bind_param("i", $vendor['id']);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            $categories = $catResult->fetch_all(MYSQLI_ASSOC);
            $catStmt->close();
            
            sendResponse(true, [
                'vendor' => $vendor,
                'categories' => $categories
            ], 'Vendor found');
        } else {
            sendError('Vendor not found', 404);
        }
        $stmt->close();
    }
    else {
        // Get all vendors
        $result = $conn->query("
            SELECT v.*, c.name as category_name,
                (SELECT COUNT(*) FROM products p WHERE p.vendor_id = v.id AND p.active = 1) as product_count
            FROM vendors v
            LEFT JOIN categories c ON c.id = v.category_id
            WHERE v.active = 1
            ORDER BY v.name ASC
        ");
        $vendors = $result->fetch_all(MYSQLI_ASSOC);
        sendResponse(true, $vendors, count($vendors) . ' vendors found');
    }
}

// PUT - Update vendor
elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        sendError('Vendor ID and data required', 400);
    }
    
    $id = (int)$input['id'];
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($input['name'])) {
        $updates[] = "name = ?";
        $params[] = $input['name'];
        $types .= 's';
    }
    if (isset($input['whatsapp'])) {
        $updates[] = "whatsapp = ?";
        $params[] = preg_replace('/[^0-9]/', '', $input['whatsapp']);
        $types .= 's';
    }
    if (isset($input['description'])) {
        $updates[] = "description = ?";
        $params[] = $input['description'];
        $types .= 's';
    }
    if (isset($input['is_open'])) {
        $updates[] = "is_open = ?";
        $params[] = (int)$input['is_open'];
        $types .= 'i';
    }
    if (isset($input['momo_number'])) {
        // Store in a separate column or settings table - for now, store in whatsapp or add column
        // We'll add a custom field
        $updates[] = "whatsapp = ?";
        $params[] = preg_replace('/[^0-9]/', '', $input['momo_number']);
        $types .= 's';
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $updates[] = "updated_at = NOW()";
    $sql = "UPDATE vendors SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        sendResponse(true, ['id' => $id], 'Vendor updated successfully');
    } else {
        sendError('Failed to update vendor', 500);
    }
    $stmt->close();
}

else {
    sendError('Method not allowed', 405);
}

$db->close();
?>