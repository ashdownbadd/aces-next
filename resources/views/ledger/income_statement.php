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
            <h1>Statement of Operations</h1>
            <p>
                View income, expenses, and the resulting surplus or deficit.
            </p>
        </div>

        <div class="ledger-page__header-actions">
            <a href="/ledger" class="btn btn--secondary">Journal Vouchers</a>
            <a href="/ledger/general" class="btn btn--secondary">General Ledger</a>
            <a href="/ledger/trial-balance" class="btn btn--secondary">Trial Balance</a>
            <a href="/ledger/balance-sheet" class="btn btn--secondary">Financial Position</a>
            <a href="/ledger/income-statement" class="btn btn--secondary" aria-current="page">
                Statement of Operations
            </a>
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
        action="/ledger/income-statement"
        class="ledger-page__filters card">

        <div class="ledger-page__filter-field">
            <label for="statement-from">From</label>
            <input
                id="statement-from"
                class="input"
                type="date"
                name="date_from"
                value="<?= $e($dateFrom ?? '') ?>">
        </div>

        <div class="ledger-page__filter-field">
            <label for="statement-to">To</label>
            <input
                id="statement-to"
                class="input"
                type="date"
                name="date_to"
                value="<?= $e($dateTo ?? '') ?>">
        </div>

        <div class="ledger-page__filter-actions">
            <button type="submit" class="btn btn--secondary">
                Generate Statement
            </button>

            <a href="/ledger/income-statement" class="btn btn--outline">
                Clear
            </a>
        </div>
    </form>

    <?php if ($incomeStatement !== null): ?>

        <section class="ledger-page__summary-grid ledger-page__summary-grid--three">

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Total Income</span>
                <strong class="ledger-stat__value">
                    <?= $money((float) $incomeStatement['total_income']) ?>
                </strong>
            </div>

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Total Expenses</span>
                <strong class="ledger-stat__value">
                    <?= $money((float) $incomeStatement['total_expenses']) ?>
                </strong>
            </div>

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Net Surplus / Deficit</span>
                <strong class="ledger-stat__value">
                    <?= $money((float) $incomeStatement['net_surplus']) ?>
                </strong>
            </div>

        </section>

        <section class="card statement-result__status">
            <strong>
                <?= (float) $incomeStatement['net_surplus'] >= 0
                    ? 'Current Period Surplus'
                    : 'Current Period Deficit' ?>
            </strong>

            <span>
                Income minus expenses =
                <?= $money((float) $incomeStatement['net_surplus']) ?>
            </span>
        </section>

        <section class="statement-result">

            <?php foreach ([
                [
                    'title' => 'Income',
                    'rows' => $incomeStatement['income'],
                    'total' => $incomeStatement['total_income'],
                    'empty' => 'No posted income was recorded for this period.',
                ],
                [
                    'title' => 'Expenses',
                    'rows' => $incomeStatement['expenses'],
                    'total' => $incomeStatement['total_expenses'],
                    'empty' => 'No posted expenses were recorded for this period.',
                ],
            ] as $section): ?>

                <section class="card ledger-detail__lines">

                    <div class="ledger-detail__section-header">
                        <h2><?= $e($section['title']) ?></h2>
                        <p>
                            Posted <?= strtolower($e($section['title'])) ?>
                            accounts for the selected period.
                        </p>
                    </div>

                    <div class="ledger-detail__table-scroll">
                        <table class="table ledger-detail__table statement-result__table">

                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="ledger-detail__amount">Amount</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($section['rows'] as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $e($row['account_code']) ?></strong>
                                            <span class="ledger-detail__account-name">
                                                <?= $e($row['account_name']) ?>
                                            </span>
                                        </td>

                                        <td class="ledger-detail__amount">
                                            <?= $money((float) $row['balance']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if ($section['rows'] === []): ?>
                                    <tr>
                                        <td colspan="2" class="ledger-page__empty">
                                            <?= $e($section['empty']) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th>Total <?= $e($section['title']) ?></th>
                                    <th class="ledger-detail__amount">
                                        <?= $money((float) $section['total']) ?>
                                    </th>
                                </tr>
                            </tfoot>

                        </table>
                    </div>

                </section>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

</div>
