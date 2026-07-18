<?php
/**
 * pages/products.php — المرحلة 9
 * يقرأ المنتجات من MySQL + إدارة الأدمن + Autocomplete + Price Slider
 */
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../config/db.php';

$pdo  = getDB();
$msg  = '';
$isAdminProd = isAdmin() && hasPermission('can_manage_products') && empty($_SESSION['admin_in_store_mode']);

// ── حذف منتج (الأدمن فقط) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product']) && $isAdminProd) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid) {
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$pid]);
        logAdminAction(getCurrentAdminId(), 'delete_product', 'product', $pid);
        $msg = '✅ Product deleted successfully.';
    }
}

// ── Pagination ───────────────────────────────────────────────
define('PRODUCTS_PER_PAGE', 24);
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * PRODUCTS_PER_PAGE;

// عدد المنتجات الكلي
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPages    = max(1, (int)ceil($totalProducts / PRODUCTS_PER_PAGE));
$currentPage   = min($currentPage, $totalPages);
$offset        = ($currentPage - 1) * PRODUCTS_PER_PAGE;

// ── جلب المنتجات من DB (مع LIMIT/OFFSET) ────────────────────
$stmt = $pdo->prepare("
    SELECT p.*, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name) AS categories,
           COALESCE(p.is_visible, 1) AS is_visible
    FROM products p
    LEFT JOIN product_category_pivot pcp ON pcp.product_id = p.id
    LEFT JOIN categories c ON c.id = pcp.category_id
    GROUP BY p.id
    HAVING is_visible = 1
    ORDER BY p.date_added DESC, p.id DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([PRODUCTS_PER_PAGE, $offset]);
$products = $stmt->fetchAll();

$csrf = generateCsrfToken();

// ── تحديد الـ tag من sales_count + date_added ────────────────
function getTag(array $p): string {
    if ($p['sales_count'] >= 5)  return 'best-seller';
    $days = (time() - strtotime($p['date_added'] ?? 'now')) / 86400;
    if ($days <= 60)             return 'new';
    if ($p['stock_quantity'] > 0 && $p['stock_quantity'] <= 5) return 'limited';
    return 'regular';
}
?>
<?php
$pageTitle = 'Products';
$pageDescription = 'Browse all products at Cairo Store.';
$extraHead = '';
if ($isAdminProd) {
    $extraHead .= '<link rel="stylesheet" href="/Task(1)/css/admin.css">' . "\n";
}
$extraHead .= '
<style>
    /* ── Price Range Slider ── */
    #priceRange { accent-color: var(--accent); }
    /* ── Autocomplete ── */
    #autocomplete-list { position:absolute; top:100%; left:0; right:0; z-index:999;
        background:var(--card-bg); border:1px solid var(--section-border);
        border-radius:0 0 8px 8px; box-shadow:0 8px 24px var(--shadow-hover);
        max-height:220px; overflow-y:auto; display:none; }
    #autocomplete-list li { padding:9px 14px; cursor:pointer; color:var(--text-color);
        font-size:.88rem; border-bottom:1px solid var(--section-border); list-style:none; }
    #autocomplete-list li:hover { background:rgba(99,102,241,.1); }
    #search-wrapper { position:relative; }
    /* ── Cart bounce ── */
    @keyframes cartBounce{0%,100%{transform:scale(1)}30%{transform:scale(1.3)}60%{transform:scale(.9)}}
    .cart-bounce{animation:cartBounce .5s cubic-bezier(.36,.07,.19,.97)}
</style>';
require_once __DIR__ . '/../components/header.php';
?>
<?php include '../components/navbar.php'; ?>

<main id="main-content" role="main">
<section class="container py-5">

    <nav class="store-breadcrumb mb-3">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <span class="current">Products</span>
    </nav>

    <h1 class="section-title">Our Products</h1>

    <?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- ── Filters ───────────────────────────────────────── -->
    <div class="row mb-4 g-3">
        <div class="col-lg-4">
            <div id="search-wrapper">
                <input type="text" id="search" class="form-control" placeholder="Search products...">
                <ul id="autocomplete-list"></ul>
            </div>
        </div>
        <div class="col-lg-3">
            <select id="sort" class="form-select">
                <option value="">Sort Products</option>
                <optgroup label="By Name">
                    <option value="az">Name A-Z</option>
                    <option value="za">Name Z-A</option>
                </optgroup>
                <optgroup label="By Price">
                    <option value="low">Price Low → High</option>
                    <option value="high">Price High → Low</option>
                </optgroup>
                <optgroup label="By Category">
                    <option value="cat-phone">📱 Phones</option>
                    <option value="cat-computer">💻 Computers</option>
                    <option value="cat-accessories">🎧 Accessories</option>
                    <option value="cat-gaming">🎮 Gaming</option>
                </optgroup>
                <optgroup label="By Price Range">
                    <option value="price-u100">Under $100</option>
                    <option value="price-u300">Under $300</option>
                    <option value="price-u500">Under $500</option>
                    <option value="price-o500">$500 &amp; Above</option>
                </optgroup>
            </select>
        </div>
        <div class="col-lg-3 d-flex align-items-center gap-2">
            <input type="range" id="priceRange" min="0" max="2000" value="2000" class="form-range">
            <span id="priceRangeVal" class="small fw-bold" style="white-space:nowrap;color:var(--accent);">≤$2000</span>
        </div>
        <div class="col-lg-2">
            <button id="reset" class="btn btn-secondary w-100">Reset</button>
        </div>
    </div>

    <div id="results-count" class="mb-3" style="color:var(--placeholder-color);font-size:.85rem;"></div>

    <!-- ── Products Grid ──────────────────────────────── -->
    <div class="row" id="products-container">

        <?php foreach ($products as $p):
            $price     = (float)$p['price'];
            $discount  = (float)$p['discount_percentage'];
            $afterDisc = (float)$p['price_after_discount'];
            $finalPrice = $discount > 0 ? $afterDisc : $price;
            $stock     = (int)$p['stock_quantity'];
            $imgSrc    = htmlspecialchars($p['image_path'] ?: '');
            $tag       = getTag($p);
            $cats      = strtolower($p['categories'] ?? '');
        ?>
        <div class="col-lg-4 col-md-6 mb-4 product-item reveal"
             data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
             data-price="<?= $finalPrice ?>"
             data-cats="<?= htmlspecialchars($cats) ?>">
            <div class="card product-card h-100 shadow border-0 position-relative" role="article">

                <?php if ($discount > 0): ?>
                <span class="discount-badge">-<?= $discount ?>%</span>
                <?php endif; ?>

                <?php if ($isAdminProd): ?>
                <!-- زر حذف للأدمن -->
                <form method="POST" style="position:absolute;top:8px;right:8px;z-index:20;">
                    <input type="hidden" name="delete_product" value="1">
                    <input type="hidden" name="product_id"    value="<?= $p['id'] ?>">
                    <input type="hidden" name="csrf_token"    value="<?= htmlspecialchars($csrf) ?>">
                    <button type="submit" class="delete-product-btn"
                        onclick="return confirm('Delete «<?= htmlspecialchars(addslashes($p['name'])) ?>»?')"
                        title="Delete">✕</button>
                </form>
                <?php else: ?>
                <!-- Wishlist للمستخدم -->
                <button class="favorite-btn" aria-label="Add to wishlist"
                    data-pid="<?= $p['id'] ?>"
                    data-product='<?= htmlspecialchars(json_encode([
                        'id'         => (int)$p['id'],
                        'name'       => $p['name'],
                        'price'      => $finalPrice,
                        'image_path' => $p['image_path'],
                        'image'      => $p['image_path'],
                    ])) ?>'>🤍</button>
                <?php endif; ?>

                <!-- صورة المنتج -->
                <a href="<?= $isAdminProd
                    ? '/Task(1)/admin/manage-product.php?id='.$p['id']
                    : '/Task(1)/pages/product-details.php?id='.$p['id'] ?>"
                   style="text-decoration:none;">
                    <img src="<?= $imgSrc ?>"
                         class="card-img-top product-image"
                         alt="<?= htmlspecialchars($p['name']) ?>"
                         loading="lazy">
                </a>

                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="mb-2">
                        <h5 class="fw-bold"><?= htmlspecialchars($p['name']) ?></h5>

                        <!-- مؤشر المخزون -->
                        <?php if ($stock === 0): ?>
                        <span class="badge bg-danger mb-1">Out of Stock</span>
                        <?php elseif ($stock <= 5): ?>
                        <span class="badge bg-warning text-dark mb-1">Limited (<?= $stock ?> left)</span>
                        <?php endif; ?>

                        <div class="price-box mt-1">
                            <span class="new-price fs-5 fw-bold">$<?= number_format($finalPrice,2) ?></span>
                            <?php if ($discount > 0): ?>
                            <span class="old-price ms-1">$<?= number_format($price,2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$isAdminProd): ?>
                    <div>
                        <div class="quantity-box mb-2 d-flex justify-content-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm"
                                    onclick="changeQtyDB('<?= $p['id'] ?>',-1)">−</button>
                            <input type="number" value="1" id="qty-<?= $p['id'] ?>"
                                   class="form-control quantity-input"
                                   style="width:60px;" min="1" max="<?= $stock ?>">
                            <button class="btn btn-outline-secondary btn-sm"
                                    onclick="changeQtyDB('<?= $p['id'] ?>',1)">+</button>
                        </div>
                        <?php if ($stock > 0): ?>
                        <?php if (isUser()): ?>
                        <button class="btn btn-success w-100"
                                onclick="addToCartDB(<?= $p['id'] ?>, <?= $finalPrice ?>, <?= $stock ?>)">
                            🛒 Add to Cart
                        </button>
                        <?php else: ?>
                        <button class="btn btn-success w-100 btn-disabled-faded"
                                disabled
                                data-bs-toggle="modal" data-bs-target="#loginModal"
                                onclick="this.removeAttribute('disabled')">
                            🛒 Add to Cart
                        </button>
                        <?php endif; ?>
                        <?php else: ?>
                        <?php if (isUser()): ?>
                        <form method="POST" action="/Task(1)/handlers/notify_handler.php">
                            <input type="hidden" name="product_id"  value="<?= $p['id'] ?>">
                            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
                            <button class="btn btn-outline-warning w-100">🔔 Notify Me</button>
                        </form>
                        <?php else: ?>
                        <button class="btn btn-outline-warning w-100"
                            data-bs-toggle="modal" data-bs-target="#loginModal">
                            🔔 Notify Me (Login)
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- بطاقة + للأدمن -->
        <?php if ($isAdminProd): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <a href="/Task(1)/admin/add-product.php" class="add-product-card" title="Add Product">+</a>
        </div>
        <?php endif; ?>

    </div>

    <?php if ($totalPages > 1): ?>
    <!-- ── Pagination ──────────────────────────────────── -->
    <nav aria-label="Products pagination" class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
            <?php
            // نحافظ على باقي query params
            $baseQuery = array_diff_key($_GET, ['page' => '']);
            $buildUrl  = fn(int $p) => '?' . http_build_query(array_merge($baseQuery, ['page' => $p]));
            ?>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($buildUrl($currentPage - 1)) ?>">‹ Prev</a>
            </li>
            <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($buildUrl($p)) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($buildUrl($currentPage + 1)) ?>">Next ›</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</section>
</main>

<?php include '../components/footer.php'; ?>

<script>
// ── بيانات المنتجات لـ JS ────────────────────────────────────
window.dbProducts = <?= json_encode(array_values(array_map(function($p) {
    return [
        'id'         => (int)$p['id'],
        'name'       => $p['name'],
        'price'      => (float)($p['discount_percentage']>0 ? $p['price_after_discount'] : $p['price']),
        'image'      => $p['image_path'],
        'image_path' => $p['image_path'],
        'tag'        => getTag($p),
        'categories' => $p['categories'] ?? '',
    ];
}, $products)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

// ── Wishlist buttons ─────────────────────────────────────────
document.querySelectorAll('.favorite-btn[data-product]').forEach(btn => {
    const p  = JSON.parse(btn.dataset.product);
    const wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
    if (wl.some(i => i.id == p.id)) btn.innerHTML = '❤️';
    btn.addEventListener('click', () => {
        let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        const idx = wishlist.findIndex(i => i.id == p.id);
        if (idx > -1) { wishlist.splice(idx, 1); btn.innerHTML = '🤍'; }
        else           { wishlist.push(p);         btn.innerHTML = '❤️'; }
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        if (typeof updateCounters === 'function') updateCounters();
    });
});

// ── Cart helpers ─────────────────────────────────────────────
window.changeQtyDB = (id, val) => {
    const input = document.getElementById('qty-' + id);
    if (!input) return;
    const v = parseInt(input.value) + val;
    if (v >= 1) input.value = v;
};

window.addToCartDB = (id, price, stock) => {
    const input = document.getElementById('qty-' + id);
    const qty   = parseInt(input?.value || 1);
    if (qty > stock) { if (typeof showToast==='function') showToast('Not enough stock!','error'); return; }
    const p = window.dbProducts?.find(x => x.id == id);
    if (!p) return;
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const ex = cart.find(i => i.id == id);
    if (ex) ex.quantity += qty;
    else cart.push({ id, name: p.name, price, image_path: p.image_path, quantity: qty });
    localStorage.setItem('cart', JSON.stringify(cart));
    if (typeof refreshCartUI === 'function') refreshCartUI();
    if (typeof showToast    === 'function') showToast('Added to cart!', 'success');
    if (input) input.value = 1;
    const cb = document.querySelector('[data-bs-target="#cartSidebar"]');
    if (cb) { cb.classList.add('cart-bounce'); setTimeout(() => cb.classList.remove('cart-bounce'), 500); }
};

// ── Filters ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const searchEl = document.getElementById('search');
    const sortEl   = document.getElementById('sort');
    const slider   = document.getElementById('priceRange');
    const sliderLbl= document.getElementById('priceRangeVal');
    const resetBtn = document.getElementById('reset');
    const countEl  = document.getElementById('results-count');
    const items    = document.querySelectorAll('.product-item');

    function applyFilters() {
        const q        = searchEl?.value.toLowerCase().trim() || '';
        const sort     = sortEl?.value || '';
        const maxPrice = parseInt(slider?.value || 9999);
        if (sliderLbl) sliderLbl.textContent = '≤$' + maxPrice;

        let visible = [];
        items.forEach(item => {
            let show = true;
            if (q      && !item.dataset.name.includes(q))      show = false;
            if (parseFloat(item.dataset.price) > maxPrice)     show = false;
            if (sort.startsWith('cat-')) {
                const cat = sort.replace('cat-', '');
                if (!item.dataset.cats.includes(cat))          show = false;
            }
            if (sort === 'price-u100' && parseFloat(item.dataset.price) >= 100) show = false;
            if (sort === 'price-u300' && parseFloat(item.dataset.price) >= 300) show = false;
            if (sort === 'price-u500' && parseFloat(item.dataset.price) >= 500) show = false;
            if (sort === 'price-o500' && parseFloat(item.dataset.price) <  500) show = false;
            item.style.display = show ? '' : 'none';
            if (show) visible.push(item);
        });

        const container = document.getElementById('products-container');
        if (sort === 'az' || sort === 'za') {
            visible.sort((a,b) => sort==='az'
                ? a.dataset.name.localeCompare(b.dataset.name)
                : b.dataset.name.localeCompare(a.dataset.name));
            visible.forEach(el => container.appendChild(el));
        }
        if (sort === 'low' || sort === 'high') {
            visible.sort((a,b) => sort==='low'
                ? parseFloat(a.dataset.price)-parseFloat(b.dataset.price)
                : parseFloat(b.dataset.price)-parseFloat(a.dataset.price));
            visible.forEach(el => container.appendChild(el));
        }
        if (countEl) countEl.textContent = `Showing ${visible.length} of ${items.length} products`;
        if (typeof window.initScrollReveal==='function') window.initScrollReveal();
    }

    searchEl?.addEventListener('input',  applyFilters);
    sortEl?.addEventListener('change',   applyFilters);
    slider?.addEventListener('input',    applyFilters);
    resetBtn?.addEventListener('click', () => {
        if (searchEl) searchEl.value = '';
        if (sortEl)   sortEl.value   = '';
        if (slider) { slider.value = 2000; if (sliderLbl) sliderLbl.textContent='≤$2000'; }
        items.forEach(el => el.style.display = '');
        if (countEl) countEl.textContent = `Showing ${items.length} products`;
    });
    if (countEl) countEl.textContent = `Showing ${items.length} products`;
    if (typeof window.initScrollReveal==='function') window.initScrollReveal();

    // ── Autocomplete ─────────────────────────────────────────
    const acList = document.getElementById('autocomplete-list');
    if (searchEl && acList && window.dbProducts) {
        searchEl.addEventListener('input', () => {
            const q = searchEl.value.toLowerCase().trim();
            acList.innerHTML = '';
            if (!q) { acList.style.display='none'; return; }
            const hits = window.dbProducts.filter(p => p.name.toLowerCase().includes(q)).slice(0,5);
            if (!hits.length) { acList.style.display='none'; return; }
            hits.forEach(p => {
                const li = document.createElement('li');
                li.textContent = p.name;
                li.addEventListener('click', () => {
                    window.location.href = '/Task(1)/pages/product-details.php?id=' + p.id;
                });
                acList.appendChild(li);
            });
            acList.style.display = 'block';
        });
        document.addEventListener('click', e => {
            if (!searchEl.contains(e.target)) acList.style.display = 'none';
        });
    }

    // ── URL ?cat= filter ─────────────────────────────────────
    const cat = new URLSearchParams(window.location.search).get('cat');
    if (cat && sortEl) {
        const opt = sortEl.querySelector(`option[value="cat-${cat}"]`);
        if (opt) { sortEl.value = `cat-${cat}`; applyFilters(); }
    }
});
</script>
</body>
</html>
