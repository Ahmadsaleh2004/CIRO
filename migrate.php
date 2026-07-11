<?php
/**
 * migrate.php — ترحيل data/products.json → MySQL
 * شغّله مرة واحدة بعد seed_admin_password.php
 * CLI:     php migrate.php
 * Browser: http://localhost/Task(1)/migrate.php
 */
require_once __DIR__ . '/config/db.php';

$jsonFile = __DIR__ . '/data/products.json';
if (!file_exists($jsonFile)) die("❌ data/products.json not found.\n");

$items = json_decode(file_get_contents($jsonFile), true);
if (!$items)              die("❌ Failed to parse JSON.\n");

$pdo = getDB();

// خريطة التصنيفات
$catMap = [];
foreach ($pdo->query("SELECT id,name FROM categories")->fetchAll() as $r) $catMap[$r['name']] = $r['id'];

$ageMap = [];
foreach ($pdo->query("SELECT id,name FROM age_groups")->fetchAll() as $r)  $ageMap[$r['name']] = $r['id'];

function guessCategory(array $p): string {
    $t = strtolower($p['name'] . ' ' . ($p['description'] ?? ''));
    if (preg_match('/iphone|phone|mobile/i', $t))                               return 'phone';
    if (preg_match('/macbook|laptop|ipad|tablet|computer/i', $t))               return 'computer';
    if (preg_match('/ps4|ps5|playstation|xbox|nintendo|controller|gaming/i', $t)) return 'gaming';
    return 'accessories';
}

function defaultStock(string $tag): int {
    return match($tag) { 'limited'=>5, 'best-seller'=>30, 'new'=>15, default=>20 };
}

// تحويل مسار الصورة: "../images/X" → "/Task(1)/images/X"
function convertImg(string $img): string {
    return preg_replace('#^\.\./images/#', '/Task(1)/images/', $img);
}

$ins = $pdo->prepare("
    INSERT INTO products
        (name, description, country_of_origin, manufacturer, price,
         discount_percentage, gender_category, image_path, date_added,
         sales_count, stock_quantity)
    VALUES (?,?,?,?,?,0,'both',?,?,0,?)
");
$insCat = $pdo->prepare("INSERT IGNORE INTO product_category_pivot  (product_id,category_id)  VALUES (?,?)");
$insAge = $pdo->prepare("INSERT IGNORE INTO product_age_group_pivot (product_id,age_group_id) VALUES (?,?)");

$ok = 0; $fail = [];
foreach ($items as $p) {
    try {
        $pdo->beginTransaction();
        $ins->execute([
            $p['name'],
            $p['description'] ?? null,
            $p['madeIn']      ?? null,
            $p['brand']       ?? null,
            $p['price'],
            convertImg($p['image'] ?? ''),
            $p['releaseDate'] ?? date('Y-m-d'),
            defaultStock($p['tag'] ?? 'regular'),
        ]);
        $pid = (int)$pdo->lastInsertId();
        $cat = guessCategory($p);
        if (isset($catMap[$cat]))         $insCat->execute([$pid, $catMap[$cat]]);
        if (isset($ageMap['all_ages']))   $insAge->execute([$pid, $ageMap['all_ages']]);
        $pdo->commit();
        $ok++;
        echo "✅ [{$pid}] {$p['name']}\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        $fail[] = "❌ {$p['name']}: " . $e->getMessage();
        echo end($fail) . "\n";
    }
}

echo "\n══════════════════════════════\n";
echo "✅ Migrated: {$ok}\n";
echo "❌ Failed  : " . count($fail) . "\n";
echo "\ndata/products.json NOT deleted — delete it manually after review.\n";
