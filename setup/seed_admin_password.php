<?php
/**
 * setup/seed_admin_password.php
 * شغّل مرة واحدة بعد schema.sql لضبط كلمة سر الأدمن.
 *
 * الاستخدام من CLI:
 *   php setup/seed_admin_password.php --email="admin@gmail.com" --password="YourPass"
 *
 * أو عبر متغيرات البيئة (.env):
 *   ADMIN_EMAIL=admin@gmail.com ADMIN_PASSWORD=YourPass php setup/seed_admin_password.php
 *
 * ⚠️ لا تكتب بيانات الأدمن مباشرة في هذا الملف.
 */

// ── 1. منع التشغيل من المتصفح (CLI فقط) ─────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("❌ هذا الملف مخصص للتشغيل من سطر الأوامر (CLI) فقط.\n");
}

require_once __DIR__ . '/../config/db.php';

// ── 2. قراءة البيانات من CLI args أو Environment variables ────
$email    = null;
$password = null;

// محاولة قراءة من --email / --password args
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--email='))    $email    = substr($arg, 8);
    if (str_starts_with($arg, '--password=')) $password = substr($arg, 11);
}

// Fallback: قراءة من متغيرات البيئة
if (!$email)    $email    = getenv('ADMIN_EMAIL')    ?: null;
if (!$password) $password = getenv('ADMIN_PASSWORD') ?: null;

// ── 3. التحقق من توفر البيانات ────────────────────────────────
if (!$email || !$password) {
    exit(
        "❌ يجب تمرير الإيميل وكلمة السر.\n" .
        "   مثال: php setup/seed_admin_password.php --email=\"admin@gmail.com\" --password=\"YourPass\"\n" .
        "   أو:   ADMIN_EMAIL=... ADMIN_PASSWORD=... php setup/seed_admin_password.php\n"
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("❌ صيغة الإيميل غير صحيحة.\n");
}

if (strlen($password) < 8) {
    exit("❌ كلمة السر يجب أن تكون 8 أحرف على الأقل.\n");
}

// ── 4. تشفير كلمة السر وتحديث قاعدة البيانات ─────────────────
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo  = getDB();
$stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
$stmt->execute([$hashed, $email]);

echo $stmt->rowCount() > 0
    ? "✅ تم ضبط كلمة السر بنجاح للإيميل: {$email}\n"
    : "⚠️  لم يُعثر على أدمن بهذا الإيميل — شغّل schema.sql أولاً.\n";
