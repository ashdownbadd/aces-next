<?php

declare(strict_types=1);

use App\Foundation\Session;
use App\Http\Request;
use App\Foundation\CsrfToken;
use App\Http\Response;

$root = dirname(__DIR__, 3);

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';

/** @var \App\Foundation\Application $app */
$container = $app->container();
$session = $container->get(Session::class);
$csrf = $container->get(CsrfToken::class);
$router = $container->get(\App\Foundation\Router::class);
$database = $container->get(\App\Foundation\Database::class);

/**
 * Read the private response status without changing production Response API.
 */
function responseStatus(Response $response): int
{
    $reflection = new ReflectionClass($response);
    $property = $reflection->getProperty('status');

    return (int) $property->getValue($response);
}

/**
 * @param array<string, int> $userIds
 */
function dispatchAs(
    string $role,
    string $method,
    string $uri,
    array $userIds,
    Session $session,
    CsrfToken $csrf,
    \App\Foundation\Router $router,
): Response {
    $session->flush();
    $session->put('user_id', $userIds[$role]);

    $input = [];

    if ($method === 'POST') {
        $input['_csrf'] = $csrf->token();
    }

    return $router->dispatch(
        new Request(
            method: $method,
            uri: $uri,
            input: $input,
        ),
    );
}

/**
 * @param array<string, int> $userIds
 */
function assertDenied(
    string $label,
    string $role,
    string $method,
    string $uri,
    array $userIds,
    Session $session,
    CsrfToken $csrf,
    \App\Foundation\Router $router,
): void {
    $response = dispatchAs(
        $role,
        $method,
        $uri,
        $userIds,
        $session,
        $csrf,
        $router,
    );

    $status = responseStatus($response);

    if ($status !== 403) {
        throw new RuntimeException(
            sprintf(
                '%s → expected 403, got %d.',
                $label,
                $status,
            ),
        );
    }
}

/**
 * @param array<string, int> $userIds
 */
function assertStatus(
    string $label,
    string $role,
    string $method,
    string $uri,
    int $expectedStatus,
    array $userIds,
    Session $session,
    CsrfToken $csrf,
    \App\Foundation\Router $router,
): void {
    $response = dispatchAs(
        $role,
        $method,
        $uri,
        $userIds,
        $session,
        $csrf,
        $router,
    );

    $status = responseStatus($response);

    if ($status !== $expectedStatus) {
        throw new RuntimeException(
            sprintf(
                '%s → expected %d, got %d.',
                $label,
                $expectedStatus,
                $status,
            ),
        );
    }
}

$rows = $database->connection()->query(
    "SELECT id, role
     FROM users
     WHERE is_active = 1
       AND role IN ('admin', 'membership', 'loan_officer', 'accounting')
     ORDER BY id"
)->fetchAll(PDO::FETCH_ASSOC);

$userIds = [];

foreach ($rows as $row) {
    $role = (string) $row['role'];

    if (! isset($userIds[$role])) {
        $userIds[$role] = (int) $row['id'];
    }
}

foreach (['admin', 'membership', 'loan_officer', 'accounting'] as $role) {
    if (! isset($userIds[$role])) {
        throw new RuntimeException(
            sprintf('Active QA user for role "%s" was not found.', $role),
        );
    }
}

/*
 * These are the routes whose middleware is supposed to enforce
 * role-specific access. The IDs are intentionally arbitrary: an
 * unauthorized request must be rejected before controller lookup,
 * regardless of whether the resource exists.
 */
$membershipRoutes = [
    ['GET', '/members/999999/edit'],
    ['POST', '/members/999999/status'],
    ['POST', '/members/create'],
    ['POST', '/members/register'],
    ['POST', '/members/beneficiaries'],
    ['POST', '/members/beneficiaries/update'],
    ['POST', '/members/beneficiaries/delete'],
];

$loanOfficerRoutes = [
    ['GET', '/loans/create'],
    ['POST', '/loans/create'],
    ['GET', '/loans/999999/review'],
    ['POST', '/loans/999999/submit'],
    ['POST', '/loans/999999/approve'],
    ['POST', '/loans/999999/reject'],
];

$accountingRoutes = [
    ['POST', '/loans/payments/999999/reverse'],
    ['POST', '/loans/999999/payments'],
    ['POST', '/loans/999999/release'],
    ['POST', '/ledger/999999/approve'],
    ['POST', '/ledger/999999/reject'],
    ['POST', '/ledger/999999/post'],
];

try {
    foreach ($membershipRoutes as [$method, $uri]) {
        assertDenied(
            "Membership route bypass by loan officer: {$method} {$uri}",
            'loan_officer',
            $method,
            $uri,
            $userIds,
            $session,
            $csrf,
            $router,
        );

        assertDenied(
            "Membership route bypass by accounting: {$method} {$uri}",
            'accounting',
            $method,
            $uri,
            $userIds,
            $session,
            $csrf,
            $router,
        );
    }

    foreach ($loanOfficerRoutes as [$method, $uri]) {
        assertDenied(
            "Loan officer route bypass by membership: {$method} {$uri}",
            'membership',
            $method,
            $uri,
            $userIds,
            $session,
            $csrf,
            $router,
        );

        assertDenied(
            "Loan officer route bypass by accounting: {$method} {$uri}",
            'accounting',
            $method,
            $uri,
            $userIds,
            $session,
            $csrf,
            $router,
        );
    }

    foreach ($accountingRoutes as [$method, $uri]) {
        assertDenied(
            "Accounting route bypass by membership: {$method} {$uri}",
            'membership',
            $method,
            $uri,
            $userIds,
            $session,
            $csrf,
            $router,
        );

        assertDenied(
            "Accounting route bypass by loan officer: {$method} {$uri}",
            'loan_officer',
            $method,
            $uri,
            $userIds,
            $session,
            $csrf,
            $router,
        );
    }

    /*
     * Method tampering: operation routes must not become reachable by
     * changing POST to GET, and GET-only routes must not accept POST.
     */
    assertStatus(
        'POST-only loan approval via GET',
        'loan_officer',
        'GET',
        '/loans/999999/approve',
        404,
        $userIds,
        $session,
        $csrf,
        $router,
    );

    assertStatus(
        'POST-only ledger posting via GET',
        'accounting',
        'GET',
        '/ledger/999999/post',
        404,
        $userIds,
        $session,
        $csrf,
        $router,
    );

    assertStatus(
        'GET-only loan show via POST',
        'loan_officer',
        'POST',
        '/loans/999999/show',
        404,
        $userIds,
        $session,
        $csrf,
        $router,
    );

    assertStatus(
        'GET-only ledger show via POST',
        'accounting',
        'POST',
        '/ledger/999999',
        404,
        $userIds,
        $session,
        $csrf,
        $router,
    );

    /*
     * CSRF must remain a separate boundary. A correctly-role-authorized
     * actor still cannot invoke a state-changing route without a token.
     */
    $session->flush();
    $session->put('user_id', $userIds['accounting']);

    $csrfBypassResponse = $router->dispatch(
        new Request(
            method: 'POST',
            uri: '/ledger/999999/post',
            input: [],
        ),
    );

    if (responseStatus($csrfBypassResponse) !== 419) {
        throw new RuntimeException(
            sprintf(
                'POST without CSRF token → expected 419, got %d.',
                responseStatus($csrfBypassResponse),
            ),
        );
    }

    /*
     * Authentication boundary: a protected route must not fall through
     * to its controller when there is no authenticated actor.
     */
    $session->flush();

    $guestResponse = $router->dispatch(
        new Request(
            method: 'GET',
            uri: '/dashboard',
        ),
    );

    if (responseStatus($guestResponse) !== 302) {
        throw new RuntimeException(
            sprintf(
                'Unauthenticated dashboard access → expected 302, got %d.',
                responseStatus($guestResponse),
            ),
        );
    }

} finally {
    $session->flush();
    $session->destroy();
}

echo "================================================\n";
echo "ACES AUTHORIZATION BYPASS INTEGRATION TEST: PASS\n";
echo "================================================\n";
echo "Direct membership-route bypass       → blocked ✓\n";
echo "Direct loan-officer bypass           → blocked ✓\n";
echo "Direct accounting-route bypass       → blocked ✓\n";
echo "HTTP method tampering                → blocked ✓\n";
echo "CSRF bypass on state-changing route  → blocked ✓\n";
echo "Unauthenticated route access         → redirected ✓\n";
echo "================================================\n";
