<?php
$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_orders');

$pdo = getDB();
$msg = '';

// ── تصدير CSV ────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query("
        SELECT o.order_id, u.full_name, u.email, o.total_amount,
               o.status, o.payment_method, o.created_at
        FROM orders o JOIN users u ON u.id=o.user_id
        ORDER BY o.created_at DESC
    ")->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output','w');
    fputcsv($f,['Order ID','Customer','Email','Total','Status','Payment','Date']);
    foreach ($rows as $r) {
        fputcsv($f,[$r['order_id'],$r['full_name'],$r['email'],
            '$'.$r['total_amount'],$r['status'],$r['payment_method'],$r['created_at']]);
    }
    fclose($f);
    exit;
}

// ── تغيير حالة الطلب ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $oid    = (int)($_POST['order_id']   ?? 0);
    $status = $_POST['new_status']        ?? '';
    if ($oid && in_array($status,['pending','shipped','completed','cancelled'])) {
        $pdo->prepare("UPDATE orders SET status=?, is_notified=1 WHERE order_id=?")->execute([$status,$oid]);
        logAdminAction($adminId,'change_order_status','order',$oid,"new status: {$status}");
        $msg = "✅ تم تغيير حالة الطلب #{$oid} إلى {$status}.";
    }
}

// تحديد كل الطلبات كمُشعَر بها
$pdo->query("UPDATE orders SET is_notified=1");

$orders = $pdo->query("
    SELECT o.*, u.full_name, u.email
    FROM orders o JOIN users u ON u.id=o.user_id
    ORDER BY o.created_at DESC
")->fetchAll();

$statusColors = ['pending'=>'warning','shipped'=>'primary','completed'=>'success','cancelled'=>'danger'];
?>

<div class="admin-page-header">
    <h1>📦 Manage Orders</h1>
    <a href="?export=csv" class="btn btn-outline-success btn-sm">📥 Export CSV</a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card admin-table p-0">
<table class="table mb-0">
    <thead>
        <tr>
            <th>#</th><th>Customer</th><th>Total</th>
            <th>Payment</th><th>Status</th><th>Date</th><th>Change Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td><?= $o['order_id'] ?></td>
        <td>
            <div class="fw-semibold"><?= htmlspecialchars($o['full_name']) ?></div>
            <small style="color:var(--placeholder-color);"><?= htmlspecialchars($o['email']) ?></small>
        </td>
        <td><strong>$<?= number_format($o['total_amount'],2) ?></strong></td>
        <td><?= htmlspecialchars($o['payment_method']) ?></td>
        <td>
            <span class="badge status-<?= $o['status'] ?>">
                <?= ucfirst($o['status']) ?>
            </span>
        </td>
        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
        <td>
            <form method="POST" class="d-flex gap-1 align-items-center">
                <input type="hidden" name="change_status" value="1">
                <input type="hidden" name="order_id"     value="<?= $o['order_id'] ?>">
                <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf) ?>">
                <select name="new_status" class="form-select form-select-sm" style="width:120px;">
                    <?php foreach (['pending','shipped','completed','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>>
                        <?= ucfirst($st) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-success">✔</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/layout_end.php'; ?>
