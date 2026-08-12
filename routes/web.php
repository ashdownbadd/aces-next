<?php

declare(strict_types=1);

use App\Features\Authentication\Controllers\LoginController;
use App\Features\Dashboard\Controllers\DashboardController;
use App\Features\Members\Controllers\MembersController;
use App\Features\Members\Controllers\BeneficiaryController;
use App\Foundation\Router;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;

/** @var Router $router */


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Members
|--------------------------------------------------------------------------
*/

$router->get(
    '/members',
    [MembersController::class, 'index'],
    [
        AuthMiddleware::class,
    ],
);

/*
 * View a single member profile.
 *
 * Example:
 * /members/2
 *
 * The number here is the database ID.
 * The displayed member number remains 0001.
 */
$router->get(
    '/members/{id}',
    [MembersController::class, 'show'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/members/create',
    [MembersController::class, 'create'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/members/create',
    [MembersController::class, 'storeStep'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/members/register',
    [MembersController::class, 'register'],
    [
        AuthMiddleware::class,
    ],
);


/*
|--------------------------------------------------------------------------
| Member Beneficiaries
|--------------------------------------------------------------------------
*/

$router->post(
    '/members/beneficiaries',
    [BeneficiaryController::class, 'store'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/members/beneficiaries/update',
    [BeneficiaryController::class, 'update'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/members/beneficiaries/delete',
    [BeneficiaryController::class, 'destroy'],
    [
        AuthMiddleware::class,
    ],
);


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

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
