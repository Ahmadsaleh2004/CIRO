<?php
$pageTitle = 'Site Configuration';
require_once __DIR__ . '/../admin/layout.php';
requirePermission('can_edit_site_content');

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $fields = [
        'footer_text','facebook_url','instagram_url','snapchat_url',
        'whatsapp_number','tiktok_url','twitter_x_url','google_maps_url',
        'copyright_text','phone_number','working_hours','employees_count','site_url',
        'return_policy','privacy_policy','terms_and_conditions',
    ];

    if (hasPermission('can_manage_checkout_settings')) {
        $fields[] = 'default_currency';
        $fields[] = 'default_language';
    }

    $setParts = implode(', ', array_map(fn($f) => "{$f}=?", $fields));
    $values   = array_map(fn($f) => trim($_POST[$f] ?? ''), $fields);
    $values[] = 1; // WHERE id=1

    $pdo->prepare("UPDATE website_settings SET {$setParts} WHERE id=?")->execute($values);
    $msg = '✅ Settings saved successfully.';
}

// بعد الحفظ نجبر إعادة قراءة البيانات المحدّثة مباشرة من DB
$ws = $pdo->query("SELECT * FROM website_settings LIMIT 1")->fetch() ?: [];
?>

<div class="admin-page-header">
    <h1>⚙️ Site Configuration</h1>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="POST">
    <input type="hidden" name="save_settings" value="1">
    <input type="hidden" name="csrf_token"    value="<?= htmlspecialchars($csrf) ?>">

    <!-- ── General ──────────────────────────────────── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">🌐 General & Contact</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="float-group">
                    <input type="text" name="site_url" placeholder=" "
                           value="<?= htmlspecialchars($ws['site_url'] ?? '') ?>">
                    <label>Site URL</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="float-group">
                    <input type="text" name="copyright_text" placeholder=" "
                           value="<?= htmlspecialchars($ws['copyright_text'] ?? '') ?>">
                    <label>Copyright Text</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="float-group">
                    <input type="text" name="phone_number" placeholder=" "
                           value="<?= htmlspecialchars($ws['phone_number'] ?? '') ?>">
                    <label>Phone Number</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="float-group">
                    <input type="text" name="working_hours" placeholder=" "
                           value="<?= htmlspecialchars($ws['working_hours'] ?? '') ?>">
                    <label>Working Hours</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="float-group">
                    <input type="number" name="employees_count" placeholder=" " min="1"
                           value="<?= htmlspecialchars($ws['employees_count'] ?? 50) ?>">
                    <label>Employees Count</label>
                </div>
            </div>
            <div class="col-12">
                <div class="float-group">
                    <textarea name="footer_text" rows="2" placeholder=" "><?= htmlspecialchars($ws['footer_text'] ?? '') ?></textarea>
                    <label>Footer Text</label>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Social ────────────────────────────────────── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">📱 Social Media</h5>
        <div class="row g-3">
            <?php
            $socialFields = [
                'facebook_url'   => 'Facebook URL',
                'instagram_url'  => 'Instagram URL',
                'snapchat_url'   => 'Snapchat URL',
                'whatsapp_number'=> 'WhatsApp Number',
                'tiktok_url'     => 'TikTok URL',
                'twitter_x_url'  => 'Twitter/X URL',
                'google_maps_url'=> 'Google Maps URL',
            ];
            foreach ($socialFields as $field => $label):
            ?>
            <div class="col-md-4">
                <div class="float-group">
                    <input type="text" name="<?= $field ?>" placeholder=" "
                           value="<?= htmlspecialchars($ws[$field] ?? '') ?>">
                    <label><?= $label ?></label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Policies ──────────────────────────────────── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">📜 Policies</h5>
        <div class="float-group">
            <textarea name="privacy_policy" rows="5" placeholder=" "><?= htmlspecialchars($ws['privacy_policy'] ?? '') ?></textarea>
            <label>Privacy Policy</label>
        </div>
        <div class="float-group">
            <textarea name="return_policy" rows="5" placeholder=" "><?= htmlspecialchars($ws['return_policy'] ?? '') ?></textarea>
            <label>Return Policy</label>
        </div>
        <div class="float-group">
            <textarea name="terms_and_conditions" rows="5" placeholder=" "><?= htmlspecialchars($ws['terms_and_conditions'] ?? '') ?></textarea>
            <label>Terms & Conditions</label>
        </div>
    </div>

    <!-- ── Checkout (conditional) ────────────────────── -->
    <?php if (hasPermission('can_manage_checkout_settings')): ?>
    <div class="card p-4 mb-4">
        <h5 class="mb-3">💳 Checkout Settings</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="float-group">
                    <select name="default_currency">
                        <?php foreach (['USD'=>'USD ($)','JOD'=>'JOD (د.أ)','EGP'=>'EGP (ج.م)','SAR'=>'SAR (ر.س)','AED'=>'AED (د.إ)'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($ws['default_currency']??'USD')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Default Currency</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="float-group">
                    <select name="default_language">
                        <option value="en" selected>English</option>
                    </select>
                    <label>Default Language</label>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-success btn-lg px-5">💾 Save All Settings</button>
</form>

<?php require_once __DIR__ . '/layout_end.php'; ?>
