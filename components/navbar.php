<?php
/**
 * components/navbar.php
 * ديناميكي: زائر / مستخدم / أدمن
 */

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

$currentPage   = basename($_SERVER['PHP_SELF']);
$loggedInUser  = isUser();
$loggedInAdmin = isAdmin();
$userName      = $_SESSION['user_name']  ?? '';
$adminName     = $_SESSION['admin_name'] ?? '';
$adminRole     = getAdminRole();

function isActive(string $page): string {
    global $currentPage;
    return $currentPage === $page ? 'active fw-bold' : '';
}

// عدد رسائل Support غير مقروءة (للأدمن فقط)
$newMessages = 0;
$newOrders   = 0;
if ($loggedInAdmin) {
    try {
        $pdo = getDB();
        if (hasPermission('can_manage_support')) {
            $newMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_notified=0")->fetchColumn();
        }
        if (hasPermission('can_manage_orders')) {
            $newOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE is_notified=0")->fetchColumn();
        }
    } catch (Exception $e) {}
}
?>
<nav class="navbar navbar-expand-lg custom-navbar sticky-top" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/Task(1)/index.php">🏪 Cairo Store</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNav" aria-controls="navbarNav"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= isActive('index.php') ?>" href="/Task(1)/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('products.php') ?>" href="/Task(1)/pages/products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('aboutus.php') ?>" href="/Task(1)/pages/aboutus.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('contactus.php') ?>" href="/Task(1)/pages/contactus.php">Contact Us</a>
                </li>

                <?php if ($loggedInAdmin): ?>
                    <!-- لا تعرض روابط لوحة التحكم هنا — تظهر فقط بـ admin/layout.php -->
                <?php elseif (!$loggedInUser): ?>
                    <!-- زائر: زر Log In -->
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#"
                           data-bs-toggle="modal" data-bs-target="#loginModal">Log In</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex gap-2 align-items-center">
                <!-- Wishlist — للجميع -->
                <a href="/Task(1)/pages/wishlist.php"
                   class="btn btn-outline-danger position-relative" aria-label="Wishlist">
                    ❤️ <span id="wishlist-count" class="counter-badge" aria-live="polite">0</span>
                </a>

                <!-- Cart — للمستخدم والأدمن فقط -->
                <?php if ($loggedInUser || $loggedInAdmin): ?>
                <button type="button"
                    class="btn btn-outline-warning position-relative"
                    data-bs-toggle="offcanvas" data-bs-target="#cartSidebar"
                    aria-controls="cartSidebar" aria-label="Shopping cart">
                    🛒 <span id="cart-count" class="counter-badge" aria-live="polite">0</span>
                </button>
                <?php endif; ?>

                <!-- Theme Toggle -->
                <button id="theme-toggle" class="btn btn-outline-light"
                        aria-label="Toggle theme" title="Toggle Theme">🌙</button>

                <!-- زر لوحة التحكم — للأدمن فقط على الصفحات العامة -->
                <?php if ($loggedInAdmin): ?>
                <a href="/Task(1)/admin/dashboard.php"
                   class="btn btn-warning fw-semibold" title="Admin Panel">
                    🛠️ Admin Panel
                </a>
                <?php endif; ?>

                <!-- Dropdown المستخدم -->
                <?php if ($loggedInUser): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        👤 <?= htmlspecialchars($userName) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end"
                        style="background:var(--card-bg);border:1px solid var(--section-border);">
                        <li><a class="dropdown-item" href="/Task(1)/pages/my-info.php"
                               style="color:var(--text-color);">👤 My Info</a></li>
                        <li><a class="dropdown-item" href="/Task(1)/pages/contactus.php"
                               style="color:var(--text-color);">💬 Contact Us</a></li>
                        <li><hr class="dropdown-divider" style="border-color:var(--section-border);"></li>
                        <li><a class="dropdown-item text-danger" href="#"
                               onclick="logoutUser()">🚪 Log Out</a></li>
                    </ul>
                </div>

                <!-- Dropdown الأدمن -->
                <?php elseif ($loggedInAdmin): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-warning dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        👑 <?= htmlspecialchars($adminName) ?>
                        <span class="badge bg-dark ms-1" style="font-size:.6rem;"><?= htmlspecialchars($adminRole) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end"
                        style="background:var(--card-bg);border:1px solid var(--section-border);">
                        <li><a class="dropdown-item" href="/Task(1)/pages/my-info.php"
                               style="color:var(--text-color);">👤 My Info</a></li>
                        <li><a class="dropdown-item" href="/Task(1)/pages/contactus.php"
                               style="color:var(--text-color);">💬 Contact Us</a></li>
                        <?php if ($adminRole === 'A'): ?>
                        <li><a class="dropdown-item" href="/Task(1)/admin/backup.php"
                               style="color:var(--text-color);">💾 Backup DB</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider" style="border-color:var(--section-border);"></li>
                        <li><a class="dropdown-item text-danger" href="#"
                               onclick="logoutUser()">🚪 Log Out</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
