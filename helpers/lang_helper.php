<?php
/**
 * helpers/lang_helper.php
 * Language is fixed to English — no toggle.
 */

function getLang(): string {
    return 'en';
}

function loadTranslations(): array {
    $file = __DIR__ . '/../lang/en.php';
    return file_exists($file) ? require $file : [];
}

$GLOBALS['lang'] = 'en';
$GLOBALS['t']    = loadTranslations();
$t               = $GLOBALS['t'];
$lang            = 'en';
