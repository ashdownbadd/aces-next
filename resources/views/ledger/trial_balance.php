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
            <h1>Trial Balance</h1>
            <p>
                Verify that the Posted ledger remains in balance.
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
        action="/ledger/trial-balance"
        class="ledger-page__filters card">

        <div class="ledger-page__filter-field">
            <label for="trial-balance-as-of">As of</label>
            <input
                id="trial-balance-as-of"
                class="input"
                type="date"
                name="as_of"
                value="<?= $e($asOfDate ?? '') ?>">
        </div>

        <div class="ledger-page__filter-actions">
            <button
                type="submit"
                class="btn btn--secondary">
                Generate Trial Balance
            </button>

            <a
                href="/ledger/trial-balance"
                class="btn btn--outline">
                Clear
            </a>
        </div>

    </form>

    <?php if ($trialBalance !== null): ?>

        <?php
        $balanced = (bool) $trialBalance['balanced'];
        ?>

        <section class="ledger-page__summary-grid ledger-page__summary-grid--three">

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Accounts</span>

                <strong class="ledger-stat__value">
                    <?= count($trialBalance['rows']) ?>
                </strong>

                <span class="ledger-stat__hint">
                    with non-zero Posted balances
                </span>
            </div>

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Total Debit</span>

                <strong class="ledger-stat__value">
                    <?= $money((float) $trialBalance['total_debit']) ?>
                </strong>
            </div>

            <div class="card ledger-stat">
                <span class="ledger-stat__label">Total Credit</span>

                <strong class="ledger-stat__value">
                    <?= $money((float) $trialBalance['total_credit']) ?>
                </strong>
            </div>

        </section>

        <section class="card trial-balance__status <?= $balanced
            ? 'trial-balance__status--balanced'
            : 'trial-balance__status--unbalanced' ?>">

            <strong>
                <?= $balanced
                    ? 'Trial Balance is Balanced'
                    : 'Trial Balance is NOT Balanced' ?>
            </strong>

            <span>
                <?= $balanced
                    ? 'Total debits equal total credits.'
                    : 'There is an accounting imbalance that requires investigation.' ?>
            </span>

        </section>

        <section class="card ledger-detail__lines">

            <div class="ledger-detail__section-header">
                <h2>
                    Trial Balance
                    <?php if (($asOfDate ?? '') !== ''): ?>
                        — As of <?= $e($asOfDate) ?>
                    <?php endif; ?>
                </h2>

                <p>
                    Only Posted journal entries are included.
                </p>
            </div>

            <div class="ledger-detail__table-scroll">

                <table class="table ledger-detail__table trial-balance__table">

                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Normal Balance</th>
                            <th class="ledger-detail__amount">
                                Debit
                            </th>
                            <th class="ledger-detail__amount">
                                Credit
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($trialBalance['rows'] as $row): ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= $e($row['account_code']) ?>
                                    </strong>

                                    <span class="ledger-detail__account-name">
                                        <?= $e($row['account_name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= $e($row['account_type']) ?>
                                </td>

                                <td>
                                    <?= $e($row['normal_balance']) ?>
                                </td>

                                <td class="ledger-detail__amount">
                                    <?= (float) $row['debit'] > 0
                                        ? $money((float) $row['debit'])
                                        : '—' ?>
                                </td>

                                <td class="ledger-detail__amount">
                                    <?= (float) $row['credit'] > 0
                                        ? $money((float) $row['credit'])
                                        : '—' ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        <?php if ($trialBalance['rows'] === []): ?>

                            <tr>
                                <td
                                    colspan="5"
                                    class="ledger-page__empty">
                                    No Posted account balances were found.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="3">
                                Total
                            </th>

                            <th class="ledger-detail__amount">
                                <?= $money(
                                    (float) $trialBalance['total_debit']
                                ) ?>
                            </th>

                            <th class="ledger-detail__amount">
                                <?= $money(
                                    (float) $trialBalance['total_credit']
                                ) ?>
                            </th>
                        </tr>
                    </tfoot>

                </table>

            </div>

        </section>

    <?php endif; ?>

</div>
