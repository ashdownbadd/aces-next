<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\View;
use App\Foundation\Container;

final class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            View::class,
            fn(Container $container) => new View(
                __DIR__ . '/../../resources/views',
            ),
        );
    }
}
