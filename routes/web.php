<?php

declare(strict_types=1);

use App\Features\ActivityLogs\Controllers\ActivityLogController;
use App\Features\Authentication\Controllers\LoginController;
use App\Features\Dashboard\Controllers\DashboardController;
use App\Features\Members\Controllers\MembersController;
use App\Features\Members\Controllers\BeneficiaryController;
use App\Features\Loans\Controllers\LoanController;
use App\Features\Ledger\Controllers\LedgerController;
use App\Foundation\Router;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\OperationsMiddleware;
use App\Http\Middleware\AccountingMiddleware;
use App\Http\Middleware\AdminMiddleware;

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
        OperationsMiddleware::class,
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
        OperationsMiddleware::class,
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
        OperationsMiddleware::class,
    ],
);

$router->post(
    '/members/register',
    [MembersController::class, 'register'],
    [
        OperationsMiddleware::class,
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
        OperationsMiddleware::class,
    ],
);

$router->post(
    '/members/beneficiaries/update',
    [BeneficiaryController::class, 'update'],
    [
        OperationsMiddleware::class,
    ],
);

$router->post(
    '/members/beneficiaries/delete',
    [BeneficiaryController::class, 'destroy'],
    [
        OperationsMiddleware::class,
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
    '/loans/members/search',
    [LoanController::class, 'memberSearch'],
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
        OperationsMiddleware::class,
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
        OperationsMiddleware::class,
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
        AccountingMiddleware::class,
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
        AccountingMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/release',
    [LoanController::class, 'release'],
    [
        AccountingMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/approve',
    [LoanController::class, 'approve'],
    [
        AdminMiddleware::class,
    ],
);

$router->post(
    '/loans/{id}/reject',
    [LoanController::class, 'reject'],
    [
        AdminMiddleware::class,
    ],
);

/*
|--------------------------------------------------------------------------

/*
|--------------------------------------------------------------------------
| Ledger / Accounting
|--------------------------------------------------------------------------
*/

$router->get(
    '/ledger',
    [LedgerController::class, 'index'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/ledger/accounts',
    [LedgerController::class, 'accounts'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/ledger/general',
    [LedgerController::class, 'general'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/ledger/trial-balance',
    [LedgerController::class, 'trialBalance'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/ledger/balance-sheet',
    [LedgerController::class, 'balanceSheet'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/ledger/income-statement',
    [LedgerController::class, 'incomeStatement'],
    [
        AuthMiddleware::class,
    ],
);

$router->get(
    '/ledger/{id}',
    [LedgerController::class, 'show'],
    [
        AuthMiddleware::class,
    ],
);

$router->post(
    '/ledger/{id}/approve',
    [LedgerController::class, 'approve'],
    [
        AccountingMiddleware::class,
    ],
);

$router->post(
    '/ledger/{id}/reject',
    [LedgerController::class, 'reject'],
    [
        AccountingMiddleware::class,
    ],
);

$router->post(
    '/ledger/{id}/post',
    [LedgerController::class, 'post'],
    [
        AccountingMiddleware::class,
    ],
);

/*
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

$router->post(
    '/logout',
    [LoginController::class, 'logout'],
    [
        AuthMiddleware::class,
    ],
);
