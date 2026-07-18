<?php
/**
 * components/header.php
 * الـ <head> المشترك لكل صفحات المتجر
 */

// Security Headers لكل صفحة عامة
require_once __DIR__ . '/../config/error_handler.php';

$_title  = isset($pageTitle)       ? htmlspecialchars($pageTitle) . ' | Cairo Store' : 'Cairo Store';
$_desc        = isset($pageDescription) ? htmlspecialchars($pageDescription)               : 'Cairo Store — Best Electronics Store with Premium Products and Fast Delivery';
$_image       = isset($pageImage)       ? htmlspecialchars($pageImage)                     : '';
$_robots      = !empty($noIndex)        ? 'noindex,nofollow'                               : 'index, follow';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <meta name="description" content="<?= $_desc ?>">
    <meta name="robots" content="<?= $_robots ?>">
    <?php if ($_image): ?>
    <meta property="og:image" content="<?= $_image ?>">
    <?php endif; ?>
    <meta property="og:title"       content="<?= $_title ?>">
    <meta property="og:description" content="<?= $_desc ?>">
    <meta property="og:type"        content="website">
    <meta name="twitter:card"       content="summary_large_image">
    <meta name="twitter:title"      content="<?= $_title ?>">
    <meta name="twitter:description"content="<?= $_desc ?>">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Task(1)/css/style.css?v=<?= filemtime(__DIR__.'/../css/style.css') ?>">
    <link rel="stylesheet" href="/Task(1)/css/dark-theme.css" id="theme-style" disabled>
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="page-transitioning">
<a href="#main-content" class="skip-nav">Skip to main content</a>
