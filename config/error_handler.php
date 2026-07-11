<?php
/**
 * config/error_handler.php
 * معالج أخطاء مخصص — يسجّل الأخطاء في ملف فقط بدون عرض على الشاشة
 * يُستدعى في بداية كل ملف AJAX handler
 */

// معالجة الأخطاء العادية (Warnings, Notices, Deprecated)
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

// تسجيل الأخطاء الفادحة عند انتهاء السكربت
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
