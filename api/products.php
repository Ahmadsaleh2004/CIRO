<?php
/**
 * api/products.php — نقطة وصول REST تجريبية
 * GET /Task(1)/api/products.php
 * GET /Task(1)/api/products.php?id=5
 * GET /Task(1)/api/products.php?cat=phone&q=apple
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/db.php';

function apiRespond(bool $ok, $data, string $msg = ''): void {
    echo json_encode(['success'=>$ok, 'message'=>$msg, 'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = getDB();
    $id  = isset($_GET['id'])  ? (int)$_GET['id'] : null;
    $cat = isset($_GET['cat']) ? trim($_GET['cat']) : null;
    $q   = isset($_GET['q'])   ? trim($_GET['q'])   : null;

    if ($id) {
        $stmt = $pdo->prepare("
            SELECT p.*, GROUP_CONCAT(DISTINCT c.name) AS categories,
                   ROUND(AVG(pr.rating),1) AS avg_rating, COUNT(pr.id) AS review_count
            FROM products p
            LEFT JOIN product_category_pivot pcp ON pcp.product_id=p.id
            LEFT JOIN categories c ON c.id=pcp.category_id
            LEFT JOIN product_reviews pr ON pr.product_id=p.id
            WHERE p.id=?
            GROUP BY p.id
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) { http_response_code(404); apiRespond(false, null, 'Product not found'); }
        apiRespond(true, $product);
    }

    $sql    = "SELECT p.*, GROUP_CONCAT(DISTINCT c.name) AS categories
               FROM products p
               LEFT JOIN product_category_pivot pcp ON pcp.product_id=p.id
               LEFT JOIN categories c ON c.id=pcp.category_id
               WHERE 1=1";
    $params = [];

    if ($cat) {
        $sql .= " AND c.name = ?";
        $params[] = $cat;
    }
    if ($q) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.manufacturer LIKE ?)";
        $params[] = "%{$q}%";
        $params[] = "%{$q}%";
        $params[] = "%{$q}%";
    }

    $sql .= " GROUP BY p.id ORDER BY p.date_added DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    apiRespond(true, $products, count($products) . ' products found');

} catch (Exception $e) {
    http_response_code(500);
    apiRespond(false, null, 'Server error');
}
