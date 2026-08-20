<?php

declare(strict_types=1);

$title = 'Loan Application';

$loan = $loan ?? [];
$member = $member ?? [];
$schedule = $schedule ?? [];
$payments = $payments ?? [];
$error = (string) ($error ?? '');
$success = (string) ($success ?? '');

$memberName = (string) (
    $loan['member_name']
    ?? '—'
);

$memberNumber = (string) (
    $loan['member_number']
    ?? ($member['member_number'] ?? '—')
);

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

$status = (string) ($loan['application_status'] ?? '');
$loanStatus = (string) ($loan['loan_status'] ?? '');

?>

<div class="loan-detail">

    <div class="loan-detail__breadcrumb">
        <a href="/loans">Loan Applications</a>
        <span>/</span>
        <span>#<?= (int) ($loan['id'] ?? 0) ?></span>
    </div>

    <header class="loan-detail__header">

        <div>
            <h1 class="loan-detail__title">
                Loan Application #<?= (int) ($loan['id'] ?? 0) ?>
            </h1>

            <p class="loan-detail__description">
                Review the application and take the next workflow action.
            </p>
        </div>

        <div class="loan-detail__status-group">
            <?php if ($status !== ''): ?>
                <span class="badge"><?= $e($status) ?></span>
            <?php endif; ?>

            <?php if ($loanStatus !== ''): ?>
                <span class="badge"><?= $e($loanStatus) ?></span>
            <?php endif; ?>
        </div>

    </header>

    <?php if (in_array($loanStatus, ['Active', 'Fully Paid'], true)): ?>

        <div class="loan-detail__report-actions">
            <a
                class="btn btn--secondary"
                href="/loans/<?= (int) ($loan['id'] ?? 0) ?>/statement-of-account">
                Export Statement of Account
            </a>
        </div>

    <?php endif; ?>

    <?php if ($success === 'payment-reversed'): ?>
        <div class="alert alert--success">
            Payment reversed successfully. The affected amortization balances have been restored.
        </div>
    <?php elseif ($success === 'approved'): ?>
        <div class="alert alert--success">
            Loan application approved successfully.
        </div>
    <?php elseif ($success === 'released'): ?>
        <div class="alert alert--success">
            Loan released successfully. The loan is now Active and its schedule has been persisted.
        </div>
    <?php elseif ($success === 'rejected'): ?>
        <div class="alert alert--success">
            Loan application rejected successfully.
        </div>
    <?php elseif ($error !== ''): ?>
        <div class="alert alert--error">
            <?= $e($error) ?>
        </div>
    <?php endif; ?>

    <section class="card loan-detail__section">

        <div class="loan-detail__section-header">
            <h2>Member Information</h2>
        </div>

        <dl class="loan-detail__summary">

            <div>
                <dt>Member</dt>
                <dd><?= $e($memberName) ?></dd>
            </div>

            <div>
                <dt>Member ID</dt>
                <dd><?= $e($memberNumber) ?></dd>
            </div>

        </dl>

    </section>

    <section class="card loan-detail__section">

        <div class="loan-detail__section-header">
            <h2>Loan Information</h2>
        </div>

        <dl class="loan-detail__summary">

            <div>
                <dt>Loan Type</dt>
                <dd><?= $e($loan['loan_type'] ?? '—') ?></dd>
            </div>

            <div>
                <dt>Collateral</dt>
                <dd><?= $e($loan['collateral'] ?? '—') ?></dd>
            </div>

            <div>
                <dt>Principal</dt>
                <dd><?= $money((float) ($loan['principal_amount'] ?? 0)) ?></dd>
            </div>

            <div>
                <dt>Interest Rate</dt>
                <dd><?= number_format((float) ($loan['interest_rate'] ?? 0), 2) ?>%</dd>
            </div>

            <div>
                <dt>Amortization Type</dt>
                <dd><?= $e($loan['amortization_type'] ?? '—') ?></dd>
            </div>

            <div>
                <dt>Payment Frequency</dt>
                <dd><?= $e($loan['payment_frequency'] ?? '—') ?></dd>
            </div>

            <div>
                <dt>Terms</dt>
                <dd><?= (int) ($loan['terms_months'] ?? 0) ?> month(s)</dd>
            </div>

            <div>
                <dt>Start Date</dt>
                <dd><?= $e($loan['start_date'] ?? '—') ?></dd>
            </div>

            <div>
                <dt>Net Proceeds</dt>
                <dd><?= $money((float) ($loan['net_proceeds'] ?? 0)) ?></dd>
            </div>

        </dl>

    </section>

    <?php if ($status === 'Rejected'): ?>

        <section class="card loan-detail__section">

            <div class="loan-detail__section-header">
                <h2>Rejection</h2>
            </div>

            <p class="loan-detail__rejection">
                <?= nl2br($e($loan['rejection_reason'] ?? '—')) ?>
            </p>

        </section>

    <?php endif; ?>


    <?php if ($loanStatus === 'Active'): ?>

        <?php
        $nextAmountDue = 0.00;
        $totalOverdue = 0.00;
        $totalOutstanding = 0.00;
        $nextDueDate = null;
        $foundNext = false;

        foreach ($schedule as $row) {
            $remPrincipal = (float) ($row['rem_principal'] ?? 0);
            $remInterest = (float) ($row['rem_interest'] ?? 0);
            $remPenalty = (float) ($row['rem_penalty'] ?? 0);

            $rowOutstanding = $remPrincipal
                + $remInterest
                + $remPenalty;

            $totalOutstanding += $rowOutstanding;

            $rowStatus = (string) ($row['status'] ?? '');

            if ($rowStatus === 'Overdue') {
                $totalOverdue += $rowOutstanding;
            }

            if (
                !$foundNext
                && $rowStatus !== 'Paid'
                && $rowOutstanding > 0.004
            ) {
                $nextAmountDue = $rowOutstanding;
                $nextDueDate = (string) ($row['due_date'] ?? '');
                $foundNext = true;
            }
        }

        $totalOutstanding = round($totalOutstanding, 2);
        $totalOverdue = round($totalOverdue, 2);
        $nextAmountDue = round($nextAmountDue, 2);
        ?>

        <section class="card loan-detail__section">

            <div class="loan-detail__section-header">
                <div>
                    <h2>Payment Summary</h2>
                    <p>
                        Payments settle the earliest unpaid installment first:
                        penalty → interest → principal.
                    </p>
                </div>
            </div>

            <div class="loan-payment__summary">

                <div>
                    <span>Next Amount Due</span>
                    <strong><?= $money($nextAmountDue) ?></strong>

                    <?php if ($nextDueDate !== null): ?>
                        <small><?= $e($nextDueDate) ?></small>
                    <?php endif; ?>
                </div>

                <div>
                    <span>Total Overdue</span>
                    <strong><?= $money($totalOverdue) ?></strong>
                </div>

                <div>
                    <span>Total Outstanding</span>
                    <strong><?= $money($totalOutstanding) ?></strong>
                </div>

                <div class="loan-payment__summary--total">
                    <span>Current Installment</span>
                    <strong><?= $money($nextAmountDue) ?></strong>
                </div>

            </div>

            <form
                method="POST"
                action="/loans/<?= (int) ($loan['id'] ?? 0) ?>/payments"
                class="loan-payment__form">

                <div class="form-group">
                    <label class="form-label" for="amount-paid">
                        Amount Paid
                    </label>

                    <input
                        id="amount-paid"
                        name="amount_paid"
                        class="input"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="payment-remarks">
                        Remarks
                    </label>

                    <input
                        id="payment-remarks"
                        name="remarks"
                        class="input"
                        type="text"
                        maxlength="1000">
                </div>

                <button
                    type="submit"
                    class="btn btn--primary">
                    Apply Payment
                </button>

            </form>

        </section>

    <?php endif; ?>

    <?php if ($loanStatus === 'Fully Paid'): ?>

        <section class="card loan-detail__section loan-detail__fully-paid-message">

            <div class="loan-detail__section-header">
                <div>
                    <h2>Loan Fully Paid</h2>
                    <p>
                        This loan has no remaining balance. Payment entry is disabled.
                        The Statement of Account remains available for reporting.
                    </p>
                </div>
            </div>

        </section>

    <?php endif; ?>

    <section class="card loan-detail__section">

        <div class="loan-detail__section-header">
            <div>
                <h2>Amortization Schedule</h2>
                <p>
                    The persisted schedule and current installment balances.
                </p>
            </div>
        </div>

        <?php if ($schedule !== []): ?>

            <div class="loan-detail__table-wrap">

                <table class="table loan-detail__table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Principal</th>
                            <th>Interest</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($schedule as $row): ?>

                            <?php
                            $principal = (float) ($row['principal'] ?? 0);
                            $interest = (float) ($row['interest'] ?? 0);
                            $remPrincipal = (float) ($row['rem_principal'] ?? $principal);
                            $remInterest = (float) ($row['rem_interest'] ?? $interest);
                            $remPenalty = (float) ($row['rem_penalty'] ?? 0);
                            $payment = $remPrincipal + $remInterest + $remPenalty;
                            ?>

                            <tr>
                                <td><?= (int) ($row['period'] ?? 0) ?></td>
                                <td><?= $e($row['due_date'] ?? '—') ?></td>
                                <td><?= $money($principal) ?></td>
                                <td><?= $money($interest) ?></td>
                                <td><?= $money($payment) ?></td>
                                <td><?= $e($row['status'] ?? '—') ?></td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php else: ?>

            <p class="loan-detail__empty">
                No schedule preview is available.
            </p>

        <?php endif; ?>

    </section>

    <section class="card loan-detail__section">

        <div class="loan-detail__section-header">
            <div>
                <h2>Payment Ledger</h2>
                <p>
                    Every applied payment remains in the financial history.
                    Reversed payments are retained and marked Reversed.
                </p>
            </div>
        </div>

        <?php if ($payments !== []): ?>

            <div class="loan-detail__table-wrap">

                <table class="table loan-detail__table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount Paid</th>
                            <th>Penalty</th>
                            <th>Interest</th>
                            <th>Principal</th>
                            <th>Excess</th>
                            <th>Remarks</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($payments as $payment): ?>

                            <?php
                            $paymentId = (int) ($payment['id'] ?? 0);
                            $reversed = !empty($payment['reversed_at']);
                            ?>

                            <tr>
                                <td><?= $e($payment['payment_datetime'] ?? '—') ?></td>
                                <td><?= $money((float) ($payment['amount_paid'] ?? 0)) ?></td>
                                <td><?= $money((float) ($payment['penalty_applied'] ?? 0)) ?></td>
                                <td><?= $money((float) ($payment['interest_applied'] ?? 0)) ?></td>
                                <td><?= $money((float) ($payment['principal_applied'] ?? 0)) ?></td>
                                <td><?= $money((float) ($payment['excess'] ?? 0)) ?></td>
                                <td><?= $e($payment['remarks'] ?? '—') ?></td>

                                <td>
                                    <?php if ($reversed): ?>
                                        <span class="badge">Reversed</span>
                                        <small>
                                            <?= $e($payment['reversal_reason'] ?? '') ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge">Applied</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!$reversed && $paymentId > 0): ?>

                                        <form
                                            method="POST"
                                            action="/loans/payments/<?= $paymentId ?>/reverse"
                                            class="loan-payment__reverse-form">

                                            <input
                                                name="reason"
                                                class="input"
                                                type="text"
                                                maxlength="1000"
                                                placeholder="Reversal reason"
                                                required>

                                            <button
                                                type="submit"
                                                class="btn btn--danger btn--sm">
                                                Reverse
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span>—</span>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php else: ?>

            <p class="loan-detail__empty">
                No payments have been recorded for this loan.
            </p>

        <?php endif; ?>

    </section>

    <?php if ($status === 'Approved' && ($loanStatus === '' || $loanStatus === null)): ?>

        <section class="card loan-detail__section">

            <div class="loan-detail__section-header">
                <div>
                    <h2>Release Loan</h2>
                    <p>
                        Confirm the release date. Releasing the loan changes the
                        financial status to Active and persists the amortization schedule.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="/loans/<?= (int) ($loan['id'] ?? 0) ?>/release"
                class="loan-detail__release-form">

                <div class="form-group">
                    <label class="form-label" for="release-date">
                        Release Date
                    </label>

                    <input
                        id="release-date"
                        name="release_date"
                        class="input"
                        type="date"
                        value="<?= $e(date('Y-m-d')) ?>"
                        required>

                    <span class="form-help">
                        The release date is recorded with the loan and used when
                        the amortization schedule is persisted.
                    </span>
                </div>

                <div class="loan-detail__release-warning">
                    <strong>Ready for release</strong>
                    <span>
                        This will change the loan to Active and create the persisted
                        amortization schedule.
                    </span>
                </div>

                <button
                    type="submit"
                    class="btn btn--primary">
                    Release Loan
                </button>

            </form>

        </section>

    <?php endif; ?>

    <?php if ($status === 'Under Review'): ?>

        <section class="card loan-detail__section">

            <div class="loan-detail__section-header">
                <div>
                    <h2>Decision</h2>
                    <p>
                        One approver must make the final application decision.
                    </p>
                </div>
            </div>

            <div class="loan-detail__decision">

                <form
                    method="POST"
                    action="/loans/<?= (int) ($loan['id'] ?? 0) ?>/approve">

                    <button type="submit" class="btn btn--primary">
                        Approve Loan
                    </button>

                </form>

                <form
                    method="POST"
                    action="/loans/<?= (int) ($loan['id'] ?? 0) ?>/reject"
                    class="loan-detail__reject-form">

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="rejection-reason">
                            Rejection Reason
                        </label>

                        <textarea
                            id="rejection-reason"
                            name="reason"
                            class="input"
                            rows="4"
                            required></textarea>

                    </div>

                    <button type="submit" class="btn btn--danger">
                        Reject Loan
                    </button>

                </form>

            </div>

        </section>

    <?php endif; ?>

</div>
