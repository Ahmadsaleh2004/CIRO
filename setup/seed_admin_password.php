<?php
/**
 * شغّل مرة واحدة بعد schema.sql لضبط كلمة سر الأدمن
 * CLI:      php seed_admin_password.php
 * Browser:  http://localhost/Task(1)/config/seed_admin_password.php
 */
require_once __DIR__ . '/../config/db.php';

$plain  = '120220622083';
$hashed = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo  = getDB();
$stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
$stmt->execute([$hashed, 'ahmadsaleh9688@gmail.com']);

echo $stmt->rowCount() > 0
    ? "✅ Password set.\nEmail: ahmadsaleh9688@gmail.com\nPassword: {$plain}\n"
    : "⚠️ Admin not found — run schema.sql first.\n";
