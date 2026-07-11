<?php
/**
 * pages/my-info.php — المرحلة 4
 * 3 أقسام للمستخدم: بياناتي | طلباتي | عناويني
 * + قسم 4 للأدمن Role A: تغيير الرتبة
 */

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';

requireLogin();

$pdo     = getDB();
$isAdm   = isAdmin();
$userId  = getCurrentUserId();
$adminId = getCurrentAdminId();

// ── جلب البيانات ─────────────────────────────────────────────
if ($isAdm) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$adminId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    updateUserActivity();
}
$profile = $stmt->fetch();

$updateMsg = '';
$updateErr = '';
$addrMsg   = '';

// ── تحديث البيانات الشخصية ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $currentPass = $_POST['current_password'] ?? '';

    if (!password_verify($currentPass, $profile['password'])) {
        $updateErr = 'كلمة السر الحالية غير صحيحة.';
    } else {
        if ($isAdm) {
            $pdo->prepare("UPDATE admins SET full_name=?, phone_number=? WHERE id=?")
                ->execute([trim($_POST['full_name']), trim($_POST['phone_number']), $adminId]);
        } else {
            $pdo->prepare("
                UPDATE users
                SET full_name=?, phone_number=?, country=?, city=?, gender=?, birth_date=?
                WHERE id=?
            ")->execute([
                trim($_POST['full_name']),
                trim($_POST['phone_number']) ?: null,
                trim($_POST['country'])      ?: null,
                trim($_POST['city'])         ?: null,
                $_POST['gender']             ?? null,
                $_POST['birth_date']         ?: null,
                $userId,
            ]);
        }
        $updateMsg = '✅ تم تحديث بياناتك بنجاح.';
        $_SESSION[$isAdm ? 'admin_name' : 'user_name'] = trim($_POST['full_name']);
        // أعد جلب البيانات المحدّثة
        $stmt->execute([$isAdm ? $adminId : $userId]);
        $profile = $stmt->fetch();
    }
}

// ── تغيير رتبة الأدمن (Role A فقط من My Info) ───────────────
if ($isAdm && isRoleA() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_own_role'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $confirmPass = $_POST['confirm_pass'] ?? '';
    $newRole     = $_POST['new_role']     ?? '';

    if (!password_verify($confirmPass, $profile['password'])) {
        $updateErr = 'كلمة السر غير صحيحة.';
    } elseif (!in_array($newRole, ['A','B','C','D'])) {
        $updateErr = 'رتبة غير صالحة.';
    } else {
        $pdo->prepare("UPDATE admins SET role=? WHERE id=?")->execute([$newRole, $adminId]);
        $_SESSION['admin_role'] = $newRole;
        logAdminAction($adminId, 'change_role', 'admin', $adminId, "changed own role to {$newRole}");
        $updateMsg = "تم تغيير رتبتك إلى {$newRole}.";
    }
}

// ── إضافة عنوان ──────────────────────────────────────────────
if (!$isAdm && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    if (!empty($_POST['is_default'])) {
        $pdo->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=?")->execute([$userId]);
    }
    $pdo->prepare("
        INSERT INTO user_addresses (user_id,label,country,city,full_address,phone_number,is_default)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([
        $userId,
        trim($_POST['addr_label'])   ?: 'Home',
        trim($_POST['addr_country']) ?: null,
        trim($_POST['addr_city'])    ?: null,
        trim($_POST['full_address']),
        trim($_POST['addr_phone'])   ?: null,
        !empty($_POST['is_default']) ? 1 : 0,
    ]);
    $addrMsg = '✅ تمت إضافة العنوان.';
}

// ── حذف عنوان ────────────────────────────────────────────────
if (!$isAdm && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $pdo->prepare("DELETE FROM user_addresses WHERE id=? AND user_id=?")
        ->execute([(int)$_POST['addr_id'], $userId]);
    $addrMsg = '✅ تم حذف العنوان.';
}

// ── تعيين عنوان افتراضي ──────────────────────────────────────
if (!$isAdm && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $pdo->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=?")->execute([$userId]);
    $pdo->prepare("UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?")
        ->execute([(int)$_POST['addr_id'], $userId]);
    $addrMsg = '✅ تم تعيين العنوان الافتراضي.';
}

// ── جلب الطلبات ──────────────────────────────────────────────
$orders = [];
if (!$isAdm && $userId) {
    $stmt2 = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
    $stmt2->execute([$userId]);
    $orders = $stmt2->fetchAll();
    foreach ($orders as &$ord) {
        $s = $pdo->prepare("
            SELECT oi.*, p.name, p.image_path
            FROM order_items oi JOIN products p ON p.id=oi.product_id
            WHERE oi.order_id=?
        ");
        $s->execute([$ord['order_id']]);
        $ord['items'] = $s->fetchAll();
    }
    unset($ord);
}

// ── جلب العناوين ─────────────────────────────────────────────
$addresses = [];
if (!$isAdm && $userId) {
    $stmt3 = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
    $stmt3->execute([$userId]);
    $addresses = $stmt3->fetchAll();
}

$csrf = generateCsrfToken();
$statusColors = ['pending'=>'warning','shipped'=>'primary','completed'=>'success','cancelled'=>'danger'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Info | Cairo Store</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
    <style>
        .info-tab-btn { color:var(--text-color)!important; border:1px solid var(--section-border); border-radius:8px; padding:8px 18px; background:var(--card-bg); cursor:pointer; transition:.2s; }
        .info-tab-btn.active { background:var(--accent)!important; color:#fff!important; border-color:var(--accent); }
        .order-card { border:1px solid var(--section-border); border-radius:12px; padding:16px; margin-bottom:14px; }
        .addr-card { border:2px solid var(--section-border); border-radius:12px; padding:14px; position:relative; }
        .addr-card.default { border-color:var(--accent); }
    </style>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
<?php include '../components/navbar.php'; ?>

<main id="main-content" class="container py-5">
    <div class="row justify-content-center">
    <div class="col-lg-10">

        <h1 class="section-title mb-4">👤 My Info</h1>

        <!-- Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-4" role="tablist">
            <button class="info-tab-btn active" onclick="switchTab('profile',this)">📋 Personal Info</button>
            <?php if (!$isAdm): ?>
            <button class="info-tab-btn" onclick="switchTab('orders',this)">📦 My Orders</button>
            <button class="info-tab-btn" onclick="switchTab('addresses',this)">📍 Saved Addresses</button>
            <?php endif; ?>
            <?php if ($isAdm && isRoleA()): ?>
            <button class="info-tab-btn" onclick="switchTab('adminrole',this)">👑 Admin Settings</button>
            <?php endif; ?>
        </div>

        <!-- ════ Tab: Personal Info ════════════════════════════ -->
        <div id="tab-profile" class="tab-section active-tab">
            <div class="card p-4">
                <h4 class="mb-4">Personal Information</h4>

                <?php if ($updateMsg): ?><div class="alert alert-success"><?= htmlspecialchars($updateMsg) ?></div><?php endif; ?>
                <?php if ($updateErr): ?><div class="alert alert-danger"><?= htmlspecialchars($updateErr) ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <input type="hidden" name="csrf_token"    value="<?= htmlspecialchars($csrf) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="float-group">
                                <input type="text" name="full_name" placeholder=" " required
                                       value="<?= htmlspecialchars($profile['full_name']) ?>">
                                <label>Full Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Email: Read-only دائماً -->
                            <div class="float-group">
                                <input type="email" placeholder=" " readonly
                                       value="<?= htmlspecialchars($profile['email']) ?>"
                                       style="opacity:.6;cursor:not-allowed;">
                                <label>Email (Read-only)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="float-group">
                                <input type="tel" name="phone_number" placeholder=" "
                                       value="<?= htmlspecialchars($profile['phone_number'] ?? '') ?>">
                                <label>Phone Number</label>
                            </div>
                        </div>

                        <?php if (!$isAdm): ?>
                        <div class="col-md-6">
                            <div class="float-group">
                                <select name="gender">
                                    <option value="male"   <?= ($profile['gender']??'')==='male'  ?'selected':'' ?>>Male</option>
                                    <option value="female" <?= ($profile['gender']??'')==='female'?'selected':'' ?>>Female</option>
                                </select>
                                <label>Gender</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="float-group">
                                <input type="text" name="country" placeholder=" "
                                       value="<?= htmlspecialchars($profile['country'] ?? '') ?>">
                                <label>Country</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="float-group">
                                <input type="text" name="city" placeholder=" "
                                       value="<?= htmlspecialchars($profile['city'] ?? '') ?>">
                                <label>City</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="float-group">
                                <input type="date" name="birth_date" placeholder=" "
                                       value="<?= htmlspecialchars($profile['birth_date'] ?? '') ?>">
                                <label>Birth Date</label>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="float-group">
                                <input type="password" name="current_password" placeholder=" " required>
                                <label>Current Password (required to save)</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success px-5 mt-2">💾 Save Changes</button>
                </form>
            </div>
        </div><!-- /tab-profile -->

        <!-- ════ Tab: My Orders ════════════════════════════════ -->
        <?php if (!$isAdm): ?>
        <div id="tab-orders" class="tab-section" style="display:none;">
            <div class="card p-4">
                <h4 class="mb-4">My Orders</h4>
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <div style="font-size:4rem;">📭</div>
                        <h5 class="mt-3">No orders yet</h5>
                        <a href="/Task(1)/pages/products.php" class="btn btn-success mt-3">Browse Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                    <div class="order-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Order #<?= $o['order_id'] ?></strong>
                            <span class="badge bg-<?= $statusColors[$o['status']] ?? 'secondary' ?>">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </div>
                        <p class="small mb-2" style="color:var(--placeholder-color);">
                            📅 <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                            &nbsp;|&nbsp; 💳 <?= htmlspecialchars($o['payment_method']) ?>
                        </p>
                        <?php foreach ($o['items'] as $item): ?>
                        <div class="d-flex justify-content-between small py-1 border-bottom"
                             style="border-color:var(--section-border)!important;">
                            <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                            <span>$<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between fw-bold mt-2">
                            <span>Total:</span>
                            <span>$<?= number_format($o['total_amount'], 2) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div><!-- /tab-orders -->

        <!-- ════ Tab: Saved Addresses ══════════════════════════ -->
        <div id="tab-addresses" class="tab-section" style="display:none;">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Saved Addresses</h4>
                    <button class="btn btn-success btn-sm"
                        data-bs-toggle="collapse" data-bs-target="#addAddrForm">+ Add Address</button>
                </div>

                <?php if ($addrMsg): ?><div class="alert alert-success"><?= htmlspecialchars($addrMsg) ?></div><?php endif; ?>

                <!-- فورم إضافة عنوان -->
                <div class="collapse mb-4" id="addAddrForm">
                    <div class="card card-body">
                        <form method="POST">
                            <input type="hidden" name="add_address" value="1">
                            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="float-group">
                                        <input type="text" name="addr_label" placeholder=" " value="Home">
                                        <label>Label</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="float-group">
                                        <input type="text" name="addr_country" placeholder=" ">
                                        <label>Country</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="float-group">
                                        <input type="text" name="addr_city" placeholder=" ">
                                        <label>City</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="float-group">
                                        <textarea name="full_address" rows="2" placeholder=" " required></textarea>
                                        <label>Full Address</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="float-group">
                                        <input type="tel" name="addr_phone" placeholder=" ">
                                        <label>Phone</label>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="is_default" id="isDefaultNew">
                                        <label class="form-check-label" for="isDefaultNew">Set as default</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-2">Save Address</button>
                        </form>
                    </div>
                </div>

                <!-- قائمة العناوين -->
                <?php if (empty($addresses)): ?>
                    <p class="text-center" style="color:var(--placeholder-color);">No saved addresses yet.</p>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($addresses as $addr): ?>
                    <div class="col-md-6">
                        <div class="addr-card <?= $addr['is_default'] ? 'default' : '' ?>">
                            <?php if ($addr['is_default']): ?>
                                <span class="badge bg-success position-absolute" style="top:10px;right:10px;">Default</span>
                            <?php endif; ?>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($addr['label']) ?></h6>
                            <p class="small mb-1"><?= htmlspecialchars($addr['full_address']) ?></p>
                            <p class="small mb-2" style="color:var(--placeholder-color);">
                                <?= htmlspecialchars(trim(($addr['city']?$addr['city'].', ':'').($addr['country']??''))) ?>
                            </p>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if (!$addr['is_default']): ?>
                                <form method="POST">
                                    <input type="hidden" name="set_default"  value="1">
                                    <input type="hidden" name="addr_id"      value="<?= $addr['id'] ?>">
                                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf) ?>">
                                    <button class="btn btn-sm btn-outline-success">⭐ Set Default</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this address?')">
                                    <input type="hidden" name="delete_address" value="1">
                                    <input type="hidden" name="addr_id"        value="<?= $addr['id'] ?>">
                                    <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf) ?>">
                                    <button class="btn btn-sm btn-outline-danger">🗑 Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div><!-- /tab-addresses -->
        <?php endif; ?>

        <!-- ════ Tab: Admin Role (Role A فقط) ══════════════════ -->
        <?php if ($isAdm && isRoleA()): ?>
        <div id="tab-adminrole" class="tab-section" style="display:none;">
            <div class="card p-4">
                <h4 class="mb-3">👑 Change My Role</h4>
                <p class="small mb-3" style="color:var(--placeholder-color);">
                    يتطلب تأكيد كلمة السر. تغيير الرتبة من A سيُقيّد صلاحياتك.
                </p>
                <?php if ($updateMsg): ?><div class="alert alert-success"><?= htmlspecialchars($updateMsg) ?></div><?php endif; ?>
                <?php if ($updateErr): ?><div class="alert alert-danger"><?= htmlspecialchars($updateErr) ?></div><?php endif; ?>
                <form method="POST" style="max-width:360px;">
                    <input type="hidden" name="change_own_role" value="1">
                    <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($csrf) ?>">
                    <div class="float-group">
                        <select name="new_role">
                            <option value="A" selected>A — Super Admin</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                        <label>New Role</label>
                    </div>
                    <div class="float-group">
                        <input type="password" name="confirm_pass" placeholder=" " required>
                        <label>Confirm Password</label>
                    </div>
                    <button type="submit" class="btn btn-warning px-4">Change Role</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
    </div>
</main>

<?php include '../components/footer.php'; ?>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-section').forEach(el => {
        el.style.display = 'none';
        el.classList.remove('active-tab');
    });
    document.querySelectorAll('.info-tab-btn').forEach(b => b.classList.remove('active'));
    const target = document.getElementById('tab-' + name);
    target.style.display = 'block';
    target.offsetHeight; // force reflow
    target.classList.add('active-tab');
    btn.classList.add('active');
}
</script>
</body>
</html>
