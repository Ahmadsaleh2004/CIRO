<?php
/**
 * pages/admin-reauth.php
 * تأكيد هوية الأدمن قبل الرجوع للوحة التحكم من المتجر
 */
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

if (!isAdmin()) {
    header('Location: /Task(1)/index.php');
    exit;
}

// إذا لم يكن في store mode — لا داعي للصفحة
if (empty($_SESSION['admin_in_store_mode'])) {
    $dest = $_GET['redirect'] ?? '/Task(1)/admin/support.php';
    header('Location: ' . $dest);
    exit;
}

$redirect  = $_GET['redirect'] ?? '/Task(1)/admin/support.php';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$csrf      = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Identity | Cairo Store Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
    <style>
        body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg-color); }
        .reauth-card { max-width:420px; width:100%; padding:2.5rem;
                       border-radius:16px; background:var(--card-bg);
                       box-shadow:0 8px 32px var(--shadow-color);
                       border:1px solid var(--section-border); }
    </style>
</head>
<body>
<div class="reauth-card">
    <div class="text-center mb-4">
        <span style="font-size:3rem;">🔐</span>
        <h4 class="fw-bold mt-2">Confirm Your Identity</h4>
        <p class="text-muted small">
            Hi <strong><?= htmlspecialchars($adminName) ?></strong>,
            enter your password to return to the Admin Panel.
        </p>
    </div>

    <div id="reauthError" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>

    <div class="float-group mb-3">
        <input type="password" id="reauthPass" class="form-control"
               placeholder=" " autocomplete="current-password">
        <label>Admin Password</label>
    </div>

    <button id="reauthBtn" class="btn btn-warning w-100 fw-bold">
        🔓 Confirm &amp; Return to Admin Panel
    </button>

    <div class="text-center mt-3">
        <a href="/Task(1)/index.php" class="small text-muted">← Stay in Store</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Task(1)/js/helpers.js"></script>
<script>
(function () {
    applySavedTheme();

    const btn    = document.getElementById('reauthBtn');
    const passEl = document.getElementById('reauthPass');
    const errEl  = document.getElementById('reauthError');
    const CSRF   = <?= json_encode($csrf) ?>;
    const REDIR  = <?= json_encode($redirect) ?>;

    window._csrfToken = CSRF;

    btn.addEventListener('click', async () => {
        const pass = passEl.value;
        if (!pass) { passEl.focus(); return; }

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
        errEl.style.display = 'none';

        const fd = new FormData();
        fd.append('password',   pass);
        fd.append('redirect',   REDIR);
        fd.append('csrf_token', CSRF);

        const data = await fetchWithCsrfRetry(
            '/Task(1)/handlers/admin_reauth.php',
            { method: 'POST', body: fd }
        );

        if (data.success) {
            window.location.href = REDIR;
        } else {
            errEl.textContent   = data.message || 'Incorrect password.';
            errEl.style.display = 'block';
            btn.disabled        = false;
            btn.innerHTML       = '🔓 Confirm &amp; Return to Admin Panel';
            passEl.value        = '';
            passEl.focus();
        }
    });

    passEl.addEventListener('keydown', e => {
        if (e.key === 'Enter') btn.click();
    });
})();
</script>
</body>
</html>
