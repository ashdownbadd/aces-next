<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\ActivityLogs\Controllers\ActivityLogController;
use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Foundation\Container;
use App\Foundation\Database;
use App\Foundation\View;
use App\Providers\ServiceProvider;

final class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            ActivityLogRepository::class,
            fn(Container $container) => new ActivityLogRepository(
                $container->get(Database::class),
            ),
        );

        $this->container->singleton(
            ActivityLogService::class,
            fn(Container $container) => new ActivityLogService(
                $container->get(ActivityLogRepository::class),
            ),
        );

        $this->container->singleton(
            ActivityLogController::class,
            fn(Container $container) => new ActivityLogController(
                $container->get(View::class),
                $container->get(ActivityLogService::class),
            ),
        );
    }
}
