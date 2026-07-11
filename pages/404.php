<?php
require_once __DIR__ . '/../helpers/auth_helper.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | Cairo Store</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
<?php include '../components/navbar.php'; ?>

<main id="main-content" class="container py-5 text-center">
    <div class="fade-in-up" style="max-width:480px;margin:auto;">
        <div style="font-size:6rem;">🔍</div>
        <h1 class="fw-bold mt-3" style="font-size:5rem;color:var(--accent);">404</h1>
        <h2 class="mb-3">Page Not Found</h2>
        <p style="color:var(--placeholder-color);">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="/Task(1)/index.php"         class="btn btn-success px-4">🏠 Home</a>
            <a href="/Task(1)/pages/products.php" class="btn btn-outline-secondary px-4">🛍️ Products</a>
        </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>
</body>
</html>
