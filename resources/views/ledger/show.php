<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$money = static fn(float $v): string => '₱' . number_format($v, 2, '.', ',');
$status = (string) ($voucher['status'] ?? '');
$statusClass = match ($status) {
    'Pending' => 'pending',
    'Approved' => 'approved',
    'Rejected' => 'rejected',
    'Posted' => 'posted',
    default => 'default',
};
?>

<div class="ledger-detail">

    <div class="ledger-detail__breadcrumb">
        <a href="/ledger">Journal Vouchers</a>
        <span>/</span>
        <span><?= $e($voucher['reference_number'] ?? '—') ?></span>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert--success"><?= $e($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= $e($error) ?></div>
    <?php endif; ?>

    <header class="ledger-detail__header">
        <div>
            <h1><?= $e($voucher['reference_number'] ?? 'Journal Voucher') ?></h1>
            <p><?= $e($voucher['particulars'] ?? '—') ?></p>
        </div>
        <span class="ledger-status ledger-status--<?= $e($statusClass) ?>">
            <?= $e($status) ?>
        </span>
    </header>

    <section class="card ledger-detail__meta-panel">

        <div class="ledger-detail__meta-grid">

            <div class="ledger-detail__meta">
                <span>Transaction Date</span>
                <strong><?= $e($voucher['transaction_date'] ?? '—') ?></strong>
            </div>

            <div class="ledger-detail__meta">
                <span>Source</span>
                <strong>
                    <?php if (!empty($voucher['source_type'])): ?>
                        <?= $e($voucher['source_type']) ?>
                        <?php if ((int) ($voucher['source_id'] ?? 0) > 0): ?>
                            #<?= (int) $voucher['source_id'] ?>
                        <?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </strong>
            </div>

            <div class="ledger-detail__meta">
                <span>Created By</span>
                <strong><?= $e($voucher['created_by_username'] ?? '—') ?></strong>
            </div>

            <div class="ledger-detail__meta">
                <span>Approved By</span>
                <strong><?= $e($voucher['approved_by_username'] ?? '—') ?></strong>
            </div>

            <div class="ledger-detail__meta">
                <span>Posted By</span>
                <strong><?= $e($voucher['posted_by_username'] ?? '—') ?></strong>
            </div>

            <div class="ledger-detail__meta">
                <span>Reversal Of</span>
                <strong>
                    <?php if ((int) ($voucher['reversal_of_voucher_id'] ?? 0) > 0): ?>
                        <a href="/ledger/<?= (int) $voucher['reversal_of_voucher_id'] ?>">
                            JV #<?= (int) $voucher['reversal_of_voucher_id'] ?>
                        </a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </strong>
            </div>

        </div>

    </section>

    <?php if (!empty($voucher['rejection_reason'])): ?>
        <section class="card ledger-detail__rejection">
            <span>Rejection Reason</span>
            <p><?= $e($voucher['rejection_reason']) ?></p>
        </section>
    <?php endif; ?>

    <section class="card ledger-detail__lines">
        <div class="ledger-detail__section-header">
            <h2>Journal Lines</h2>
            <p>Debit total must equal credit total.</p>
        </div>

        <div class="ledger-detail__table-scroll">
            <table class="table ledger-detail__table">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Description</th>
                        <th>Member</th>
                        <th>Loan</th>
                        <th class="ledger-detail__amount">Debit</th>
                        <th class="ledger-detail__amount">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <tr>
                            <td>
                                <strong><?= $e($line['account_code'] ?? '—') ?></strong>
                                <span class="ledger-detail__account-name">
                                    <?= $e($line['account_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td><?= $e($line['line_description'] ?? '—') ?></td>
                            <td>
                                <?= (int) ($line['member_id'] ?? 0) > 0
                                    ? '#' . (int) $line['member_id']
                                    : '—' ?>
                            </td>
                            <td>
                                <?php if ((int) ($line['loan_id'] ?? 0) > 0): ?>
                                    <a href="/loans/<?= (int) $line['loan_id'] ?>/show">
                                        Loan #<?= (int) $line['loan_id'] ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="ledger-detail__amount">
                                <?= (float) $line['debit'] > 0 ? $money((float) $line['debit']) : '—' ?>
                            </td>
                            <td class="ledger-detail__amount">
                                <?= (float) $line['credit'] > 0 ? $money((float) $line['credit']) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">Total</th>
                        <th class="ledger-detail__amount"><?= $money($debitTotal) ?></th>
                        <th class="ledger-detail__amount"><?= $money($creditTotal) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="ledger-detail__actions">
        <?php if ($status === 'Pending'): ?>
            <form method="POST" action="/ledger/<?= (int) $voucher['id'] ?>/approve">
                <button type="submit" class="btn btn--primary">
                    Approve Voucher
                </button>
            </form>

            <form
                method="POST"
                action="/ledger/<?= (int) $voucher['id'] ?>/reject"
                class="ledger-detail__reject-form">
                <input
                    id="ledger-rejection-reason"
                    class="input"
                    type="text"
                    name="reason"
                    maxlength="1000"
                    required
                    placeholder="Reason for rejection">
                <button type="submit" class="btn btn--danger">
                    Reject Voucher
                </button>
            </form>
        <?php elseif ($status === 'Approved'): ?>
            <form method="POST" action="/ledger/<?= (int) $voucher['id'] ?>/post">
                <button type="submit" class="btn btn--primary">
                    Post Voucher
                </button>
            </form>
        <?php elseif ($status === 'Posted'): ?>
            <div class="ledger-detail__locked">
                Posted vouchers are immutable. Corrections require a reversal.
            </div>
        <?php endif; ?>
    </section>

</div>
