<?php

declare(strict_types=1);

use App\Features\ActivityLogs\Controllers\ActivityLogController;
use App\Features\Authentication\Controllers\LoginController;
use App\Features\Dashboard\Controllers\DashboardController;
use App\Features\Members\Controllers\MembersController;
use App\Features\Members\Controllers\BeneficiaryController;
use App\Features\Loans\Controllers\LoanController;
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
| Activity Logs
|--------------------------------------------------------------------------
*/

$router->get(
    '/activity-logs',
    [ActivityLogController::class, 'index'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/activity-logs/{id}',
    [ActivityLogController::class, 'show'],
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

$router->get(
    '/members/{id}/edit',
    [MembersController::class, 'edit'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/members/{id}',
    [MembersController::class, 'show'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/members/{id}/status',
    [MembersController::class, 'changeStatus'],
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
| Loans
|--------------------------------------------------------------------------
*/

$router->get(
    '/loans',
    [LoanController::class, 'index'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/loans/create',
    [LoanController::class, 'create'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/create',
    [LoanController::class, 'store'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/loans/{id}/review',
    [LoanController::class, 'review'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/submit',
    [LoanController::class, 'submit'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/loans/{id}/show',
    [LoanController::class, 'show'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/payments/{id}/reverse',
    [LoanController::class, 'reversePayment'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/loans/{id}/statement-of-account',
    [LoanController::class, 'statementOfAccount'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/payments',
    [LoanController::class, 'payment'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/release',
    [LoanController::class, 'release'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/approve',
    [LoanController::class, 'approve'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/reject',
    [LoanController::class, 'reject'],
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
