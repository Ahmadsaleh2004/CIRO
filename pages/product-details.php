<?php
/**
 * pages/product-details.php — المرحلة 9
 * يقرأ من MySQL + تقييمات + مخزون + Zoom + Notify Me
 */
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();
$pid = (int)($_GET['id'] ?? 0);
if (!$pid) { header('Location: /Task(1)/pages/products.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->execute([$pid]);
$p = $stmt->fetch();
if (!$p) { header('Location: /Task(1)/pages/404.php'); exit; }

if (isUser()) updateUserActivity();

// ── معالجة تقييم ─────────────────────────────────────────────
$reviewMsg = $reviewErr = '';
if (isUser() && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_review'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $uid     = getCurrentUserId();
    if (empty($rating) && empty($comment)) {
        $reviewErr = 'Please provide a rating or a comment.';
    } elseif (!empty($rating) && ($rating < 1 || $rating > 5)) {
        $reviewErr = 'Please select a rating from 1 to 5.';
    } else {
        $ex = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id=? AND user_id=? LIMIT 1");
        $ex->execute([$pid, $uid]);
        if ($ex->fetch()) {
            $pdo->prepare("UPDATE product_reviews SET rating=?,comment=? WHERE product_id=? AND user_id=?")
                ->execute([$rating ?: null, $comment?:null, $pid, $uid]);
            $reviewMsg = '✅ Your review has been updated.';
        } else {
            $pdo->prepare("INSERT INTO product_reviews (product_id,user_id,rating,comment) VALUES (?,?,?,?)")
                ->execute([$pid, $uid, $rating ?: null, $comment?:null]);
            $reviewMsg = '✅ Thank you! Your review has been added.';
        }
    }
}

// ── جلب التقييمات ────────────────────────────────────────────
$reviews = $pdo->prepare("
    SELECT pr.*, u.full_name FROM product_reviews pr
    JOIN users u ON u.id=pr.user_id
    WHERE pr.product_id=? ORDER BY pr.created_at DESC
");
$reviews->execute([$pid]);
$reviews   = $reviews->fetchAll();
$avgRating = count($reviews) ? round(array_sum(array_column($reviews,'rating'))/count($reviews),1) : 0;

$myReview = null;
if (isUser()) {
    $s = $pdo->prepare("SELECT * FROM product_reviews WHERE product_id=? AND user_id=? LIMIT 1");
    $s->execute([$pid, getCurrentUserId()]);
    $myReview = $s->fetch();
}

// ── المنتجات المشابهة ─────────────────────────────────────────
$related = $pdo->prepare("
    SELECT DISTINCT p.* FROM products p
    JOIN product_category_pivot pcp ON pcp.product_id=p.id
    WHERE pcp.category_id IN (
        SELECT category_id FROM product_category_pivot WHERE product_id=?
    ) AND p.id!=? LIMIT 4
");
$related->execute([$pid, $pid]);
$related = $related->fetchAll();
if (empty($related)) {
    $r2 = $pdo->prepare("SELECT * FROM products WHERE manufacturer=? AND id!=? LIMIT 4");
    $r2->execute([$p['manufacturer'], $pid]);
    $related = $r2->fetchAll();
}

$price      = (float)$p['price'];
$discount   = (float)$p['discount_percentage'];
$afterDisc  = (float)$p['price_after_discount'];
$finalPrice = $discount > 0 ? $afterDisc : $price;
$stock      = (int)$p['stock_quantity'];
$imgSrc     = htmlspecialchars($p['image_path'] ?: '');
$csrf       = generateCsrfToken();
$notified   = !empty($_GET['notified']);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['name']) ?> | Cairo Store</title>
    <meta name="description" content="<?= htmlspecialchars(substr($p['description']??'',0,155)) ?>">
    <meta property="og:title"       content="<?= htmlspecialchars($p['name']) ?> | Cairo Store">
    <meta property="og:description" content="<?= htmlspecialchars(substr($p['description']??'',0,155)) ?>">
    <meta property="og:image"       content="<?= $imgSrc ?>">
    <meta property="og:type"        content="product">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
    <style>
        .zoom-wrapper{overflow:hidden;border-radius:14px;cursor:zoom-in;background:var(--bg-color);}
        .zoom-wrapper img{transition:transform .4s ease;width:100%;display:block;}
        .zoom-wrapper:hover img{transform:scale(1.35);}
        @media(hover:none){.zoom-wrapper:hover img{transform:none;}}
        .star-span{font-size:1.4rem;cursor:pointer;transition:transform .15s;color:#d1d5db;}
        .star-span.active,.star-span:hover{color:#f59e0b;transform:scale(1.2);}
        .review-card{border:1px solid var(--section-border);border-radius:10px;padding:14px;margin-bottom:12px;}
        @keyframes cartBounce{0%,100%{transform:scale(1)}30%{transform:scale(1.3)}60%{transform:scale(.9)}}
        .cart-bounce{animation:cartBounce .5s cubic-bezier(.36,.07,.19,.97)}
    </style>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
<?php include '../components/navbar.php'; ?>

<main id="main-content" class="container py-5">

    <nav class="store-breadcrumb mb-4">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <a href="/Task(1)/pages/products.php">Products</a>
        <span class="sep">/</span>
        <span class="current"><?= htmlspecialchars($p['name']) ?></span>
    </nav>

    <!-- ── Product Detail ─────────────────────────────── -->
    <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
            <div class="zoom-wrapper position-relative">
                <?php if ($discount > 0): ?>
                <span class="discount-badge" style="z-index:5;">-<?= $discount ?>%</span>
                <?php endif; ?>
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['name']) ?>" id="productMainImg">
            </div>
        </div>
        <div class="col-lg-6">
            <h1 class="fw-bold mb-2"><?= htmlspecialchars($p['name']) ?></h1>

            <!-- Rating -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <span style="color:#f59e0b;font-size:1.1rem;">
                    <?php for($i=1;$i<=5;$i++) echo $i<=$avgRating?'★':'☆'; ?>
                </span>
                <small style="color:var(--placeholder-color);">
                    <?= number_format($avgRating,1) ?> (<?= count($reviews) ?> reviews)
                </small>
            </div>

            <!-- Price -->
            <div class="price-box mb-3">
                <span class="new-price">$<?= number_format($finalPrice,2) ?></span>
                <?php if ($discount > 0): ?>
                <span class="old-price ms-2">$<?= number_format($price,2) ?></span>
                <?php endif; ?>
            </div>

            <p class="product-description mb-4"><?= htmlspecialchars($p['description']??'') ?></p>

            <!-- Specs -->
            <div class="product-specs mb-4 p-3 rounded">
                <div class="row g-2">
                    <?php if ($p['manufacturer']): ?>
                    <div class="col-sm-6">
                        <span class="spec-label">🏷️ Brand:</span>
                        <span class="spec-value"> <?= htmlspecialchars($p['manufacturer']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($p['country_of_origin']): ?>
                    <div class="col-sm-6">
                        <span class="spec-label">🌍 Origin:</span>
                        <span class="spec-value"> <?= htmlspecialchars($p['country_of_origin']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($p['date_added']): ?>
                    <div class="col-sm-12 mt-1">
                        <span class="spec-label">📅 Date Added:</span>
                        <span class="spec-value"> <?= date('d M Y', strtotime($p['date_added'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stock badge -->
            <?php if ($stock === 0): ?>
            <div class="mb-3"><span class="badge bg-danger fs-6">Out of Stock</span></div>
            <?php elseif ($stock <= 5): ?>
            <div class="mb-3"><span class="badge bg-warning text-dark fs-6">⚠️ Only <?= $stock ?> left!</span></div>
            <?php endif; ?>

            <?php if ($stock > 0): ?>
            <!-- Qty + Cart -->
            <div class="quantity-box mb-4">
                <button class="btn btn-outline-secondary" id="minusBtn">−</button>
                <input type="number" value="1" min="1" max="<?= $stock ?>"
                       id="productQty" class="form-control quantity-input" style="width:70px;">
                <button class="btn btn-outline-secondary" id="plusBtn">+</button>
            </div>
            <div class="d-flex gap-2">
                <?php if (isUser()): ?>
                <button id="addCartBtn" class="btn btn-success btn-lg px-5">🛒 Add To Cart</button>
                <?php else: ?>
                <button id="addCartBtn"
                        class="btn btn-success btn-lg px-5 btn-disabled-faded"
                        disabled
                        data-bs-toggle="modal" data-bs-target="#loginModal"
                        onclick="this.removeAttribute('disabled')">
                    🛒 Add To Cart
                </button>
                <?php endif; ?>
                <button id="wishBtn" class="btn btn-outline-danger btn-lg">🤍</button>
            </div>

            <?php else: ?>
            <!-- Notify Me -->
            <?php if ($notified): ?>
            <div class="alert alert-success py-2">✅ We'll notify you when this product is back in stock!</div>
            <?php elseif (isUser()): ?>
            <form method="POST" action="/Task(1)/handlers/notify_handler.php">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <button class="btn btn-outline-warning btn-lg">🔔 Notify Me When Available</button>
            </form>
            <?php else: ?>
            <button class="btn btn-outline-warning btn-lg"
                    data-bs-toggle="modal" data-bs-target="#loginModal">
                🔔 Notify Me (Login Required)
            </button>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <hr class="my-5">

    <!-- ── Reviews ────────────────────────────────────── -->
    <h2 class="section-title">⭐ Reviews & Ratings</h2>

    <?php if (isUser()): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3"><?= $myReview ? '✏️ Edit Your Review' : '+ Add Your Review' ?></h5>
        <?php if ($reviewMsg): ?><div class="alert alert-success py-2"><?= htmlspecialchars($reviewMsg) ?></div><?php endif; ?>
        <?php if ($reviewErr): ?><div class="alert alert-danger  py-2"><?= htmlspecialchars($reviewErr) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="submit_review" value="1">
            <input type="hidden" name="csrf_token"    value="<?= htmlspecialchars($csrf) ?>">
            <div class="mb-3">
                <label class="fw-bold mb-2">Rating <span class="text-danger">*</span></label>
                <div id="starWidget">
                    <?php for ($i=1;$i<=5;$i++): ?>
                    <span class="star-span <?= ($myReview && $myReview['rating']>=$i)?'active':'' ?>"
                          data-val="<?= $i ?>">
                        <?= ($myReview && $myReview['rating']>=$i)?'★':'☆' ?>
                    </span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?= $myReview['rating']??0 ?>">
            </div>
            <div class="float-group">
                <textarea name="comment" rows="3" placeholder=" "><?= htmlspecialchars($myReview['comment']??'') ?></textarea>
                <label>Comment (optional)</label>
            </div>
            <button id="reviewSubmitBtn" type="submit" class="btn btn-success btn-disabled-faded" disabled aria-disabled="true">Submit Review</button>
        </form>
    </div>
    <?php elseif (!isAdmin() || !empty($_SESSION['admin_in_store_mode'])): ?>
    <div class="alert alert-info py-2 mb-4">
        <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a> to leave a review.
    </div>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
    <p style="color:var(--placeholder-color);">No reviews yet. Be the first!</p>
    <?php else: ?>
    <?php foreach ($reviews as $rv): ?>
    <div class="review-card">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <strong><?= htmlspecialchars($rv['full_name']) ?></strong>
            <small style="color:var(--placeholder-color);"><?= date('d M Y', strtotime($rv['created_at'])) ?></small>
        </div>
        <div style="color:#f59e0b;">
            <?php for($i=1;$i<=5;$i++) echo $i<=$rv['rating']?'★':'☆'; ?>
        </div>
        <?php if ($rv['comment']): ?>
        <p class="small mb-0 mt-1"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <hr class="my-5">

    <!-- ── Related ────────────────────────────────────── -->
    <h2 class="section-title">You May Also Like</h2>
    <div class="row">
        <?php foreach ($related as $r):
            $rPrice = (float)($r['discount_percentage']>0 ? $r['price_after_discount'] : $r['price']);
        ?>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="/Task(1)/pages/product-details.php?id=<?= $r['id'] ?>"
               class="image-only-product reveal">
                <img src="<?= htmlspecialchars($r['image_path']??'') ?>"
                     class="img-fill" alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy">
                <div class="price-overlay">$<?= number_format($rPrice,2) ?></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

</main>

<?php include '../components/footer.php'; ?>

<script>
// ── Star Widget ─────────────────────────────────────────────
const stars      = document.querySelectorAll('.star-span');
const ratingInpt = document.getElementById('ratingInput');
const reviewBtn  = document.getElementById('reviewSubmitBtn');
const commentTxt = document.querySelector('textarea[name="comment"]');

function checkReviewValidity() {
    const hasRating  = ratingInpt && parseInt(ratingInpt.value) >= 1;
    const hasComment = commentTxt && commentTxt.value.trim().length > 0;
    if (reviewBtn && typeof updateButtonState === 'function') {
        updateButtonState(reviewBtn, hasRating || hasComment);
    }
}

if (stars.length) {
    stars.forEach(s => {
        s.addEventListener('mouseover', () => {
            const v = parseInt(s.dataset.val);
            stars.forEach((st,i) => { st.textContent=i<v?'★':'☆'; st.style.color=i<v?'#f59e0b':'#d1d5db'; });
        });
        s.addEventListener('click', () => {
            const v = parseInt(s.dataset.val);
            if (ratingInpt) ratingInpt.value = v;
            stars.forEach((st,i) => { st.classList.toggle('active',i<v); st.textContent=i<v?'★':'☆'; });
            checkReviewValidity();
        });
    });
    document.getElementById('starWidget')?.addEventListener('mouseleave', () => {
        const cur = parseInt(ratingInpt?.value||0);
        stars.forEach((st,i) => { st.textContent=i<cur?'★':'☆'; st.style.color=i<cur?'#f59e0b':'#d1d5db'; });
    });
}
if (commentTxt) commentTxt.addEventListener('input', checkReviewValidity);
// تشغيل أولي (عند تعديل التقييم)
checkReviewValidity();

// ── Qty & Cart ───────────────────────────────────────────────
const qty   = document.getElementById('productQty');
const plus  = document.getElementById('plusBtn');
const minus = document.getElementById('minusBtn');
const stock = <?= $stock ?>;

if (plus)  plus.onclick  = () => { const v=parseInt(qty.value); if(v<stock) qty.value=v+1; };
if (minus) minus.onclick = () => { const v=parseInt(qty.value); if(v>1)     qty.value=v-1; };

document.getElementById('addCartBtn')?.addEventListener('click', () => {
    const q = parseInt(qty.value);
    if (q > stock) { if(typeof showToast==='function') showToast('Not enough stock!','error'); return; }
    const product = { id:<?= $pid ?>, name:<?= json_encode($p['name']) ?>,
                      price:<?= $finalPrice ?>, image_path:<?= json_encode($p['image_path']) ?> };
    let cart = JSON.parse(localStorage.getItem('cart')||'[]');
    const ex = cart.find(i=>i.id==product.id);
    if (ex) ex.quantity+=q; else cart.push({...product,quantity:q});
    localStorage.setItem('cart',JSON.stringify(cart));
    if (typeof refreshCartUI==='function') refreshCartUI();
    if (typeof showToast==='function')     showToast('Added to cart!','success');
    qty.value = 1;
    const cb = document.querySelector('[data-bs-target="#cartSidebar"]');
    if (cb) { cb.classList.add('cart-bounce'); setTimeout(()=>cb.classList.remove('cart-bounce'),500); }
});

// ── Wishlist ─────────────────────────────────────────────────
const wishBtn = document.getElementById('wishBtn');
if (wishBtn) {
    const prod = { id:<?= $pid ?>, name:<?= json_encode($p['name']) ?>,
                   price:<?= $finalPrice ?>, image_path:<?= json_encode($p['image_path']) ?> };
    let wl = JSON.parse(localStorage.getItem('wishlist')||'[]');
    if (wl.some(i=>i.id==prod.id)) wishBtn.innerHTML='❤️';
    wishBtn.onclick = () => {
        wl = JSON.parse(localStorage.getItem('wishlist')||'[]');
        const idx = wl.findIndex(i=>i.id==prod.id);
        if (idx>-1) { wl.splice(idx,1); wishBtn.innerHTML='🤍'; }
        else         { wl.push(prod);   wishBtn.innerHTML='❤️'; }
        localStorage.setItem('wishlist',JSON.stringify(wl));
        if (typeof updateCounters==='function') updateCounters();
    };
}
if (typeof window.initScrollReveal==='function') requestAnimationFrame(window.initScrollReveal);
</script>
</body>
</html>
