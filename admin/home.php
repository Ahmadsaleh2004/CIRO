<?php
/**
 * admin/home.php — صفحة الترحيب الرئيسية للأدمن
 */
$pageTitle = 'Home';
require_once __DIR__ . '/../admin/layout.php';

// بناء قائمة المربعات حسب الصلاحيات
$tiles = [];

if ($adminRole === 'A') {
    $tiles[] = ['icon'=>'👑','label'=>'Manage Admins',    'url'=>'/Task(1)/admin/manage-admins.php',  'color'=>'#f59e0b'];
}
if (hasPermission('can_view_dashboard')) {
    $tiles[] = ['icon'=>'📊','label'=>'Dashboard',         'url'=>'/Task(1)/admin/dashboard.php',       'color'=>'#6366f1'];
}
if (hasPermission('can_manage_products')) {
    $tiles[] = ['icon'=>'🛍️','label'=>'Products',          'url'=>'/Task(1)/admin/products-list.php',   'color'=>'#16a34a'];
}
if (hasPermission('can_manage_users')) {
    $tiles[] = ['icon'=>'👥','label'=>'Users',              'url'=>'/Task(1)/admin/manage-users.php',    'color'=>'#0ea5e9'];
}
if (hasPermission('can_manage_support')) {
    $tiles[] = ['icon'=>'💬','label'=>'Support',            'url'=>'/Task(1)/admin/support.php',         'color'=>'#8b5cf6'];
}
if (hasPermission('can_manage_orders')) {
    $tiles[] = ['icon'=>'📦','label'=>'Orders',             'url'=>'/Task(1)/admin/manage-orders.php',   'color'=>'#f97316'];
}
if (hasPermission('can_edit_site_content')) {
    $tiles[] = ['icon'=>'⚙️','label'=>'Site Configuration','url'=>'/Task(1)/admin/site-settings.php',   'color'=>'#64748b'];
}
if ($adminRole === 'A') {
    $tiles[] = ['icon'=>'💾','label'=>'Backup DB',          'url'=>'/Task(1)/admin/backup.php',          'color'=>'#dc2626'];
}
// يظهر دائماً
$tiles[] = ['icon'=>'🌐','label'=>'Store',               'url'=>'/Task(1)/handlers/store_mode.php',  'color'=>'#0d9488'];

$tileCount = count($tiles);
?>

<style>
.home-welcome {
    background: linear-gradient(135deg, var(--navbar-bg), #2d2f6e);
    border-radius: var(--card-radius);
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    color: #fff;
}
.home-welcome h1 { font-size: 1.9rem; font-weight: 700; margin-bottom: .4rem; }
.home-welcome p  { opacity: .75; margin: 0; font-size: .95rem; }

.home-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .6rem;
    padding: 2rem 1rem;
    border-radius: var(--card-radius);
    background: var(--card-bg);
    border: 1px solid var(--section-border);
    text-decoration: none !important;
    color: var(--text-color) !important;
    transition: transform var(--dur-base) var(--ease-bounce),
                box-shadow var(--dur-base) var(--ease-standard);
    min-height: 140px;
}
.home-tile:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px var(--shadow-hover);
}
.home-tile .tile-icon {
    font-size: 2.4rem;
    line-height: 1;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,0,0,.06);
}
body.dark-mode .home-tile .tile-icon { background: rgba(255,255,255,.08); }
.home-tile .tile-label {
    font-size: .9rem;
    font-weight: 600;
    text-align: center;
}

/* حالة المربع الواحد */
.single-tile-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 3rem 0;
    gap: 1.5rem;
}
.single-tile-wrap .home-tile {
    width: 200px;
    min-height: 160px;
}
</style>

<!-- Greeting -->
<div class="home-welcome">
    <h1>Welcome back, <?= htmlspecialchars($adminName) ?> 👋</h1>
    <p>Choose a section below to get started.</p>
</div>

<?php if ($tileCount === 1): ?>
<!-- حالة صلاحية واحدة -->
<div class="single-tile-wrap">
    <p class="text-muted">You have access to one section:</p>
    <a href="<?= htmlspecialchars($tiles[0]['url']) ?>" class="home-tile">
        <div class="tile-icon" style="background:<?= $tiles[0]['color'] ?>22;color:<?= $tiles[0]['color'] ?>;">
            <?= $tiles[0]['icon'] ?>
        </div>
        <span class="tile-label"><?= htmlspecialchars($tiles[0]['label']) ?></span>
    </a>
</div>

<?php else: ?>
<!-- شبكة المربعات -->
<div class="row g-3">
    <?php foreach ($tiles as $tile): ?>
    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
        <a href="<?= htmlspecialchars($tile['url']) ?>" class="home-tile h-100">
            <div class="tile-icon"
                 style="background:<?= $tile['color'] ?>22;color:<?= $tile['color'] ?>;">
                <?= $tile['icon'] ?>
            </div>
            <span class="tile-label"><?= htmlspecialchars($tile['label']) ?></span>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_end.php'; ?>
