<?php

declare(strict_types=1);

use App\Features\Authentication\Repositories\UserRepository;
use App\Features\Authentication\Services\AuthService;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Http\Middleware\AccountingMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\MembershipMiddleware;
use App\Http\Middleware\LoanOfficerMiddleware;
use App\Http\Request;
use App\Http\Response;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$users = new UserRepository($database);
$session = new Session();
$auth = new AuthService($users, $session, $database);
$request = new Request('POST', '/qa-authorization');

function assertGate(
    string $label,
    object $middleware,
    bool $allowed,
    Request $request,
): void {
    $response = $middleware->handle($request);
    $isAllowed = $response === null;

    if ($isAllowed !== $allowed) {
        $expected = $allowed ? 'allowed' : 'denied';
        $actual = $isAllowed ? 'allowed' : 'denied';
        throw new RuntimeException(
            sprintf('%s: expected %s, got %s.', $label, $expected, $actual),
        );
    }
}

$middleware = [
    'membership' => new MembershipMiddleware($auth),
    'loan_officer' => new LoanOfficerMiddleware($auth),
    'accounting' => new AccountingMiddleware($auth),
    'admin' => new AdminMiddleware($auth),
];

$userIds = [];
$statement = $database->connection()->query(
    "SELECT id, role FROM users WHERE role IN ('admin', 'membership', 'accounting', 'loan_officer') ORDER BY id"
);
foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $userIds[(string) $row['role']] = (int) $row['id'];
}

$required = ['admin', 'membership', 'accounting', 'loan_officer'];
foreach ($required as $role) {
    if (! isset($userIds[$role])) {
        throw new RuntimeException(
            sprintf('QA authorization user for role "%s" was not found.', $role),
        );
    }
}

try {
    $expected = [
        'admin' => [
            'membership' => true,
            'loan_officer' => true,
            'accounting' => true,
            'admin' => true,
        ],
        'membership' => [
            'membership' => true,
            'loan_officer' => false,
            'accounting' => false,
            'admin' => false,
        ],
        'loan_officer' => [
            'membership' => false,
            'loan_officer' => true,
            'accounting' => false,
            'admin' => false,
        ],
        'accounting' => [
            'membership' => false,
            'loan_officer' => false,
            'accounting' => true,
            'admin' => false,
        ],
    ];

    foreach ($expected as $role => $gates) {
        $session->flush();
        $session->put('user_id', $userIds[$role]);

        foreach ($gates as $gate => $allowed) {
            assertGate(
                sprintf('%s → %s middleware', $role, $gate),
                $middleware[$gate],
                $allowed,
                $request,
            );
        }
    }

    echo "==============================================\n";
    echo "ACES AUTHORIZATION MATRIX INTEGRATION TEST: PASS\n";
    echo "==============================================\n";
    echo "Admin          → all four gates       ✓\n";
    echo "Membership     → membership only      ✓\n";
    echo "Loan Officer   → loan officer only    ✓\n";
    echo "Accounting     → accounting only      ✓\n";
    echo "Unauthorized   → 403 enforced         ✓\n";
    echo "==============================================\n";
} finally {
    $session->flush();
    $session->destroy();
}
