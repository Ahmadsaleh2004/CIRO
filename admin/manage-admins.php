<?php
/**
 * admin/manage-admins.php — المرحلة 6
 * حصراً لـ Role A
 */
ob_start();
$pageTitle = 'Manage Admins';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
require_once __DIR__ . '/../helpers/http_helper.php';

// قيد صارم بالكود — Role A فقط
if (!isRoleA()) {
    http_response_code(403);
    echo '<div class="container py-5 text-center"><h2>403 — This page is restricted to Role A only.</h2></div>';
    require_once __DIR__ . '/layout_end.php';
    exit;
}

$pdo = getDB();
$msg = $err = '';

// ══ إرسال إشعار جماعي (برودكاست) ═══════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['broadcast_notification'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $title = trim($_POST['broad_title'] ?? '');
    $body  = trim($_POST['broad_body'] ?? '');
    if ($title && $body) {
        $allUsers = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if ($allUsers) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, ?, ?, ?)");
            foreach ($allUsers as $uId) {
                $stmt->execute([$uId, $title, $body, $adminId]);
            }
            logAdminAction($adminId, 'broadcast_notification', 'system', 0, "Broadcast: {$title}");
            $msg = '✅ Broadcast notification sent to ' . count($allUsers) . ' users.';
        } else {
            $err = 'No users found to send the notification to.';
        }
    } else {
        $err = 'Please fill in all required fields.';
    }
}

// ══ إضافة أدمن جديد ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $s = $pdo->prepare("SELECT password FROM admins WHERE id=? LIMIT 1");
    $s->execute([$adminId]);
    $me = $s->fetch();


    if (!$me || !password_verify($_POST['confirm_current_pass'] ?? '', $me['password'])) {

        $err = 'Your current password is incorrect.';
    } else {
        $newEmail = trim(strtolower($_POST['new_email'] ?? ''));
        // Phase 14: فحص صيغة الإيميل — يجب @gmail.com حصراً
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || !str_ends_with($newEmail, '@gmail.com')) {
            $err = 'Email must be a @gmail.com address.';
        } else {
        $chk = $pdo->prepare("SELECT id FROM admins WHERE email=? LIMIT 1");
        $chk->execute([$newEmail]);
        if ($chk->fetch()) {
            $err = 'This email is already registered.';
        } else {
            $newPass = trim($_POST['new_password'] ?? '');
            if (!isStrongPassword($newPass)) {
                $err = 'Password must be at least 8 characters with uppercase, lowercase, number, and symbol.';
            } else {
            $hash    = password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12]);
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
            $msg = "✅ Admin ({$newEmail}) added successfully.";
            } // end isStrongPassword check
        } // end email duplicate check
    } // end email format check
} // end add_admin}
}

// ══ حذف أدمن ═════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $targetId = (int)($_POST['target_id'] ?? 0);
    $s = $pdo->prepare("SELECT password FROM admins WHERE id=? LIMIT 1");
    $s->execute([$adminId]);
    $me = $s->fetch();

    if (!$me || !password_verify($_POST['confirm_del_pass'] ?? '', $me['password'])) {
        $err = 'Incorrect password.';
    } elseif ($targetId === $adminId) {
        $err = 'You cannot delete your own account.';
    } else {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ($total <= 1) {
            $err = 'Cannot delete the last admin in the system.';
        } else {
            $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$targetId]);
            logAdminAction($adminId,'delete_admin','admin',$targetId);
            $msg = '✅ Admin deleted successfully.';
        }
    }
}

// ══ تحديث الصلاحيات ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_perms'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $targetId = (int)($_POST['target_id'] ?? 0);

    // تحقق من كلمة سر الأدمن الحالي قبل الحفظ (Phase 22-2)
    $sMe = $pdo->prepare("SELECT password FROM admins WHERE id=? LIMIT 1");
    $sMe->execute([$adminId]);
    $meRow = $sMe->fetch();
    if (!$meRow || !password_verify($_POST['confirm_edit_pass'] ?? '', $meRow['password'])) {
        $err = 'Incorrect password. Changes were not saved.';
    } else {
        $newRole = $_POST['edit_role'] ?? '';
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
        $msg = '✅ Permissions updated successfully.';
    }
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
    <div class="d-flex gap-2">
        <a href="/Task(1)/handlers/export_csv.php?type=admins" class="btn btn-success btn-sm btn-export-csv" target="_blank">📄 Export CSV</a>
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addAdminForm">+ Add Admin</button>
        <button class="btn btn-outline-info" data-bs-toggle="collapse" data-bs-target="#broadcastForm">📢 Broadcast Notification</button>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- ── فورم إرسال إشعار جماعي (برودكاست) ────────────────────── -->
<div class="collapse mb-4" id="broadcastForm">
<div class="card p-4">
    <h5 class="mb-3">📢 Broadcast Notification to All Users</h5>
    <form method="POST">
        <input type="hidden" name="broadcast_notification" value="1">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="row g-3">
            <div class="col-md-12">
                <div class="float-group">
                    <input type="text" id="broadTitle" name="broad_title" placeholder=" " required>
                    <label>Notification Title</label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="float-group">
                    <textarea id="broadBody" name="broad_body" rows="4" placeholder=" " required></textarea>
                    <label>Message Body</label>
                </div>
            </div>
        </div>
        <button id="broadSendBtn" type="submit" class="btn btn-info mt-3 btn-disabled-faded" disabled aria-disabled="true">Send Broadcast</button>
    </form>
</div>
</div>

<!-- ── فورم إضافة أدمن ─────────────────────────────────────── -->
<div class="collapse mb-4" id="addAdminForm">
<div class="card p-4">
    <h5 class="mb-3">Add New Admin</h5>
    <form method="POST">
        <input type="hidden" name="add_admin"  value="1">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="float-group"><input type="text"     id="newAdmName"     name="new_name"     placeholder=" " required><label>Full Name</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group"><input type="email"    id="newAdmEmail"    name="new_email"    placeholder=" " required><label>Email</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group"><input type="tel"      id="newAdmPhone"    name="new_phone"    placeholder=" "><label>Phone</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group"><input type="password" id="newAdmPassword" name="new_password" placeholder=" " required><label>Password</label></div>
            </div>
            <div class="col-md-4">
                <div class="float-group">
                    <select id="newAdmRole" name="new_role">
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
            <input type="password" id="newAdmCurrentPass" name="confirm_current_pass" placeholder=" " required>
            <label>Your Password (re-auth)</label>
        </div>
        <!-- الزر مخفي حتى تكتمل الحقول -->
        <button id="addAdminBtn" type="submit" class="btn btn-success" style="display:none;">Add Admin</button>
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
            <!-- Phase 22-2: كلمة سر تأكيد الهوية قبل حفظ التعديل -->
            <div class="float-group mb-3">
                <input type="password" name="confirm_edit_pass" id="confirm_edit_pass" placeholder=" " required>
                <label>Your Password (re-auth)</label>
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

<?php
$extraScripts = '<script>
function openPermModal(id,name,role,prod,users,dash,supp,cont,check,ord) {
    document.getElementById("permTargetId").value = id;
    document.getElementById("permModalTitle").textContent = "Edit: " + name;
    document.getElementById("permRole").value = role;
    document.getElementById("ep_products").checked  = !!prod;
    document.getElementById("ep_users").checked     = !!users;
    document.getElementById("ep_dashboard").checked = !!dash;
    document.getElementById("ep_support").checked   = !!supp;
    document.getElementById("ep_content").checked   = !!cont;
    document.getElementById("ep_checkout").checked  = !!check;
    document.getElementById("ep_orders").checked    = !!ord;
    document.getElementById("confirm_edit_pass").value = "";
    new bootstrap.Modal(document.getElementById("permModal")).show();
}
function openDeleteModal(id, name) {
    document.getElementById("delTargetId").value = id;
    document.getElementById("delAdminName").textContent = name;
    new bootstrap.Modal(document.getElementById("deleteAdminModal")).show();
}

// ── Add Admin Form — show button only when all fields filled ──
(function () {
    var name    = document.getElementById("newAdmName");
    var email   = document.getElementById("newAdmEmail");
    var pass    = document.getElementById("newAdmPassword");
    var current = document.getElementById("newAdmCurrentPass");
    var btn     = document.getElementById("addAdminBtn");
    if (!btn) return;

    function checkAdminForm() {
        var ok = name  && name.value.trim().length >= 2
              && email && /^[^\s@]+@gmail\.com$/.test(email.value.trim())
              && pass  && pass.value.length >= 6
              && current && current.value.length > 0;
        btn.style.display = ok ? "" : "none";
    }

    [name, email, pass, current].forEach(function(el) {
        if (el) el.addEventListener("input", checkAdminForm);
    });
    checkAdminForm();
})();

// ── Broadcast Form Validity ───────────────────────────────────
(function () {
    var title = document.getElementById("broadTitle");
    var body  = document.getElementById("broadBody");
    var btn   = document.getElementById("broadSendBtn");
    if (!title || !body || !btn) return;
    function check() {
        updateButtonState(btn, title.value.trim().length > 0 && body.value.trim().length > 0);
    }
    [title, body].forEach(function(el) { el.addEventListener("input", check); });
    check();
})();
</script>';
?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
