<?php

declare(strict_types=1);

$title = 'Review Loan Application';

$loan = $loan ?? [];
$member = $member ?? [];

$memberName = trim(
    (string) ($member['first_name'] ?? '')
    . ' '
    . (string) ($member['middle_name'] ?? '')
    . ' '
    . (string) ($member['last_name'] ?? '')
);

if ($memberName === '') {
    $memberName = (string) ($member['member_name'] ?? '—');
}

$memberNumber = (string) (
    $member['member_number']
    ?? $loan['member_id']
    ?? '—'
);

$loanType = (string) ($loan['loan_type'] ?? '—');
$collateral = (string) ($loan['collateral'] ?? '—');
$principal = (float) ($loan['principal_amount'] ?? 0);
$interestRate = (float) ($loan['interest_rate'] ?? 0);
$amortizationType = (string) ($loan['amortization_type'] ?? '');
$paymentFrequency = (string) ($loan['payment_frequency'] ?? '');
$manualPayment = $loan['manual_payment'] ?? null;
$termsMonths = (int) ($loan['terms_months'] ?? 0);
$startDate = (string) ($loan['start_date'] ?? '');
$processingFee = (float) ($loan['processing_fee'] ?? 0);
$insurance = (float) ($loan['insurance'] ?? 0);
$notarialFee = (float) ($loan['notarial_fee'] ?? 400);
$netProceeds = (float) ($loan['net_proceeds'] ?? 0);
$tctNo = (string) ($loan['tct_no'] ?? '');
$taxDeclarationNo = (string) ($loan['tax_declaration_no'] ?? '');
$realPropertyPaymentStatus = (string) (
    $loan['real_property_payment_status'] ?? ''
);
$notes = (string) ($loan['notes'] ?? '');
$schedule = $schedule ?? [];
$applicationStatus = (string) (
    $loan['application_status'] ?? 'Pending'
);
$submitted = (bool) ($submitted ?? false);

$hasRealProperty = $collateral === 'Real Property';

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

?>

<div class="loan-review">

    <div class="loan-review__breadcrumb">
        <a href="/loans/create">Loans</a>
        <span>/</span>
        <span>Review Application</span>
    </div>

    <header class="loan-review__header">
        <div>
            <h1 class="loan-review__title">Review Loan Application</h1>
            <p class="loan-review__description">
                Verify the application before submitting it for formal review.
            </p>
        </div>

        <span class="badge">
            <?= $e($submitted ? 'Under Review' : $applicationStatus) ?>
        </span>
    </header>

    <?php if ($submitted): ?>

        <section class="loan-review__handoff">

            <div class="loan-review__handoff-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="m5 12 4 4L19 6" />
                </svg>
            </div>

            <div class="loan-review__handoff-content">

                <span class="loan-review__eyebrow">
                    Application Submitted
                </span>

                <h2>Now Under Review</h2>

                <p>
                    This application has been submitted successfully and is
                    now waiting for an administrator's approval decision.
                </p>

                <div class="loan-review__handoff-meta">

                    <div>
                        <span>Status</span>
                        <strong>Under Review</strong>
                    </div>

                    <div>
                        <span>Next Step</span>
                        <strong>Administrator decision</strong>
                    </div>

                </div>

            </div>

            <div class="loan-review__handoff-actions">

                <a
                    href="/loans?status=Under%20Review"
                    class="btn btn--primary">
                    Open Approval Queue
                </a>

                <a
                    href="/loans/<?= (int) ($loan['id'] ?? 0) ?>/show"
                    class="btn btn--secondary">
                    View Application
                </a>

            </div>

        </section>

    <?php endif; ?>

    <section class="card loan-review__section">

        <div class="loan-review__section-header">
            <div>
                <h2 class="loan-review__section-title">Member Information</h2>
            </div>
        </div>

        <dl class="loan-review__summary">

            <div class="loan-review__summary-item">
                <dt>Member</dt>
                <dd><?= $e($memberName) ?></dd>
            </div>

            <div class="loan-review__summary-item">
                <dt>Member ID</dt>
                <dd><?= $e($memberNumber) ?></dd>
            </div>

        </dl>

    </section>

    <section class="card loan-review__section">

        <div class="loan-review__section-header">
            <div>
                <h2 class="loan-review__section-title">Loan Information</h2>
            </div>
        </div>

        <dl class="loan-review__summary">

            <div class="loan-review__summary-item">
                <dt>Loan Type</dt>
                <dd><?= $e($loanType) ?></dd>
            </div>

            <div class="loan-review__summary-item">
                <dt>Collateral</dt>
                <dd><?= $e($collateral) ?></dd>
            </div>

            <div class="loan-review__summary-item">
                <dt>Principal Amount</dt>
                <dd class="loan-review__value--money"><?= $money($principal) ?></dd>
            </div>

            <div class="loan-review__summary-item">
                <dt>Interest Rate</dt>
                <dd><?= $e(number_format($interestRate, 2)) ?>%</dd>
            </div>

            <?php if ($amortizationType !== ''): ?>

                <div class="loan-review__summary-item">
                    <dt>Amortization Type</dt>
                    <dd><?= $e($amortizationType) ?></dd>
                </div>

            <?php endif; ?>

            <?php if ($paymentFrequency !== ''): ?>

                <div class="loan-review__summary-item">
                    <dt>Payment Frequency</dt>
                    <dd><?= $e($paymentFrequency) ?></dd>
                </div>

            <?php endif; ?>

            <?php if ($manualPayment !== null): ?>

                <div class="loan-review__summary-item">
                    <dt>Manual Payment</dt>
                    <dd class="loan-review__value--money">
                        <?= $money((float) $manualPayment) ?>
                    </dd>
                </div>

            <?php endif; ?>

            <div class="loan-review__summary-item">
                <dt>Terms</dt>
                <dd><?= $e($termsMonths) ?> month(s)</dd>
            </div>

            <div class="loan-review__summary-item">
                <dt>Start Date</dt>
                <dd><?= $e($startDate ?: '—') ?></dd>
            </div>

        </dl>

    </section>

    <section class="card loan-review__section">

        <div class="loan-review__section-header">
            <div>
                <h2 class="loan-review__section-title">Computed Deductions</h2>
            </div>
        </div>

        <div class="loan-review__totals">

            <div class="loan-review__total-row">
                <span>Processing Fee</span>
                <strong><?= $money($processingFee) ?></strong>
            </div>

            <div class="loan-review__total-row">
                <span>Insurance</span>
                <strong><?= $money($insurance) ?></strong>
            </div>

            <div class="loan-review__total-row">
                <span>Notarial Fee</span>
                <strong><?= $money($notarialFee) ?></strong>
            </div>

            <div class="loan-review__total-row loan-review__total-row--grand">
                <span>Net Proceeds</span>
                <strong><?= $money($netProceeds) ?></strong>
            </div>

        </div>

    </section>

    <section class="card loan-review__section">

        <div class="loan-review__section-header">
            <div>
                <h2 class="loan-review__section-title">Amortization Schedule</h2>
                <p class="loan-review__section-description">
                    Preview of the payment schedule that will be generated from these loan terms.
                </p>
            </div>
        </div>

        <?php if ($schedule !== []): ?>

            <div class="loan-review__schedule-wrap">
                <table class="table loan-review__schedule">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Due Date</th>
                            <th scope="col" class="u-text-right">Principal</th>
                            <th scope="col" class="u-text-right">Interest</th>
                            <th scope="col" class="u-text-right">Payment</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($schedule as $row): ?>

                            <?php
                            $rowPrincipal = (float) ($row['principal'] ?? 0);
                            $rowInterest = (float) ($row['interest'] ?? 0);
                            $rowPenalty = (float) ($row['rem_penalty'] ?? 0);
                            $rowPayment = $rowPrincipal + $rowInterest + $rowPenalty;
                            $rowStatus = (string) ($row['status'] ?? 'Pending');
                            ?>

                            <tr>
                                <td><?= (int) ($row['period'] ?? 0) ?></td>
                                <td><?= $e($row['due_date'] ?? '—') ?></td>
                                <td class="u-text-right"><?= $money($rowPrincipal) ?></td>
                                <td class="u-text-right"><?= $money($rowInterest) ?></td>
                                <td class="u-text-right"><?= $money($rowPayment) ?></td>
                                <td><?= $e($rowStatus) ?></td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <p class="loan-review__empty-schedule">
                The amortization schedule preview is unavailable for the current draft values.
            </p>

        <?php endif; ?>

    </section>

    <?php if ($hasRealProperty): ?>

        <section class="card loan-review__section">

            <div class="loan-review__section-header">
                <div>
                    <h2 class="loan-review__section-title">
                        Real Property Information
                    </h2>
                </div>
            </div>

            <dl class="loan-review__summary">

                <div class="loan-review__summary-item">
                    <dt>TCT No.</dt>
                    <dd><?= $e($tctNo ?: '—') ?></dd>
                </div>

                <div class="loan-review__summary-item">
                    <dt>Tax Declaration No.</dt>
                    <dd><?= $e($taxDeclarationNo ?: '—') ?></dd>
                </div>

                <div class="loan-review__summary-item">
                    <dt>Payment Status</dt>
                    <dd><?= $e($realPropertyPaymentStatus ?: '—') ?></dd>
                </div>

            </dl>

        </section>

    <?php endif; ?>

    <?php if ($notes !== ''): ?>

        <section class="card loan-review__section">

            <div class="loan-review__section-header">
                <div>
                    <h2 class="loan-review__section-title">Notes</h2>
                </div>
            </div>

            <p class="loan-review__notes">
                <?= nl2br($e($notes)) ?>
            </p>

        </section>

    <?php endif; ?>

    <div class="loan-review__actions">

        <a
            class="btn btn--secondary"
            href="/loans/create">

            ← Back to Create Loan

        </a>

        <?php if (!$submitted && $applicationStatus === 'Pending'): ?>

            <form
                method="POST"
                action="/loans/<?= (int) ($loan['id'] ?? 0) ?>/submit">

                <button
                    type="submit"
                    class="btn btn--primary">

                    Submit Application →

                </button>

            </form>

        <?php endif; ?>

    </div>

    <?php if (!$submitted && $applicationStatus === 'Pending'): ?>

        <p class="form-help">
            Submitting will change this application from Pending to Under Review.
        </p>

    <?php endif; ?>

</div>
