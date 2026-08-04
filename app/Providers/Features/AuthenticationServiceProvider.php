<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Authentication\Controllers\LoginController;
use App\Features\Authentication\Repositories\UserRepository;
use App\Features\Authentication\Services\AuthService;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Foundation\Container;
use App\Foundation\Session;
use App\Providers\ServiceProvider;

final class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            UserRepository::class,
            fn(Container $container) => new UserRepository(
                $container->get(\App\Foundation\Database::class),
            ),
        );

        $this->container->singleton(
            AuthService::class,
            fn(Container $container) => new AuthService(
                $container->get(UserRepository::class),
                $container->get(Session::class),
            ),
        );

        $this->container->singleton(
            LoginController::class,
            fn(Container $container) => new LoginController(
                $container->get(\App\Foundation\View::class),
                $container->get(AuthService::class),
            ),
        );

        $this->container->singleton(
            AuthMiddleware::class,
            fn(Container $container) => new AuthMiddleware(
                $container->get(AuthService::class),
            ),
        );

        $this->container->singleton(
            GuestMiddleware::class,
            fn(Container $container) => new GuestMiddleware(
                $container->get(AuthService::class),
            ),
        );
    }
}
