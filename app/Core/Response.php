<?php

namespace App\Core;

final class Response
{
    public function __construct(private string $content = '', private int $status = 200, private array $headers = []) {}

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) header("{$name}: {$value}");
        echo $this->content;
        exit;
    }
}
