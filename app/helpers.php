<?php

use App\Core\Database;
use App\Core\Response;

function url(string $path = ''): string
{
    $base = rtrim((require BASE_PATH . '/config/app.php')['base_url'], '/');
    if (str_starts_with($path, '/media/')) {
        $path = preg_replace('/\.(?:jpe?g|png|webp|ico)$/i', '', $path);
    }
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $prefix = PHP_SAPI === 'cli-server'
        && realpath($_SERVER['DOCUMENT_ROOT'] ?? '') === realpath(BASE_PATH)
        ? '/public/assets/'
        : '/assets/';

    return url($prefix . ltrim($path, '/'));
}
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

function view(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/app/Views/' . str_replace('.', '/', $name) . '.php';
    return (string) ob_get_clean();
}

function redirect(string $path): Response { return Response::redirect(url($path)); }
function csrf_token(): string { return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['_csrf'] ?? '', (string) ($_POST['_token'] ?? ''))) throw new RuntimeException('Sesi form kedaluwarsa. Muat ulang halaman.'); }
function flash(string $key, ?string $value = null): ?string { if ($value !== null) { $_SESSION['_flash'][$key] = $value; return null; } $v = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $v; }
function auth(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(): void { if (!auth()) { flash('error', 'Silakan masuk terlebih dahulu.'); header('Location: ' . url('/login')); exit; } }
function require_admin(): void { require_auth(); if ((auth()['role'] ?? '') !== 'admin') { http_response_code(403); exit(view('layouts.error', ['code' => 403, 'message' => 'Anda tidak memiliki akses ke halaman ini.'])); } }
function db(): PDO { return Database::connection(); }
function setting(string $key, mixed $default = null): mixed
{
    static $settings;
    if ($settings === null) {
        try { $settings = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR); }
        catch (Throwable) { $settings = []; }
    }
    $value = $settings[$key] ?? $default;
    return is_string($value) && str_starts_with($value, 'enc:') ? decrypt_secret($value) : $value;
}
function money(float|int|string $amount): string { return 'Rp' . number_format((float) $amount, 0, ',', '.'); }
function slugify(string $text): string { $text = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-')); return $text ?: bin2hex(random_bytes(4)); }
function media_url(string $collection, string $file): string { return url('/media/' . $collection . '/' . pathinfo($file, PATHINFO_FILENAME)); }
function current_path(): string { return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; }
function safe_url(?string $value, string $fallback = '#'): string { $value = trim((string) $value); if ($value === '') return $fallback; if (str_starts_with($value, '/')) return $value; return filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true) ? $value : $fallback; }
function secret_key(): string { static $key; if ($key !== null) return $key; $database = require BASE_PATH . '/config/database.php'; if (!$database) throw new RuntimeException('Konfigurasi database belum tersedia.'); return $key = hash('sha256', json_encode([$database['host'], $database['database'], $database['username'], $database['password']]), true); }
function encrypt_secret(string $value): string { $iv = random_bytes(12); $tag = ''; $cipher = openssl_encrypt($value, 'aes-256-gcm', secret_key(), OPENSSL_RAW_DATA, $iv, $tag); if ($cipher === false) throw new RuntimeException('Gagal mengenkripsi data rahasia.'); return 'enc:' . base64_encode($iv . $tag . $cipher); }
function decrypt_secret(string $value): string { $raw = base64_decode(substr($value, 4), true); if ($raw === false || strlen($raw) < 29) return ''; $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', secret_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16)); return $plain === false ? '' : $plain; }
