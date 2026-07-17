<?php
/**
 * admin/user-details.php — الجزء 11/18
 * صفحة تفاصيل المستخدم: بيانات + إنذارات + طلبات + رسائل
 */
$pageTitle = 'User Details';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_users');
session_write_close();

$pdo    = getDB();
$userId = (int)($_GET['user_id'] ?? 0);

if (!$userId) {
    echo '<div class="container py-5 text-center"><h2>No user specified.</h2><a href="/Task(1)/admin/manage-users.php" class="btn btn-secondary">← Back to Users</a></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    echo '<div class="container py-5 text-center"><h2>User not found.</h2><a href="/Task(1)/admin/manage-users.php" class="btn btn-secondary">← Back to Users</a></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

// ── Strikes ───────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM user_strikes WHERE user_id = ? ORDER BY created_at ASC LIMIT 3");
$stmt->execute([$userId]);
$strikes      = $stmt->fetchAll();
$strikesCount = count($strikes);
$isBlocked    = $strikesCount >= 3;

// ── Orders ────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// ── Messages ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM contact_messages
    WHERE user_id = ? OR email = ?
    ORDER BY sent_at DESC
");
$stmt->execute([$userId, $user['email']]);
$messages = $stmt->fetchAll();

$csrf = generateCsrfToken();
?>

<style>
/* ── Strike Button ── */
.strike-btn {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: 2px solid var(--section-border);
    background: transparent;
    color: var(--placeholder-color);
    font-size: 1.3rem;
    cursor: pointer;
    transition: all var(--dur-base) var(--ease-bounce);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.strike-btn:hover {
    border-color: #dc2626;
    background: rgba(220,38,38,.08);
    color: #dc2626;
}
.strike-btn.active {
    border-color: #dc2626;
    background: #dc2626;
    color: #fff;
    box-shadow: 0 0 12px rgba(220,38,38,.4);
}
.strike-btn.active:hover {
    background: #b91c1c;
}
.strike-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.85rem 0;
    border-bottom: 1px solid var(--section-border);
}
.strike-row:last-child { border-bottom: none; }
.strike-reason {
    font-size: .88rem;
    color: var(--text-color);
    line-height: 1.5;
}
.strike-reason .reason-label { font-weight: 700; color: #dc2626; }
.strike-reason .reason-date  { font-size: .75rem; color: var(--placeholder-color); margin-top: 2px; }
</style>

<!-- Back button -->
<div class="mb-3">
    <a href="/Task(1)/admin/manage-users.php" class="btn btn-outline-secondary btn-sm">← Back to Users</a>
</div>

<!-- ══ 1. User Info ══════════════════════════════════════════════════ -->
<div class="card p-4 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-3">
                👤 <?= htmlspecialchars($user['full_name']) ?>
                <?php if ($isBlocked): ?>
                <span class="badge bg-danger ms-2">⛔ Blocked</span>
                <?php else: ?>
                <span class="badge bg-success ms-2">✅ Active</span>
                <?php endif; ?>
            </h2>
            <div class="row g-2">
                <div class="col-sm-6">
                    <span class="text-muted small">Email</span><br>
                    <strong><?= htmlspecialchars($user['email']) ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Phone</span><br>
                    <strong><?= htmlspecialchars($user['phone_number'] ?? '—') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Joined</span><br>
                    <strong><?= date('d M Y', strtotime($user['created_at'])) ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Last Activity</span><br>
                    <strong><?= $user['last_activity'] ? date('d M Y, H:i', strtotime($user['last_activity'])) : '—' ?></strong>
                </div>
                <?php if ($user['gender']): ?>
                <div class="col-sm-6">
                    <span class="text-muted small">Gender</span><br>
                    <strong><?= ucfirst($user['gender']) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($user['country'] || $user['city']): ?>
                <div class="col-sm-6">
                    <span class="text-muted small">Location</span><br>
                    <strong><?= htmlspecialchars(trim(($user['city'] ?? '') . ', ' . ($user['country'] ?? ''), ', ')) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="fs-3 fw-bold <?= $isBlocked ? 'text-danger' : 'text-success' ?>">
                <?= $strikesCount ?>/3 Strikes
            </div>
            <small class="text-muted"><?= $isBlocked ? 'Account suspended' : 'Account in good standing' ?></small>
        </div>
    </div>
</div>

<!-- ══ 2. Strikes ════════════════════════════════════════════════════ -->
<div class="card p-4 mb-4">
    <h4 class="fw-bold mb-3">⚠️ Account Strikes</h4>

    <div id="strikesContainer">
    <?php for ($i = 1; $i <= 3; $i++):
        $strike = $strikes[$i - 1] ?? null;
        $active = !empty($strike);
    ?>
    <div class="strike-row" id="strike-row-<?= $i ?>">
        <button
            class="strike-btn <?= $active ? 'active' : '' ?>"
            data-index="<?= $i ?>"
            data-strike-id="<?= $active ? (int)$strike['id'] : 0 ?>"
            data-active="<?= $active ? '1' : '0' ?>"
            onclick="handleStrikeClick(this)"
            title="<?= $active ? 'Click to remove this strike' : 'Click to add a strike' ?>">
            <?= $active ? '❌' : $i ?>
        </button>
        <div class="strike-reason">
            <?php if ($active): ?>
                <div class="reason-label">Strike #<?= $i ?></div>
                <div><?= htmlspecialchars($strike['reason']) ?></div>
                <div class="reason-date"><?= date('d M Y, H:i', strtotime($strike['created_at'])) ?></div>
            <?php else: ?>
                <span class="text-muted">Strike #<?= $i ?> — No warning issued</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endfor; ?>
    </div>
</div>

<!-- ══ 3. Orders ═════════════════════════════════════════════════════ -->
<div class="card p-4 mb-4">
    <h4 class="fw-bold mb-3">📦 Order History (<?= count($orders) ?>)</h4>
    <?php if (empty($orders)): ?>
        <p class="text-muted">No orders placed by this user.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Order ID</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o):
                $sc = match($o['status']) {
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'taken'     => 'primary',
                    default     => 'warning text-dark'
                };
            ?>
                <tr>
                    <td><a href="/Task(1)/admin/order-details.php?id=<?= $o['order_id'] ?>" class="fw-semibold">#<?= $o['order_id'] ?></a></td>
                    <td><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                    <td>$<?= number_format($o['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($o['payment_method']) ?></td>
                    <td><span class="badge bg-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ══ 4. Messages ═══════════════════════════════════════════════════ -->
<div class="card p-4 mb-4">
    <h4 class="fw-bold mb-3">💬 Support Messages (<?= count($messages) ?>)</h4>
    <?php if (empty($messages)): ?>
        <p class="text-muted">No messages sent by this user.</p>
    <?php else: ?>
    <?php foreach ($messages as $m): ?>
    <div class="border rounded p-3 mb-2">
        <div class="d-flex justify-content-between mb-1">
            <strong class="small">Message #<?= $m['id'] ?></strong>
            <small class="text-muted"><?= date('d M Y, H:i', strtotime($m['sent_at'])) ?></small>
        </div>
        <p class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($m['message']) ?></p>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$extraScripts = '<script>
(function() {
    const USER_ID = ' . $userId . ';
    const HANDLER = "/Task(1)/handlers/strikes_handler.php";
    let currentStrikes = ' . $strikesCount . ';

    window.handleStrikeClick = async function(btn) {
        const index    = parseInt(btn.dataset.index);
        const isActive = btn.dataset.active === "1";
        const strikeId = parseInt(btn.dataset.strikeId);

        if (isActive) {
            // حذف إنذار
            const result = await Swal.fire({
                title: "Remove Strike #" + index + "?",
                text: "This will remove the strike from the user\'s record.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                confirmButtonText: "Yes, Remove",
                cancelButtonText: "Cancel"
            });
            if (!result.isConfirmed) return;

            const fd = new FormData();
            fd.append("action",    "remove_strike");
            fd.append("user_id",   USER_ID);
            fd.append("strike_id", strikeId);
            fd.append("csrf_token", window._csrfToken || "");

            const data = await fetchWithCsrfRetry(HANDLER, { method: "POST", body: fd });
            if (!data.success) { showToast(data.message || "Error", "error"); return; }
            if (data.csrf_token) updateCsrfToken(data.csrf_token);

            // تحديث DOM
            btn.classList.remove("active");
            btn.dataset.active   = "0";
            btn.dataset.strikeId = "0";
            btn.textContent      = index;
            btn.title            = "Click to add a strike";
            const reasonDiv = btn.closest(".strike-row").querySelector(".strike-reason");
            if (reasonDiv) reasonDiv.innerHTML = \'<span class="text-muted">Strike #\' + index + \' — No warning issued</span>\';
            currentStrikes--;
            updateStatusBadge();
            showToast("Strike removed", "success");
            // reload خفيف بعد ثانية لإعادة ترتيب الإنذارات
            setTimeout(() => location.reload(), 900);

        } else {
            // إضافة إنذار
            const result = await Swal.fire({
                title: "Issue Strike #" + index,
                input: "textarea",
                inputLabel: "Reason for strike (will be sent as notification)",
                inputPlaceholder: "Enter reason...",
                showCancelButton: true,
                confirmButtonText: "Issue Strike",
                confirmButtonColor: "#dc2626",
                cancelButtonText: "Cancel",
                inputValidator: v => !v.trim() ? "Please enter a reason!" : null
            });
            if (!result.isConfirmed || !result.value) return;

            const fd = new FormData();
            fd.append("action",    "add_strike");
            fd.append("user_id",   USER_ID);
            fd.append("reason",    result.value);
            fd.append("csrf_token", window._csrfToken || "");

            const data = await fetchWithCsrfRetry(HANDLER, { method: "POST", body: fd });
            if (!data.success) { showToast(data.message || "Error", "error"); return; }
            if (data.csrf_token) updateCsrfToken(data.csrf_token);

            // تحديث DOM
            btn.classList.add("active");
            btn.dataset.active   = "1";
            btn.dataset.strikeId = data.strike_id || "0";
            btn.innerHTML        = "❌";
            btn.title            = "Click to remove this strike";
            const reasonDiv = btn.closest(".strike-row").querySelector(".strike-reason");
            if (reasonDiv) {
                reasonDiv.innerHTML =
                    \'<div class="reason-label">Strike #\' + index + \'</div>\' +
                    \'<div>\' + escHtml(result.value) + \'</div>\' +
                    \'<div class="reason-date">\' + data.created_at + \'</div>\';
            }
            currentStrikes++;
            updateStatusBadge();
            showToast("Strike issued and user notified", "success");
        }
    };

    function updateStatusBadge() {
        const badge    = document.querySelector("h2 .badge");
        const subtext  = document.querySelector(".col-md-4 small");
        const counter  = document.querySelector(".fs-3.fw-bold");
        if (counter) counter.textContent = currentStrikes + "/3 Strikes";
        if (badge) {
            if (currentStrikes >= 3) {
                badge.className = "badge bg-danger ms-2";
                badge.textContent = "⛔ Blocked";
                if (subtext) subtext.textContent = "Account suspended";
                if (counter) { counter.className = "fs-3 fw-bold text-danger"; }
            } else {
                badge.className = "badge bg-success ms-2";
                badge.textContent = "✅ Active";
                if (subtext) subtext.textContent = "Account in good standing";
                if (counter) { counter.className = "fs-3 fw-bold text-success"; }
            }
        }
    }

    function escHtml(str) {
        return str.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    }
})();
</script>';
?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
