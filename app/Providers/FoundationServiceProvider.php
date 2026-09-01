<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Config;
use App\Foundation\CsrfToken;
use App\Foundation\Container;
use App\Foundation\Database;
use App\Foundation\Session;

final class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            Config::class,
            function (Container $container): Config {

                $config = new Config();

                $config->load(__DIR__ . '/../../config');

                return $config;
            }
        );

        $this->container->singleton(
            Session::class,
            fn() => new Session(),
        );

        $this->container->singleton(
            Database::class,
            fn(Container $container) => new Database(
                $container->get(Config::class),
            ),
        );

        $this->container->singleton(
            CsrfToken::class,
            fn(Container $container) => new CsrfToken(
                $container->get(Session::class),
            ),
        );
    }
}
