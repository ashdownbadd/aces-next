<?php

declare(strict_types=1);

namespace App\Foundation;

final readonly class Route
{
    /**
     * @param callable|array{class-string, string} $handler
     * @param array<class-string> $middleware
     */
    public function __construct(
        public string $method,
        public string $uri,
        public mixed $handler,
        public array $middleware = [],
    ) {}
}
