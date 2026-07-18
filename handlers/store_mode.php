<?php
/**
 * handlers/store_mode.php
 * يضبط admin_in_store_mode ثم يُوجّه للمتجر
 * يقبل GET و POST — لكن يرفض أي طريقة أخرى
 */
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/http_helper.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    respond(false, 'Invalid request method.');
}

if (!isAdmin()) {
    header('Location: /Task(1)/index.php');
    exit;
}

$_SESSION['admin_in_store_mode'] = true;
header('Location: /Task(1)/index.php');
exit;
