<?php

namespace App\Core;

use Throwable;

final class Application
{
    public readonly Router $router;

    public function __construct(public readonly array $config)
    {
        $this->router = new Router();
    }

    public function handle(Request $request): Response
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        try {
            $installed = is_file(BASE_PATH . '/storage/installed.lock');
            if (!$installed && !str_starts_with($request->path, '/install')) return Response::redirect(url('/install'));
            if ($installed && str_starts_with($request->path, '/install')) return Response::redirect(url('/login'));
            return $this->router->dispatch($request);
        } catch (Throwable $e) {
            error_log('[' . date('c') . '] ' . $e . PHP_EOL, 3, BASE_PATH . '/storage/logs/app.log');
            $message = $this->config['debug'] ? $e->getMessage() : 'Terjadi kesalahan. Periksa log aplikasi.';
            return new Response(view('layouts.error', ['code' => 500, 'message' => $message]), 500);
        }
    }
}
