<?php

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $input,
        public readonly array $files,
        public readonly array $server,
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = basename($scriptName) === 'index.php' ? dirname($scriptName) : '/';
        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($path, $scriptDir . '/')) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }
        $path = '/' . trim($path, '/');
        return new self($method, $path === '/' ? '/' : rtrim($path, '/'), $_GET, $_POST, $_FILES, $_SERVER);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->input;
    }
}
