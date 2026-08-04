<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Home\Controllers\HomeController;
use App\Foundation\Config;
use App\Foundation\Container;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Foundation\View;
use App\Providers\ServiceProvider;

final class HomeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            HomeController::class,
            function (Container $container): HomeController {

                return new HomeController(
                    $container->get(Config::class),
                    $container->get(View::class),
                    $container->get(Database::class),
                    $container->get(Session::class),
                );
            }
        );
    }
}
