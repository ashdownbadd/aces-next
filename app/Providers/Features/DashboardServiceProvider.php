<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Dashboard\Controllers\DashboardController;
use App\Features\Dashboard\Repositories\DashboardRepository;
use App\Features\Dashboard\Services\DashboardService;
use App\Foundation\Container;
use App\Foundation\Database;
use App\Foundation\View;
use App\Providers\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            DashboardRepository::class,
            fn (Container $container) => new DashboardRepository(
                $container->get(Database::class),
            ),
        );

        $this->container->singleton(
            DashboardService::class,
            fn (Container $container) => new DashboardService(
                $container->get(DashboardRepository::class),
            ),
        );

        $this->container->singleton(
            DashboardController::class,
            fn (Container $container) => new DashboardController(
                $container->get(View::class),
                $container->get(DashboardService::class),
            ),
        );
    }
}
