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

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin() || !hasPermission('can_manage_products')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action  = $_POST['action'] ?? '';
$adminId = getCurrentAdminId();
$pdo     = getDB();

verifyCsrfToken($_POST['csrf_token'] ?? '');

// ── toggle visibility ─────────────────────────────────────────
if ($action === 'toggle_visibility') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (!$pid) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }

    $pdo->prepare("UPDATE products SET is_visible = 1 - COALESCE(is_visible, 1) WHERE id = ?")
        ->execute([$pid]);

    $s = $pdo->prepare("SELECT COALESCE(is_visible, 1) FROM products WHERE id = ?");
    $s->execute([$pid]);
    $newVal = (int)$s->fetchColumn();

    logAdminAction($adminId, 'toggle_visibility', 'product', $pid, "is_visible={$newVal}");
    echo json_encode(['success' => true, 'is_visible' => $newVal, 'csrf_token' => generateCsrfToken()]);
    exit;
}

// ── delete product ────────────────────────────────────────────
if ($action === 'delete_product') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (!$pid) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);

    logAdminAction($adminId, 'delete_product', 'product', $pid);
    echo json_encode(['success' => true, 'csrf_token' => generateCsrfToken()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
