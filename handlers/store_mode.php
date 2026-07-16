<?php
/**
 * handlers/store_mode.php
 * يضبط admin_in_store_mode ثم يُوجّه للمتجر
 */
require_once __DIR__ . '/../helpers/auth_helper.php';

if (!isAdmin()) {
    header('Location: /Task(1)/index.php');
    exit;
}

$_SESSION['admin_in_store_mode'] = true;
header('Location: /Task(1)/index.php');
exit;
