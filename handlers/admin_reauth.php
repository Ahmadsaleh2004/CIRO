<?php
/**
 * handlers/admin_reauth.php
 * يتحقق من كلمة سر الأدمن قبل العودة للوحة التحكم
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/http_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    respond(false, 'Invalid request method.');
}

if (!isAdmin()) {
    respond(false, 'Unauthorized');
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$pass    = $_POST['password'] ?? '';
$adminId = getCurrentAdminId();
$pdo     = getDB();

$stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($pass, $admin['password'])) {
    respond(false, 'Incorrect password.');
}

// كلمة السر صحيحة
unset($_SESSION['admin_in_store_mode']);

$redirect = $_POST['redirect'] ?? '/Task(1)/admin/home.php';
if (!str_contains($redirect, '/Task(1)/admin/')) {
    $redirect = '/Task(1)/admin/home.php';
}

respond(true, 'Verified.', ['redirect' => $redirect]);
