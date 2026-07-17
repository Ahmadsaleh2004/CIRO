<?php
/**
 * admin/layout.php — القالب المشترك لكل صفحات الأدمن
 * يُضمَّن في بداية كل صفحة أدمن مع تعريف $pageTitle
 */

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    header('Location: /Task(1)/index.php?error=unauthorized');
    exit;
}

// ── فحص وضعية "الأدمن يتصفح كمتجر" — يتطلب تأكيد كلمة السر ──────
if (!empty($_SESSION['admin_in_store_mode'])) {
    header('Location: /Task(1)/pages/admin-reauth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = getAdminRole();
$adminId   = getCurrentAdminId();
$csrf      = generateCsrfToken();

// Badges
$newOrders = $newMessages = 0;
try {
    $pdo = getDB();
    if (hasPermission('can_manage_orders'))  $newOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders          WHERE is_notified=0")->fetchColumn();
    if (hasPermission('can_manage_support')) $newMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_notified=0")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> | Cairo Store Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
    <link rel="stylesheet" href="/Task(1)/css/admin.css">
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>

<nav class="navbar custom-navbar sticky-top navbar-expand-lg" id="mainNavbar">
    <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold" href="/Task(1)/admin/home.php">
            🏪 Cairo Store
            <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;vertical-align:middle;">ADMIN</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav mx-auto gap-1">

                <?php if ($adminRole === 'A'): ?>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-semibold" href="/Task(1)/admin/manage-admins.php">👑 Admins</a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('can_view_dashboard')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/admin/dashboard.php">📊 Dashboard</a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('can_manage_products')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/admin/products-list.php">🛍️ Products</a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('can_manage_users')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/admin/manage-users.php">👥 Users</a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('can_manage_support')): ?>
                <li class="nav-item position-relative">
                    <a class="nav-link" href="/Task(1)/admin/support.php">
                        💬 Support
                        <?php if ($newMessages > 0): ?>
                        <span class="counter-badge" style="top:-4px;right:-8px;"><?= $newMessages ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('can_manage_orders')): ?>
                <li class="nav-item position-relative">
                    <a class="nav-link" href="/Task(1)/admin/manage-orders.php">
                        📦 Orders
                        <?php if ($newOrders > 0): ?>
                        <span class="counter-badge" style="top:-4px;right:-8px;"><?= $newOrders ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('can_edit_site_content')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/admin/site-settings.php">⚙️ Site Configuration</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link" href="/Task(1)/handlers/store_mode.php" title="View store as visitor">🌐 Store</a>
                </li>
            </ul>

            <div class="d-flex gap-2 align-items-center">
                <button id="theme-toggle" class="btn btn-outline-light" title="Toggle Theme">🌙</button>
                <div class="dropdown">
                    <button class="btn btn-outline-warning dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        👑 <?= htmlspecialchars($adminName) ?>
                        <span class="badge bg-dark ms-1" style="font-size:.6rem;"><?= htmlspecialchars($adminRole) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end"
                        style="background:var(--card-bg);border:1px solid var(--section-border);">
                        <li><a class="dropdown-item" href="/Task(1)/pages/my-info.php"       style="color:var(--text-color);">👤 My Info</a></li>
                        <li><a class="dropdown-item" href="/Task(1)/pages/contactus.php"      style="color:var(--text-color);">💬 Contact</a></li>
                        <?php if ($adminRole === 'A'): ?>
                        <li><a class="dropdown-item" href="/Task(1)/admin/backup.php"         style="color:var(--text-color);">💾 Backup DB</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider" style="border-color:var(--section-border);"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="logoutUser()">🚪 Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
// تمرير التوكن لـ JS
window._csrfToken = <?= json_encode($csrf) ?>;
</script>

<main id="main-content" class="container-fluid py-4 px-4">
