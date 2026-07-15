<?php

use App\Core\Application;

$config = require BASE_PATH . '/config/app.php';
date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name($config['session_name']);
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}

$app = new Application($config);
require BASE_PATH . '/routes/web.php';

return $app;
