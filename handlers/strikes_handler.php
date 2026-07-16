<?php
/**
 * handlers/strikes_handler.php
 * AJAX handler لعمليات الإنذارات (add/remove)
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin() || !hasPermission('can_manage_users')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$pdo     = getDB();
$action  = $_POST['action']  ?? '';
$userId  = (int)($_POST['user_id'] ?? 0);
$adminId = getCurrentAdminId();

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// ── add_strike ────────────────────────────────────────────────
if ($action === 'add_strike') {
    $reason = trim($_POST['reason'] ?? '');
    if (!$reason) {
        echo json_encode(['success' => false, 'message' => 'Please enter a reason for the strike.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_strikes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $currentCount = (int)$stmt->fetchColumn();

    if ($currentCount >= 3) {
        echo json_encode(['success' => false, 'message' => 'User already has 3 strikes (blocked).']);
        exit;
    }

    $ins = $pdo->prepare("INSERT INTO user_strikes (user_id, reason, issued_by_admin_id) VALUES (?, ?, ?)");
    $ins->execute([$userId, $reason, $adminId]);
    $newStrikeId = (int)$pdo->lastInsertId();
    $strikeNum   = $currentCount + 1;

    // إشعار للمستخدم
    $notifTitle = "Official Warning #{$strikeNum}";
    $notifMsg   = "You have received an official warning:\n{$reason}\nPlease follow our policies to avoid automatic account suspension at the 3rd strike.";
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, ?, ?, ?)")
        ->execute([$userId, $notifTitle, $notifMsg, $adminId]);

    // عند الإنذار الثالث — إلغاء كل الطلبات النشطة فوراً
    if ($strikeNum === 3) {
        $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled' 
            WHERE user_id = ? 
              AND status IN ('not_taken', 'taken')
        ")->execute([$userId]);
    }

    logAdminAction($adminId, 'add_strike', 'user', $userId, "Strike #{$strikeNum}. Reason: {$reason}");

    echo json_encode([
        'success'    => true,
        'message'    => 'Strike added and user notified.',
        'strike_num' => $strikeNum,
        'strike_id'  => $newStrikeId,
        'reason'     => $reason,
        'created_at' => date('d M Y, h:i A'),
        'csrf_token' => generateCsrfToken(),
    ]);
    exit;
}

// ── remove_strike ─────────────────────────────────────────────
if ($action === 'remove_strike') {
    $strikeId = (int)($_POST['strike_id'] ?? 0);
    if (!$strikeId) {
        echo json_encode(['success' => false, 'message' => 'Invalid strike ID.']);
        exit;
    }

    $pdo->prepare("DELETE FROM user_strikes WHERE id = ? AND user_id = ?")
        ->execute([$strikeId, $userId]);

    logAdminAction($adminId, 'remove_strike', 'user', $userId, "Strike ID {$strikeId} removed.");

    echo json_encode([
        'success'    => true,
        'message'    => 'Strike removed successfully.',
        'csrf_token' => generateCsrfToken(),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
