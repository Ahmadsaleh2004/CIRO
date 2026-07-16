<?php
/**
 * handlers/notifications_handler.php — المرحلة 15
 * يدعم 3 actions:
 *   GET  ?action=list          → قائمة الإشعارات + عدد غير المقروءة
 *   POST action=mark_read      → يُحدّث is_read=1 لإشعار محدد
 *   POST action=mark_all_read  → يُحدّث is_read=1 لكل إشعارات المستخدم
 *   POST action=send [admin]   → إرسال إشعار من الأدمن لمستخدم
 */

require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

$pdo    = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond(bool $ok, string $msg, array $extra = []): void {
    // أرجع توكن جديد دائماً لمزامنة الـ DOM
    $extra['csrf_token'] = generateCsrfToken();
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

// ── GET: list ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    if (!isUser()) respond(false, 'Unauthorized');

    session_write_close();

    $uid  = getCurrentUserId();
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.message, n.is_read, n.created_at,
               a.full_name AS sender_name,
               a.email     AS sender_email
        FROM notifications n
        LEFT JOIN admins a ON a.id = n.sender_admin_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $cntStmt->execute([$uid]);
    $unread = (int)$cntStmt->fetchColumn();

    respond(true, 'ok', ['notifications' => $rows, 'unread' => $unread]);
}

// ── POST: mark_read ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_read') {
    if (!isUser()) respond(false, 'Unauthorized');
    $id = (int)($_POST['id'] ?? 0);
    $uid = getCurrentUserId();
    if ($id) {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $uid]);
    }
    respond(true, 'ok');
}

// ── POST: mark_all_read ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_all_read') {
    if (!isUser()) respond(false, 'Unauthorized');
    $uid = getCurrentUserId();
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    respond(true, 'ok');
}

// ── POST: dismiss (حذف إشعار واحد) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'dismiss') {
    if (!isUser()) respond(false, 'Unauthorized');
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $uid = getCurrentUserId();
    $id  = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM notifications WHERE id=? AND user_id=?")->execute([$id, $uid]);
    }
    respond(true, 'ok', ['csrf_token' => generateCsrfToken()]);
}

// ── POST: delete_all (حذف كل الإشعارات) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_all') {
    if (!isUser()) respond(false, 'Unauthorized');
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $uid = getCurrentUserId();
    $pdo->prepare("DELETE FROM notifications WHERE user_id=?")->execute([$uid]);
    respond(true, 'ok', ['csrf_token' => generateCsrfToken()]);
}

// ── POST: send (للأدمن فقط) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    if (!isAdmin()) respond(false, 'Unauthorized');
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $targetUserId = (int)($_POST['user_id']  ?? 0);
    $title        = trim($_POST['title']      ?? '');
    $message      = trim($_POST['message']    ?? '');
    $adminId      = getCurrentAdminId();

    if (!$targetUserId || !$title || !$message) {
        respond(false, 'Missing required fields.');
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
    $check->execute([$targetUserId]);
    if (!$check->fetch()) respond(false, 'User not found.');

    $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, sender_admin_id)
        VALUES (?, ?, ?, ?)
    ")->execute([$targetUserId, $title, $message, $adminId]);

    respond(true, 'Notification sent successfully.');
}

respond(false, 'Invalid request.');
