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
$actionRequired = $action_required ?? [
    'under_review_loans' => 0,
    'overdue_loans' => 0,
];
$alerts = $alerts ?? [
    'negative_equity' => [],
    'past_due_loans' => [],
];
?>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 1 1"
    preserveAspectRatio="none"
    style="display:block;position:absolute;width:0;height:0;max-width:0;max-height:0;margin:0;padding:0;overflow:hidden;pointer-events:none;"
    width="0"
    height="0"
    aria-hidden="true"
    focusable="false">
    <defs>
        <clipPath id="dashboard-card-clip" clipPathUnits="objectBoundingBox">
            <path d="M0.0769,0H0.6923A0.0769,0.1,0,0,1,0.7692,0.1V0.1A0.0769,0.1,0,0,0,0.8462,0.2H0.9231A0.0769,0.1,0,0,1,1,0.3V0.9A0.0769,0.1,0,0,1,0.9231,1H0.0769A0.0769,0.1,0,0,1,0,0.9V0.1A0.0769,0.1,0,0,1,0.0769,0Z" />
        </clipPath>
    </defs>
</svg>

<div class="page dashboard">

    <section class="dashboard-hero">
        <span class="dashboard-hero__eyebrow">
            <?= date('l, F j, Y'); ?>
        </span>

        <h1 class="dashboard-hero__title">
            <?= $greeting ?>,
            <strong class="dashboard-hero__user">
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
        <div class="alert alert--success" role="status" aria-live="polite">
            <?= htmlspecialchars((string) $_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert--danger" role="alert">
            <?= htmlspecialchars((string) $_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <section class="section dashboard-overview">
        <div class="section__body">

            <div
                class="dashboard__stats"
                data-dashboard-stats>

                <?php foreach ($cards as $index => $dashboardCard): ?>

                    <?php
                    $title = (string) ($dashboardCard['title'] ?? '');
                    $value = (string) ($dashboardCard['value'] ?? 0);
                    $description = (string) ($dashboardCard['description'] ?? '');
                    $icon = ''; // Overview cards intentionally have no left-side circle.
                    $color = (string) ($dashboardCard['color'] ?? 'primary');
                    $url = (string) ($dashboardCard['url'] ?? '');
                    $hidden = $index >= 4;

                    $class = 'dashboard-stat-card'
                        . ($hidden ? ' dashboard__stat--additional' : '');
                    $shellClass = $hidden ? 'dashboard__stat--additional' : '';

                    $variant = in_array(
                        $color,
                        ['primary', 'success', 'warning', 'danger'],
                        true
                    ) ? $color : 'primary';

                    $href = $url !== '' ? $url : '';
                    $actionUrl = $url !== '' ? $url : '';
                    $actionLabel = $url !== '' ? 'Open ' . $title : '';
                    $actionOutsideCard = true;
                    $body = null;
                    $meta = null;
                    $status = null;
                    $subtitle = null;
                    ?>

                    <?php require __DIR__ . '/../components/card.php'; ?>

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
    $underReviewLoanCount =
        (int) ($actionRequired['under_review_loans'] ?? 0);

    $overdueLoanCount =
        (int) ($actionRequired['overdue_loans'] ?? 0);

    $hasActionRequired =
        $underReviewLoanCount > 0
        || $overdueLoanCount > 0;
    ?>

    <section class="dashboard-actions" aria-labelledby="dashboard-actions-title">

        <div class="dashboard-actions__header">

            <div>
                <span class="dashboard-actions__eyebrow">
                    Attention
                </span>

                <h2
                    class="dashboard-actions__title"
                    id="dashboard-actions-title">
                    Action Required
                </h2>

                <p class="dashboard-actions__description">
                    Work items that may need your attention.
                </p>
            </div>

            <span class="dashboard-actions__count">
                <?= $hasActionRequired
                    ? ($underReviewLoanCount + $overdueLoanCount)
                    : 0 ?>
                <?= ($underReviewLoanCount + $overdueLoanCount) === 1
                    ? 'Item'
                    : 'Items' ?>
            </span>

        </div>

        <?php if ($hasActionRequired): ?>

            <div class="dashboard-actions__grid">

                <?php if ($underReviewLoanCount > 0): ?>

                    <a
                        class="dashboard-actions__item"
                        href="/loans?status=Under%20Review">

                        <span class="dashboard-actions__icon" aria-hidden="true">
                            <i class="fas fa-clipboard-check"></i>
                        </span>

                        <span class="dashboard-actions__content">
                            <strong>
                                <?= $underReviewLoanCount ?>
                                <?= $underReviewLoanCount === 1
                                    ? 'Loan application'
                                    : 'Loan applications' ?>
                                under review
                            </strong>

                            <small>
                                Waiting for an approval decision.
                            </small>
                        </span>

                        <span
                            class="dashboard-actions__arrow"
                            aria-hidden="true">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                    </a>

                <?php endif; ?>

                <?php if ($overdueLoanCount > 0): ?>

                    <a
                        class="dashboard-actions__item"
                        href="/loans">

                        <span class="dashboard-actions__icon dashboard-actions__icon--warning" aria-hidden="true">
                            <i class="fas fa-calendar-xmark"></i>
                        </span>

                        <span class="dashboard-actions__content">
                            <strong>
                                <?= $overdueLoanCount ?>
                                <?= $overdueLoanCount === 1
                                    ? 'Loan'
                                    : 'Loans' ?>
                                overdue
                            </strong>

                            <small>
                                Payment periods require attention.
                            </small>
                        </span>

                        <span
                            class="dashboard-actions__arrow"
                            aria-hidden="true">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                    </a>

                <?php endif; ?>

            </div>

        <?php else: ?>

            <div class="dashboard-actions__empty">

                <div>
                    <strong>Nothing requires attention right now.</strong>
                    <span>
                        Current operational items are up to date.
                    </span>
                </div>

            </div>

        <?php endif; ?>

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
        const hiddenCards = [...stats.querySelectorAll('.dashboard__stat--additional')];

        const icon = toggle.querySelector('svg');

        toggle.addEventListener('click', () => {
            const expanded =
                toggle.getAttribute('aria-expanded') === 'true';

            hiddenCards.forEach((card) => {
                if (expanded) {
                    card.setAttribute('hidden', '');
                    card.setAttribute('aria-hidden', 'true');
                } else {
                    card.removeAttribute('hidden');
                    card.removeAttribute('aria-hidden');
                }
            });

            toggle.setAttribute(
                'aria-expanded',
                expanded ? 'false' : 'true'
            );

            if (label) {
                label.textContent =
                    expanded ?
                    'Show More' :
                    'Show Less';
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