<?php
/**
 * setup/seed_dates.php — الجزء 1/18
 * يضبط تواريخ وهمية لأحدث 7 منتجات (حسب الـ id الأصغر → الأحدث)
 * شغّله مرة واحدة فقط: http://localhost/Task(1)/setup/seed_dates.php
 */
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

// جلب أصغر 7 IDs موجودة (نفترض أنها المنتجات الأساسية)
$stmt = $pdo->query("SELECT id FROM products ORDER BY id ASC LIMIT 7");
$ids  = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($ids) < 7) {
    die("❌ أقل من 7 منتجات في قاعدة البيانات. أضف منتجات أولاً.");
}

$intervals = [3, 5, 8, 12, 15, 20, 25];

$upd = $pdo->prepare("UPDATE products SET date_added = DATE_SUB(NOW(), INTERVAL ? DAY) WHERE id = ?");

foreach ($ids as $i => $id) {
    $upd->execute([$intervals[$i], $id]);
}

echo "✅ تم ضبط تواريخ New Arrivals لـ 7 منتجات بنجاح:<br>";
foreach ($ids as $i => $id) {
    echo "  - Product ID {$id} → منذ {$intervals[$i]} يوم<br>";
}
