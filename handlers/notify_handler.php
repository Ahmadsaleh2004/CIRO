<?php
/**
 * handlers/notify_handler.php
 * معالج "نبّهني لما يتوفر" (stock_notifications)
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

requireUser();
verifyCsrfToken($_POST['csrf_token'] ?? '');

$pdo = getDB();
$pid = (int)($_POST['product_id'] ?? 0);
$uid = getCurrentUserId();

if ($pid && $uid) {
    $exists = $pdo->prepare("SELECT id FROM stock_notifications WHERE product_id=? AND user_id=? LIMIT 1");
    $exists->execute([$pid, $uid]);
    if (!$exists->fetch()) {
        $pdo->prepare("INSERT INTO stock_notifications (product_id,user_id) VALUES (?,?)")->execute([$pid, $uid]);
    }
}

// ── Session Locking Fix ────────────────────────────────────────
// نُحرّر قفل الجلسة فوراً بعد انتهاء العمل بالـ session
// لتفادي حجب الطلبات المتزامنة الأخرى.
session_write_close();

// Redirect back with message
header('Location: /Task(1)/pages/product-details.php?id=' . $pid . '&notified=1');
exit;
