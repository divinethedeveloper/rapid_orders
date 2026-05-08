<?php
// backend/products.php
// REST API endpoint for product management

// Only show errors in log, not in output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

// ============================================
// HELPER FUNCTIONS
// ============================================

function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function sendError($message, $code = 400) {
    sendResponse(false, null, $message, $code);
}

// ============================================
// MAIN REQUEST HANDLER
// ============================================

try {
    $db = Database::getInstance();
    
    if (!$db->isConnected()) {
        sendError('Database connection failed. Please check your database settings.', 500);
    }
    
    $conn = $db->getConnection();
    
} catch (Exception $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle GET request
if ($method === 'GET') {
    
    // Get single product by ID
    if ($product_id) {
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                c.id as category_id,
                c.name as category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id = ? AND p.active = 1
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if ($product) {
            // Fetch images
            $imgStmt = $conn->prepare("
                SELECT image_url, sort_order, is_primary 
                FROM product_images 
                WHERE product_id = ? 
                ORDER BY sort_order ASC
            ");
            $imgStmt->bind_param("i", $product_id);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();
            $product['images'] = $imgResult->fetch_all(MYSQLI_ASSOC);
            $imgStmt->close();
            
            sendResponse(true, $product, 'Product found');
        } else {
            sendError('Product not found', 404);
        }
        $stmt->close();
    }
    
    // Get products by vendor
    $vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
    
    if ($vendor_id) {
        $stmt = $conn->prepare("
            SELECT 
                p.id,
                p.vendor_id,
                p.name,
                p.slug,
                p.description,
                p.price,
                p.in_stock,
                p.stock_qty,
                c.id as category_id,
                c.name as category
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.vendor_id = ? AND p.active = 1
            ORDER BY p.sort_order ASC, p.created_at DESC
        ");
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);
        
        // Fetch images for each product (simplified)
        foreach ($products as &$product) {
            $imgStmt = $conn->prepare("
                SELECT image_url 
                FROM product_images 
                WHERE product_id = ? 
                ORDER BY sort_order ASC 
                LIMIT 3
            ");
            $imgStmt->bind_param("i", $product['id']);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();
            $images = $imgResult->fetch_all(MYSQLI_ASSOC);
            $product['images'] = array_column($images, 'image_url');
            $imgStmt->close();
        }
        
        sendResponse(true, $products, count($products) . ' products found');
        $stmt->close();
    }
    
    // If no vendor_id, return empty array
    sendResponse(true, [], 'No products found');
}

// Handle POST request - create or update product
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Invalid JSON data', 400);
    }
    
    $product_id = isset($input['id']) && !empty($input['id']) ? (int)$input['id'] : null;
    $vendor_id = isset($input['vendor_id']) ? (int)$input['vendor_id'] : null;
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $price = isset($input['price']) ? (float)$input['price'] : 0;
    $in_stock = isset($input['in_stock']) ? (int)$input['in_stock'] : 1;
    $category_id = isset($input['category_id']) && !empty($input['category_id']) ? (int)$input['category_id'] : null;
    $images = isset($input['images']) && is_array($input['images']) ? $input['images'] : [];
    
    if (empty($name)) {
        sendError('Product name is required', 400);
    }
    
    if ($price < 0) {
        sendError('Valid price is required', 400);
    }
    
    if (!$vendor_id && !$product_id) {
        sendError('Vendor ID is required', 400);
    }
    
    $slug = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $name), '-'));
    
    $db->beginTransaction();
    
    try {
        if ($product_id) {
            // Update existing product
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, 
                    in_stock = ?, category_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ssdiis", $name, $description, $price, $in_stock, $category_id, $product_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete old images
            $delStmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $delStmt->bind_param("i", $product_id);
            $delStmt->execute();
            $delStmt->close();
            
        } else {
            // Create new product
            $stmt = $conn->prepare("
                INSERT INTO products (vendor_id, category_id, name, slug, description, price, in_stock, active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->bind_param("iisssdi", $vendor_id, $category_id, $name, $slug, $description, $price, $in_stock);
            $stmt->execute();
            $product_id = $db->lastInsertId();
            $stmt->close();
        }
        
        // Insert new images
        if (!empty($images)) {
            $imgStmt = $conn->prepare("
                INSERT INTO product_images (product_id, image_url, sort_order, is_primary)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($images as $index => $image_url) {
                $image_url = trim($image_url);
                if (!empty($image_url)) {
                    $is_primary = ($index === 0) ? 1 : 0;
                    $imgStmt->bind_param("isii", $product_id, $image_url, $index, $is_primary);
                    $imgStmt->execute();
                }
            }
            $imgStmt->close();
        }
        
        $db->commit();
        sendResponse(true, ['id' => $product_id], $product_id ? 'Product updated' : 'Product created');
        
    } catch (Exception $e) {
        $db->rollback();
        sendError('Database error: ' . $e->getMessage(), 500);
    }
}

// Handle DELETE request
elseif ($method === 'DELETE') {
    if (!$product_id) {
        sendError('Product ID is required', 400);
    }
    
    $stmt = $conn->prepare("UPDATE products SET active = 0 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        sendResponse(true, null, 'Product deleted');
    } else {
        sendError('Product not found', 404);
    }
    $stmt->close();
}

else {
    sendError('Method not allowed', 405);
}

$db->close();
?>