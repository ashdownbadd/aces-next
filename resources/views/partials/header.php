<?php

$currentRoute = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

function topnavActive(
    string $path,
    string $currentRoute
): string {
    if ($path === '/dashboard') {
        return $currentRoute === '/'
            || $currentRoute === '/dashboard'
            ? ' is-active'
            : '';
    }

    return str_starts_with($currentRoute, $path)
        ? ' is-active'
        : '';
}

?>

<header class="c-navbar">

    <a href="/dashboard" class="c-navbar__brand">
        ACES
    </a>

    <nav class="c-navbar__nav" aria-label="Primary navigation">

        <a href="/dashboard" class="c-navbar__link<?= topnavActive('/dashboard', $currentRoute) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" />
            </svg>
            Dashboard
        </a>

        <a href="/members" class="c-navbar__link<?= topnavActive('/members', $currentRoute) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M16 19v-1.2A3.8 3.8 0 0 0 12.2 14H7.8A3.8 3.8 0 0 0 4 17.8V19m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-1a3 3 0 1 0 0-6m4 11v-1.4A3.6 3.6 0 0 0 17.8 14" />
            </svg>
            Members
        </a>

        <a href="/loans" class="c-navbar__link<?= topnavActive('/loans', $currentRoute) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 3h12a2 2 0 0 1 2 2v14H4V5a2 2 0 0 1 2-2Zm0 4h12M8 11h8M8 15h5" />
            </svg>
            Loans
        </a>

        <a href="/ledger" class="c-navbar__link<?= topnavActive('/ledger', $currentRoute) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5" />
            </svg>
            Ledger
        </a>

        <a href="/activity-logs" class="c-navbar__link<?= topnavActive('/activity-logs', $currentRoute) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 5h14v14H5zM8 9h8M8 12h6M8 15h4" />
            </svg>
            Activity Logs
        </a>

    </nav>

    <div class="c-navbar__actions">

        <span class="c-navbar__user">
            <span class="c-navbar__status"></span>
            Administrator
        </span>

        <a href="/logout" class="btn btn--outline btn--sm">
            Logout
        </a>

    </div>

</header>
