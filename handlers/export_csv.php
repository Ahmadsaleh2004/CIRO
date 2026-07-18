<?php
/**
 * handlers/export_csv.php
 * Handler مركزي لتصدير CSV من صفحات الأدمن
 * يُستدعى عبر GET: ?export=csv&type={users|orders|products|admins}&...فلاتر
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Unauthorized');
}

$pdo     = getDB();
$adminId = getCurrentAdminId();
$type    = $_GET['type'] ?? '';
$search  = trim($_GET['q']      ?? '');
$filter  = trim($_GET['status'] ?? '');
$now     = date('Y-m-d_H-i-s');

/**
 * إرسال ملف CSV مع BOM لدعم UTF-8 في إكسل
 */
function sendCsv(string $filename, array $headers, array $rows): void {
    // تفريغ أي output تم إرساله (HTML من layout.php)
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // BOM لدعم العربي في إكسل
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ══ USERS ═══════════════════════════════════════════════════════
if ($type === 'users') {
    requirePermission('can_manage_users');

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($filter === 'block') {
        $where[] = "(SELECT COUNT(*) FROM user_strikes WHERE user_id=u.id) >= 3";
    } elseif ($filter === 'not_active') {
        $where[] = "(SELECT COUNT(*) FROM user_strikes WHERE user_id=u.id) < 3 AND (u.last_activity < DATE_SUB(NOW(), INTERVAL 3 MONTH) OR u.last_activity IS NULL)";
    } elseif ($filter === 'active') {
        $where[] = "(SELECT COUNT(*) FROM user_strikes WHERE user_id=u.id) < 3 AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    }

    $sql = "SELECT u.id, u.full_name, u.email, u.phone_number, u.country, u.city, u.gender,
                   u.birth_date, u.last_activity, u.created_at,
                   (SELECT COUNT(*) FROM user_strikes WHERE user_id=u.id) AS strikes
            FROM users u"
         . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY u.last_activity DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    logAdminAction($adminId, 'export_csv', 'users', 0, "Exported " . count($data) . " users. Filter: {$filter} Search: {$search}");

    $headers = ['ID','Full Name','Email','Phone','Country','City','Gender','Birth Date','Last Activity','Joined','Strikes'];
    $rows = array_map(fn($r) => [
        $r['id'], $r['full_name'], $r['email'], $r['phone_number'] ?? '',
        $r['country'] ?? '', $r['city'] ?? '', $r['gender'] ?? '',
        $r['birth_date'] ?? '', $r['last_activity'] ?? '', $r['created_at'],
        $r['strikes'],
    ], $data);

    sendCsv("users_{$now}.csv", $headers, $rows);
}

// ══ ORDERS ══════════════════════════════════════════════════════
if ($type === 'orders') {
    requirePermission('can_manage_orders');

    $where  = [];
    $params = [];

    if ($filter !== '') {
        $where[]  = "o.status = ?";
        $params[] = $filter;
    }
    if ($search !== '') {
        if (is_numeric($search)) {
            $where[]  = "(o.order_id = ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $params[] = (int)$search;
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        } else {
            $where[]  = "(u.full_name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
    }

    $sql = "SELECT o.order_id, u.full_name, u.email, o.total_amount,
                   o.payment_method, o.status, o.created_at
            FROM orders o JOIN users u ON u.id=o.user_id"
         . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY CASE o.status WHEN 'not_taken' THEN 1 WHEN 'taken' THEN 2 WHEN 'cancelled' THEN 3 WHEN 'completed' THEN 4 ELSE 5 END, o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    logAdminAction($adminId, 'export_csv', 'orders', 0, "Exported " . count($data) . " orders. Filter: {$filter} Search: {$search}");

    $headers = ['Order ID','Customer','Email','Total ($)','Payment','Status','Date'];
    $rows = array_map(fn($r) => [
        $r['order_id'], $r['full_name'], $r['email'],
        number_format($r['total_amount'], 2),
        $r['payment_method'], $r['status'],
        $r['created_at'],
    ], $data);

    sendCsv("orders_{$now}.csv", $headers, $rows);
}

// ══ PRODUCTS ════════════════════════════════════════════════════
if ($type === 'products') {
    requirePermission('can_manage_products');

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "name LIKE ?";
        $params[] = "%{$search}%";
    }

    $sql = "SELECT id, name, price, discount_percentage, price_after_discount,
                   stock_quantity, sales_count, gender_category,
                   manufacturer, date_added, is_visible
            FROM products"
         . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY date_added DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    logAdminAction($adminId, 'export_csv', 'products', 0, "Exported " . count($data) . " products. Search: {$search}");

    $headers = ['ID','Name','Price ($)','Discount (%)','Final Price ($)','Stock','Sales','Gender','Brand','Date Added','Visible'];
    $rows = array_map(fn($r) => [
        $r['id'], $r['name'],
        number_format($r['price'], 2),
        $r['discount_percentage'],
        number_format($r['price_after_discount'] ?? $r['price'], 2),
        $r['stock_quantity'], $r['sales_count'],
        $r['gender_category'] ?? '', $r['manufacturer'] ?? '',
        $r['date_added'] ?? '',
        $r['is_visible'] ? 'Yes' : 'No',
    ], $data);

    sendCsv("products_{$now}.csv", $headers, $rows);
}

// ══ ADMINS ══════════════════════════════════════════════════════
if ($type === 'admins') {
    if (!isRoleA()) {
        http_response_code(403);
        die('Unauthorized — Role A only');
    }

    $stmt = $pdo->query("
        SELECT a.id, a.full_name, a.email, a.phone_number, a.role, a.created_at,
               ap.can_manage_products, ap.can_manage_users, ap.can_manage_orders,
               ap.can_manage_support, ap.can_view_dashboard
        FROM admins a
        LEFT JOIN admin_permissions ap ON ap.admin_id = a.id
        ORDER BY a.created_at ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    logAdminAction($adminId, 'export_csv', 'admins', 0, "Exported " . count($data) . " admins.");

    $headers = ['ID','Full Name','Email','Phone','Role','Products','Users','Orders','Support','Dashboard','Joined'];
    $rows = array_map(fn($r) => [
        $r['id'], $r['full_name'], $r['email'],
        $r['phone_number'] ?? '', $r['role'],
        $r['can_manage_products'] ? 'Yes' : 'No',
        $r['can_manage_users']    ? 'Yes' : 'No',
        $r['can_manage_orders']   ? 'Yes' : 'No',
        $r['can_manage_support']  ? 'Yes' : 'No',
        $r['can_view_dashboard']  ? 'Yes' : 'No',
        $r['created_at'],
    ], $data);

    sendCsv("admins_{$now}.csv", $headers, $rows);
}

// نوع غير معروف
http_response_code(400);
die('Invalid export type.');
