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

?>

<div class="loan-list">

    <header class="loan-list__header">
        <div>
            <h1 class="loan-list__title">Loan Applications</h1>
            <p class="loan-list__description">
                Review and manage submitted loan applications.
            </p>
        </div>

        <a class="btn btn--primary" href="/loans/create">
            + Create Loan
        </a>
    </header>

    <section class="card loan-list__filters">

        <form method="GET" action="/loans" class="loan-list__filter-form" data-live-search data-live-search-server="true"-container>

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

            <button type="submit" class="btn btn--secondary">
                Filter
            </button>

        </form>

        <div class="loan-list__summary">
            <?= $e($total) ?> application(s)
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
                                </td>

                                <td class="loan-list__action-column">
                                    <a
                                        class="btn btn--secondary btn--sm"
                                        href="/loans/<?= (int) ($loan['id'] ?? 0) ?>/show">
                                        View
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <?php if ($lastPage > 1): ?>

        <nav class="loan-list__pagination" aria-label="Loan pagination">

            <?php if ($page > 1): ?>
                <a
                    class="btn btn--secondary btn--sm"
                    href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
                    ← Previous
                </a>
            <?php endif; ?>

            <span>
                Page <?= $e($page) ?> of <?= $e($lastPage) ?>
            </span>

            <?php if ($page < $lastPage): ?>
                <a
                    class="btn btn--secondary btn--sm"
                    href="?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
                    Next →
                </a>
            <?php endif; ?>

        </nav>

    <?php endif; ?>

</div>
