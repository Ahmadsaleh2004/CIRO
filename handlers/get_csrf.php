<?php
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
header('Content-Type: application/json');
echo json_encode(['token' => generateCsrfToken()]);
