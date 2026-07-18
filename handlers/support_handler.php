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
require_once __DIR__ . '/../helpers/http_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    respond(false, 'Invalid request method.');
}

if (!isAdmin() || !hasPermission('can_manage_support')) {
    respond(false, 'Unauthorized');
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$pdo     = getDB();
$action  = $_POST['action'] ?? '';
$adminId = getCurrentAdminId();

// ── delete ────────────────────────────────────────────────────
if ($action === 'delete') {
    $msgId = (int)($_POST['message_id'] ?? 0);
    if (!$msgId) respond(false, 'Invalid ID');

    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
    logAdminAction($adminId, 'delete_support_message', 'contact_messages', $msgId);
    respond(true, 'Message deleted.');
}

// ── reply ─────────────────────────────────────────────────────
if ($action === 'reply') {
    $targetUserId = (int)($_POST['user_id']   ?? 0);
    $replyText    = trim($_POST['reply_text'] ?? '');

    if (!$targetUserId || !$replyText) respond(false, 'Missing fields');

    $check = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $check->execute([$targetUserId]);
    if (!$check->fetch()) respond(false, 'User not found');

    $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, 'Support Response', ?, ?)")
        ->execute([$targetUserId, $replyText, $adminId]);

    logAdminAction($adminId, 'reply_support_message', 'user', $targetUserId, "Replied: {$replyText}");
    respond(true, 'Reply sent successfully.');
}

respond(false, 'Unknown action');
