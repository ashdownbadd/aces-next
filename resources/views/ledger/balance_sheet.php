<?php

$e = static fn(mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8',
);

$money = static fn(float $value): string => '₱' . number_format(
    $value,
    2,
    '.',
    ',',
);

?>

<div class="ledger-page">

    <header class="page-header">
        <div>
            <h1>Statement of Financial Position</h1>
            <p>
                View the cooperative's assets, liabilities, and equity.
            </p>
        </div>

        <div class="ledger-page__header-actions">
    <a href="/ledger" class="btn btn--secondary">Journal Vouchers</a>
    <a href="/ledger/general" class="btn btn--secondary">General Ledger</a>
    <a href="/ledger/trial-balance" class="btn btn--secondary">Trial Balance</a>
    <a href="/ledger/balance-sheet" class="btn btn--secondary">Financial Position</a>
    <a href="/ledger/income-statement" class="btn btn--secondary">Statement of Operations</a>
    <a href="/ledger/accounts" class="btn btn--secondary">Chart of Accounts</a>
</div>
    </header>

    <?php if (!empty($error)): ?>
        <div class="alert alert--error">
            <?= $e($error) ?>
        </div>
    <?php endif; ?>

    <form
        method="GET"
        action="/ledger/balance-sheet"
        class="ledger-page__filters card">

        <div class="ledger-page__filter-field">
            <label for="balance-sheet-as-of">As of</label>
            <input
                id="balance-sheet-as-of"
                class="input"
                type="date"
                name="as_of"
                value="<?= $e($asOfDate ?? '') ?>">
        </div>

        <div class="ledger-page__filter-actions">
            <button
                type="submit"
                class="btn btn--secondary">
                Generate Statement
            </button>

            <a
                href="/ledger/balance-sheet"
                class="btn btn--outline">
                Clear
            </a>
        </div>

    </form>

    <?php if ($balanceSheet !== null): ?>

        <?php $balanced = (bool) $balanceSheet['balanced']; ?>

        <section class="ledger-page__summary-grid ledger-page__summary-grid--three">

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Total Assets</span>
                <strong class="ledger-stat__value">
                    <?= $money((float) $balanceSheet['total_assets']) ?>
                </strong>
            </div>

            <div class="card ledger-stat">
                <span class="ledger-stat__label">
                    Total Liabilities
                </span>
                <strong class="ledger-stat__value">
                    <?= $money((float) $balanceSheet['total_liabilities']) ?>
                </strong>
            </div>

            <div class="card ledger-stat">
                <span class="ledger-stat__label">
                    Total Equity
                </span>
                <strong class="ledger-stat__value">
                    <?= $money((float) $balanceSheet['total_equity']) ?>
                </strong>
            </div>

        </section>

        <section class="card trial-balance__status <?= $balanced
            ? 'trial-balance__status--balanced'
            : 'trial-balance__status--unbalanced' ?>">

            <strong>
                <?= $balanced
                    ? 'Statement is Balanced'
                    : 'Statement is NOT Balanced' ?>
            </strong>

            <span>
                Assets
                <?= $money((float) $balanceSheet['total_assets']) ?>
                =
                Liabilities + Equity
                <?= $money((float) $balanceSheet['liabilities_and_equity']) ?>
            </span>
        </section>

        <section class="balance-sheet">

            <section class="card ledger-detail__lines">
                <div class="ledger-detail__section-header">
                    <h2>Assets</h2>
                    <p>Posted asset balances as of the selected date.</p>
                </div>

                <div class="ledger-detail__table-scroll">
                    <table class="table ledger-detail__table balance-sheet__table">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="ledger-detail__amount">
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($balanceSheet['assets'] as $row): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= $e($row['account_code']) ?>
                                        </strong>
                                        <span class="ledger-detail__account-name">
                                            <?= $e($row['account_name']) ?>
                                        </span>
                                    </td>

                                    <td class="ledger-detail__amount">
                                        <?= $money((float) $row['balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($balanceSheet['assets'] === []): ?>
                                <tr>
                                    <td colspan="2" class="ledger-page__empty">
                                        No non-zero asset balances.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total Assets</th>
                                <th class="ledger-detail__amount">
                                    <?= $money(
                                        (float) $balanceSheet['total_assets']
                                    ) ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="card ledger-detail__lines">
                <div class="ledger-detail__section-header">
                    <h2>Liabilities</h2>
                    <p>Posted liability balances as of the selected date.</p>
                </div>

                <div class="ledger-detail__table-scroll">
                    <table class="table ledger-detail__table balance-sheet__table">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="ledger-detail__amount">
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($balanceSheet['liabilities'] as $row): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= $e($row['account_code']) ?>
                                        </strong>
                                        <span class="ledger-detail__account-name">
                                            <?= $e($row['account_name']) ?>
                                        </span>
                                    </td>

                                    <td class="ledger-detail__amount">
                                        <?= $money((float) $row['balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($balanceSheet['liabilities'] === []): ?>
                                <tr>
                                    <td colspan="2" class="ledger-page__empty">
                                        No non-zero liability balances.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total Liabilities</th>
                                <th class="ledger-detail__amount">
                                    <?= $money(
                                        (float) $balanceSheet['total_liabilities']
                                    ) ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="card ledger-detail__lines">
                <div class="ledger-detail__section-header">
                    <h2>Equity</h2>
                    <p>
                        Permanent equity plus current-period surplus/deficit.
                    </p>
                </div>

                <div class="ledger-detail__table-scroll">
                    <table class="table ledger-detail__table balance-sheet__table">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="ledger-detail__amount">
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($balanceSheet['equity'] as $row): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= $e($row['account_code']) ?>
                                        </strong>
                                        <span class="ledger-detail__account-name">
                                            <?= $e($row['account_name']) ?>
                                        </span>
                                    </td>

                                    <td class="ledger-detail__amount">
                                        <?= $money((float) $row['balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($balanceSheet['equity'] === []): ?>
                                <tr>
                                    <td colspan="2" class="ledger-page__empty">
                                        No non-zero equity balances.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total Equity</th>
                                <th class="ledger-detail__amount">
                                    <?= $money(
                                        (float) $balanceSheet['total_equity']
                                    ) ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

        </section>

        <section class="card balance-sheet__equation">
            <div>
                <span class="ledger-stat__label">
                    Accounting Equation
                </span>

                <strong>
                    <?= $money((float) $balanceSheet['total_assets']) ?>
                    =
                    <?= $money(
                        (float) $balanceSheet['total_liabilities']
                    ) ?>
                    +
                    <?= $money((float) $balanceSheet['total_equity']) ?>
                </strong>
            </div>

            <span class="ledger-stat__hint">
                Current period surplus:
                <?= $money((float) $balanceSheet['net_surplus']) ?>
            </span>
        </section>

    <?php endif; ?>

</div>
