<?php

declare(strict_types=1);

$hour = (int) date('G');

if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

$cards = $cards ?? [];
$alerts = $alerts ?? [
    'negative_equity' => [],
    'past_due_loans' => [],
];
?>

<div class="page dashboard">

    <section class="dashboard-hero">
        <span class="dashboard-hero__eyebrow">
            <?= date('l, F j, Y'); ?>
        </span>

        <h1 class="dashboard-hero__title">
            <?= $greeting ?>,
            <strong>
                <?= htmlspecialchars(
                    $_SESSION['username'] ?? 'Administrator'
                ) ?>
            </strong>
        </h1>

        <p class="dashboard-hero__description">
            Here's the current overview of your cooperative.
        </p>
    </section>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert--success">
            <?= htmlspecialchars((string) $_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert--danger">
            <?= htmlspecialchars((string) $_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <section class="section">
        <div class="section__header">
            <div>
                <h2 class="section__title">System Overview</h2>

            </div>
        </div>

        <div class="section__body">

            <div
                class="dashboard__stats"
                data-dashboard-stats>

                <?php foreach ($cards as $index => $card): ?>

                    <?php
                    $title =
                        (string) ($card['title'] ?? '');

                    $value =
                        (string) ($card['value'] ?? 0);

                    $description =
                        (string) ($card['description'] ?? '');

                    $icon =
                        (string) (
                            $card['icon']
                            ?? 'fas fa-chart-bar'
                        );

                    $color =
                        (string) (
                            $card['color']
                            ?? 'primary'
                        );

                    $url =
                        (string) (
                            $card['url']
                            ?? ''
                        );

                    $isAdditional =
                        $index >= 4;
                    ?>

                    <?php if ($url !== ''): ?>

                        <a
                            class="stat-card stat-card--<?= htmlspecialchars($color) ?><?= $isAdditional ? ' dashboard__stat--additional' : '' ?>"
                            href="<?= htmlspecialchars($url) ?>"
                            <?= $isAdditional ? 'hidden' : '' ?>>

                    <?php else: ?>

                        <div
                            class="stat-card stat-card--<?= htmlspecialchars($color) ?><?= $isAdditional ? ' dashboard__stat--additional' : '' ?>"
                            <?= $isAdditional ? 'hidden' : '' ?>>

                    <?php endif; ?>

                        <div class="stat-card__body">

                            <div class="stat-card__content">

                                <span class="stat-card__title">
                                    <?= htmlspecialchars($title) ?>
                                </span>

                                <h2 class="stat-card__value">
                                    <?= htmlspecialchars($value) ?>
                                </h2>

                                <span class="stat-card__subtitle">
                                    <?= htmlspecialchars($description) ?>
                                </span>

                            </div>

                            <div class="stat-card__icon" aria-hidden="true">
                                <i class="<?= htmlspecialchars($icon) ?>"></i>
                            </div>

                        </div>

                    <?php if ($url !== ''): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

            <?php if (count($cards) > 4): ?>
                <div class="dashboard__stats-toggle">
                    <button
                        type="button"
                        class="dashboard__show-more"
                        data-dashboard-stats-toggle
                        aria-expanded="false">

                        <span data-dashboard-stats-label>
                            Show More
                        </span>

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>

                    </button>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <?php
    $negativeEquity =
        $alerts['negative_equity'] ?? [];

    $pastDueLoans =
        $alerts['past_due_loans'] ?? [];

    $negativeEquityCount = count($negativeEquity);
    $pastDueLoanCount = count($pastDueLoans);
    $hasAlerts =
        $negativeEquityCount > 0
        || $pastDueLoanCount > 0;
    ?>

    <section class="section dashboard-health">

        <div class="section__header">
            <div>
                <h2 class="section__title">
                    Cooperative Alerts
                </h2>

                <p class="section__description">
                    Items that may require administrator attention.
                </p>
            </div>

            <span
                class="dashboard-health__status <?= $hasAlerts ? 'dashboard-health__status--attention' : 'dashboard-health__status--healthy' ?>">
                <span class="dashboard-health__status-dot"></span>
                <?= $hasAlerts ? 'Attention Required' : 'All Clear' ?>
            </span>
        </div>

        <div class="dashboard-health__grid">

            <article
                class="dashboard-health__card <?= $negativeEquityCount > 0 ? 'dashboard-health__card--warning' : 'dashboard-health__card--healthy' ?>">

                <div class="dashboard-health__card-content">

                    <span class="dashboard-health__label">
                        Negative Share Capital
                    </span>

                    <strong class="dashboard-health__value">
                        <?= $negativeEquityCount ?>
                    </strong>

                    <p class="dashboard-health__description">
                        Members with a negative share capital balance.
                    </p>

                </div>

                <div class="dashboard-health__card-icon" aria-hidden="true">
                    <i class="fas fa-scale-unbalanced-flip"></i>
                </div>

            </article>

            <article
                class="dashboard-health__card <?= $pastDueLoanCount > 0 ? 'dashboard-health__card--warning' : 'dashboard-health__card--healthy' ?>">

                <div class="dashboard-health__card-content">

                    <span class="dashboard-health__label">
                        Overdue Loans
                    </span>

                    <strong class="dashboard-health__value">
                        <?= $pastDueLoanCount ?>
                    </strong>

                    <p class="dashboard-health__description">
                        Active loan accounts with overdue periods.
                    </p>

                </div>

                <div class="dashboard-health__card-icon" aria-hidden="true">
                    <i class="fas fa-calendar-xmark"></i>
                </div>

            </article>

        </div>

    </section>

</div>

<script>
(() => {
    const stats = document.querySelector('[data-dashboard-stats]');
    const toggle = document.querySelector('[data-dashboard-stats-toggle]');

    if (!stats || !toggle) {
        return;
    }

    const label = toggle.querySelector('[data-dashboard-stats-label]');
    const hiddenCards =
        [...stats.querySelectorAll('.dashboard__stat--additional')];

    const icon = toggle.querySelector('svg');

    toggle.addEventListener('click', () => {
        const expanded =
            toggle.getAttribute('aria-expanded') === 'true';

        hiddenCards.forEach((card) => {
            card.hidden = expanded;
        });

        toggle.setAttribute(
            'aria-expanded',
            expanded ? 'false' : 'true'
        );

        if (label) {
            label.textContent =
                expanded
                    ? 'Show More'
                    : 'Show Less';
        }

        if (icon) {
            icon.classList.toggle(
                'is-open',
                !expanded
            );
        }
    });
})();
</script>
