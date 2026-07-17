<?php
/**
 * admin/support.php — الجزء 12/18
 */
$pageTitle = 'Support Messages';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_support');
session_write_close();

$pdo = getDB();

// تحديد كل الرسائل كمقروءة عند فتح الصفحة
getDB()->query("UPDATE contact_messages SET is_notified=1");

// ── فلترة + Pagination ────────────────────────────────────────
$search      = trim($_GET['q'] ?? '');
$whereClause = '';
$queryParams = [];
if ($search !== '') {
    $whereClause   = " WHERE cm.full_name LIKE ? OR cm.email LIKE ? OR cm.message LIKE ? ";
    $queryParams   = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages cm $whereClause");
$countStmt->execute($queryParams);
$totalMessages = (int)$countStmt->fetchColumn();

$perPage     = 15;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;
$totalPages  = max(1, (int)ceil($totalMessages / $perPage));

$stmt = $pdo->prepare("
    SELECT cm.*, u.full_name AS user_name
    FROM contact_messages cm
    LEFT JOIN users u ON u.id = cm.user_id
    $whereClause
    ORDER BY cm.sent_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($queryParams);
$messages = $stmt->fetchAll();

$csrf = generateCsrfToken();
?>

<div class="admin-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1>💬 Support Messages <span class="badge bg-secondary ms-2"><?= $totalMessages ?></span></h1>
    <form class="d-flex search-form" method="GET" action="">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Search sender, email, text..."
               value="<?= htmlspecialchars($search) ?>" style="width:220px;">
        <button type="submit" class="btn btn-sm btn-outline-secondary ms-1">🔍</button>
        <?php if ($search): ?>
            <a href="/Task(1)/admin/support.php" class="btn btn-sm btn-outline-secondary ms-1">✕</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($messages)): ?>
<div class="text-center py-5">
    <div style="font-size:3rem;">📭</div>
    <p class="mt-2 text-muted">No messages yet.</p>
</div>
<?php else: ?>

<div id="messages-list">
<?php foreach ($messages as $m): ?>
<div class="card p-3 mb-3 support-msg-card"
     id="msg-card-<?= $m['id'] ?>"
     data-user-id="<?= $m['user_id'] ?: '' ?>"
     style="cursor:pointer;border:1px solid var(--section-border);border-radius:var(--card-radius);background:var(--card-bg);transition:box-shadow var(--dur-fast);">

    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($m['full_name']) ?></h5>
            <small class="text-muted">✉️ <?= htmlspecialchars($m['email']) ?></small>
            <?php if ($m['user_id']): ?>
                <span class="badge bg-success ms-2 small">Registered</span>
            <?php else: ?>
                <span class="badge bg-secondary ms-2 small">Guest</span>
            <?php endif; ?>
        </div>
        <small class="text-muted text-nowrap ms-3">
            📅 <?= date('d M Y, h:i A', strtotime($m['sent_at'])) ?>
        </small>
    </div>

    <p class="mb-3" style="color:var(--text-color);white-space:pre-wrap;"><?= nl2br(htmlspecialchars($m['message'])) ?></p>

    <div class="d-flex gap-2 justify-content-end">
        <?php if ($m['user_id']): ?>
        <button class="btn btn-sm btn-warning reply-btn"
                data-msg-id="<?= $m['id'] ?>"
                data-user-id="<?= $m['user_id'] ?>"
                data-user-name="<?= htmlspecialchars(addslashes($m['full_name'])) ?>">
            ↩️ Reply
        </button>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-danger delete-btn"
                data-msg-id="<?= $m['id'] ?>">
            🗑️ Delete
        </button>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination pagination-sm">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled':'' ?>">
            <a class="page-link" href="?page=<?= $currentPage-1 ?><?= $search ? '&q='.urlencode($search):'' ?>">‹</a>
        </li>
        <?php for ($i = max(1,$currentPage-2); $i <= min($totalPages,$currentPage+2); $i++): ?>
        <li class="page-item <?= $i===$currentPage?'active':'' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search):'' ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled':'' ?>">
            <a class="page-link" href="?page=<?= $currentPage+1 ?><?= $search ? '&q='.urlencode($search):'' ?>">›</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php
$extraScripts = '<script>
(function() {
    const CSRF    = window._csrfToken || ' . json_encode($csrf) . ';
    const HANDLER = "/Task(1)/handlers/support_handler.php";

    // ── Card click → User Details ────────────────────────────────
    document.querySelectorAll(".support-msg-card").forEach(function(card) {
        card.addEventListener("mouseenter", function() { card.style.boxShadow = "0 4px 12px var(--shadow-hover)"; });
        card.addEventListener("mouseleave", function() { card.style.boxShadow = ""; });
        card.addEventListener("click", function(e) {
            if (e.target.closest("button")) return;
            const uid = card.dataset.userId;
            if (uid) window.location.href = "/Task(1)/admin/user-details.php?user_id=" + uid;
        });
    });

    // ── Reply ────────────────────────────────────────────────────
    document.querySelectorAll(".reply-btn").forEach(function(btn) {
        btn.addEventListener("click", async function(e) {
            e.stopPropagation();
            const userId   = btn.dataset.userId;
            const userName = btn.dataset.userName;

            const result = await Swal.fire({
                title: "Reply to " + userName,
                input: "textarea",
                inputLabel: "Your reply (sent as notification)",
                inputPlaceholder: "Write your reply here...",
                showCancelButton: true,
                confirmButtonText: "Send Reply",
                confirmButtonColor: "#d97706",
                cancelButtonText: "Cancel",
                inputValidator: function(v) {
                    if (!v.trim()) return "Please write a reply first.";
                },
                didOpen: function() {
                    var confirmBtn = Swal.getConfirmButton();
                    var input      = Swal.getInput();
                    confirmBtn.disabled = true;
                    confirmBtn.classList.add("btn-disabled-faded");
                    input.addEventListener("input", function() {
                        var ok = input.value.trim().length > 0;
                        confirmBtn.disabled = !ok;
                        if (ok) confirmBtn.classList.remove("btn-disabled-faded");
                        else    confirmBtn.classList.add("btn-disabled-faded");
                    });
                }
            });

            if (!result.isConfirmed || !result.value) return;

            const fd = new FormData();
            fd.append("action",     "reply");
            fd.append("user_id",    userId);
            fd.append("reply_text", result.value.trim());
            fd.append("csrf_token", window._csrfToken || CSRF);

            const data = await fetchWithCsrfRetry(HANDLER, { method: "POST", body: fd });
            if (data.csrf_token) updateCsrfToken(data.csrf_token);
            if (data.success) {
                showToast("Reply sent successfully!", "success");
            } else {
                showToast(data.message || "Error sending reply", "error");
            }
        });
    });

    // ── Delete ───────────────────────────────────────────────────
    document.querySelectorAll(".delete-btn").forEach(function(btn) {
        btn.addEventListener("click", async function(e) {
            e.stopPropagation();
            const msgId = btn.dataset.msgId;

            const result = await Swal.fire({
                title: "Delete this message?",
                text: "This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, Delete",
                cancelButtonText: "Cancel"
            });
            if (!result.isConfirmed) return;

            const fd = new FormData();
            fd.append("action",     "delete");
            fd.append("message_id", msgId);
            fd.append("csrf_token", window._csrfToken || CSRF);

            const data = await fetchWithCsrfRetry(HANDLER, { method: "POST", body: fd });
            if (data.csrf_token) updateCsrfToken(data.csrf_token);
            if (data.success) {
                const card = document.getElementById("msg-card-" + msgId);
                if (card) {
                    card.style.transition = "opacity .3s";
                    card.style.opacity    = "0";
                    setTimeout(function() { card.remove(); }, 300);
                }
                showToast("Message deleted", "success");
            } else {
                showToast(data.message || "Error", "error");
            }
        });
    });
})();
</script>';
?>

<?php require_once __DIR__ . '/../admin/layout_end.php'; ?>
