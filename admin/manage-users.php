<?php
/**
 * admin/manage-users.php — المرحلة 8
 */
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_users');

$pdo = getDB();
$msg = '';

// ── تصدير CSV ────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query("SELECT full_name,email,country,last_activity,created_at FROM users ORDER BY last_activity DESC")->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output','w');
    fputcsv($f,['Full Name','Email','Country','Last Activity','Joined']);
    foreach ($rows as $r) fputcsv($f,[$r['full_name'],$r['email'],$r['country'],$r['last_activity'],$r['created_at']]);
    fclose($f);
    exit;
}

// ── حذف مستخدم ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        logAdminAction($adminId,'delete_user','user',$uid);
        $msg = '✅ تم حذف المستخدم.';
    }
}

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$users = $pdo->query("SELECT * FROM users ORDER BY last_activity DESC")->fetchAll();
?>

<div class="admin-page-header">
    <h1>👥 Manage Users <span class="badge bg-secondary ms-2"><?= $totalUsers ?></span></h1>
    <a href="?export=csv" class="btn btn-outline-success btn-sm">📥 Export CSV</a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card admin-table p-0">
<table class="table mb-0">
    <thead>
        <tr>
            <th>#</th><th>Name</th><th>Email</th><th>Country</th>
            <th>Last Activity</th><th>Joined</th><th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['full_name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['country'] ?? '—') ?></td>
        <td><?= $u['last_activity'] ? date('d M Y, H:i', strtotime($u['last_activity'])) : '—' ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
            <form method="POST"
                  onsubmit="return confirm('Delete user: <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                <input type="hidden" name="delete_user" value="1">
                <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <button class="btn btn-sm btn-outline-danger">🗑 Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/layout_end.php'; ?>
