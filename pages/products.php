<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Cairo Store</title>
    <meta name="description" content="Browse all products available at Cairo Store. Search, filter and discover the latest electronics.">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="Products | Cairo Store">
    <meta property="og:description" content="Browse all products available at Cairo Store.">
    <meta property="og:type" content="website">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">

<a href="#main-content" class="skip-nav">Skip to main content</a>

<?php include "../components/navbar.php"; ?>

<main id="main-content" role="main">
<section class="container py-5">

    <!-- Breadcrumb -->
    <nav class="store-breadcrumb mb-3">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <span class="current">Products</span>
    </nav>

    <h1 class="section-title">Our Products</h1>

    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <input type="text" id="search" class="form-control" placeholder="Search Product...">
        </div>
        <div class="col-lg-4 mb-3">
            <select id="sort" class="form-select">
                <option value="">Sort Products</option>
                <optgroup label="By Name">
                    <option value="az">Name A-Z</option>
                    <option value="za">Name Z-A</option>
                </optgroup>
                <optgroup label="By Price">
                    <option value="low">Price Low To High</option>
                    <option value="high">Price High To Low</option>
                </optgroup>
                <optgroup label="By Tag">
                    <option value="tag-best-seller">⭐ Best Sellers</option>
                    <option value="tag-new">🆕 New Arrivals</option>
                    <option value="tag-limited">🔥 Limited Edition</option>
                    <option value="tag-regular">🏷️ Regular</option>
                </optgroup>
                <optgroup label="By Brand">
                    <option value="brand-Apple">🍎 Apple</option>
                    <option value="brand-Sony">🎵 Sony</option>
                    <option value="brand-Samsung">🌀 Samsung</option>
                    <option value="brand-Nintendo">🕹️ Nintendo</option>
                    <option value="brand-Canon">📷 Canon</option>
                </optgroup>
                <optgroup label="By Price Range">
                    <option value="price-u100">💰 Under $100</option>
                    <option value="price-u300">💰 Under $300</option>
                    <option value="price-u500">💰 Under $500</option>
                    <option value="price-o500">💎 $500 &amp; Above</option>
                </optgroup>
            </select>
        </div>
        <div class="col-lg-4 mb-3">
            <button id="reset" class="btn btn-secondary w-100">Reset</button>
        </div>
    </div>

    <!-- Results Count -->
    <div id="results-count"></div>

    <div class="row" id="products-container"></div>

</section>
</main>

<?php include "../components/footer.php"; ?>

</body>
</html>