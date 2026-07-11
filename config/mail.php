<?php
/**
 * config/mail.php
 * إعدادات SMTP لإرسال البريد الإلكتروني باستخدام PHPMailer أو mail()
 *
 * للاختبار المحلي باستخدام Mailtrap أو MailHog:
 * 1. Mailtrap:
 *    - سجل في https://mailtrap.io
 *    - انسخ بيانات SMTP (Host: sandbox.smtp.mailtrap.io, Port: 2525)
 *    - ضع Username و Password هنا.
 * 2. MailHog:
 *    - شغّل MailHog محلياً (https://github.com/mailhog/MailHog)
 *    - اضبط Host ليكون 'localhost' والمنفذ 1025 بدون تشفير أو مصادقة.
 */

return [
    'smtp_host'     => 'sandbox.smtp.mailtrap.io', // أو 'localhost' لـ MailHog
    'smtp_port'     => 2525,                       // أو 1025 لـ MailHog
    'smtp_auth'     => true,                       // اجعلها false لـ MailHog
    'smtp_username' => 'YOUR_MAILTRAP_USERNAME',   // اتركه فارغاً لـ MailHog
    'smtp_password' => 'YOUR_MAILTRAP_PASSWORD',   // اتركه فارغاً لـ MailHog
    'smtp_secure'   => 'tls',                      // اتركه فارغاً لـ MailHog
    'from_email'    => 'no-reply@cairostore.com',
    'from_name'     => 'Cairo Store',
];
