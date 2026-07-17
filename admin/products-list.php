<?php
/**
 * admin/products-list.php
 */
$pageTitle = 'Products';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_products');

// تحرير Session Lock فوراً بعد قراءة بيانات الجلسة
session_write_close();

$pdo = getDB();

// ── بحث + Pagination ─────────────────────────────────────────
$search      = trim($_GET['q'] ?? '');
$searchParam = $search !== '' ? "%{$search}%" : null;
$perPage     = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM products" . ($searchParam ? " WHERE name LIKE ?" : "");
$cStmt    = $pdo->prepare($countSql);
$cStmt->execute($searchParam ? [$searchParam] : []);
$totalProducts = (int)$cStmt->fetchColumn();
$totalPages    = max(1, (int)ceil($totalProducts / $perPage));

$dataSql = "SELECT * FROM products"
         . ($searchParam ? " WHERE name LIKE ?" : "")
         . " ORDER BY date_added DESC, id DESC LIMIT {$perPage} OFFSET {$offset}";
$pStmt = $pdo->prepare($dataSql);
$pStmt->execute($searchParam ? [$searchParam] : []);
$products = $pStmt->fetchAll();

$csrf = generateCsrfToken();
?>

<div class="admin-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1>🛍️ Products <span class="badge bg-secondary ms-2"><?= $totalProducts ?></span></h1>
    <a href="/Task(1)/admin/add-product.php" class="btn btn-success btn-sm">+ Add Product</a>
</div>

<div class="mb-3">
    <form method="GET" action="" class="d-flex gap-2 search-form">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Search by product name..." value="<?= htmlspecialchars($search) ?>"
               style="max-width:280px;">
        <button type="submit" class="btn btn-sm btn-outline-secondary">🔍 Search</button>
        <?php if ($search): ?>
            <a href="?" class="btn btn-sm btn-outline-secondary">✕ Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card admin-table p-0">
<table class="table mb-0">
    <thead>
        <tr>
            <th>#</th>
            <th>Image</th>
            <th>Name</th>
            <th>Price</th>
            <th>Discount</th>
            <th>Stock</th>
            <th>Added</th>
            <th class="text-center">Visibility</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($products)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No products found.</td></tr>
    <?php else: ?>
    <?php foreach ($products as $i => $pr):
        $finalPrice = (float)$pr['price'] * (1 - ((float)$pr['discount_percentage'] / 100));
        $isVisible  = (int)($pr['is_visible'] ?? 1);
    ?>
        <tr id="product-row-<?= $pr['id'] ?>">
            <td><?= $offset + $i + 1 ?></td>
            <td>
                <?php if ($pr['image_path']): ?>
                <img src="<?= htmlspecialchars($pr['image_path']) ?>" alt=""
                     style="height:42px;width:42px;object-fit:cover;border-radius:6px;opacity:<?= $isVisible ? '1':'0.35' ?>">
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="/Task(1)/admin/manage-product.php?id=<?= $pr['id'] ?>"
                   class="text-decoration-none fw-semibold <?= !$isVisible ? 'text-muted':'' ?>">
                    <?= htmlspecialchars($pr['name']) ?>
                </a>
                <?php if (!$isVisible): ?>
                <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Hidden</span>
                <?php endif; ?>
            </td>
            <td>$<?= number_format($pr['price'], 2) ?></td>
            <td>
                <?php if ((float)$pr['discount_percentage'] > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $pr['discount_percentage'] ?>%</span>
                    <small class="text-muted d-block">→ $<?= number_format($finalPrice,2) ?></small>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ((int)$pr['stock_quantity'] === 0): ?>
                    <span class="badge bg-danger">Out of Stock</span>
                <?php elseif ((int)$pr['stock_quantity'] <= 5): ?>
                    <span class="badge bg-warning text-dark"><?= $pr['stock_quantity'] ?></span>
                <?php else: ?>
                    <span class="badge bg-success"><?= $pr['stock_quantity'] ?></span>
                <?php endif; ?>
            </td>
            <td><?= $pr['date_added'] ? date('d M Y', strtotime($pr['date_added'])) : '—' ?></td>
            <td class="text-center">
                <button class="btn btn-sm <?= $isVisible ? 'btn-outline-secondary':'btn-outline-warning' ?> toggle-vis-btn"
                        data-id="<?= $pr['id'] ?>"
                        title="<?= $isVisible ? 'Hide from store':'Show in store' ?>">
                    <?= $isVisible ? '👁️':'🚫' ?>
                </button>
            </td>
            <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                    <a href="/Task(1)/admin/manage-product.php?id=<?= $pr['id'] ?>"
                       class="btn btn-sm btn-outline-primary" title="Edit">✏️</a>
                    <button class="btn btn-sm btn-outline-danger del-btn"
                            data-id="<?= $pr['id'] ?>"
                            data-name="<?= htmlspecialchars($pr['name'], ENT_QUOTES) ?>"
                            title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="d-flex justify-content-center mt-4">
    <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active':'' ?>">
            <a class="page-link" href="?page=<?= $p ?><?= $search ? '&q='.urlencode($search):'' ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php
$extraScripts = '<script>
(function(){
    const HANDLER = "/Task(1)/handlers/product_handler.php";

    function getToken() { return window._csrfToken || ""; }

    // ── Toggle Visibility ─────────────────────────────────────
    document.querySelectorAll(".toggle-vis-btn").forEach(function(btn){
        btn.addEventListener("click", async function(){
            btn.disabled = true;
            var fd = new FormData();
            fd.append("action",     "toggle_visibility");
            fd.append("product_id", btn.dataset.id);
            fd.append("csrf_token", getToken());

            var data = await fetchWithCsrfRetry(HANDLER, {method:"POST", body:fd});

            if (!data.success) {
                showToast(data.message || "Error", "error");
                btn.disabled = false;
                return;
            }

            var visible = (data.is_visible === 1);
            btn.textContent = visible ? "👁️" : "🚫";
            btn.title       = visible ? "Hide from store" : "Show in store";
            btn.className   = "btn btn-sm toggle-vis-btn " + (visible ? "btn-outline-secondary":"btn-outline-warning");
            btn.disabled    = false;

            var row = document.getElementById("product-row-" + btn.dataset.id);
            if (row) {
                var img    = row.querySelector("img");
                var link   = row.querySelector("a.fw-semibold");
                var badge  = row.querySelector(".badge.bg-secondary");
                if (img)  img.style.opacity = visible ? "1" : "0.35";
                if (link) link.classList.toggle("text-muted", !visible);
                if (!visible && !badge && link) {
                    var sp = document.createElement("span");
                    sp.className = "badge bg-secondary ms-1";
                    sp.style.fontSize = ".65rem";
                    sp.textContent = "Hidden";
                    link.after(sp);
                } else if (visible && badge) {
                    badge.remove();
                }
            }
            showToast(visible ? "Product is now visible" : "Product hidden from store", "success");
        });
    });

    // ── Delete Product ────────────────────────────────────────
    document.querySelectorAll(".del-btn").forEach(function(btn){
        btn.addEventListener("click", function(){
            var pid  = btn.dataset.id;
            var name = btn.dataset.name;
            Swal.fire({
                title: "Delete Product?",
                text:  "\\"" + name + "\\" will be permanently deleted.",
                icon:  "warning",
                showCancelButton:   true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor:  "#6c757d",
                confirmButtonText:  "Yes, Delete",
                cancelButtonText:   "Cancel"
            }).then(async function(result){
                if (!result.isConfirmed) return;
                var fd = new FormData();
                fd.append("action",     "delete_product");
                fd.append("product_id", pid);
                fd.append("csrf_token", getToken());

                var data = await fetchWithCsrfRetry(HANDLER, {method:"POST", body:fd});
                if (data.success) {
                    var row = document.getElementById("product-row-" + pid);
                    if (row) row.remove();
                    showToast("Product deleted", "success");
                } else {
                    showToast(data.message || "Error deleting product", "error");
                }
            });
        });
    });
})();
</script>';
?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
