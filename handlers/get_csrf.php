<?php
/**
 * handlers/get_csrf.php
 * يُرجع CSRF Token جديداً عبر AJAX (GET request).
 * يُستخدم كـ Fallback عند انتهاء صلاحية التوكن.
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

header('Content-Type: application/json');

$token = generateCsrfToken();

// ── Session Locking Fix ────────────────────────────────────────
// نُحرّر قفل الجلسة فوراً بعد توليد التوكن لتفادي حجب
// الطلبات المتزامنة الأخرى من نفس المتصفح.
session_write_close();

echo json_encode(['token' => $token]);
