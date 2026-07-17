<?php
/**
 * admin/manage-orders.php — الجزء 14/18
 */
$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_orders');
session_write_close();

$pdo = getDB();

// تنظيف الطلبات المنتهية مهلتها تلقائياً
$pdo->prepare("UPDATE orders SET status='not_taken', taken_at=NULL WHERE status='taken' AND taken_at < ?")
    ->execute([date('Y-m-d H:i:s', time() - 3 * 3600)]);

// تحديد كل الطلبات كمقروءة
$pdo->query("UPDATE orders SET is_notified=1");

// ── AJAX: delete order ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_order') {
    header('Content-Type: application/json; charset=utf-8');
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $oid = (int)($_POST['order_id'] ?? 0);
    if ($oid) {
        $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$oid]);
        logAdminAction($adminId, 'delete_order', 'orders', $oid);
    }
    echo json_encode(['success' => true, 'csrf_token' => generateCsrfToken()]);
    exit;
}

// ── فلترة + بحث ──────────────────────────────────────────────
$filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$whereClauses = [];
$queryParams  = [];

if ($filter !== '') {
    $whereClauses[] = " o.status = ? ";
    $queryParams[]  = $filter;
}

if ($search !== '') {
    if (is_numeric($search)) {
        $whereClauses[] = " (o.order_id = ? OR u.full_name LIKE ? OR u.email LIKE ?) ";
        $queryParams[]  = (int)$search;
        $queryParams[]  = "%{$search}%";
        $queryParams[]  = "%{$search}%";
    } else {
        $whereClauses[] = " (u.full_name LIKE ? OR u.email LIKE ?) ";
        $queryParams[]  = "%{$search}%";
        $queryParams[]  = "%{$search}%";
    }
}

$whereClause = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id $whereClause");
$countStmt->execute($queryParams);
$totalOrders = (int)$countStmt->fetchColumn();

$perPage     = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;
$totalPages  = max(1, (int)ceil($totalOrders / $perPage));

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    $whereClause
    ORDER BY
      CASE o.status
        WHEN 'not_taken'  THEN 1
        WHEN 'taken'      THEN 2
        WHEN 'cancelled'  THEN 3
        WHEN 'completed'  THEN 4
        ELSE 5
      END ASC,
      o.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($queryParams);
$orders = $stmt->fetchAll();

$csrf = generateCsrfToken();
?>

<div class="admin-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1>📦 Manage Orders <span class="badge bg-secondary ms-2"><?= $totalOrders ?></span></h1>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Search -->
        <form class="d-flex search-form" method="GET" action="">
            <?php if ($filter): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
            <?php endif; ?>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Order ID or customer..." value="<?= htmlspecialchars($search) ?>"
                   style="width:200px;">
            <button type="submit" class="btn btn-sm btn-outline-secondary ms-1">🔍</button>
            <?php if ($search): ?>
                <a href="?<?= $filter ? 'status='.urlencode($filter) : '' ?>" class="btn btn-sm btn-outline-secondary ms-1">✕</a>
            <?php endif; ?>
        </form>
        <!-- Sort by Status -->
        <select class="form-select form-select-sm" style="width:160px;"
                onchange="filterStatus(this.value)">
            <option value="">All Orders</option>
            <option value="not_taken"  <?= $filter==='not_taken'  ?'selected':'' ?>>Not Taken</option>
            <option value="taken"      <?= $filter==='taken'      ?'selected':'' ?>>Taken</option>
            <option value="cancelled"  <?= $filter==='cancelled'  ?'selected':'' ?>>Cancelled</option>
            <option value="completed"  <?= $filter==='completed'  ?'selected':'' ?>>Completed</option>
        </select>
    </div>
</div>

<div class="card admin-table p-0">
<table class="table table-hover mb-0">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Date</th>
            <th class="text-center">Delete</th>
        </tr>
    </thead>
    <tbody id="ordersTableBody">
    <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="text-center py-4 text-muted">No orders found.</td></tr>
    <?php else: ?>
    <?php foreach ($orders as $o):
        [$statusLabel, $badgeColor] = match($o['status']) {
            'not_taken'  => ['Not Taken',  'warning text-dark'],
            'taken'      => ['Taken',       'primary'],
            'cancelled'  => ['Cancelled',   'danger'],
            'completed'  => ['Completed',   'success'],
            default      => [ucfirst($o['status']), 'secondary'],
        };
    ?>
    <tr id="order-row-<?= $o['order_id'] ?>"
        style="cursor:pointer;"
        onclick="goToOrderDetails(<?= $o['order_id'] ?>)">
        <td><strong>#<?= $o['order_id'] ?></strong></td>
        <td>
            <div class="fw-semibold"><?= htmlspecialchars($o['full_name']) ?></div>
            <small class="text-muted"><?= htmlspecialchars($o['email']) ?></small>
        </td>
        <td><strong>$<?= number_format($o['total_amount'], 2) ?></strong></td>
        <td><?= htmlspecialchars(ucwords(str_replace('_',' ',$o['payment_method']))) ?></td>
        <td><span class="badge bg-<?= $badgeColor ?>"><?= $statusLabel ?></span></td>
        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
        <td class="text-center" onclick="event.stopPropagation();">
            <button class="btn btn-sm btn-outline-danger delete-order-btn"
                    data-id="<?= $o['order_id'] ?>"
                    title="Delete order">🗑️</button>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination pagination-sm">
        <li class="page-item <?= $currentPage<=1?'disabled':'' ?>">
            <a class="page-link" href="?page=<?= $currentPage-1 ?><?= $filter?'&status='.urlencode($filter):'' ?><?= $search?'&q='.urlencode($search):'' ?>">‹</a>
        </li>
        <?php for ($i=max(1,$currentPage-2); $i<=min($totalPages,$currentPage+2); $i++): ?>
        <li class="page-item <?= $i===$currentPage?'active':'' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= $filter?'&status='.urlencode($filter):'' ?><?= $search?'&q='.urlencode($search):'' ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage>=$totalPages?'disabled':'' ?>">
            <a class="page-link" href="?page=<?= $currentPage+1 ?><?= $filter?'&status='.urlencode($filter):'' ?><?= $search?'&q='.urlencode($search):'' ?>">›</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php
$extraScripts = '<script>
(function() {
    function goToOrderDetails(id) {
        window.location.href = "/Task(1)/admin/order-details.php?id=" + id;
    }
    window.goToOrderDetails = goToOrderDetails;

    function filterStatus(status) {
        var p = new URLSearchParams(window.location.search);
        if (status) p.set("status", status); else p.delete("status");
        p.delete("page");
        window.location.href = "?" + p.toString();
    }
    window.filterStatus = filterStatus;

    // ── Delete AJAX ───────────────────────────────────────────
    document.querySelectorAll(".delete-order-btn").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var oid = btn.dataset.id;
            Swal.fire({
                title: "Delete Order #" + oid + "?",
                text: "This order and all its items will be permanently deleted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, Delete",
                cancelButtonText: "Cancel"
            }).then(async function(result) {
                if (!result.isConfirmed) return;
                var fd = new FormData();
                fd.append("action",   "delete_order");
                fd.append("order_id", oid);
                fd.append("csrf_token", window._csrfToken || "");
                var data = await fetchWithCsrfRetry("/Task(1)/admin/manage-orders.php", { method: "POST", body: fd });
                if (data.success) {
                    var row = document.getElementById("order-row-" + oid);
                    if (row) {
                        row.style.transition = "opacity .3s";
                        row.style.opacity    = "0";
                        setTimeout(function() { row.remove(); }, 300);
                    }
                    showToast("Order #" + oid + " deleted", "success");
                } else {
                    showToast(data.message || "Error", "error");
                }
            });
        });
    });
})();
</script>';
?>

<?php require_once __DIR__ . '/../admin/layout_end.php'; ?>
