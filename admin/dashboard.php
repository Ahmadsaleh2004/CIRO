<?php
/**
 * admin/dashboard.php — صفحة الإحصائيات الحقيقية
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../admin/layout.php';
requirePermission('can_view_dashboard');

$pdo = getDB();

// ── بطاقات إحصائية ───────────────────────────────────────────
$todaySales   = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status!='cancelled'")->fetchColumn();
$todayOrders  = (int)   $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$newUsersWeek = (int)   $pdo->query("SELECT COUNT(*) FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$pendingOrders= (int)   $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'not_taken'")->fetchColumn();
$newMessages  = (int)   $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_notified=0")->fetchColumn();
$totalStrikes = (int)   $pdo->query("SELECT COUNT(*) FROM user_strikes WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();

// ── رسم مبيعات آخر 30 يوم ────────────────────────────────────
$salesRows = $pdo->query("
    SELECT DATE(created_at) AS day, SUM(total_amount) AS total
    FROM orders
    WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) AND status!='cancelled'
    GROUP BY DATE(created_at) ORDER BY day ASC
")->fetchAll();
$chartLabels = json_encode(array_column($salesRows,'day'));
$chartValues = json_encode(array_map('floatval', array_column($salesRows,'total')));

// ── مستخدمون نشطون ───────────────────────────────────────────
$active24h = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE last_activity>=DATE_SUB(NOW(),INTERVAL 1 DAY)")->fetchColumn();
$active7d  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE last_activity>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$active30d = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE last_activity>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();

// ── Best Selling ──────────────────────────────────────────────
$bsQ     = trim($_GET['q'] ?? '');
$bsParams = [];
$bsWhere  = '';
if ($bsQ) {
    $bsWhere  = " WHERE name LIKE ?";
    $bsParams[] = "%{$bsQ}%";
}
$bsStmt = $pdo->prepare("SELECT id,name,image_path,sales_count,stock_quantity FROM products{$bsWhere} ORDER BY sales_count DESC LIMIT 12");
$bsStmt->execute($bsParams);
$bestProducts = $bsStmt->fetchAll();

// بناء JS string بشكل صحيح
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded",()=>{
    const dark=document.body.classList.contains("dark-mode");
    const grid=dark?"rgba(255,255,255,.07)":"rgba(0,0,0,.06)";
    const tc=dark?"#e6edf3":"#1a1a2e";
    const axes={x:{ticks:{color:tc},grid:{color:grid}},y:{ticks:{color:tc},grid:{color:grid}}};

    new Chart(document.getElementById("salesChart"),{
        type:"line",
        data:{
            labels:' . $chartLabels . ',
            datasets:[{label:"Sales ($)",data:' . $chartValues . ',
                borderColor:"#6366f1",backgroundColor:"rgba(99,102,241,.12)",
                tension:.4,fill:true,pointRadius:4,pointBackgroundColor:"#6366f1"}]
        },
        options:{responsive:true,plugins:{legend:{labels:{color:tc}}},scales:axes}
    });

    new Chart(document.getElementById("activeChart"),{
        type:"bar",
        data:{
            labels:["Last 24h","Last 7d","Last 30d"],
            datasets:[{label:"Active Users",data:[' . $active24h . ',' . $active7d . ',' . $active30d . '],
                backgroundColor:["#16a34a","#6366f1","#f59e0b"],borderRadius:8}]
        },
        options:{responsive:true,plugins:{legend:{labels:{color:tc}}},scales:axes}
    });
});
</script>';
?>

<div class="admin-page-header">
    <h1>📊 Dashboard</h1>
    <span style="color:var(--placeholder-color);font-size:.85rem;">
        Welcome back, <?= htmlspecialchars($adminName) ?>
    </span>
</div>

<!-- ── Stats ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-icon">💰</span>
            <div class="stat-value">$<?= number_format($todaySales,2) ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <a href="/Task(1)/admin/manage-orders.php?status=not_taken" style="text-decoration:none;">
        <div class="stat-card" style="<?= $pendingOrders > 0 ? 'border-color:#f59e0b;' : '' ?>">
            <span class="stat-icon">📦</span>
            <div class="stat-value" style="<?= $pendingOrders > 0 ? 'color:#f59e0b;' : '' ?>"><?= $pendingOrders ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="/Task(1)/admin/support.php" style="text-decoration:none;">
        <div class="stat-card" style="<?= $newMessages > 0 ? 'border-color:#6366f1;' : '' ?>">
            <span class="stat-icon">💬</span>
            <div class="stat-value" style="<?= $newMessages > 0 ? 'color:#6366f1;' : '' ?>"><?= $newMessages ?></div>
            <div class="stat-label">New Messages</div>
        </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-icon">👤</span>
            <div class="stat-value"><?= $newUsersWeek ?></div>
            <div class="stat-label">New Users (7d)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-icon">⚠️</span>
            <div class="stat-value" style="color:#dc3545;"><?= $totalStrikes ?></div>
            <div class="stat-label">Strikes (7d)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-icon">📈</span>
            <div class="stat-value">$<?= number_format($todaySales, 2) ?></div>
            <div class="stat-label">Revenue Today</div>
        </div>
    </div>
</div>

<!-- ── Charts ─────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="chart-card">
            <h5>📈 Sales — Last 30 Days</h5>
            <canvas id="salesChart" height="110"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card">
            <h5>👥 Active Users</h5>
            <canvas id="activeChart" height="190"></canvas>
        </div>
    </div>
</div>

<!-- ── Best Sellers ───────────────────────────────────────── -->
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">⭐ Best Selling Products</h5>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Search..." style="width:180px;"
                   value="<?= htmlspecialchars($bsQ) ?>">
            <button class="btn btn-sm btn-success">Go</button>
            <?php if ($bsQ): ?>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">✕</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="row g-3">
        <?php if (empty($bestProducts)): ?>
        <p class="text-center" style="color:var(--placeholder-color);">No products found.</p>
        <?php endif; ?>
        <?php foreach ($bestProducts as $bp): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="/Task(1)/admin/manage-product.php?id=<?= $bp['id'] ?>"
               class="card p-2 text-center text-decoration-none h-100 d-block">
                <img src="<?= htmlspecialchars($bp['image_path'] ?: '') ?>"
                     alt="<?= htmlspecialchars($bp['name']) ?>"
                     style="height:70px;object-fit:contain;margin:auto;"
                     loading="lazy">
                <p class="small fw-bold mb-0 mt-1"
                   style="font-size:.72rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                    <?= htmlspecialchars($bp['name']) ?>
                </p>
                <span class="badge bg-success mt-1"><?= $bp['sales_count'] ?> sold</span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout_end.php'; ?>
