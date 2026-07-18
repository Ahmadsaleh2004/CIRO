<?php
/**
 * handlers/product_handler.php
 * AJAX handler لعمليات المنتجات (visibility + delete)
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
require_once __DIR__ . '/../helpers/http_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    respond(false, 'Invalid request method.');
}

if (!isAdmin() || !hasPermission('can_manage_products')) {
    respond(false, 'Unauthorized');
}

$action  = $_POST['action'] ?? '';
$adminId = getCurrentAdminId();
$pdo     = getDB();

verifyCsrfToken($_POST['csrf_token'] ?? '');

// ── toggle visibility ─────────────────────────────────────────
if ($action === 'toggle_visibility') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (!$pid) respond(false, 'Invalid ID');

    $pdo->prepare("UPDATE products SET is_visible = 1 - COALESCE(is_visible, 1) WHERE id = ?")
        ->execute([$pid]);

    $s = $pdo->prepare("SELECT COALESCE(is_visible, 1) FROM products WHERE id = ?");
    $s->execute([$pid]);
    $newVal = (int)$s->fetchColumn();

    logAdminAction($adminId, 'toggle_visibility', 'product', $pid, "is_visible={$newVal}");
    respond(true, 'Visibility updated.', ['is_visible' => $newVal]);
}

// ── delete product ────────────────────────────────────────────
if ($action === 'delete_product') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (!$pid) respond(false, 'Invalid ID');

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
    logAdminAction($adminId, 'delete_product', 'product', $pid);
    respond(true, 'Product deleted.');
}

respond(false, 'Unknown action');
