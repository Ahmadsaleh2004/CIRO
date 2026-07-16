<?php
/**
 * helpers/csrf_helper.php
 * إدارة CSRF Token — توليد + تحقق + دوران (Token Rotation)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * يولّد توكن جديد إذا لا يوجد، ويُرجع التوكن الحالي.
 * يُستخدم لعرض الحقل المخفي بالنماذج.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * يُرجع التوكن الحالي بدون توليد جديد.
 * يُستخدم داخل JSON responses لإرجاع التوكن بعد الدوران.
 */
function getCurrentCsrfToken(): string {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * يتحقق من صحة التوكن، يُدير الدوران (Rotation)،
 * وفي حال الفشل يُرجع JSON error ويوقف التنفيذ.
 */
function verifyCsrfToken(string $token): void {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
    }
    // Token Rotation: نحذف القديم ليُولَّد جديد عند الطلب التالي
    unset($_SESSION['csrf_token']);
}

/**
 * يُرجع حقل hidden جاهزاً للنماذج.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}
