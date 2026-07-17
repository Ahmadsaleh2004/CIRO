<?php
$pageTitle = 'Add Product';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_products');

$pdo        = getDB();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$ageGroups  = $pdo->query("SELECT * FROM age_groups")->fetchAll();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_product'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $name        = trim($_POST['name']         ?? '');
    $desc        = trim($_POST['description']  ?? '');
    $origin      = trim($_POST['country']      ?? '');
    $brand       = trim($_POST['manufacturer'] ?? '');
    $price       = (float)($_POST['price']     ?? 0);
    $discount    = (float)($_POST['discount']  ?? 0);
    $gender      = $_POST['gender']            ?? 'both';
    $stock       = (int)($_POST['stock']       ?? 0);
    $dateAdded   = $_POST['date_added']         ?? date('Y-m-d');
    $cats        = $_POST['categories']         ?? [];
    $ages        = $_POST['age_groups']         ?? [];

    // رفع صورة
    $imgPath = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        // ── التحقق من الامتداد (Whitelist) ────────────────────
        $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($ext, $allowedExts)) {
            $err = 'صيغة الصورة غير مقبولة. المسموح: JPG, PNG, WEBP.';
        } else {
            $tmpPath = $_FILES['image']['tmp_name'];

            // ── التحقق الفعلي من محتوى الملف (MIME) ──────────
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes)) {
                $err = 'محتوى الملف لا يطابق صيغة صورة حقيقية.';
            } elseif (!getimagesize($tmpPath)) {
                // ── التحقق الإضافي: هل هو صورة قابلة للقراءة ─
                $err = 'الملف المرفوع ليس صورة صالحة.';
            } else {
                $dir  = __DIR__ . '/../images/';
                $file = 'product_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmpPath, $dir . $file)) {
                    $imgPath = '/Task(1)/images/' . $file;
                } else {
                    $err = 'فشل رفع الصورة — تأكد من صلاحيات المجلد.';
                }
            }
        }
    }

    if (!$err) {
        if (!$name || $price <= 0) {
            $err = 'الاسم والسعر حقول إلزامية.';
        } else {
            $ins = $pdo->prepare("
                INSERT INTO products
                    (name,description,country_of_origin,manufacturer,price,
                     discount_percentage,gender_category,image_path,date_added,stock_quantity)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $ins->execute([$name,$desc,$origin,$brand,$price,$discount,
                in_array($gender,['male','female','both'])?$gender:'both',
                $imgPath,$dateAdded,$stock]);
            $newId = (int)$pdo->lastInsertId();

            foreach ($cats as $cid) {
                $pdo->prepare("INSERT IGNORE INTO product_category_pivot (product_id,category_id) VALUES (?,?)")
                    ->execute([$newId,(int)$cid]);
            }
            foreach ($ages as $aid) {
                $pdo->prepare("INSERT IGNORE INTO product_age_group_pivot (product_id,age_group_id) VALUES (?,?)")
                    ->execute([$newId,(int)$aid]);
            }
            logAdminAction($adminId,'add_product','product',$newId,$name);

            if (isset($_POST['add_another'])) {
                $msg = "✅ تمت إضافة «{$name}». أضف منتجاً آخر:";
            } else {
                header('Location: /Task(1)/pages/products.php');
                exit;
            }
        }
    }
}
?>

<div class="admin-page-header">
    <h1>➕ Add Product</h1>
    <a href="/Task(1)/pages/products.php" class="btn btn-outline-secondary btn-sm">← Products</a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card p-4">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="save_product" value="1">
    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf) ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <div class="float-group"><input type="text"   name="name"         placeholder=" " required><label>Product Name</label></div>
        </div>
        <div class="col-md-3">
            <div class="float-group"><input type="number" name="price"         placeholder=" " min="0" step="0.01" required><label>Price ($)</label></div>
        </div>
        <div class="col-md-3">
            <div class="float-group"><input type="number" name="discount"      placeholder=" " min="0" max="100" step="0.1" value="0"><label>Discount (%)</label></div>
        </div>
        <div class="col-md-6">
            <div class="float-group"><textarea name="description" rows="3" placeholder=" "></textarea><label>Description</label></div>
        </div>
        <div class="col-md-3">
            <div class="float-group"><input type="text"   name="country"       placeholder=" "><label>Country of Origin</label></div>
        </div>
        <div class="col-md-3">
            <div class="float-group"><input type="text"   name="manufacturer"  placeholder=" "><label>Brand / Manufacturer</label></div>
        </div>
        <div class="col-md-3">
            <div class="float-group"><input type="number" name="stock"         placeholder=" " min="0" value="10"><label>Stock Quantity</label></div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <select name="gender">
                    <option value="both">Both</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <label>Gender Category</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group"><input type="date" name="date_added" value="<?= date('Y-m-d') ?>"><label>Date Added</label></div>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold mb-1 d-block">Product Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <label class="small fw-bold mb-2">Categories</label>
            <div class="perm-grid">
                <?php foreach ($categories as $cat): ?>
                <label class="perm-item">
                    <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>">
                    <?= ucfirst($cat['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold mb-2">Age Groups</label>
            <div class="perm-grid">
                <?php foreach ($ageGroups as $ag): ?>
                <label class="perm-item">
                    <input type="checkbox" name="age_groups[]" value="<?= $ag['id'] ?>">
                    <?= str_replace('_',' ',ucfirst($ag['name'])) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" name="save_product" class="btn btn-success">Add Product</button>
        <button type="submit" name="add_another"  class="btn btn-outline-success">Add Another</button>
        <a href="/Task(1)/pages/products.php"     class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div>

<?php require_once __DIR__ . '/layout_end.php'; ?>
