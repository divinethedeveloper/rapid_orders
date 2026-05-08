<?php
// backend/homepage.php
// Returns all active vendors as JSON for the homepage

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── DB config ────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'rapidorders');
define('DB_USER', 'root');       // change to your MySQL user
define('DB_PASS', '');           // change to your MySQL password
define('DB_PORT', 3306);

// ── Connect ──────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ── Query ────────────────────────────────────────────────────
// Uses the v_vendors view created in the SQL schema.
// Falls back to a raw join if the view doesn't exist yet.
try {
    $stmt = $pdo->query("
        SELECT
            v.id,
            v.name,
            v.slug,
            v.description,
            v.whatsapp,
            v.cover_image,
            v.is_open,
            c.name        AS category,
            c.slug        AS category_slug,
            c.color_class,
            (
                SELECT COUNT(*)
                FROM products p
                WHERE p.vendor_id = v.id AND p.active = 1
            ) AS product_count
        FROM vendors v
        LEFT JOIN categories c ON c.id = v.category_id
        WHERE v.active = 1
        ORDER BY v.name ASC
    ");

    $vendors = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($vendors),
        'vendors' => $vendors,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    exit;
}