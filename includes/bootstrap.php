<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Pontianak');

$docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$appRoot = str_replace('\\', '/', APP_ROOT);
define('BASE_URL', $docRoot !== '' && str_starts_with($appRoot, $docRoot) ? substr($appRoot, strlen($docRoot)) : '');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

ini_set('session.use_strict_mode', '1');
session_name('POSSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE_URL . '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once APP_ROOT . '/config/app.php';
require_once APP_ROOT . '/config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/transaksi_service.php';
