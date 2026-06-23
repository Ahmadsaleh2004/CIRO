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

<!-- ===================== SLIDER (UNTOUCHED) ===================== -->
<section>
    <div id="mainSlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <a href="/Task(1)/pages/product-details.php?id=1"><img src="images/airpods.jpg" class="d-block w-100 slider-image" alt="Airpods"></a>
                <div class="carousel-caption d-none d-md-block text-shadow">
                    <h2 class="display-4 fw-bold">Wireless Freedom</h2>
                    <p class="lead">Experience premium sound quality with Apple Airpods.</p>
                </div>
            </div>
            <div class="carousel-item">
                <a href="/Task(1)/pages/product-details.php?id=2"><img src="images/airpods pro.jpg" class="d-block w-100 slider-image" alt="Airpods Pro"></a>
                <div class="carousel-caption d-none d-md-block text-shadow">
                    <h2 class="display-4 fw-bold">Active Noise Cancellation</h2>
                    <p class="lead">Immerse yourself in music with Airpods Pro.</p>
                </div>
            </div>
            <div class="carousel-item">
                <a href="/Task(1)/pages/product-details.php?id=3"><img src="images/apple watch.jpg" class="d-block w-100 slider-image" alt="Apple Watch"></a>
                <div class="carousel-caption d-none d-md-block text-shadow">
                    <h2 class="display-4 fw-bold">Smart Fitness</h2>
                    <p class="lead">Track your health and goals with Apple Watch.</p>
                </div>
            </div>
            <div class="carousel-item">
                <a href="/Task(1)/pages/product-details.php?id=8"><img src="images/iphon11 pro.jpg" class="d-block w-100 slider-image" alt="iPhone 11 Pro"></a>
                <div class="carousel-caption d-none d-md-block text-shadow">
                    <h2 class="display-4 fw-bold">Professional Photography</h2>
                    <p class="lead">Advanced smartphone with excellent camera system.</p>
                </div>
            </div>
            <div class="carousel-item">
                <a href="/Task(1)/pages/product-details.php?id=9"><img src="images/macbook.jpg" class="d-block w-100 slider-image" alt="MacBook"></a>
                <div class="carousel-caption d-none d-md-block text-shadow">
                    <h2 class="display-4 fw-bold">High Performance</h2>
                    <p class="lead">Powerful laptop designed for professionals.</p>
                </div>
            </div>
            <div class="carousel-item">
                <a href="/Task(1)/pages/product-details.php?id=12"><img src="images/ps4.jpg" class="d-block w-100 slider-image" alt="PS4"></a>
                <div class="carousel-caption d-none d-md-block text-shadow">
                    <h2 class="display-4 fw-bold">Gaming Excellence</h2>
                    <p class="lead">Dive into a new world of gaming with PS4.</p>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
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
    <h2 class="section-title">Best Sellers</h2>
    <div class="row" id="best-sellers-container"></div>
</section>

<!-- ===================== NEW ARRIVALS ===================== -->
<section class="container py-4">
    <h2 class="section-title">New Arrivals</h2>
    <div class="row" id="new-arrivals-container"></div>
</section>

<!-- ===================== EXPLORE MORE (Image + Price only) ===================== -->
<section class="container py-4">
    <h2 class="section-title">Explore More</h2>
    <div class="row" id="other-products-container"></div>
</section>

<?php include "components/footer.php"; ?>

</body>
</html>