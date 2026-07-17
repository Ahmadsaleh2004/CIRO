<?php
/**
 * admin/manage-users.php — الجزء 15/18
 */
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_users');
session_write_close();

$pdo = getDB();

// ── AJAX: delete user ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    header('Content-Type: application/json; charset=utf-8');
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        logAdminAction($adminId, 'delete_user', 'user', $uid);
    }
    echo json_encode(['success' => true, 'csrf_token' => generateCsrfToken()]);
    exit;
}

// ── AJAX: send notification ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_notification') {
    header('Content-Type: application/json; charset=utf-8');
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $uid   = (int)($_POST['user_id']    ?? 0);
    $title = trim($_POST['notif_title'] ?? '');
    $body  = trim($_POST['notif_body']  ?? '');
    if ($uid && $title && $body) {
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,sender_admin_id) VALUES (?,?,?,?)")
            ->execute([$uid, $title, $body, $adminId]);
        logAdminAction($adminId, 'send_notification', 'user', $uid, "Title: {$title}");
        echo json_encode(['success' => true, 'csrf_token' => generateCsrfToken()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.', 'csrf_token' => generateCsrfToken()]);
    }
    exit;
}

// ── فلترة + بحث ──────────────────────────────────────────────
$filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$whereClauses = [];
$queryParams  = [];

if ($search !== '') {
    $whereClauses[] = " (u.full_name LIKE ? OR u.email LIKE ?) ";
    $queryParams[]  = "%{$search}%";
    $queryParams[]  = "%{$search}%";
}

if ($filter !== '') {
    if ($filter === 'block') {
        $whereClauses[] = " (SELECT COUNT(*) FROM user_strikes WHERE user_id = u.id) >= 3 ";
    } elseif ($filter === 'not_active') {
        $whereClauses[] = " ((SELECT COUNT(*) FROM user_strikes WHERE user_id = u.id) < 3
                             AND (u.last_activity < DATE_SUB(NOW(), INTERVAL 3 MONTH) OR u.last_activity IS NULL)) ";
    } elseif ($filter === 'active') {
        $whereClauses[] = " ((SELECT COUNT(*) FROM user_strikes WHERE user_id = u.id) < 3
                             AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 3 MONTH)) ";
    }
}

$whereClause = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereClause");
$countStmt->execute($queryParams);
$totalUsers = (int)$countStmt->fetchColumn();

$perPage     = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;
$totalPages  = max(1, (int)ceil($totalUsers / $perPage));

// الترتيب: Active(1) → Block(2) → Not Active(3)، ثم تنازلي حسب last_activity
$stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM user_strikes WHERE user_id = u.id) AS strikes_count,
           (SELECT COUNT(*) FROM users u2 WHERE u2.created_at <= u.created_at) AS display_num
    FROM users u
    $whereClause
    ORDER BY
      CASE
        WHEN (SELECT COUNT(*) FROM user_strikes WHERE user_id = u.id) < 3
             AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 3 MONTH) THEN 1
        WHEN (SELECT COUNT(*) FROM user_strikes WHERE user_id = u.id) >= 3 THEN 2
        ELSE 3
      END ASC,
      u.last_activity DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($queryParams);
$users = $stmt->fetchAll();

$csrf = generateCsrfToken();
?>

<div class="admin-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1>👥 Manage Users <span class="badge bg-secondary ms-2"><?= $totalUsers ?></span></h1>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Search -->
        <form class="d-flex search-form" method="GET" action="">
            <?php if ($filter): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
            <?php endif; ?>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>"
                   style="width:200px;">
            <button type="submit" class="btn btn-sm btn-outline-secondary ms-1">🔍</button>
            <?php if ($search): ?>
                <a href="?<?= $filter ? 'status='.urlencode($filter) : '' ?>"
                   class="btn btn-sm btn-outline-secondary ms-1">✕</a>
            <?php endif; ?>
        </form>
        <!-- Sort by Status -->
        <select class="form-select form-select-sm" style="width:150px;"
                onchange="filterStatus(this.value)">
            <option value="">All Users</option>
            <option value="active"     <?= $filter==='active'     ?'selected':'' ?>>Active</option>
            <option value="block"      <?= $filter==='block'      ?'selected':'' ?>>Blocked</option>
            <option value="not_active" <?= $filter==='not_active' ?'selected':'' ?>>Not Active</option>
        </select>
    </div>
</div>

<div class="card admin-table p-0">
<table class="table mb-0">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Strikes</th>
            <th>Last Activity</th>
            <th>Joined</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u):
        $sc          = (int)$u['strikes_count'];
        $lastActTime = $u['last_activity'] ? strtotime($u['last_activity']) : 0;
        $isBlocked   = $sc >= 3;
        $isNotActive = !$isBlocked && $lastActTime < (time() - 3 * 30 * 24 * 3600);

        [$statusText, $statusClass] = $isBlocked
            ? ['Blocked',    'danger']
            : ($isNotActive ? ['Not Active', 'secondary'] : ['Active', 'success']);

        // Display number = ترتيب هذا المستخدم حسب تاريخ التسجيل عالمياً
        $displayNum = (int)$u['display_num'];
    ?>
    <tr class="user-row" data-uid="<?= $u['id'] ?>" style="cursor:pointer;">
        <td><?= $displayNum ?></td>
        <td><span class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></span></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span></td>
        <td>
            <span class="badge bg-<?= $sc>=3 ? 'danger' : ($sc>0 ? 'warning text-dark' : 'success') ?>">
                <?= $sc ?>/3
            </span>
        </td>
        <td><?= $u['last_activity'] ? date('d M Y', strtotime($u['last_activity'])) : '—' ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td class="text-center">
            <div class="d-flex gap-1 justify-content-center" onclick="event.stopPropagation()">
                <button class="btn btn-sm btn-outline-info notif-btn"
                        data-uid="<?= $u['id'] ?>"
                        data-name="<?= htmlspecialchars(addslashes($u['full_name'])) ?>"
                        title="Send Notification">🔔</button>
                <button class="btn btn-sm btn-outline-danger delete-user-btn"
                        data-uid="<?= $u['id'] ?>"
                        data-name="<?= htmlspecialchars(addslashes($u['full_name'])) ?>"
                        title="Delete user">🗑️</button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($users)): ?>
    <tr><td colspan="8" class="text-center py-4 text-muted">No users found.</td></tr>
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

<!-- Send Notification Modal -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);color:var(--text-color);border:1px solid var(--section-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--section-border);">
                <h5 class="modal-title">🔔 Send Notification to <span id="notifTargetName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="float-group mb-3">
                    <input type="text" id="notifTitleInput" placeholder=" ">
                    <label>Notification Title</label>
                </div>
                <div class="float-group mb-3">
                    <textarea id="notifBodyInput" rows="4" placeholder=" "></textarea>
                    <label>Message Body</label>
                </div>
                <button id="notifSendBtn" class="btn btn-success w-100 btn-disabled-faded" disabled>
                    Send Notification
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
(function() {
    const CSRF = window._csrfToken || ' . json_encode($csrf) . ';

    // ── Row click → User Details ──────────────────────────────
    document.querySelectorAll(".user-row").forEach(function(row) {
        row.addEventListener("mouseenter", function() { row.style.backgroundColor = "rgba(99,102,241,.06)"; });
        row.addEventListener("mouseleave", function() { row.style.backgroundColor = ""; });
        row.addEventListener("click", function(e) {
            if (e.target.closest("button, a, form, input")) return;
            window.location.href = "/Task(1)/admin/user-details.php?user_id=" + row.dataset.uid;
        });
    });

    function filterStatus(v) {
        var p = new URLSearchParams(window.location.search);
        if (v) p.set("status", v); else p.delete("status");
        p.delete("page");
        window.location.href = "?" + p.toString();
    }
    window.filterStatus = filterStatus;

    // ── Delete ────────────────────────────────────────────────
    document.querySelectorAll(".delete-user-btn").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var uid  = btn.dataset.uid;
            var name = btn.dataset.name;
            Swal.fire({
                title: "Delete " + name + "?",
                text: "This will permanently delete the user and all their data.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, Delete",
                cancelButtonText: "Cancel"
            }).then(async function(result) {
                if (!result.isConfirmed) return;
                var fd = new FormData();
                fd.append("action",   "delete_user");
                fd.append("user_id",  uid);
                fd.append("csrf_token", window._csrfToken || CSRF);
                var data = await fetchWithCsrfRetry("/Task(1)/admin/manage-users.php", { method: "POST", body: fd });
                if (data.success) {
                    var row = document.querySelector(".user-row[data-uid=\"" + uid + "\"]");
                    if (row) { row.style.transition = "opacity .3s"; row.style.opacity = "0"; setTimeout(function() { row.remove(); }, 300); }
                    showToast("User deleted", "success");
                } else {
                    showToast(data.message || "Error", "error");
                }
            });
        });
    });

    // ── Send Notification ─────────────────────────────────────
    var currentUid = null;
    document.querySelectorAll(".notif-btn").forEach(function(btn) {
        btn.addEventListener("click", function() {
            currentUid = btn.dataset.uid;
            document.getElementById("notifTargetName").textContent = btn.dataset.name;
            document.getElementById("notifTitleInput").value = "";
            document.getElementById("notifBodyInput").value = "";
            updateButtonState(document.getElementById("notifSendBtn"), false);
            new bootstrap.Modal(document.getElementById("notifModal")).show();
        });
    });

    ["notifTitleInput","notifBodyInput"].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener("input", function() {
            var t = document.getElementById("notifTitleInput").value.trim();
            var b = document.getElementById("notifBodyInput").value.trim();
            updateButtonState(document.getElementById("notifSendBtn"), t.length > 0 && b.length > 0);
        });
    });

    document.getElementById("notifSendBtn")?.addEventListener("click", async function() {
        var title = document.getElementById("notifTitleInput").value.trim();
        var body  = document.getElementById("notifBodyInput").value.trim();
        if (!title || !body || !currentUid) return;
        var fd = new FormData();
        fd.append("action",      "send_notification");
        fd.append("user_id",     currentUid);
        fd.append("notif_title", title);
        fd.append("notif_body",  body);
        fd.append("csrf_token",  window._csrfToken || CSRF);
        var data = await fetchWithCsrfRetry("/Task(1)/admin/manage-users.php", { method: "POST", body: fd });
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById("notifModal"))?.hide();
            showToast("Notification sent!", "success");
        } else {
            showToast(data.message || "Error", "error");
        }
    });
})();
</script>';
?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
