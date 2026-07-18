<?php
/**
 * sitemap.php — Sitemap XML ديناميكي
 * http://localhost/Task(1)/sitemap.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/settings_helper.php';
header('Content-Type: application/xml; charset=utf-8');

$pdo      = getDB();
$ws       = getSiteSettings();
$base     = rtrim($ws['site_url'] ?? 'https://cairostore.com', '/');

$products = $pdo->query("SELECT id, date_added FROM products ORDER BY date_added DESC")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url><loc><?= $base ?>/Task(1)/index.php</loc>
         <changefreq>daily</changefreq><priority>1.0</priority>
         <lastmod><?= date('Y-m-d') ?></lastmod></url>

    <url><loc><?= $base ?>/Task(1)/pages/products.php</loc>
         <changefreq>daily</changefreq><priority>0.9</priority>
         <lastmod><?= date('Y-m-d') ?></lastmod></url>

    <url><loc><?= $base ?>/Task(1)/pages/aboutus.php</loc>
         <changefreq>monthly</changefreq><priority>0.6</priority>
         <lastmod><?= date('Y-m-d') ?></lastmod></url>

    <url><loc><?= $base ?>/Task(1)/pages/contactus.php</loc>
         <changefreq>monthly</changefreq><priority>0.6</priority>
         <lastmod><?= date('Y-m-d') ?></lastmod></url>

    <?php foreach ($products as $p): ?>
    <url>
        <loc><?= $base ?>/Task(1)/pages/product-details.php?id=<?= (int)$p['id'] ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        <lastmod><?= htmlspecialchars($p['date_added'] ?? date('Y-m-d')) ?></lastmod>
    </url>
    <?php endforeach; ?>

</urlset>
