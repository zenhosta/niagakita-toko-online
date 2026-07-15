<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Request;

define('BASE_PATH', __DIR__);
require BASE_PATH . '/vendor/autoload.php';

$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if (preg_match('#^/assets/([a-zA-Z0-9_./-]+)$#', $requestPath, $matches) && !str_contains($matches[1], '..')) {
    $assetRoot = realpath(BASE_PATH . '/public/assets');
    $path = realpath(BASE_PATH . '/public/assets/' . $matches[1]);
    if (!$assetRoot || !$path || !str_starts_with($path, $assetRoot . DIRECTORY_SEPARATOR) || !is_file($path)) {
        http_response_code(404);
        exit;
    }
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = ['css' => 'text/css; charset=UTF-8', 'js' => 'application/javascript; charset=UTF-8', 'json' => 'application/json; charset=UTF-8'][$extension]
        ?? (new finfo(FILEINFO_MIME_TYPE))->file($path)
        ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=604800');
    readfile($path);
    exit;
}

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
