<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cairo Store | Home</title>
    <meta name="description" content="Cairo Store - Best Electronics Store with Premium Products and Fast Delivery">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
</head>
<body>

<?php include "components/navbar.php"; ?>

<!-- ===================== SLIDER ===================== -->
<section>
    <div id="mainSlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner" id="slider-inner">
            <!-- يتبنى dynamically من products.js -->
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- ===================== SHOP BY CATEGORY ===================== -->
<section class="container py-5">
    <h2 class="section-title">Shop By Category</h2>
    <div class="d-flex justify-content-center flex-wrap gap-3">
        <a href="/Task(1)/pages/products.php?cat=phones"      class="btn btn-outline-dark px-4 py-2">📱 Phones</a>
        <a href="/Task(1)/pages/products.php?cat=computers"   class="btn btn-outline-dark px-4 py-2">💻 Computers</a>
        <a href="/Task(1)/pages/products.php?cat=accessories" class="btn btn-outline-dark px-4 py-2">🎧 Accessories</a>
        <a href="/Task(1)/pages/products.php?cat=gaming"      class="btn btn-outline-dark px-4 py-2">🎮 Gaming</a>
    </div>
</section>

<!-- ===================== BEST SELLERS ===================== -->
<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Best Sellers</h2>
        <a href="/Task(1)/pages/products.php?cat=all&tag=best-seller" class="section-view-all">View All →</a>
    </div>
    <div class="section-carousel-wrapper">
        <button class="section-carousel-btn prev-btn" data-target="best-sellers-track">&#8249;</button>
        <div class="section-carousel-track" id="best-sellers-track"></div>
        <button class="section-carousel-btn next-btn" data-target="best-sellers-track">&#8250;</button>
    </div>
</section>

<!-- ===================== NEW ARRIVALS ===================== -->
<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">New Arrivals</h2>
        <a href="/Task(1)/pages/products.php?cat=all&tag=new" class="section-view-all">View All →</a>
    </div>
    <div class="section-carousel-wrapper">
        <button class="section-carousel-btn prev-btn" data-target="new-arrivals-track">&#8249;</button>
        <div class="section-carousel-track" id="new-arrivals-track"></div>
        <button class="section-carousel-btn next-btn" data-target="new-arrivals-track">&#8250;</button>
    </div>
</section>

<!-- ===================== EXPLORE MORE ===================== -->
<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Explore More</h2>
        <a href="/Task(1)/pages/products.php" class="section-view-all">View All →</a>
    </div>
    <div class="section-carousel-wrapper">
        <button class="section-carousel-btn prev-btn" data-target="other-products-track">&#8249;</button>
        <div class="section-carousel-track" id="other-products-track"></div>
        <button class="section-carousel-btn next-btn" data-target="other-products-track">&#8250;</button>
    </div>
</section>

<?php include "components/footer.php"; ?>

</body>
</html>