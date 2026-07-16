<?php
/**
 * handlers/support_handler.php
 * AJAX handler لعمليات Support (reply / delete)
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin() || !hasPermission('can_manage_support')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'csrf_token' => generateCsrfToken()]);
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$pdo     = getDB();
$action  = $_POST['action'] ?? '';
$adminId = getCurrentAdminId();

// ── delete ────────────────────────────────────────────────────
if ($action === 'delete') {
    $msgId = (int)($_POST['message_id'] ?? 0);
    if (!$msgId) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID', 'csrf_token' => generateCsrfToken()]);
        exit;
    }
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
    logAdminAction($adminId, 'delete_support_message', 'contact_messages', $msgId);
    echo json_encode(['success' => true, 'csrf_token' => generateCsrfToken()]);
    exit;
}

// ── reply ─────────────────────────────────────────────────────
if ($action === 'reply') {
    $targetUserId = (int)($_POST['user_id']    ?? 0);
    $replyText    = trim($_POST['reply_text']  ?? '');

    if (!$targetUserId || !$replyText) {
        echo json_encode(['success' => false, 'message' => 'Missing fields', 'csrf_token' => generateCsrfToken()]);
        exit;
    }

    // تحقق أن المستخدم موجود
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $check->execute([$targetUserId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User not found', 'csrf_token' => generateCsrfToken()]);
        exit;
    }

    $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, sender_admin_id)
        VALUES (?, 'Support Response', ?, ?)
    ")->execute([$targetUserId, $replyText, $adminId]);

    logAdminAction($adminId, 'reply_support_message', 'user', $targetUserId, "Replied: {$replyText}");
    echo json_encode(['success' => true, 'csrf_token' => generateCsrfToken()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action', 'csrf_token' => generateCsrfToken()]);
