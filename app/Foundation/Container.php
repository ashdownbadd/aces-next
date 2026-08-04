<?php

declare(strict_types=1);

namespace App\Foundation;

use Closure;
use InvalidArgumentException;

final class Container
{
    /**
     * @var array<string, Closure(Container): mixed>
     */
    private array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    public function singleton(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (! array_key_exists($id, $this->bindings)) {
            throw new InvalidArgumentException(
                sprintf('Nothing has been bound for [%s].', $id)
            );
        }

        $instance = ($this->bindings[$id])($this);

        $this->instances[$id] = $instance;

        return $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings);
    }
}
