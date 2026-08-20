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
                <?= htmlspecialchars($_SESSION['username'] ?? 'Administrator') ?>
            </strong>
            👋
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
                <p class="section__description">
                    Key cooperative statistics.
                </p>
            </div>
        </div>

        <div class="section__body">
            <div class="dashboard__stats">

                <?php foreach ($cards as $card): ?>

                    <?php
                    $title = (string) ($card['title'] ?? '');
                    $value = (string) ($card['value'] ?? 0);
                    $description = (string) ($card['description'] ?? '');
                    $icon = (string) ($card['icon'] ?? 'fas fa-chart-bar');
                    $color = (string) ($card['color'] ?? 'primary');
                    $url = (string) ($card['url'] ?? '');
                    ?>

                    <?php if ($url !== ''): ?>
                        <a
                            class="stat-card stat-card--<?= htmlspecialchars($color) ?>"
                            href="<?= htmlspecialchars($url) ?>">
                    <?php else: ?>
                        <div class="stat-card stat-card--<?= htmlspecialchars($color) ?>">
                    <?php endif; ?>

                        <div class="stat-card__body">

                            <div class="stat-card__content">

                                <span class="stat-card__title">
                                    <?= htmlspecialchars($title) ?>
                                </span>

                                <h2 class="stat-card__value">
                                    <?= htmlspecialchars($value) ?>
                                </h2>

                                <?php if ($description !== ''): ?>
                                    <span class="stat-card__subtitle">
                                        <?= htmlspecialchars($description) ?>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <div class="stat-card__icon">
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
        </div>
    </section>

    <section class="section">
        <div class="section__header">
            <div>
                <h2 class="section__title">System Health</h2>
                <p class="section__description">
                    Monitor issues requiring administrator attention.
                </p>
            </div>
        </div>

        <div class="section__body">

            <?php
            $negativeEquity = $alerts['negative_equity'] ?? [];
            $pastDueLoans = $alerts['past_due_loans'] ?? [];
            $hasAlerts = $negativeEquity !== [] || $pastDueLoans !== [];
            ?>

            <?php if ($hasAlerts): ?>

                <div class="alert alert--warning">

                    <div class="alert__title">
                        <i class="fas fa-triangle-exclamation"></i>
                        System Health Alerts
                    </div>

                    <?php if ($negativeEquity !== []): ?>
                        <p>
                            <strong><?= count($negativeEquity) ?></strong>
                            member(s) currently have a negative share capital balance.
                        </p>
                    <?php endif; ?>

                    <?php if ($pastDueLoans !== []): ?>
                        <p>
                            <strong><?= count($pastDueLoans) ?></strong>
                            loan account(s) are already past due.
                        </p>
                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="alert alert--success">

                    <div class="alert__title">
                        <i class="fas fa-circle-check"></i>
                        System Healthy
                    </div>

                    <p>
                        No negative share capital or overdue loans were detected.
                    </p>

                </div>

            <?php endif; ?>

        </div>
    </section>

</div>
