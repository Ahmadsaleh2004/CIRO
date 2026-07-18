<?php
/**
 * handlers/get_csrf.php
 * يُرجع CSRF Token جديداً عبر AJAX (GET request فقط).
 * يُستخدم كـ Fallback عند انتهاء صلاحية التوكن.
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = generateCsrfToken();

// تحرير قفل الجلسة فوراً لتفادي حجب الطلبات المتزامنة
session_write_close();

echo json_encode(['token' => $token]);
