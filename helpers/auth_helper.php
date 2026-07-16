<?php
/**
 * helpers/auth_helper.php
 * دوال المصادقة والصلاحيات — تُضمَّن في كل صفحة محمية
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // true في HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Session Isolation Check ────────────────────────────────────
if (isset($_SESSION['user_id']) && isset($_SESSION['admin_id'])) {
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lang_helper.php';

define('SESSION_IDLE_TIMEOUT', 1800); // 30 دقيقة

// ── Session Timeout ───────────────────────────────────────────
function checkSessionTimeout(): void {
    if (isset($_SESSION['last_active'])) {
        if (time() - $_SESSION['last_active'] > SESSION_IDLE_TIMEOUT) {
            session_unset();
            session_destroy();
            header('Location: /Task(1)/index.php?session=expired');
            exit;
        }
    }
    $_SESSION['last_active'] = time();
}

// ── حالة تسجيل الدخول ────────────────────────────────────────
function isLoggedIn(): bool {
    checkSessionTimeout();
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

function isUser(): bool {
    return isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

function getAdminRole(): string {
    return $_SESSION['admin_role'] ?? '';
}

function isRoleA(): bool {
    return getAdminRole() === 'A';
}

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function getCurrentAdminId(): ?int {
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

// ── صلاحيات الأدمن ───────────────────────────────────────────
function loadAdminPermissions(int $adminId): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admin_permissions WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    $_SESSION['admin_permissions'] = $stmt->fetch() ?: [];
}

function getAdminPermissions(): array {
    return $_SESSION['admin_permissions'] ?? [];
}

function hasPermission(string $perm): bool {
    if (isRoleA()) return true; // A يتجاوز كل الصلاحيات
    $perms = getAdminPermissions();
    return !empty($perms[$perm]);
}

function requirePermission(string $perm): void {
    if (!isAdmin()) {
        header('Location: /Task(1)/index.php?error=unauthorized');
        exit;
    }
    if (!hasPermission($perm)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;text-align:center;padding:60px"><h2>403 — Access Denied.</h2><a href="/Task(1)/index.php">← Back</a></div>');
    }
}

function requireUser(): void {
    if (!isUser()) {
        header('Location: /Task(1)/index.php?login=required');
        exit;
    }
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /Task(1)/index.php?login=required');
        exit;
    }
}

// ── تحديث last_activity ───────────────────────────────────────
function updateUserActivity(): void {
    $uid = getCurrentUserId();
    if ($uid) {
        getDB()->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$uid]);
    }
}

// ── تسجيل محاولة دخول ────────────────────────────────────────
function logLoginAttempt(string $email, bool $success): void {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = getDB()->prepare("INSERT INTO login_attempts (email,ip_address,attempted_at,success) VALUES (?,?,NOW(),?)");
    $stmt->execute([$email, $ip, $success ? 1 : 0]);
}

// ── Rate Limiting (5 محاولات / 10 دقائق) ─────────────────────
function isRateLimited(string $email): bool {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = getDB()->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE (email = ? OR ip_address = ?)
          AND success = 0
          AND attempted_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute([$email, $ip]);
    return (int)$stmt->fetchColumn() >= 5;
}

function getRateLimitMinutes(string $email): int {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = getDB()->prepare("
        SELECT attempted_at FROM login_attempts
        WHERE (email = ? OR ip_address = ?) AND success = 0
        ORDER BY attempted_at DESC LIMIT 1
    ");
    $stmt->execute([$email, $ip]);
    $last = $stmt->fetchColumn();
    if (!$last) return 0;
    return max(0, (int)ceil((600 - (time() - strtotime($last))) / 60));
}

// ── تسجيل الخروج ──────────────────────────────────────────────
function logout(): void {
    session_unset();
    session_destroy();
    header('Location: /Task(1)/index.php');
    exit;
}

// ── Rate Limiting العام (المرحلة 4) ───────────────────────────
function checkRateLimit(string $action, int $maxAttempts, int $timeWindowMinutes): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $pdo = getDB();

    // إنشاء الجدول ذاتياً إن لم يكن موجوداً
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action_ip_time (action, ip_address, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM rate_limits
        WHERE action = ?
          AND (ip_address = ? OR (user_id IS NOT NULL AND user_id = ?))
          AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([$action, $ip, $userId, $timeWindowMinutes]);
    return (int)$stmt->fetchColumn() >= $maxAttempts;
}

function logRateLimitAttempt(string $action): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $pdo = getDB();

    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (action, ip_address, user_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$action, $ip, $userId]);
}

