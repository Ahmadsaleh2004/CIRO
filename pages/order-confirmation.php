<?php
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../config/db.php';

requireUser();
$pdo     = getDB();
$orderId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name FROM orders o
    JOIN users u ON u.id=o.user_id
    WHERE o.order_id=? AND o.user_id=? LIMIT 1
");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) { header('Location: /Task(1)/pages/products.php'); exit; }

$items = $pdo->prepare("
    SELECT oi.*, p.name, p.image_path FROM order_items oi
    JOIN products p ON p.id=oi.product_id
    WHERE oi.order_id=?
");
$items->execute([$orderId]);
$items = $items->fetchAll();

$statusColors = ['not_taken'=>'warning text-dark','taken'=>'primary','completed'=>'success','cancelled'=>'danger'];
?>
<?php
$pageTitle = 'Order Confirmed';
$noIndex = true;
require_once __DIR__ . '/../components/header.php';
?>
<?php include '../components/navbar.php'; ?>

<main id="main-content" class="container py-5">
    <div class="text-center mb-5 fade-in-up">
        <div style="font-size:5rem;">🎉</div>
        <h1 class="fw-bold mt-3">Order Confirmed!</h1>
        <p style="color:var(--placeholder-color);">
            Thank you, <?= htmlspecialchars($order['full_name']) ?>! Your order has been placed.
        </p>
        <div class="d-flex justify-content-center gap-2 mt-2">
            <span class="badge bg-<?= $statusColors[$order['status']] ?> fs-6">
                <?= ucfirst($order['status']) ?>
            </span>
            <span class="badge bg-secondary fs-6">Order #<?= $orderId ?></span>
        </div>
    </div>

    <div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card p-4 mb-4">
            <h5 class="mb-3">📦 Order Summary</h5>
            <?php foreach ($items as $item): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($item['image_path']): ?>
                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt=""
                         style="width:44px;height:44px;object-fit:contain;border-radius:6px;" loading="lazy">
                    <?php endif; ?>
                    <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                </div>
                <strong>$<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></strong>
            </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total:</span>
                <span class="text-success">$<?= number_format($order['total_amount'], 2) ?></span>
            </div>
            <p class="small mt-2 mb-0" style="color:var(--placeholder-color);">
                💳 <?= htmlspecialchars($order['payment_method']) ?> &nbsp;|&nbsp;
                📅 <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
            </p>
        </div>

        <div class="text-center d-flex gap-3 justify-content-center">
            <a href="/Task(1)/pages/products.php"  class="btn btn-success btn-lg px-5">Continue Shopping</a>
            <a href="/Task(1)/pages/my-info.php"   class="btn btn-outline-secondary btn-lg px-4">My Orders</a>
        </div>
    </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>
</body>
</html>
