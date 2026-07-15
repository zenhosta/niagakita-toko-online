<?php

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable|array $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, callable|array $handler): void { $this->add('PUT', $path, $handler); }
    public function delete(string $path, callable|array $handler): void { $this->add('DELETE', $path, $handler); }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', fn($m) => '(?P<' . $m[1] . '>[a-zA-Z0-9_.-]+)', $path);
        $this->routes[] = compact('method', 'path', 'pattern', 'handler');
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match('#^' . $route['pattern'] . '$#', $request->path, $matches)) continue;
            $allowed[] = $route['method'];
            if ($route['method'] !== $request->method) continue;
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];
            if (is_array($handler)) $handler = [new $handler[0](), $handler[1]];
            $result = $handler($request, ...array_values($params));
            return $result instanceof Response ? $result : new Response((string) $result);
        }
        return new Response($allowed ? '405 Method Not Allowed' : view('layouts.error', ['code' => 404, 'message' => 'Halaman tidak ditemukan.']), $allowed ? 405 : 404);
    }
}
