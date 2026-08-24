<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$search = (string) ($search ?? '');
$status = (string) ($status ?? '');
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? 0);

$statusClass = static fn(string $value): string => match ($value) {
    'Pending' => 'pending',
    'Approved' => 'approved',
    'Rejected' => 'rejected',
    'Posted' => 'posted',
    default => 'default',
};
?>

<div class="ledger-page">

    <header class="page-header">
        <div>
            <h1>Journal Vouchers</h1>
            <p>Review, approve, reject, and post accounting transactions.</p>
        </div>
        <a href="/ledger/accounts" class="btn btn--secondary">
            Chart of Accounts
        </a>
    </header>

    <section class="ledger-page__summary-grid">
        <div class="card ledger-stat">
            <span class="ledger-stat__label">Matching Vouchers</span>
            <strong class="ledger-stat__value"><?= $e($total) ?></strong>
        </div>
        <div class="card ledger-stat">
            <span class="ledger-stat__label">Current Page</span>
            <strong class="ledger-stat__value"><?= $e($currentPage) ?></strong>
            <span class="ledger-stat__hint">of <?= $e($totalPages) ?></span>
        </div>
    </section>

    <form method="GET" action="/ledger" class="ledger-page__filters card">
        <div class="ledger-page__filter-field">
            <label for="ledger-search">Search</label>
            <input
                id="ledger-search"
                class="input"
                type="search"
                name="search"
                value="<?= $e($search) ?>"
                placeholder="Reference, particulars, or source">
        </div>

        <div class="ledger-page__filter-field">
            <label for="ledger-status">Status</label>
            <select id="ledger-status" class="input" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $option): ?>
                    <option
                        value="<?= $e($option) ?>"
                        <?= $status === $option ? 'selected' : '' ?>>
                        <?= $e($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ledger-page__filter-actions">
            <button type="submit" class="btn btn--secondary">Filter</button>
            <a href="/ledger" class="btn btn--outline">Clear</a>
        </div>
    </form>

    <section class="card ledger-page__table-wrap">
        <?php if ($vouchers === []): ?>
            <div class="ledger-page__empty">
                <h2>No journal vouchers found</h2>
                <p>There are no vouchers matching the current filters.</p>
            </div>
        <?php else: ?>
            <div class="ledger-page__table-scroll">
                <table class="table ledger-page__table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Particulars</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td><strong><?= $e($voucher['reference_number'] ?? '—') ?></strong></td>
                                <td><?= $e($voucher['transaction_date'] ?? '—') ?></td>
                                <td class="ledger-page__particulars">
                                    <?= $e($voucher['particulars'] ?? '—') ?>
                                </td>
                                <td>
                                    <?php if (!empty($voucher['source_type'])): ?>
                                        <?= $e($voucher['source_type']) ?>
                                        <?php if ((int) ($voucher['source_id'] ?? 0) > 0): ?>
                                            #<?= (int) $voucher['source_id'] ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ledger-status ledger-status--<?= $e($statusClass((string) ($voucher['status'] ?? ''))) ?>">
                                        <?= $e($voucher['status'] ?? '—') ?>
                                    </span>
                                </td>
                                <td><?= $e($voucher['created_by_username'] ?? '—') ?></td>
                                <td>
                                    <a
                                        class="btn btn--secondary btn--sm"
                                        href="/ledger/<?= (int) $voucher['id'] ?>">
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

    <?php if ($totalPages > 1): ?>
        <nav class="ledger-page__pagination" aria-label="Journal voucher pagination">
            <?php if ($currentPage > 1): ?>
                <a
                    class="btn btn--secondary btn--sm"
                    href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $currentPage - 1 ?>">
                    ← Previous
                </a>
            <?php endif; ?>

            <span>Page <?= $e($currentPage) ?> of <?= $e($totalPages) ?></span>

            <?php if ($currentPage < $totalPages): ?>
                <a
                    class="btn btn--secondary btn--sm"
                    href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $currentPage + 1 ?>">
                    Next →
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

</div>
