<?php
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();
$ws  = [];
try { $ws = $pdo->query("SELECT * FROM website_settings LIMIT 1")->fetch() ?: []; } catch (Exception $e) {}

$phone        = $ws['phone_number']   ?? '+20 123 456 789';
$workingHours = $ws['working_hours']  ?? 'Sun - Thu: 9 AM - 6 PM';
$siteUrl      = $ws['site_url']       ?? 'www.cairostore.com';
$fbUrl        = $ws['facebook_url']   ?? '#';
$igUrl        = $ws['instagram_url']  ?? '#';
$returnPolicy = $ws['return_policy']  ?? '';
$employees    = (int)($ws['employees_count'] ?? 50);

$usersCount    = 0;
$productsCount = 0;
try {
    $usersCount    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $productsCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Cairo Store</title>
    <meta name="description" content="Learn about Cairo Store — our mission, team, and commitment to quality electronics.">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="About Us | Cairo Store">
    <meta property="og:type" content="website">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
<?php include '../components/navbar.php'; ?>

<main id="main-content" role="main">
<section class="container py-5">

    <nav class="store-breadcrumb mb-4">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <span class="current">About Us</span>
    </nav>

    <div class="text-center mb-5">
        <h1 class="fw-bold">About Cairo Store</h1>
        <p class="lead">Your Trusted Electronics Store</p>
    </div>

    <!-- ── Stats Bar ─────────────────────────────── -->
    <div class="row g-3 mb-5 text-center">
        <div class="col-6 col-md-3">
            <div class="card p-3">
                <div style="font-size:2rem;color:var(--accent);"><?= number_format($usersCount) ?>+</div>
                <small style="color:var(--placeholder-color);">Happy Customers</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3">
                <div style="font-size:2rem;color:var(--accent);"><?= $productsCount ?>+</div>
                <small style="color:var(--placeholder-color);">Products</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3">
                <div style="font-size:2rem;color:var(--accent);"><?= $employees ?>+</div>
                <small style="color:var(--placeholder-color);">Team Members</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3">
                <div style="font-size:2rem;color:var(--accent);">2020</div>
                <small style="color:var(--placeholder-color);">Founded</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h2 class="h3 mb-3">🏪 Who We Are</h2>
                <p>Cairo Store is a modern electronics store specialized in smartphones, laptops, gaming devices and smart accessories.</p>
                <p>We aim to provide high quality products with excellent customer service and affordable prices.</p>
                <?php if ($returnPolicy): ?>
                <hr>
                <h3 class="h5">🔄 Return Policy</h3>
                <p class="small mb-0"><?= nl2br(htmlspecialchars($returnPolicy)) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h2 class="h3 mb-3">📋 Company Information</h2>
                <p>📅 <strong>Founded:</strong> 2020</p>
                <p>📍 <strong>Location:</strong> Cairo, Egypt</p>
                <p>👥 <strong>Employees:</strong> <?= $employees ?>+</p>
                <p>👤 <strong>Customers:</strong> <?= number_format($usersCount) ?>+</p>
                <p>🌐 <strong>Website:</strong> <?= htmlspecialchars($siteUrl) ?></p>
                <p>📞 <strong>Phone:</strong> <?= htmlspecialchars($phone) ?></p>
                <p class="mb-0">🕒 <strong>Hours:</strong> <?= htmlspecialchars($workingHours) ?></p>
            </div>
        </div>
    </div>

    <div class="card mt-4 p-4">
        <h2 class="h3 mb-3">🎯 Our Mission</h2>
        <p class="mb-0">To become one of the leading online electronics stores in the Middle East by delivering quality products and outstanding shopping experiences to every customer.</p>
    </div>

    <?php if ($fbUrl !== '#' || $igUrl !== '#'): ?>
    <div class="card mt-4 p-4 text-center">
        <h3 class="h5 mb-3">🌐 Follow Us</h3>
        <div class="d-flex justify-content-center gap-3">
            <?php if ($fbUrl !== '#'): ?>
            <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" class="btn btn-outline-primary">Facebook</a>
            <?php endif; ?>
            <?php if ($igUrl !== '#'): ?>
            <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" class="btn btn-outline-danger">Instagram</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</section>
</main>

<?php include '../components/footer.php'; ?>
</body>
</html>
