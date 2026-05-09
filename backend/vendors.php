<?php
// backend/vendors.php
// Returns a single vendor + all its products (with images) as JSON
// Usage: /backend/vendors.php?slug=techhub-gh

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── DB config ────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'rapidorders');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

// ── Validate input ───────────────────────────────────────────
$slug = trim($_GET['slug'] ?? '');
if (!$slug || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing vendor slug']);
    exit;
}

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

// ── Fetch vendor ─────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT
            v.id,
            v.name,
            v.slug,
            v.description,
            v.whatsapp,
            v.cover_image,
            v.is_open,
            c.name  AS category,
            c.slug  AS category_slug,
            c.color_class,
            (
                SELECT COUNT(*)
                FROM products p
                WHERE p.vendor_id = v.id AND p.active = 1
            ) AS product_count
        FROM vendors v
        LEFT JOIN categories c ON c.id = v.category_id
        WHERE v.slug = ? AND v.active = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendor not found']);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Vendor query failed: ' . $e->getMessage()]);
    exit;
}

// ── Fetch products with grouped images ───────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.in_stock,
            p.stock_qty,
            p.sort_order,
            c.name  AS category,
            c.slug  AS category_slug,
            -- primary image
            (
                SELECT pi.image_url
                FROM product_images pi
                WHERE pi.product_id = p.id AND pi.is_primary = 1
                LIMIT 1
            ) AS primary_image,
            -- all images as pipe-separated string
            (
                SELECT GROUP_CONCAT(pi2.image_url ORDER BY pi2.sort_order SEPARATOR '|')
                FROM product_images pi2
                WHERE pi2.product_id = p.id
            ) AS images_raw
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.vendor_id = ? AND p.active = 1
        ORDER BY p.sort_order ASC, p.id ASC
    ");
    $stmt->execute([$vendor['id']]);
    $rows = $stmt->fetchAll();

    // split images_raw into a clean array per product
    $products = array_map(function($row) {
        $row['images'] = $row['images_raw']
            ? explode('|', $row['images_raw'])
            : ($row['primary_image'] ? [$row['primary_image']] : []);
        unset($row['images_raw']); // don't expose raw pipe string
        $row['price']    = (float) $row['price'];
        $row['in_stock'] = (bool)  $row['in_stock'];
        return $row;
    }, $rows);

    // unique category list for filter pills
    $categories = array_values(array_unique(
        array_filter(array_column($products, 'category'))
    ));

    echo json_encode([
        'success'    => true,
        'vendor'     => $vendor,
        'products'   => $products,
        'categories' => $categories,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Products query failed: ' . $e->getMessage()]);
    exit;
}