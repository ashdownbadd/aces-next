<?php

$currentRoute = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

function sidebarActive(
    string $path,
    string $currentRoute
): string {
    if ($path === '/dashboard') {
        return $currentRoute === '/' || $currentRoute === '/dashboard'
            ? ' class="is-active"'
            : '';
    }

    if ($path === '/loans') {
        return str_starts_with($currentRoute, '/loans')
            ? ' class="is-active"'
            : '';
    }

    if ($path === '/members') {
        return str_starts_with($currentRoute, '/members')
            ? ' class="is-active"'
            : '';
    }

    if ($path === '/activity-logs') {
        return str_starts_with($currentRoute, '/activity-logs')
            ? ' class="is-active"'
            : '';
    }

    if ($path === '/ledger') {
        return str_starts_with($currentRoute, '/ledger')
            ? ' class="is-active"'
            : '';
    }

    return $currentRoute === $path ? ' class="is-active"' : '';
}
?>

<aside class="sidebar">

    <nav>

        <a
            href="/dashboard"
            <?= sidebarActive('/dashboard', $currentRoute) ?>>
            Dashboard
        </a>

        <a
            href="/members"
            <?= sidebarActive('/members', $currentRoute) ?>>
            Members
        </a>

        <a
            href="/activity-logs"
            <?= sidebarActive('/activity-logs', $currentRoute) ?>>
            Activity Logs
        </a>

        <a
            href="/loans"
            <?= sidebarActive('/loans', $currentRoute) ?>>
            Loans
        </a>

        <a href="#">Savings</a>
        <a
            href="/ledger"
            <?= sidebarActive('/ledger', $currentRoute) ?>>
            Ledger
        </a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>

    </nav>

</aside>