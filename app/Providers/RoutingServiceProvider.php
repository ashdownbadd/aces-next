<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Container;
use App\Foundation\Router;

final class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            Router::class,
            function (Container $container): Router {

                $router = new Router($container);

                $router->load(__DIR__ . '/../../routes/web.php');

                return $router;
            }
        );
    }
}
