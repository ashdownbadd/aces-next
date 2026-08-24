<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<div class="ledger-page">

    <header class="page-header">
        <div>
            <h1>Chart of Accounts</h1>
            <p>Accounts configured for ACES double-entry accounting.</p>
        </div>
        <a href="/ledger" class="btn btn--secondary">Journal Vouchers</a>
    </header>

    <section class="card ledger-page__table-wrap">
        <?php if ($accounts === []): ?>
            <div class="ledger-page__empty">
                <h2>No accounts configured</h2>
                <p>Run the Ledger account seeder first.</p>
            </div>
        <?php else: ?>
            <div class="ledger-page__table-scroll">
                <table class="table ledger-page__table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Normal Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><strong><?= $e($account['account_code']) ?></strong></td>
                                <td><?= $e($account['account_name']) ?></td>
                                <td><?= $e($account['account_type']) ?></td>
                                <td><?= $e($account['normal_balance']) ?></td>
                                <td><?= (int) $account['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>
