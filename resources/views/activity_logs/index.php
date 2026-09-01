<?php

$actionLabels = [
    'MEMBER_CREATED' => 'Member Created',
    'MEMBER_UPDATED' => 'Member Updated',
    'MEMBER_STATUS_CHANGED' => 'Member Status Changed',
    'MEMBER_BENEFICIARY_ADDED' => 'Beneficiary Added',
    'MEMBER_BENEFICIARY_UPDATED' => 'Beneficiary Updated',
    'MEMBER_BENEFICIARY_REMOVED' => 'Beneficiary Removed',
    'LOAN_PAYMENT_APPLIED' => 'Loan Payment Applied',
    'LOAN_PAYMENT_REVERSED' => 'Loan Payment Reversed',
    'LOAN_REACTIVATED' => 'Loan Reactivated',
    'LOAN_FULLY_PAID' => 'Loan Fully Paid',
    'LOAN_RELEASED' => 'Loan Released',
    'LOAN_AMORTIZATION_GENERATED' => 'Loan Amortization Generated',
];

$actionBadgeTypes = [
    'MEMBER_CREATED' => 'created',
    'MEMBER_UPDATED' => 'updated',
    'MEMBER_STATUS_CHANGED' => 'status-changed',
    'MEMBER_BENEFICIARY_ADDED' => 'beneficiary-added',
    'MEMBER_BENEFICIARY_UPDATED' => 'beneficiary-updated',
    'MEMBER_BENEFICIARY_REMOVED' => 'beneficiary-removed',
    'LOAN_PAYMENT_APPLIED' => 'created',
    'LOAN_PAYMENT_REVERSED' => 'status-changed',
    'LOAN_REACTIVATED' => 'status-changed',
    'LOAN_FULLY_PAID' => 'status-changed',
    'LOAN_RELEASED' => 'created',
    'LOAN_AMORTIZATION_GENERATED' => 'created',
];

$formatActionLabel = static function (string $action) use ($actionLabels): string {
    if (isset($actionLabels[$action])) {
        return $actionLabels[$action];
    }

    return ucwords(
        strtolower(
            str_replace('_', ' ', $action)
        )
    );
};

$getActionBadgeType = static function (string $action) use ($actionBadgeTypes): string {
    if (isset($actionBadgeTypes[$action])) {
        return $actionBadgeTypes[$action];
    }

    if (str_contains($action, '_CREATED')) {
        return 'created';
    }

    if (str_contains($action, '_ADDED')) {
        return 'beneficiary-added';
    }

    if (str_contains($action, '_REMOVED')) {
        return 'beneficiary-removed';
    }

    if (str_contains($action, '_STATUS_')) {
        return 'status-changed';
    }

    if (str_contains($action, '_UPDATED')) {
        return 'updated';
    }

    return 'default';
};

?>

<div class="activity-logs">

    <form method="GET" action="/activity-logs" class="activity-logs__filters" data-live-search data-live-search-server="true">

        <div>
            <label for="activity-search">Search</label>
            <input
                id="activity-search"
                type="search"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                data-live-search
                data-live-search-target="#activity-logs-table-body"
                placeholder="Search activity...">
        </div>

        <div>
            <label for="activity-action">Action</label>
            <select id="activity-action" name="action">
                <option value="">All actions</option>
                <?php foreach ($actions as $availableAction): ?>
                    <option
                        value="<?= htmlspecialchars($availableAction) ?>"
                        <?= $action === $availableAction ? 'selected' : '' ?>>
                        <?= htmlspecialchars(
                            $formatActionLabel(
                                (string) $availableAction
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="activity-user">User</label>
            <select id="activity-user" name="user">
                <option value="0">All users</option>
                <?php foreach ($users as $user): ?>
                    <option
                        value="<?= (int) $user['id'] ?>"
                        <?= $userId === (int) $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(
                            $user['name'] ?: $user['username']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="activity-date-from">From</label>
            <input
                id="activity-date-from"
                type="date"
                name="date_from"
                value="<?= htmlspecialchars($dateFrom) ?>">
        </div>

        <div>
            <label for="activity-date-to">To</label>
            <input
                id="activity-date-to"
                type="date"
                name="date_to"
                value="<?= htmlspecialchars($dateTo) ?>">
        </div>

        <div class="activity-logs__filter-actions">
            <button type="submit" class="btn">Filter</button>
            <a href="/activity-logs" class="btn btn--outline">Clear</a>
        </div>

    </form>

    <div class="activity-logs__table-wrap">

        <?php if ($logs === []): ?>

            <div class="activity-logs__empty">
                <h3>No activity logs found.</h3>
                <p>Try changing your filters or perform an action in ACES.</p>
            </div>

        <?php else: ?>

            <table class="activity-logs__table">

                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Subject</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody id="activity-logs-table-body">
                    <?php foreach ($logs as $log): ?>
                        <tr data-live-search-item="true">
                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime((string) $log['created_at'])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $log['user_name']
                                        ?: ($log['username'] ?? 'System')
                                ) ?>
                            </td>

                            <td class="activity-logs__action">
                                <?php
                                $logAction = (string) $log['action'];
                                $badgeType = $getActionBadgeType($logAction);
                                ?>
                                <a
                                    href="/activity-logs/<?= (int) $log['id'] ?>"
                                    class="activity-logs__action-link">
                                    <span
                                        class="activity-logs__badge activity-logs__badge--<?= htmlspecialchars($badgeType) ?>">
                                        <?= htmlspecialchars(
                                            $formatActionLabel($logAction)
                                        ) ?>
                                    </span>
                                </a>
                            </td>

                            <td class="activity-logs__description">
                                <?= htmlspecialchars(
                                    (string) ($log['description'] ?? '')
                                ) ?>
                            </td>

                            <td>
                                <?php if ($log['subject_type'] !== null): ?>

                                    <?php
                                    $subjectType = (string) $log['subject_type'];
                                    $subjectId = (int) ($log['subject_id'] ?? 0);
                                    $isMemberSubject =
                                        strcasecmp($subjectType, 'Member') === 0
                                        && $subjectId > 0;
                                    ?>

                                    <?php if ($isMemberSubject): ?>

                                        <a href="/members/<?= $subjectId ?>">
                                            Member #<?= $subjectId ?>
                                        </a>

                                    <?php else: ?>

                                        <?= htmlspecialchars($subjectType) ?>

                                        <?php if ($subjectId > 0): ?>
                                            #<?= $subjectId ?>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($log['ip_address'] ?? '—')
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>

        <?php endif; ?>

    </div>

    <?php if ($totalLogs > 0): ?>

        <div class="activity-logs__pagination">

            <div>
                Showing
                <strong><?= $from ?></strong>
                –
                <strong><?= $to ?></strong>
                of
                <strong><?= $totalLogs ?></strong>
                records
            </div>

            <?php if ($totalPages > 1): ?>

                <div class="activity-logs__pagination-links">

                    <?php
                    $query = [
                        'search' => $search,
                        'action' => $action,
                        'user' => $userId > 0 ? $userId : '',
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ];
                    ?>

                    <?php if ($currentPage > 1): ?>
                        <?php $query['page'] = $currentPage - 1; ?>
                        <a
                            class="btn btn--outline btn--sm"
                            href="/activity-logs?<?= http_build_query($query) ?>">
                            ← Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>

                        <?php
                        if (
                            $pageNumber !== 1
                            && $pageNumber !== $totalPages
                            && abs($pageNumber - $currentPage) > 2
                        ) {
                            continue;
                        }

                        $query['page'] = $pageNumber;
                        ?>

                        <a
                            class="btn btn--sm <?= $pageNumber === $currentPage ? '' : 'btn--outline' ?>"
                            href="/activity-logs?<?= http_build_query($query) ?>">
                            <?= $pageNumber ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <?php $query['page'] = $currentPage + 1; ?>
                        <a
                            class="btn btn--outline btn--sm"
                            href="/activity-logs?<?= http_build_query($query) ?>">
                            Next →
                        </a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>