<?php
$pageTitle = 'Edit Product';
require_once __DIR__ . '/../admin/layout.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
requirePermission('can_manage_products');

$pdo = getDB();
$pid = (int)($_GET['id'] ?? 0);
if (!$pid) { header('Location: /Task(1)/admin/products-list.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->execute([$pid]);
$p = $stmt->fetch();
if (!$p) { header('Location: /Task(1)/admin/products-list.php'); exit; }

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$ageGroups  = $pdo->query("SELECT * FROM age_groups")->fetchAll();

$s = $pdo->prepare("SELECT category_id FROM product_category_pivot WHERE product_id=?"); $s->execute([$pid]);
$curCats = array_column($s->fetchAll(), 'category_id');

$s2 = $pdo->prepare("SELECT age_group_id FROM product_age_group_pivot WHERE product_id=?"); $s2->execute([$pid]);
$curAges = array_column($s2->fetchAll(), 'age_group_id');

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_product'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $name     = trim($_POST['name']         ?? '');
    $desc     = trim($_POST['description']  ?? '');
    $origin   = trim($_POST['country']      ?? '');
    $brand    = trim($_POST['manufacturer'] ?? '');
    $price    = (float)($_POST['price']     ?? 0);
    $discount = (float)($_POST['discount']  ?? 0);
    $gender   = $_POST['gender']            ?? 'both';
    $stock    = (int)($_POST['stock']       ?? 0);
    $dateAdd  = $_POST['date_added']         ?? $p['date_added'];
    $cats     = $_POST['categories']         ?? [];
    $ages     = $_POST['age_groups']         ?? [];

    $imgPath = $p['image_path'];
    if (!empty($_FILES['image']['name'])) {
        $fileTmp  = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $err = 'امتداد الملف غير مسموح به. المسموح فقط: JPG, JPEG, PNG, WEBP.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $fileTmp);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowedMimes)) {
                $err = 'الملف ليس صورة حقيقية صالحة (MIME Mismatch).';
            } else {
                $imgInfo = getimagesize($fileTmp);
                if ($imgInfo === false) {
                    $err = 'الملف المرفوع غير صالح كصورة.';
                } else {
                    $dir  = __DIR__ . '/../images/';
                    $file = 'product_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($fileTmp, $dir.$file)) {
                        $imgPath = '/Task(1)/images/' . $file;
                    } else {
                        $err = 'فشل حفظ الصورة.';
                    }
                }
            }
        }
    }

    if (!$err) {
        $pdo->prepare("UPDATE products SET name=?,description=?,country_of_origin=?,manufacturer=?,
            price=?,discount_percentage=?,gender_category=?,image_path=?,date_added=?,stock_quantity=?
            WHERE id=?")->execute([$name,$desc,$origin,$brand,$price,$discount,
            in_array($gender,['male','female','both'])?$gender:'both',
            $imgPath,$dateAdd,$stock,$pid]);

        $pdo->prepare("DELETE FROM product_category_pivot  WHERE product_id=?")->execute([$pid]);
        $pdo->prepare("DELETE FROM product_age_group_pivot WHERE product_id=?")->execute([$pid]);
        foreach ($cats as $cid) {
            $pdo->prepare("INSERT IGNORE INTO product_category_pivot (product_id,category_id) VALUES (?,?)")
                ->execute([$pid,(int)$cid]);
        }
        foreach ($ages as $aid) {
            $pdo->prepare("INSERT IGNORE INTO product_age_group_pivot (product_id,age_group_id) VALUES (?,?)")
                ->execute([$pid,(int)$aid]);
        }

        logAdminAction($adminId,'update_product','product',$pid,$name);
        $msg = '✅ تم تحديث المنتج بنجاح.';
        // أعد جلب البيانات
        $stmt->execute([$pid]); $p = $stmt->fetch();
        $s->execute([$pid]); $curCats = array_column($s->fetchAll(),'category_id');
        $s2->execute([$pid]); $curAges = array_column($s2->fetchAll(),'age_group_id');
    }
}
?>

<div class="admin-page-header">
    <h1>✏️ Edit Product</h1>
    <div class="d-flex gap-2">
        <a href="/Task(1)/pages/product-details.php?id=<?= $pid ?>"
           target="_blank" class="btn btn-outline-primary btn-sm">👁 View</a>
        <a href="/Task(1)/admin/products-list.php" class="btn btn-outline-secondary btn-sm">← Products</a>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card p-4">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="update_product" value="1">
    <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf) ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <div class="float-group">
                <input type="text" name="name" placeholder=" " required value="<?= htmlspecialchars($p['name']) ?>">
                <label>Product Name</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <input type="number" name="price" placeholder=" " min="0" step="0.01" required value="<?= $p['price'] ?>">
                <label>Price ($)</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <input type="number" name="discount" placeholder=" " min="0" max="100" step="0.1" value="<?= $p['discount_percentage'] ?>">
                <label>Discount (%)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="float-group">
                <textarea name="description" rows="3" placeholder=" "><?= htmlspecialchars($p['description']??'') ?></textarea>
                <label>Description</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <input type="text" name="country" placeholder=" " value="<?= htmlspecialchars($p['country_of_origin']??'') ?>">
                <label>Country of Origin</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <input type="text" name="manufacturer" placeholder=" " value="<?= htmlspecialchars($p['manufacturer']??'') ?>">
                <label>Brand</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <input type="number" name="stock" placeholder=" " min="0" value="<?= $p['stock_quantity'] ?>">
                <label>Stock Quantity</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <select name="gender">
                    <option value="both"   <?= $p['gender_category']==='both'  ?'selected':'' ?>>Both</option>
                    <option value="male"   <?= $p['gender_category']==='male'  ?'selected':'' ?>>Male</option>
                    <option value="female" <?= $p['gender_category']==='female'?'selected':'' ?>>Female</option>
                </select>
                <label>Gender</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="float-group">
                <input type="date" name="date_added" value="<?= htmlspecialchars($p['date_added']??'') ?>">
                <label>Date Added</label>
            </div>
        </div>
        <div class="col-md-3">
            <?php if ($p['image_path']): ?>
            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt=""
                 style="height:55px;object-fit:contain;border-radius:6px;margin-bottom:6px;" loading="lazy">
            <?php endif; ?>
            <label class="small fw-bold mb-1 d-block">New Image (optional)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <label class="small fw-bold mb-2">Categories</label>
            <div class="perm-grid">
                <?php foreach ($categories as $cat): ?>
                <label class="perm-item">
                    <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                           <?= in_array($cat['id'],$curCats)?'checked':'' ?>>
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
                    <input type="checkbox" name="age_groups[]" value="<?= $ag['id'] ?>"
                           <?= in_array($ag['id'],$curAges)?'checked':'' ?>>
                    <?= str_replace('_',' ',ucfirst($ag['name'])) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" id="saveProductBtn" class="btn btn-success btn-disabled-faded" disabled aria-disabled="true">💾 Save Changes</button>
        <a href="/Task(1)/admin/products-list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div>

<script>
(function () {
    const form = document.querySelector('form');
    const btn = document.getElementById('saveProductBtn');
    if (!form || !btn) return;

    const originalValues = {};
    const inputs = form.querySelectorAll('input:not([name="csrf_token"]), textarea, select');
    
    // حفظ القيم الأصلية
    inputs.forEach(el => {
        if (el.type === 'checkbox') {
            originalValues[el.name + '_' + el.value] = el.checked;
        } else if (el.type === 'file') {
            originalValues[el.name] = '';
        } else {
            originalValues[el.name] = el.value;
        }
    });

    function checkFormChanged() {
        let changed = false;
        let requiredFilled = true;

        inputs.forEach(el => {
            if (el.hasAttribute('required') && !el.value.trim()) {
                requiredFilled = false;
            }

            if (el.type === 'checkbox') {
                if (originalValues[el.name + '_' + el.value] !== el.checked) {
                    changed = true;
                }
            } else if (el.type === 'file') {
                if (el.files && el.files.length > 0) {
                    changed = true;
                }
            } else {
                if (originalValues[el.name] !== el.value) {
                    changed = true;
                }
            }
        });

        const ok = requiredFilled && changed;
        if (typeof updateButtonState === 'function') {
            updateButtonState(btn, ok);
        }
    }

    inputs.forEach(el => {
        el.addEventListener('input', checkFormChanged);
        el.addEventListener('change', checkFormChanged);
    });
    
    checkFormChanged();
})();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
