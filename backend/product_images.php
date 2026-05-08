<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$vendor_id = $_SESSION['vendor_id'];
$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/product_images/';
if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

$uploaded_images = [];
if (isset($_FILES['images'])) {
    foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
            $product_name = isset($_POST['product_name']) ? preg_replace('/[^a-z0-9]/i', '_', $_POST['product_name']) : 'product';
            $filename = "vendor_{$vendor_id}_{$product_name}_" . time() . "_{$index}." . $ext;
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($tmp_name, $target_file)) {
                $uploaded_images[] = '/product_images/' . $filename;
            }
        }
    }
}
echo json_encode(['success' => true, 'images' => $uploaded_images]);
?>