<?php
/**
 * helpers/settings_helper.php
 * كاش بسيط لبيانات website_settings — بدل تكرار الاستعلام في 6+ ملفات
 */

function getSiteSettings(): array {
    static $ws = null;
    if ($ws !== null) return $ws;

    try {
        $pdo = getDB();
        $ws  = $pdo->query("SELECT * FROM website_settings LIMIT 1")->fetch() ?: [];
    } catch (Exception $e) {
        $ws = [];
    }
    return $ws;
}
