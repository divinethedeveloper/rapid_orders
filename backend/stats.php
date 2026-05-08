<?php
// backend/stats.php
header('Content-Type: application/json');
require_once 'db.php';

$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'get';

$conn = getDB();

// Track a page view (call this when vendor page loads)
if ($action === 'track_view' && $vendor_id > 0) {
    $stmt = $conn->prepare("
        INSERT INTO vendor_stats (vendor_id, page_views, orders_count) 
        VALUES (?, 1, 0) 
        ON DUPLICATE KEY UPDATE page_views = page_views + 1
    ");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// Get stats
$orders_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE vendor_id = ?");
$orders_stmt->bind_param("i", $vendor_id);
$orders_stmt->execute();
$orders_count = $orders_stmt->get_result()->fetch_assoc()['count'];

$visits_stmt = $conn->prepare("SELECT page_views FROM vendor_stats WHERE vendor_id = ?");
$visits_stmt->bind_param("i", $vendor_id);
$visits_stmt->execute();
$result = $visits_stmt->get_result();
$visits = $result->fetch_assoc();
$visit_count = $visits['page_views'] ?? 0;

echo json_encode(['success' => true, 'data' => ['orders' => $orders_count, 'visits' => $visit_count]]);
?>