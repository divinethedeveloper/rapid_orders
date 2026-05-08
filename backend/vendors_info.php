<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'PUT' && isset($input['id'])) {
    $conn = getDB();
    $id = $input['id'];
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
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    $updates[] = "updated_at = NOW()";
    $sql = "UPDATE vendors SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vendor updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>