<?php
/**
 * helpers/http_helper.php
 * دوال مساعدة لاستجابات HTTP/JSON المشتركة بين كل الـ handlers
 */

/**
 * respond() — ترسل استجابة JSON موحدة وتُنهي التنفيذ
 */
function respond(bool $ok, string $msg, array $extra = []): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $extra['csrf_token'] = generateCsrfToken();
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

/**
 * isStrongPassword() — يتحقق من قوة كلمة السر
 * 8 أحرف على الأقل + حرف كبير + حرف صغير + رقم + رمز خاص
 */
function isStrongPassword(string $pass): bool {
    return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass);
}
