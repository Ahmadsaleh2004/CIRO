<?php
/**
 * config/error_handler.php
 * معالج أخطاء مخصص + Security Headers
 * يُستدعى في بداية كل ملف handler وكل صفحة تحتاج حماية
 */

// ── Security Headers (المرحلة 4) ─────────────────────────────
// نُرسل الـ Headers فقط إذا لم تُرسَل بعد
if (!headers_sent()) {
    // منع تضمين الصفحة في iframe لحماية من Clickjacking
    header('X-Frame-Options: DENY');

    // منع المتصفح من تخمين MIME type مختلف عما أُعلن
    header('X-Content-Type-Options: nosniff');

    // سياسة الـ Referrer
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy — يسمح بمصادر CDN الموجودة بالمشروع
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        // Bootstrap CSS + JS من jsdelivr
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
        "font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; " .
        // الصور من نفس الخادم فقط
        "img-src 'self' data:; " .
        "connect-src 'self';"
    );
}

// ── معالجة الأخطاء العادية (Warnings, Notices, Deprecated) ───
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    $log = sprintf(
        "[%s] [%s] %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        match ($errno) {
            E_WARNING             => 'WARNING',
            E_NOTICE              => 'NOTICE',
            E_DEPRECATED          => 'DEPRECATED',
            E_USER_WARNING        => 'USER_WARNING',
            E_USER_NOTICE         => 'USER_NOTICE',
            E_USER_DEPRECATED     => 'USER_DEPRECATED',
            E_STRICT              => 'STRICT',
            E_RECOVERABLE_ERROR   => 'RECOVERABLE_ERROR',
            default               => "ERROR($errno)",
        },
        $errstr,
        $errfile,
        $errline
    );
    error_log($log, 3, __DIR__ . '/../error.log');
    return true; // منعنا عرض الخطأ على الشاشة
});

// ── تسجيل الأخطاء الفادحة عند انتهاء السكربت ─────────────────
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $log = sprintf(
            "[%s] [FATAL] %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        error_log($log, 3, __DIR__ . '/../error.log');
    }
});
