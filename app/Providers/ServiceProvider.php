<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Container;

abstract class ServiceProvider
{
    public function __construct(
        protected readonly Container $container,
    ) {}

    abstract public function register(): void;
}
