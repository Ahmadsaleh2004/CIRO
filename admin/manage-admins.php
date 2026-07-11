<?php
/**
 * admin/manage-admins.php — المرحلة 6
 * حصراً لـ Role A
 */
$pageTitle = 'Manage Admins';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';

// قيد صارم بالكود — Role A فقط
if (!isRoleA()) {
    http_response_code(403);
    echo '<div class="container py-5 text-center"><h2>403 — هذه الصفحة حصراً لصاحب Role A.</h2></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

$pdo = getDB();
$msg = $err = '';

// ══ إضافة أدمن جديد ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $s = $pdo->prepare("SELECT password FROM admins WHERE id=? LIMIT 1");
    $s->execute([$adminId]);
    $me = $s->fetch();


    if (!$me || !password_verify($_POST['confirm_current_pass'] ?? '', $me['password'])) {

        $err = 'كلمة سرك الحالية غير صحيحة — تأكد من الهوية.';
    } else {
        $newEmail = trim(strtolower($_POST['new_email'] ?? ''));
        $chk = $pdo->prepare("SELECT id FROM admins WHERE email=? LIMIT 1");
        $chk->execute([$newEmail]);
        if ($chk->fetch()) {
            $err = 'هذا الإيميل مسجّل مسبقاً.';
        } else {
            $hash    = password_hash($_POST['new_password'] ?? '', PASSWORD_BCRYPT, ['cost'=>12]);
            $newRole = $_POST['new_role'] ?? 'B';
            $ins = $pdo->prepare("INSERT INTO admins (full_name,email,password,phone_number,role,added_by) VALUES (?,?,?,?,?,?)");
            $ins->execute([
                trim($_POST['new_name']  ?? ''),
                $newEmail, $hash,
                trim($_POST['new_phone'] ?? '') ?: null,
                in_array($newRole,['A','B','C','D']) ? $newRole : 'B',
                $adminId,
            ]);
            $newId = (int)$pdo->lastInsertId();

            // صلاحيات
            $pdo->prepare("
                INSERT INTO admin_permissions
                    (admin_id,can_manage_admins,can_manage_products,can_manage_users,
                     can_view_dashboard,can_manage_support,can_edit_site_content,
                     can_manage_checkout_settings,can_manage_orders)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([
                $newId,
                empty($_POST['perm_admins'])    ? 0:1,
                empty($_POST['perm_products'])  ? 0:1,
                empty($_POST['perm_users'])     ? 0:1,
                empty($_POST['perm_dashboard']) ? 0:1,
                empty($_POST['perm_support'])   ? 0:1,
                empty($_POST['perm_content'])   ? 0:1,
                empty($_POST['perm_checkout'])  ? 0:1,
                empty($_POST['perm_orders'])    ? 0:1,
            ]);

            logAdminAction($adminId,'add_admin','admin',$newId,"added: {$newEmail} role:{$newRole}");
            $msg = "✅ تمت إضافة الأدمن ({$newEmail}).";
        }
    }
}

// ══ حذف أدمن ═════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $targetId = (int)($_POST['target_id'] ?? 0);
    $s = $pdo->prepare("SELECT password FROM admins WHERE id=? LIMIT 1");
    $s->execute([$adminId]);
    $me = $s->fetch();

    if (!$me || !password_verify($_POST['confirm_del_pass'] ?? '', $me['password'])) {
        $err = 'كلمة سرك غير صحيحة.';
    } elseif ($targetId === $adminId) {
        $err = 'لا يمكنك حذف نفسك.';
    } else {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ($total <= 1) {
            $err = 'لا يمكن حذف آخر أدمن في النظام.';
        } else {
            $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$targetId]);
            logAdminAction($adminId,'delete_admin','admin',$targetId);
            $msg = '✅ تم حذف الأدمن.';
        }
    }
}

// ══ تحديث الصلاحيات ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_perms'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $targetId = (int)($_POST['target_id'] ?? 0);
    $newRole  = $_POST['edit_role'] ?? '';
    if (in_array($newRole,['A','B','C','D'])) {
        $pdo->prepare("UPDATE admins SET role=? WHERE id=?")->execute([$newRole,$targetId]);
    }
    $pdo->prepare("
        UPDATE admin_permissions SET
            can_manage_admins=?,can_manage_products=?,can_manage_users=?,
            can_view_dashboard=?,can_manage_support=?,can_edit_site_content=?,
            can_manage_checkout_settings=?,can_manage_orders=?
        WHERE admin_id=?
    ")->execute([
        empty($_POST['perm_admins'])    ?0:1,
        empty($_POST['perm_products'])  ?0:1,
        empty($_POST['perm_users'])     ?0:1,
        empty($_POST['perm_dashboard']) ?0:1,
        empty($_POST['perm_support'])   ?0:1,
        empty($_POST['perm_content'])   ?0:1,
        empty($_POST['perm_checkout'])  ?0:1,
        empty($_POST['perm_orders'])    ?0:1,
        $targetId,
    ]);
    logAdminAction($adminId,'update_permissions','admin',$targetId,"role:{$newRole}");
    $msg = '✅ تم تحديث الصلاحيات.';
}

// ══ جلب قائمة الأدمنية ═══════════════════════════════════════
$admins = $pdo->query("
    SELECT a.*, ap.can_manage_admins, ap.can_manage_products, ap.can_manage_users,
           ap.can_view_dashboard, ap.can_manage_support, ap.can_edit_site_content,
           ap.can_manage_checkout_settings, ap.can_manage_orders
    FROM admins a
    LEFT JOIN admin_permissions ap ON ap.admin_id = a.id
    ORDER BY a.created_at ASC
")->fetchAll();
?>

<div class="admin-page-header">
    <h1>👑 Manage Admins</h1>
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addAdminForm">+ Add Admin</button>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ── فورم إضافة أدمن ─────────────────────────────────────── -->
<div class="collapse mb-4" id="addAdminForm">
<div class="card p-4">
    <h5 class="mb-3">Add New Admin</h5>
    <form method="POST">
        <input type="hidden" name="add_admin"  value="1">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="float-group"><input type="text"     name="new_name"     placeholder=" " required><label>Full Name</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group"><input type="email"    name="new_email"    placeholder=" " required><label>Email</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group"><input type="tel"      name="new_phone"    placeholder=" "><label>Phone</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group"><input type="password" name="new_password" placeholder=" " required><label>Password</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group">
                    <select name="new_role">
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="A">A (Super)</option>
                    </select>
                    <label>Role</label>
                </div>
            </div>
        </div>
        <h6 class="mt-3 mb-2">Permissions:</h6>
        <div class="perm-grid mb-3">
            <label class="perm-item"><input type="checkbox" name="perm_products"> Manage Products</label>
            <label class="perm-item"><input type="checkbox" name="perm_users">    Manage Users</label>
            <label class="perm-item"><input type="checkbox" name="perm_dashboard">View Dashboard</label>
            <label class="perm-item"><input type="checkbox" name="perm_support">  Support</label>
            <label class="perm-item"><input type="checkbox" name="perm_content">  Edit Site Content</label>
            <label class="perm-item"><input type="checkbox" name="perm_checkout"> Checkout Settings</label>
            <label class="perm-item"><input type="checkbox" name="perm_orders">   Manage Orders</label>
        </div>
        <div class="float-group" style="max-width:320px;">
            <input type="password" name="confirm_current_pass" placeholder=" " required>
            <label>Your Password (re-auth)</label>
        </div>
        <button type="submit" class="btn btn-success">Add Admin</button>
    </form>
</div>
</div>

<!-- ── جدول الأدمنية ───────────────────────────────────────── -->
<div class="card admin-table p-0">
<table class="table mb-0">
    <thead>
        <tr>
            <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
            <th>Role</th><th>Added</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($admins as $adm): ?>
    <tr>
        <td><?= $adm['id'] ?></td>
        <td><?= htmlspecialchars($adm['full_name']) ?></td>
        <td><?= htmlspecialchars($adm['email']) ?></td>
        <td><?= htmlspecialchars($adm['phone_number'] ?? '—') ?></td>
        <td>
            <span class="badge bg-<?= $adm['role']==='A'?'warning text-dark':'secondary' ?>">
                <?= $adm['role'] ?>
            </span>
        </td>
        <td><?= date('d M Y', strtotime($adm['created_at'])) ?></td>
        <td>
            <?php if ((int)$adm['id'] !== $adminId): ?>
            <button class="btn btn-sm btn-outline-primary me-1"
                onclick="openPermModal(
                    <?= $adm['id'] ?>,
                    '<?= htmlspecialchars(addslashes($adm['full_name'])) ?>',
                    '<?= $adm['role'] ?>',
                    <?= (int)($adm['can_manage_products']??0) ?>,
                    <?= (int)($adm['can_manage_users']??0) ?>,
                    <?= (int)($adm['can_view_dashboard']??0) ?>,
                    <?= (int)($adm['can_manage_support']??0) ?>,
                    <?= (int)($adm['can_edit_site_content']??0) ?>,
                    <?= (int)($adm['can_manage_checkout_settings']??0) ?>,
                    <?= (int)($adm['can_manage_orders']??0) ?>
                )">✏️ Edit</button>
            <button class="btn btn-sm btn-outline-danger"
                onclick="openDeleteModal(<?= $adm['id'] ?>,'<?= htmlspecialchars(addslashes($adm['full_name'])) ?>')">
                🗑 Delete
            </button>
            <?php else: ?>
            <span class="badge bg-info text-dark">You</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- ── Modal تعديل الصلاحيات ──────────────────────────────── -->
<div class="modal fade" id="permModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="permModalTitle">Edit Permissions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <form method="POST" id="permForm">
            <input type="hidden" name="update_perms" value="1">
            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="target_id"    id="permTargetId">
            <div class="float-group mb-3" style="max-width:200px;">
                <select name="edit_role" id="permRole">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
                <label>Role</label>
            </div>
            <div class="perm-grid mb-3">
                <label class="perm-item"><input type="checkbox" name="perm_products"  id="ep_products"> Manage Products</label>
                <label class="perm-item"><input type="checkbox" name="perm_users"     id="ep_users">    Manage Users</label>
                <label class="perm-item"><input type="checkbox" name="perm_dashboard" id="ep_dashboard">View Dashboard</label>
                <label class="perm-item"><input type="checkbox" name="perm_support"   id="ep_support">  Support</label>
                <label class="perm-item"><input type="checkbox" name="perm_content"   id="ep_content">  Site Content</label>
                <label class="perm-item"><input type="checkbox" name="perm_checkout"  id="ep_checkout"> Checkout</label>
                <label class="perm-item"><input type="checkbox" name="perm_orders"    id="ep_orders">   Manage Orders</label>
            </div>
            <button type="submit" class="btn btn-success">Save Permissions</button>
        </form>
    </div>
</div>
</div>
</div>

<!-- ── Modal تأكيد حذف ────────────────────────────────────── -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Delete Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <p>Delete <strong id="delAdminName"></strong>?</p>
        <form method="POST">
            <input type="hidden" name="delete_admin"    value="1">
            <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="target_id"       id="delTargetId">
            <div class="float-group">
                <input type="password" name="confirm_del_pass" placeholder=" " required>
                <label>Your Password (re-auth)</label>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
function openPermModal(id,name,role,prod,users,dash,supp,cont,check,ord) {
    document.getElementById('permTargetId').value = id;
    document.getElementById('permModalTitle').textContent = 'Edit: ' + name;
    document.getElementById('permRole').value = role;
    document.getElementById('ep_products').checked  = !!prod;
    document.getElementById('ep_users').checked     = !!users;
    document.getElementById('ep_dashboard').checked = !!dash;
    document.getElementById('ep_support').checked   = !!supp;
    document.getElementById('ep_content').checked   = !!cont;
    document.getElementById('ep_checkout').checked  = !!check;
    document.getElementById('ep_orders').checked    = !!ord;
    new bootstrap.Modal(document.getElementById('permModal')).show();
}
function openDeleteModal(id, name) {
    document.getElementById('delTargetId').value = id;
    document.getElementById('delAdminName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteAdminModal')).show();
}
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
