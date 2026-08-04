<?php

declare(strict_types=1);

use App\Features\Authentication\Controllers\LoginController;
use App\Features\Dashboard\Controllers\DashboardController;
use App\Foundation\Router;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;

/** @var Router $router */

$router->get(
    '/',
    [DashboardController::class, 'index'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/dashboard',
    [DashboardController::class, 'index'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/login',
    [LoginController::class, 'show'],
    [
        GuestMiddleware::class,
    ],
);

$router->post(
    '/login',
    [LoginController::class, 'login'],
    [
        GuestMiddleware::class,
    ],
);

$router->get(
    '/logout',
    [LoginController::class, 'logout'],
    [
        AuthMiddleware::class,
    ],
);
