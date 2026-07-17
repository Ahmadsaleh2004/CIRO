<?php
/**
 * admin/order-details.php — الجزء 13/18
 */
$pageTitle = 'Order Details';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_orders');

$pdo = getDB();

// تنظيف الطلبات المنتهية مهلتها تلقائياً
$pdo->prepare("UPDATE orders SET status='not_taken', taken_at=NULL WHERE status='taken' AND taken_at < ?")
    ->execute([date('Y-m-d H:i:s', time() - 3 * 3600)]);

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    echo '<div class="container py-5 text-center"><h2>Invalid order ID.</h2><a href="/Task(1)/admin/manage-orders.php" class="btn btn-secondary">← Back</a></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*,
           u.full_name AS user_name, u.email AS user_email, u.phone_number AS user_phone,
           ua.full_address, ua.country, ua.city,
           ua.phone_number AS shipping_phone, ua.label AS address_label
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN user_addresses ua ON ua.id = o.address_id
    WHERE o.order_id = ? LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="container py-5 text-center"><h2>Order not found.</h2><a href="/Task(1)/admin/manage-orders.php" class="btn btn-secondary">← Back</a></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.name AS product_name, p.image_path
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$productNames = implode(', ', array_column($items, 'product_name'));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_strikes WHERE user_id = ?");
$stmt->execute([$order['user_id']]);
$userStrikes = (int)$stmt->fetchColumn();

$csrf = generateCsrfToken();

// حساب الوقت المتبقي للـ taken
$remSeconds = 0;
if ($order['status'] === 'taken' && $order['taken_at']) {
    $remSeconds = max(0, strtotime($order['taken_at']) + (3 * 3600) - time());
}

$statusBadge = match($order['status']) {
    'completed' => '<span class="badge bg-success fs-6">Completed</span>',
    'cancelled' => '<span class="badge bg-danger fs-6">Cancelled</span>',
    'taken'     => '<span class="badge bg-primary fs-6">Taken</span>',
    default     => '<span class="badge bg-warning text-dark fs-6">Not Taken</span>',
};
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="/Task(1)/admin/manage-orders.php" class="btn btn-outline-secondary btn-sm">← Back to Orders</a>
        <h1 class="mb-0">Order #<?= $orderId ?> <?= $statusBadge ?></h1>
    </div>

    <!-- ── Take It / Taken button ── -->
    <?php if ($order['status'] === 'not_taken' || $order['status'] === 'taken'): ?>
    <div class="d-flex align-items-center gap-3">
        <?php if ($order['status'] === 'taken'): ?>
        <div class="text-center">
            <div class="small text-muted">Time remaining</div>
            <div id="countdown" class="fw-bold font-monospace fs-5 text-danger">--:--:--</div>
        </div>
        <button id="takeItBtn" class="btn btn-danger fw-bold px-4"
                onclick="handleTakeIt()" disabled>
            🔴 Taken
        </button>
        <?php else: ?>
        <button id="takeItBtn" class="btn btn-success fw-bold px-4"
                onclick="handleTakeIt()">
            💚 Take It
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4">

    <!-- ── Left: Order Info ── -->
    <div class="col-lg-8">

        <!-- Order Details Card -->
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h4 class="fw-bold mb-0">📋 Order Information</h4>
                <small class="text-muted">Placed: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></small>
            </div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <span class="text-muted small">Order ID</span><br>
                    <strong>#<?= $orderId ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Payment Method</span><br>
                    <strong><?= htmlspecialchars(ucwords(str_replace('_',' ',$order['payment_method']))) ?></strong>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card p-4 mb-4">
            <h4 class="fw-bold mb-3">🛍️ Items Ordered</h4>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($item['image_path']): ?>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt=""
                                         style="width:44px;height:44px;object-fit:contain;border-radius:6px;background:var(--bg-color);">
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= $item['quantity'] ?></td>
                            <td>$<?= number_format($item['price_at_purchase'], 2) ?></td>
                            <td>$<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-top">
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td class="fw-bold fs-5" style="color:var(--price-color);">
                                $<?= number_format($order['total_amount'], 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Shipping -->
        <div class="card p-4 mb-4">
            <h4 class="fw-bold mb-3">📍 Shipping Details</h4>
            <?php if ($order['full_address']): ?>
            <div class="row g-2">
                <div class="col-sm-6">
                    <span class="text-muted small">Label</span><br>
                    <strong><?= htmlspecialchars($order['address_label'] ?? '—') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Phone</span><br>
                    <strong><?= htmlspecialchars($order['shipping_phone'] ?: $order['user_phone'] ?: '—') ?></strong>
                </div>
                <div class="col-12">
                    <span class="text-muted small">Address</span><br>
                    <strong><?= htmlspecialchars($order['full_address']) ?></strong>
                    <?php if ($order['city'] || $order['country']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars(trim(($order['city']??'').', '.($order['country']??''), ', ')) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">No address linked (manual or deleted).</p>
            <?php endif; ?>
        </div>

        <!-- Report Issue -->
        <div class="card p-4">
            <h4 class="fw-bold mb-2">🚨 Report an Issue</h4>
            <p class="text-muted small mb-3">Document a problem with this order. The note will appear in the user's profile page for admin review.</p>
            <div class="d-flex gap-2">
                <textarea id="reportReason" class="form-control form-control-sm"
                          rows="2" placeholder="Describe the issue..."></textarea>
                <button id="reportBtn" class="btn btn-outline-danger btn-disabled-faded" disabled
                        onclick="submitReport()" style="white-space:nowrap;">
                    🚨 Report
                </button>
            </div>
        </div>

    </div>

    <!-- ── Right: Client + Delivery ── -->
    <div class="col-lg-4">

        <!-- Client Info -->
        <div class="card p-4 mb-4">
            <h4 class="fw-bold mb-3">👤 Client</h4>
            <p class="fw-semibold mb-1"><?= htmlspecialchars($order['user_name']) ?></p>
            <p class="small text-muted mb-1">✉️ <?= htmlspecialchars($order['user_email']) ?></p>
            <p class="small mb-3">📞 <?= htmlspecialchars($order['user_phone'] ?: '—') ?></p>
            <div class="d-flex justify-content-between align-items-center p-2 rounded"
                 style="background:var(--bg-color);border:1px solid var(--section-border);">
                <span class="small">Strikes: <strong><?= $userStrikes ?>/3</strong></span>
                <a href="/Task(1)/admin/user-details.php?user_id=<?= $order['user_id'] ?>"
                   class="btn btn-sm btn-outline-warning">View Profile</a>
            </div>
        </div>

        <!-- Delivery Actions -->
        <?php if ($order['status'] === 'taken'): ?>
        <div class="card p-4 mb-4">
            <h4 class="fw-bold mb-3">⚙️ Delivery Actions</h4>
            <div class="d-flex flex-column gap-2">
                <button class="btn btn-success w-100 fw-bold"
                        onclick="updateDelivery('mark_delivered')">
                    ✅ Mark as Delivered
                </button>
                <button class="btn btn-outline-danger w-100"
                        onclick="updateDelivery('cancel_delivery')">
                    ❌ Cancel Delivery
                </button>
            </div>
        </div>
        <?php elseif ($order['status'] === 'completed' || $order['status'] === 'cancelled'): ?>
        <div class="card p-4 mb-4 text-center">
            <div class="fs-5 fw-bold <?= $order['status']==='completed'?'text-success':'text-danger' ?>">
                <?= $order['status'] === 'completed' ? '✅ Delivered' : '❌ Cancelled' ?>
            </div>
            <small class="text-muted">This order is finalized.</small>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php
$extraScripts = '<script>
(function() {
    const ORDER_ID     = ' . $orderId . ';
    const PRODUCT_NAMES = ' . json_encode($productNames) . ';
    const ORDER_DATE    = ' . json_encode(date('d M Y', strtotime($order['created_at']))) . ';
    const USER_ID       = ' . $order['user_id'] . ';
    let   remSeconds    = ' . $remSeconds . ';
    let   timerInterval = null;

    // ── Countdown Timer ──────────────────────────────────────────
    if (remSeconds > 0) {
        const el = document.getElementById("countdown");
        timerInterval = setInterval(function() {
            if (remSeconds <= 0) {
                clearInterval(timerInterval);
                if (el) el.textContent = "00:00:00";
                location.reload();
                return;
            }
            remSeconds--;
            const h = Math.floor(remSeconds / 3600);
            const m = Math.floor((remSeconds % 3600) / 60);
            const s = remSeconds % 60;
            if (el) el.textContent =
                String(h).padStart(2,"0") + ":" +
                String(m).padStart(2,"0") + ":" +
                String(s).padStart(2,"0");
        }, 1000);
    }

    // ── Take It / Taken ──────────────────────────────────────────
    window.handleTakeIt = async function() {
        const btn = document.getElementById("takeItBtn");
        const isTaken = btn && btn.textContent.includes("Taken");
        if (isTaken) return; // لا يمكن إلغاء Taken يدوياً

        const result = await Swal.fire({
            title: "Take this order?",
            text: "You will have 3 hours to deliver it.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Take It",
            confirmButtonColor: "#16a34a",
            cancelButtonText: "Cancel"
        });
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append("action",    "taken");
        fd.append("order_id",  ORDER_ID);
        fd.append("csrf_token", window._csrfToken || "");

        const data = await fetchWithCsrfRetry("/Task(1)/handlers/order_handler.php", { method: "POST", body: fd });
        if (data.csrf_token) updateCsrfToken(data.csrf_token);
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || "Error", "error");
        }
    };

    // ── Delivered / Cancel ───────────────────────────────────────
    window.updateDelivery = async function(action) {
        const isDelivered = action === "mark_delivered";
        const confirmText = isDelivered
            ? "Confirm that the order has been delivered successfully?"
            : "Cancel the delivery of this order?";
        const notifMsg = isDelivered
            ? "Your order (" + PRODUCT_NAMES + ") has been delivered on " + ORDER_DATE + "."
            : "Your order (" + PRODUCT_NAMES + ") delivery was cancelled on " + ORDER_DATE + ".";

        const result = await Swal.fire({
            title: isDelivered ? "Mark as Delivered?" : "Cancel Delivery?",
            text: confirmText,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Confirm",
            confirmButtonColor: isDelivered ? "#16a34a" : "#dc2626",
            cancelButtonText: "Cancel"
        });
        if (!result.isConfirmed) return;

        if (timerInterval) clearInterval(timerInterval);

        const fd = new FormData();
        fd.append("action",    action);
        fd.append("order_id",  ORDER_ID);
        fd.append("notif_msg", notifMsg);
        fd.append("csrf_token", window._csrfToken || "");

        const data = await fetchWithCsrfRetry("/Task(1)/handlers/order_handler.php", { method: "POST", body: fd });
        if (data.csrf_token) updateCsrfToken(data.csrf_token);
        if (data.success) {
            Swal.fire("Done!", data.message, "success").then(() => location.reload());
        } else {
            showToast(data.message || "Error", "error");
        }
    };

    // ── Report ───────────────────────────────────────────────────
    const reportTxt = document.getElementById("reportReason");
    const reportBtn = document.getElementById("reportBtn");
    if (reportTxt && reportBtn) {
        reportTxt.addEventListener("input", function() {
            updateButtonState(reportBtn, reportTxt.value.trim().length > 0);
        });
    }

    window.submitReport = async function() {
        const reason = reportTxt ? reportTxt.value.trim() : "";
        if (!reason) return;

        const result = await Swal.fire({
            title: "Report this issue?",
            text: "A note will be added to the user profile for admin review.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc2626",
            confirmButtonText: "Yes, Report",
            cancelButtonText: "Cancel"
        });
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append("action",   "report_issue");
        fd.append("order_id", ORDER_ID);
        fd.append("reason",   reason);
        fd.append("csrf_token", window._csrfToken || "");

        const data = await fetchWithCsrfRetry("/Task(1)/handlers/order_handler.php", { method: "POST", body: fd });
        if (data.csrf_token) updateCsrfToken(data.csrf_token);
        if (data.success) {
            showToast("Issue reported and saved to user profile.", "success");
            if (reportTxt) reportTxt.value = "";
            updateButtonState(reportBtn, false);
        } else {
            showToast(data.message || "Error", "error");
        }
    };

})();
</script>';
?>

<?php require_once __DIR__ . '/../admin/layout_end.php'; ?>
