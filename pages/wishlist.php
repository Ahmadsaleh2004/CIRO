<?php
require_once __DIR__ . '/../helpers/auth_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Cairo Store</title>
    <meta name="robots" content="noindex, follow">
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
        <span class="current">My Wishlist</span>
    </nav>
    <h1 class="section-title">My Wishlist</h1>
    <div id="wishlist-container" class="row"></div>
</section>
</main>

<?php include '../components/footer.php'; ?>
<script src="/Task(1)/js/wishlist.js"></script>
</body>
</html>
