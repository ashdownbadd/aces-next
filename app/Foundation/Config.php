<?php

declare(strict_types=1);

namespace App\Foundation;

final class Config
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    public function load(string $path): void
    {
        // Config files live in /config, while .env lives at the project root.
        $this->loadEnvironment(dirname($path));

        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');

            /** @var array<string, mixed> $config */
            $config = require $file;

            $this->items[$key] = $config;
        }
    }


    private function loadEnvironment(string $projectPath): void
    {
        $file = rtrim($projectPath, '/\\') . '/.env';

        if (! is_file($file) || ! is_readable($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
                continue;
            }

            $key = $matches[1];
            $value = trim($matches[2]);

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];

                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);

        $value = $this->items;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
