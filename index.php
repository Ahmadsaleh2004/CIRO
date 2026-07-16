<?php
/**
 * index.php — الصفحة الرئيسية
 * يقرأ المنتجات من MySQL ويمررها لـ JS
 */
require_once __DIR__ . '/helpers/auth_helper.php';
require_once __DIR__ . '/config/db.php';

$pdo = getDB();

// ── جلب كل المنتجات المرئية مرتبة تنازلياً بـ sales_count ──────
$products = $pdo->query("
    SELECT p.id, p.name, p.price, p.discount_percentage, p.price_after_discount,
           p.image_path, p.date_added, p.sales_count, p.stock_quantity, p.description,
           p.manufacturer, GROUP_CONCAT(DISTINCT c.name) AS categories,
           COALESCE(p.is_visible, 1) AS is_visible
    FROM products p
    LEFT JOIN product_category_pivot pcp ON pcp.product_id = p.id
    LEFT JOIN categories c ON c.id = pcp.category_id
    GROUP BY p.id
    HAVING is_visible = 1
    ORDER BY p.sales_count DESC, p.id ASC
")->fetchAll();

// ── تحديد أعلى 7 بالمبيعات → best-seller ──────────────────────
$bestSellerIds = array_slice(
    array_column($products, 'id'),
    0,
    7
);

// ── تحديد أحدث 7 بتاريخ الإضافة → new (مستثنيًا Best Sellers) ──
$productsSortedByDate = $products;
usort($productsSortedByDate, function($a, $b) {
    return strtotime($b['date_added'] ?? '2000-01-01') - strtotime($a['date_added'] ?? '2000-01-01');
});
// نستثني المنتجات اللي أخذت تاغ best-seller حتى لا تختفي من قسم New
$newArrivalIds = array_slice(
    array_values(array_filter(
        array_column($productsSortedByDate, 'id'),
        fn($id) => !in_array($id, $bestSellerIds)
    )),
    0,
    7
);

// ── دالة تحديد التاغ ────────────────────────────────────────────
function getProductTag(array $p, array $bestSellerIds, array $newArrivalIds): string {
    if (in_array((int)$p['id'], $bestSellerIds)) return 'best-seller';
    if (in_array((int)$p['id'], $newArrivalIds)) return 'new';
    if ((int)$p['stock_quantity'] > 0 && (int)$p['stock_quantity'] <= 5) return 'limited';
    return 'regular';
}

$productsJS = array_values(array_map(function($p) use ($bestSellerIds, $newArrivalIds) {
    $finalPrice = (float)($p['discount_percentage'] > 0 ? $p['price_after_discount'] : $p['price']);
    return [
        'id'          => (int)$p['id'],
        'name'        => $p['name'],
        'price'       => $finalPrice,
        'image'       => $p['image_path'] ?? '',
        'image_path'  => $p['image_path'] ?? '',
        'description' => $p['description'] ?? '',
        'brand'       => $p['manufacturer'] ?? '',
        'tag'         => getProductTag($p, $bestSellerIds, $newArrivalIds),
        'discount_percentage' => (float)$p['discount_percentage'],
        'stock_quantity'      => (int)$p['stock_quantity'],
        'categories'  => $p['categories'] ?? '',
    ];
}, $products));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cairo Store | Home</title>
    <meta name="description" content="Cairo Store — Best Electronics Store with Premium Products and Fast Delivery">
    <meta name="robots" content="index, follow">
    <meta property="og:title"       content="Cairo Store | Home">
    <meta property="og:description" content="Best Electronics Store with Premium Products and Fast Delivery">
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="https://cairostore.com">
    <meta name="twitter:card"       content="summary_large_image">
    <meta name="twitter:title"      content="Cairo Store | Home">
    <meta name="twitter:description"content="Best Electronics Store with Premium Products and Fast Delivery">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
<?php include 'components/navbar.php'; ?>

<main id="main-content" role="main">

<!-- Slider -->
<section>
    <div id="mainSlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner" id="slider-inner"></div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- Shop By Category -->
<section class="container py-5">
    <h2 class="section-title">Shop By Category</h2>
    <div class="d-flex justify-content-center flex-wrap gap-3">
        <a href="/Task(1)/pages/products.php?cat=phone"       class="btn btn-outline-dark px-4 py-2">📱 Phones</a>
        <a href="/Task(1)/pages/products.php?cat=computer"    class="btn btn-outline-dark px-4 py-2">💻 Computers</a>
        <a href="/Task(1)/pages/products.php?cat=accessories" class="btn btn-outline-dark px-4 py-2">🎧 Accessories</a>
        <a href="/Task(1)/pages/products.php?cat=gaming"      class="btn btn-outline-dark px-4 py-2">🎮 Gaming</a>
    </div>
</section>

<!-- Best Sellers -->
<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Best Sellers</h2>
        <a href="/Task(1)/pages/products.php" class="section-view-all">View All →</a>
    </div>
    <div class="section-carousel-wrapper">
        <button class="section-carousel-btn prev-btn" data-target="best-sellers-track">&#8249;</button>
        <div class="section-carousel-track" id="best-sellers-track"></div>
        <button class="section-carousel-btn next-btn" data-target="best-sellers-track">&#8250;</button>
    </div>
</section>

<!-- New Arrivals -->
<section class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">New Arrivals</h2>
        <a href="/Task(1)/pages/products.php" class="section-view-all">View All →</a>
    </div>
    <div class="section-carousel-wrapper">
        <button class="section-carousel-btn prev-btn" data-target="new-arrivals-track">&#8249;</button>
        <div class="section-carousel-track" id="new-arrivals-track"></div>
        <button class="section-carousel-btn next-btn" data-target="new-arrivals-track">&#8250;</button>
    </div>
</section>

<!-- Explore More -->
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

</main>

<?php include 'components/footer.php'; ?>

<script>
// بيانات المنتجات من PHP → JS
window.dbProducts = <?= json_encode($productsJS, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

document.addEventListener('DOMContentLoaded', () => {
    if (window.dbProducts && window.dbProducts.length > 0) {
        // renderHomeSections تحتاج p.image لا p.image_path
        const prods = window.dbProducts.map(p => ({
            ...p,
            image: p.image_path || p.image || '',
        }));
        renderHomeSections(prods);
    }
});
</script>
</body>
</html>
