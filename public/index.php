<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Request;

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (preg_match('#^/media/(?:(products|branding|homepage|footer)/)?([a-zA-Z0-9_.-]+)$#', $requestPath, $matches)) {
    $collection = $matches[1] ?: 'products';
    $file = basename($matches[2]);
    $path = BASE_PATH . '/storage/uploads/' . $collection . '/' . $file;
    if (!is_file($path) && !str_contains($file, '.')) {
        $files = glob(BASE_PATH . '/storage/uploads/' . $collection . '/' . $file . '.*') ?: [];
        $path = $files[0] ?? $path;
    }
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'], true)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

/** @var Application $app */
$app = require BASE_PATH . '/bootstrap/app.php';
$app->handle(Request::capture())->send();
