<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Details | Cairo Store</title>
<meta name="description" content="View complete product details, specifications, pricing and related products at Cairo Store.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="Product Details | Cairo Store">
<meta property="og:description" content="View complete product details at Cairo Store.">
<meta property="og:type" content="product">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">

<a href="#main-content" class="skip-nav">Skip to main content</a>

<?php include "../components/navbar.php"; ?>

<main id="main-content" role="main">
<div class="container py-5">

    <!-- Breadcrumb -->
    <nav class="store-breadcrumb mb-4">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <a href="/Task(1)/pages/products.php">Products</a>
        <span class="sep">/</span>
        <span class="current" id="breadcrumb-name">Product Details</span>
    </nav>

    <div id="product-details"></div>

    <hr class="my-5">

    <h2 class="section-title">You May Also Like</h2>
    <div class="row" id="related-products"></div>

</div>

<?php include "../components/footer.php"; ?>
<script src="/Task(1)/js/product-details.js"></script>

</main>
</body>
</html>