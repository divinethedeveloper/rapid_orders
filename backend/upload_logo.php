<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/db.php';

$vendor_id = $_SESSION['vendor_id'];
$upload_dir = dirname(__DIR__) . '/logo/';

// Check directory
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!is_writable($upload_dir)) {
    echo json_encode(['success' => false, 'message' => 'Upload directory not writable']);
    exit;
}

// Check file
if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file or upload error']);
    exit;
}

// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
finfo_close($finfo);

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Use JPEG, PNG, GIF, or WEBP.']);
    exit;
}

// Move file
$ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
$filename = "vendor_{$vendor_id}." . $ext;
$target_file = $upload_dir . $filename;

if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

chmod($target_file, 0644);

// Update database - using correct Database class
$db = Database::getInstance();
$conn = $db->getConnection();
$cover_image = '/logo/' . $filename;
$stmt = $conn->prepare("UPDATE vendors SET cover_image = ? WHERE id = ?");
$stmt->bind_param("si", $cover_image, $vendor_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'logo_url' => $cover_image]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$stmt->close();
$db->close();
?>