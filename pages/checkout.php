<?php
/**
 * pages/checkout.php — المرحلة 11
 * 3 خطوات: عنوان → دفع → مراجعة + تأكيد
 */
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../config/db.php';

requireUser();

$pdo    = getDB();
$userId = getCurrentUserId();
updateUserActivity();

$addresses = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
$addresses->execute([$userId]);
$addresses = $addresses->fetchAll();

$ws           = $pdo->query("SELECT return_policy,terms_and_conditions FROM website_settings LIMIT 1")->fetch() ?: [];
$returnPolicy = $ws['return_policy'] ?? '14-day return policy.';
$csrf         = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Cairo Store</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
    <style>
        .step-bar { display:flex; justify-content:center; gap:0; margin-bottom:2rem; }
        .step-item { display:flex; flex-direction:column; align-items:center; gap:5px; flex:1; max-width:150px; position:relative; }
        .step-circle { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-weight:700; border:2px solid var(--section-border); background:var(--card-bg);
            color:var(--text-color); transition:.3s; }
        .step-item.active .step-circle  { background:var(--accent); border-color:var(--accent); color:#fff; }
        .step-item.done   .step-circle  { background:#16a34a; border-color:#16a34a; color:#fff; }
        .step-label { font-size:.72rem; color:var(--placeholder-color); }
        .step-item.active .step-label { color:var(--accent); font-weight:600; }
        .step-item:not(:last-child)::after { content:''; position:absolute; top:19px; left:calc(50% + 19px);
            width:calc(100% - 38px); height:2px; background:var(--section-border); }
        .step-item.done:not(:last-child)::after { background:#16a34a; }
        .checkout-step { display:none; }
        .checkout-step.active { display:block; }
        .trust-badges { display:flex; flex-wrap:wrap; gap:12px; justify-content:center; margin-top:14px; }
        .trust-badge  { display:flex; align-items:center; gap:5px; font-size:.78rem; color:var(--placeholder-color); }
        .addr-radio:checked + .addr-label { border-color:var(--accent)!important; }
        .addr-label { border:2px solid var(--section-border)!important; border-radius:10px; padding:12px 14px;
            cursor:pointer; display:block; transition:.2s; }
        .addr-label:hover { border-color:var(--accent)!important; }
    </style>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
<?php include '../components/navbar.php'; ?>

<main id="main-content" class="container py-5">
    <h1 class="text-center fw-bold mb-4">🛒 Checkout</h1>

    <!-- Step Bar -->
    <div class="step-bar">
        <div class="step-item active" id="si-1"><div class="step-circle">1</div><div class="step-label">Address</div></div>
        <div class="step-item"        id="si-2"><div class="step-circle">2</div><div class="step-label">Payment</div></div>
        <div class="step-item"        id="si-3"><div class="step-circle">3</div><div class="step-label">Review</div></div>
    </div>

    <div class="row justify-content-center">
    <div class="col-lg-8">

        <!-- ── Step 1: Address ────────────────────── -->
        <div class="checkout-step active" id="step-1">
        <div class="card p-4">
            <h4 class="mb-4">📍 Delivery Address</h4>

            <?php if (!empty($addresses)): ?>
            <div class="row g-2 mb-3">
                <?php foreach ($addresses as $addr): ?>
                <div class="col-md-6">
                    <input type="radio" name="saved_addr_ui" id="addr-<?= $addr['id'] ?>"
                           value="<?= $addr['id'] ?>" class="addr-radio d-none"
                           <?= $addr['is_default'] ? 'checked' : '' ?>>
                    <label for="addr-<?= $addr['id'] ?>" class="addr-label card">
                        <strong><?= htmlspecialchars($addr['label']) ?></strong>
                        <?php if ($addr['is_default']): ?>
                            <span class="badge bg-success ms-1 small">Default</span>
                        <?php endif; ?>
                        <p class="small mb-0 mt-1"><?= htmlspecialchars($addr['full_address']) ?></p>
                        <small style="color:var(--placeholder-color);">
                            <?= htmlspecialchars(trim(($addr['city']?$addr['city'].', ':'').($addr['country']??''))) ?>
                        </small>
                    </label>
                </div>
                <?php endforeach; ?>
                <div class="col-md-6">
                    <input type="radio" name="saved_addr_ui" id="addr-0" value="0" class="addr-radio d-none">
                    <label for="addr-0" class="addr-label card" style="height:100%;display:flex;align-items:center;justify-content:center;min-height:80px;">
                        + New Address
                    </label>
                </div>
            </div>
            <?php endif; ?>

            <!-- Manual address -->
            <div id="manualAddr" <?= !empty($addresses) ? 'style="display:none;"' : '' ?>>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="float-group"><input type="text" id="ma_country" placeholder=" "><label>Country</label></div>
                    </div>
                    <div class="col-md-6">
                        <div class="float-group"><input type="text" id="ma_city"    placeholder=" "><label>City</label></div>
                    </div>
                    <div class="col-12">
                        <div class="float-group"><textarea id="ma_full" rows="2" placeholder=" "></textarea><label>Full Address</label></div>
                    </div>
                    <div class="col-md-6">
                        <div class="float-group"><input type="tel" id="ma_phone" placeholder=" "><label>Phone</label></div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="saveNewAddr">
                            <label class="form-check-label small" for="saveNewAddr">Save to my profile</label>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-success mt-4 px-5" onclick="goStep(2)">Next: Payment →</button>
        </div>
        </div>

        <!-- ── Step 2: Payment ────────────────────── -->
        <div class="checkout-step" id="step-2">
        <div class="card p-4">
            <h4 class="mb-4">💳 Payment Method</h4>
            <div class="d-flex flex-column gap-3">
                <label class="card p-3" style="border:2px solid var(--accent)!important;cursor:pointer;">
                    <input type="radio" name="payment_method" value="cash_on_delivery" checked class="me-2">
                    💵 <strong>Cash on Delivery</strong>
                    <span class="badge bg-success ms-2">Available</span>
                </label>
                <label class="card p-3" style="opacity:.5;cursor:not-allowed;">
                    <input type="radio" disabled class="me-2">
                    💳 Credit / Debit Card
                    <span class="badge bg-secondary ms-2">Coming Soon</span>
                </label>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-outline-secondary" onclick="goStep(1)">← Back</button>
                <button class="btn btn-success px-5"      onclick="goStep(3)">Next: Review →</button>
            </div>
        </div>
        </div>

        <!-- ── Step 3: Review ─────────────────────── -->
        <div class="checkout-step" id="step-3">
        <div class="card p-4">
            <h4 class="mb-4">📋 Order Review</h4>
            <div id="order-summary-view"></div>

            <div class="trust-badges">
                <div class="trust-badge">🔒 Secure Checkout</div>
                <div class="trust-badge">🔄 <?= htmlspecialchars(substr($returnPolicy,0,35)) ?>...</div>
                <div class="trust-badge">📦 Fast Delivery</div>
                <div class="trust-badge">✅ Quality Guaranteed</div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="goStep(2)">← Back</button>
                <button class="btn btn-success btn-lg px-5" id="placeOrderBtn" onclick="placeOrder()">
                    ✅ Place Order
                </button>
            </div>
        </div>
        </div>

    </div>
    </div>
</main>

<!-- hidden csrf -->
<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf) ?>">

<?php include '../components/footer.php'; ?>

<script>
let currentStep = 1;

// ── صنع radio addresses يعمل ──────────────────────────────
document.querySelectorAll('.addr-radio').forEach(r => {
    r.addEventListener('change', () => {
        const manual = document.getElementById('manualAddr');
        if (!manual) return;
        manual.style.display = r.value === '0' ? 'block' : 'none';
    });
    // إصلاح checked البداية
    if (r.checked && r.value !== '0') {
        const manual = document.getElementById('manualAddr');
        if (manual) manual.style.display = 'none';
    }
});

function goStep(n) {
    document.querySelectorAll('.checkout-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');
    for (let i = 1; i <= 3; i++) {
        const si = document.getElementById('si-' + i);
        si.classList.remove('active','done');
        if (i < n)      si.classList.add('done');
        else if (i===n) si.classList.add('active');
    }
    currentStep = n;
    if (n === 3) renderOrderSummaryView();
    window.scrollTo({top:0, behavior:'smooth'});
}

function renderOrderSummaryView() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const el   = document.getElementById('order-summary-view');
    if (!el) return;
    if (!cart.length) { el.innerHTML = '<p class="text-danger">Your cart is empty!</p>'; return; }
    let total = 0;
    el.innerHTML = cart.map(item => {
        const sub = item.price * item.quantity; total += sub;
        return `<div class="d-flex justify-content-between mb-2">
            <span>${item.name} × ${item.quantity}</span>
            <strong>$${sub.toFixed(2)}</strong>
        </div>`;
    }).join('') + `<hr><div class="d-flex justify-content-between fw-bold fs-5">
        <span>Total:</span><span class="text-success">$${total.toFixed(2)}</span>
    </div>`;
}

async function placeOrder() {
    const btn  = document.getElementById('placeOrderBtn');
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (!cart.length) { if(typeof showToast==='function') showToast('Your cart is empty!','error'); return; }

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    const savedAddrId = document.querySelector('input[name="saved_addr_ui"]:checked')?.value || '0';
    const payment     = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash_on_delivery';
    const manualAddr  = {
        country: document.getElementById('ma_country')?.value || '',
        city:    document.getElementById('ma_city')?.value    || '',
        full:    document.getElementById('ma_full')?.value    || '',
        phone:   document.getElementById('ma_phone')?.value   || '',
        save:    document.getElementById('saveNewAddr')?.checked ? 1 : 0,
    };

    const fd = new FormData();
    fd.append('cart',        JSON.stringify(cart));
    fd.append('payment',     payment);
    fd.append('address_id',  savedAddrId);
    fd.append('manual_addr', JSON.stringify(manualAddr));
    fd.append('csrf_token',  document.getElementById('csrfToken').value);

    try {
        const data = await fetchWithCsrfRetry('/Task(1)/handlers/order_handler.php', {method:'POST', body:fd});
        if (data.csrf_token) updateCsrfToken(data.csrf_token);
        if (data.success) {
            localStorage.removeItem('cart');
            if (typeof refreshCartUI === 'function') refreshCartUI();
            window.location.href = '/Task(1)/pages/order-confirmation.php?id=' + data.order_id;
        } else {
            if (typeof showToast==='function') showToast(data.message || 'Order failed.','error');
            btn.disabled  = false;
            btn.innerHTML = '✅ Place Order';
        }
    } catch {
        if (typeof showToast==='function') showToast('Connection error.','error');
        btn.disabled  = false;
        btn.innerHTML = '✅ Place Order';
    }
}
</script>
</body>
</html>
