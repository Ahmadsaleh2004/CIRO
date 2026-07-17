<?php
/**
 * handlers/admin_reauth.php
 * يطلب من الأدمن إعادة إدخال كلمة السر بعد الدخول للمتجر
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'csrf_token' => generateCsrfToken()]);
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$pass    = $_POST['password'] ?? '';
$adminId = getCurrentAdminId();
$pdo     = getDB();

$stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($pass, $admin['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.', 'csrf_token' => generateCsrfToken()]);
    exit;
}

// كلمة السر صحيحة
unset($_SESSION['admin_in_store_mode']); // نظّف العلم إن وُجد

$redirect = $_POST['redirect'] ?? '/Task(1)/admin/home.php';
// تأكد أن الـ redirect آمن
if (!str_contains($redirect, '/Task(1)/admin/')) {
    $redirect = '/Task(1)/admin/home.php';
}

echo json_encode([
    'success'    => true,
    'redirect'   => $redirect,
    'csrf_token' => generateCsrfToken(),
]);
