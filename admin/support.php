<?php
$pageTitle = 'Support Messages';
require_once __DIR__ . '/../admin/layout.php';
requirePermission('can_manage_support');

$pdo = getDB();

// تحديد كل الرسائل كمقروءة عند فتح الصفحة
$pdo->query("UPDATE contact_messages SET is_notified=1");

$messages = $pdo->query("
    SELECT cm.*, u.full_name AS user_name
    FROM contact_messages cm
    LEFT JOIN users u ON u.id = cm.user_id
    ORDER BY cm.sent_at DESC
")->fetchAll();
?>

<div class="admin-page-header">
    <h1>💬 Support Messages</h1>
    <span class="badge bg-success fs-6"><?= count($messages) ?> total</span>
</div>

<?php if (empty($messages)): ?>
<div class="text-center py-5">
    <div style="font-size:3rem;">📭</div>
    <p class="mt-2" style="color:var(--placeholder-color);">No messages yet.</p>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($messages as $m): ?>
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <strong><?= htmlspecialchars($m['full_name']) ?></strong>
                    <?php if ($m['user_name']): ?>
                        <span class="badge bg-success ms-1 small">Registered</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-1 small">Guest</span>
                    <?php endif; ?>
                </div>
                <small style="color:var(--placeholder-color);white-space:nowrap;">
                    <?= date('d M Y, H:i', strtotime($m['sent_at'])) ?>
                </small>
            </div>
            <p class="small mb-2" style="color:var(--placeholder-color);">
                ✉️ <?= htmlspecialchars($m['email']) ?>
            </p>
            <p class="mb-0 small"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
