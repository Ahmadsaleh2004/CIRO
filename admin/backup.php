<?php
$pageTitle = 'Database Backup';
require_once __DIR__ . '/../admin/layout.php';

// Role A فقط
if (!isRoleA()) {
    http_response_code(403);
    echo '<div class="container py-5 text-center"><h2>403 — Role A only.</h2></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$result  = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_backup'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $filename = 'ciro_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $outPath  = $backupDir . $filename;

    $host = escapeshellarg(DB_HOST);
    $user = escapeshellarg(DB_USER);
    $db   = escapeshellarg(DB_NAME);
    $pass = DB_PASS ? '-p' . escapeshellarg(DB_PASS) : '';
    $out  = escapeshellarg($outPath);

    $cmd = "mysqldump -h {$host} -u {$user} {$pass} {$db} > {$out} 2>&1";

    // ── Session Locking Fix ────────────────────────────────────
    // نُحرّر قفل الجلسة قبل تنفيذ mysqldump لأنه قد يستغرق ثوانٍ،
    // وذلك لتفادي تجمّد باقي تبويبات/طلبات نفس الأدمن.
    session_write_close();

    exec($cmd, $cmdOut, $returnCode);

    if ($returnCode === 0 && file_exists($outPath) && filesize($outPath) > 0) {
        $success = true;
        $result  = "✅ Backup saved: backups/{$filename} (" . round(filesize($outPath)/1024,1) . " KB)";
    } else {
        $result = "❌ Backup failed. Make sure mysqldump is in PATH.\n" . implode("\n", $cmdOut);
    }
}

$backups = array_reverse(glob($backupDir . '*.sql') ?: []);
?>

<div class="admin-page-header">
    <h1>💾 Database Backup</h1>
</div>

<?php if ($result): ?>
<div class="alert alert-<?= $success?'success':'danger' ?>" style="white-space:pre-line;">
    <?= htmlspecialchars($result) ?>
</div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <h5 class="mb-2">Create Backup</h5>
    <p class="small mb-3" style="color:var(--placeholder-color);">
        Runs <code>mysqldump</code> on <code><?= DB_NAME ?></code> and saves to <code>backups/</code>.
    </p>
    <form method="POST">
        <input type="hidden" name="run_backup"  value="1">
        <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
        <button type="submit" class="btn btn-success">▶️ Run Backup Now</button>
    </form>
</div>

<?php if (!empty($backups)): ?>
<div class="card p-0 admin-table">
    <div class="p-3 border-bottom"><h5 class="mb-0">Existing Backups (<?= count($backups) ?>)</h5></div>
    <table class="table mb-0">
        <thead><tr><th>File</th><th>Size</th><th>Date</th></tr></thead>
        <tbody>
            <?php foreach ($backups as $bf): ?>
            <tr>
                <td><?= htmlspecialchars(basename($bf)) ?></td>
                <td><?= round(filesize($bf)/1024,1) ?> KB</td>
                <td><?= date('d M Y H:i', filemtime($bf)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
