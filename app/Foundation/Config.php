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
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');

            /** @var array<string, mixed> $config */
            $config = require $file;

            $this->items[$key] = $config;
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
