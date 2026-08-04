<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Dashboard\Controllers\DashboardController;
use App\Foundation\Container;
use App\Foundation\View;
use App\Providers\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            DashboardController::class,
            fn(Container $container) => new DashboardController(
                $container->get(View::class),
            ),
        );
    }
}
