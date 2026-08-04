<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query = [],
        private readonly array $input = [],
    ) {}

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';

        if ($uri === '') {
            $uri = '/';
        }

        return new self(
            method: strtoupper($method),
            uri: $uri,
            query: $_GET,
            input: $_POST,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function query(
        string $key,
        mixed $default = null,
    ): mixed {
        return $this->query[$key] ?? $default;
    }

    public function input(
        string $key,
        mixed $default = null,
    ): mixed {
        return $this->input[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge(
            $this->query,
            $this->input,
        );
    }
}
