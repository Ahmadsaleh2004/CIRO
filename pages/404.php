<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | Cairo Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dark-theme.css" id="theme-style" disabled>
</head>
<body>
<?php include "../components/navbar.php"; ?>

<section class="container py-5 text-center" style="min-height: 70vh; display:flex; align-items:center; justify-content:center;">
    <div class="fade-in-up">
        <div style="font-size: 7rem; line-height:1;">🔍</div>
        <h1 class="fw-bold mt-3" style="font-size:5rem; color: var(--accent);">404</h1>
        <h2 class="fw-semibold mb-3" style="color: var(--text-color);">Page Not Found</h2>
        <p style="color: var(--placeholder-color); max-width:420px; margin:0 auto 2rem;">
            Oops! The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/Task(1)/index.php" class="btn btn-success px-4 py-2">🏠 Go Home</a>
            <a href="/Task(1)/pages/products.php" class="btn btn-success px-4 py-2">🛍️ Browse Products</a>
        </div>
    </div>
</section>

<?php include "../components/footer.php"; ?>
</body>
</html>
