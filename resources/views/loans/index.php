<?php

declare(strict_types=1);

$title = 'Loan Applications';
$loans = $loans ?? [];
$search = (string) ($search ?? '');
$status = (string) ($status ?? 'Under Review');
$page = (int) ($page ?? 1);
$lastPage = (int) ($lastPage ?? 1);
$total = (int) ($total ?? 0);

$e = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);

$money = static fn (float $value): string => '₱' . number_format(
    $value,
    2,
    '.',
    ','
);

$statuses = [
    'Pending',
    'Under Review',
    'Approved',
    'Rejected',
    'Overdue',
];

$statusDescriptions = [
    'Pending' => 'Applications not yet submitted for review.',
    'Under Review' => 'Applications waiting for an approval decision.',
    'Approved' => 'Applications approved and ready for release.',
    'Rejected' => 'Applications that were rejected.',
    'Overdue' => 'Active loans with overdue payment periods.',
];

$activeStatusDescription =
    $statusDescriptions[$status]
    ?? 'Review and manage cooperative loans.';

$isApprovalQueue = $status === 'Under Review';

?>

<div class="loan-list">

    <header class="loan-list__header loan-list__header--actions">
        <a class="btn btn--primary" href="/loans/create">
            + Create Loan
        </a>
    </header>

    <section class="card loan-list__filters">

        <form method="GET" action="/loans" class="loan-list__filter-form" data-live-search data-live-search-server="true">

            <div class="form-group">
                <label class="form-label" for="loan-search">Search</label>
                <input
                    id="loan-search"
                    name="search"
                    class="input"
                    type="search"
                    value="<?= $e($search) ?>"
                    data-live-search
                    data-live-search-target="#loans-table-body"
                    placeholder="Loan ID, member, or loan type">
            </div>

            <div class="form-group">
                <label class="form-label" for="loan-status">Status</label>
                <select id="loan-status" name="status" class="input">
                    <?php foreach ($statuses as $option): ?>
                        <option
                            value="<?= $e($option) ?>"
                            <?= $status === $option ? 'selected' : '' ?>>
                            <?= $e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="loan-list__filter-actions">

                <button type="submit" class="btn btn--secondary">
                    Apply Filters
                </button>

                <?php if ($search !== '' || $status !== 'Under Review'): ?>

                    <a
                        href="/loans"
                        class="loan-list__clear-filter">
                        Clear
                    </a>

                <?php endif; ?>

            </div>

        </form>

        <div class="loan-list__summary">

            <strong>
                <?= number_format($total) ?>
                <?= $status === 'Overdue'
                    ? 'overdue loans'
                    : 'applications' ?>
            </strong>

            <span class="loan-list__summary-hint">
                <?= $e($activeStatusDescription) ?>
            </span>

        </div>

    </section>

    <section class="card loan-list__table-card">

        <?php if ($loans === []): ?>

            <div class="loan-list__empty">
                <h2>No loan applications found</h2>
                <p>
                    There are no applications matching the current filters.
                </p>
            </div>

        <?php else: ?>

            <div class="loan-list__table-wrap">
                <table class="table loan-list__table">
                    <thead>
                        <tr>
                            <th scope="col">Loan</th>
                            <th scope="col">Member</th>
                            <th scope="col">Loan Type</th>
                            <th scope="col">Principal</th>
                            <th scope="col">Created</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="loan-list__action-column">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody id="loans-table-body">
                        <?php foreach ($loans as $loan): ?>

                            <tr data-live-search-item="true">
                                <td>
                                    <strong>
                                        #<?= (int) ($loan['id'] ?? 0) ?>
                                    </strong>
                                </td>

                                <td>
                                    <div class="loan-list__member">
                                        <strong>
                                            <?= $e($loan['member_name'] ?? '—') ?>
                                        </strong>
                                        <span>
                                            Member #<?= $e($loan['member_number'] ?? '—') ?>
                                        </span>
                                    </div>
                                </td>

                                <td><?= $e($loan['loan_type'] ?? '—') ?></td>

                                <td>
                                    <?= $money((float) ($loan['principal_amount'] ?? 0)) ?>
                                </td>

                                <td><?= $e($loan['created_at'] ?? '—') ?></td>

                                <td>

                                    <span class="badge">
                                        <?= $status === 'Overdue'
                                            ? 'Overdue'
                                            : $e($loan['application_status'] ?? '—') ?>
                                    </span>

                                    <?php if ($status === 'Overdue'): ?>

                                        <div class="loan-list__status-detail">
                                            Active loan with overdue payment period
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td class="loan-list__action-column">

                                    <?php
                                    $loanId = (int) ($loan['id'] ?? 0);
                                    $applicationStatus =
                                        (string) ($loan['application_status'] ?? '');
                                    $loanLifecycleStatus =
                                        (string) ($loan['loan_status'] ?? '');

                                    $actionLabel = match (true) {
                                        $status === 'Overdue' => 'View Loan',
                                        $applicationStatus === 'Under Review' => 'Review',
                                        $applicationStatus === 'Pending' => 'Review',
                                        $applicationStatus === 'Approved'
                                            && $loanLifecycleStatus === '' => 'Release',
                                        $loanLifecycleStatus === 'Active' => 'Manage',
                                        default => 'View Loan',
                                    };
                                    ?>

                                    <a
                                        class="btn btn--secondary btn--sm"
                                        href="/loans/<?= $loanId ?>/show">
                                        <?= $e($actionLabel) ?>
                                    </a>

                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <div class="loan-list__footer">

        <?php
        $from = $total > 0
            ? (($page - 1) * 25) + 1
            : 0;

        $to = $total > 0
            ? min($page * 25, $total)
            : 0;
        ?>

        <div class="loan-list__result-range">

            <?php if ($total > 0): ?>

                Showing
                <?= number_format($from) ?>
                –
                <?= number_format($to) ?>
                of
                <?= number_format($total) ?>
                <?= $status === 'Overdue'
                    ? 'overdue loans'
                    : 'loan applications' ?>

            <?php else: ?>

                No matching applications

            <?php endif; ?>

        </div>

        <?php if ($lastPage > 1): ?>

            <nav
                class="loan-list__pagination"
                aria-label="Loan pagination">

                <?php if ($page > 1): ?>

                    <a
                        class="loan-list__pagination-button"
                        href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
                        Previous
                    </a>

                <?php else: ?>

                    <span class="loan-list__pagination-button is-disabled">
                        Previous
                    </span>

                <?php endif; ?>

                <div class="loan-list__pagination-pages">

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($lastPage, $page + 2);
                    ?>

                    <?php if ($startPage > 1): ?>

                        <a
                            class="loan-list__page-number"
                            href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=1">
                            1
                        </a>

                        <?php if ($startPage > 2): ?>
                            <span class="loan-list__pagination-ellipsis">…</span>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>

                        <?php if ($p === $page): ?>

                            <span
                                class="loan-list__page-number is-current"
                                aria-current="page">
                                <?= $p ?>
                            </span>

                        <?php else: ?>

                            <a
                                class="loan-list__page-number"
                                href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>">
                                <?= $p ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>

                    <?php if ($endPage < $lastPage): ?>

                        <?php if ($endPage < $lastPage - 1): ?>
                            <span class="loan-list__pagination-ellipsis">…</span>
                        <?php endif; ?>

                        <a
                            class="loan-list__page-number"
                            href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $lastPage ?>">
                            <?= $lastPage ?>
                        </a>

                    <?php endif; ?>

                </div>

                <?php if ($page < $lastPage): ?>

                    <a
                        class="loan-list__pagination-button"
                        href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
                        Next
                    </a>

                <?php else: ?>

                    <span class="loan-list__pagination-button is-disabled">
                        Next
                    </span>

                <?php endif; ?>

            </nav>

        <?php endif; ?>

    </div>

</div>
