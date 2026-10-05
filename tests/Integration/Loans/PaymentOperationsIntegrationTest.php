<?php

declare(strict_types=1);

/**
 * ACES Payment Operations — real MySQL integration test.
 *
 * IMPORTANT:
 * - Run this from the ACES project root.
 * - It writes temporary records to the configured database.
 * - It cleans up every record it creates.
 * - It does NOT modify production PHP source files.
 *
 * Run:
 *   php tests/Integration/Loans/PaymentOperationsIntegrationTest.php
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanStatus;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf(
                '%s Expected %s, got %s.',
                $message,
                var_export($expected, true),
                var_export($actual, true),
            )
        );
    }
}

function assertNear(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(
            sprintf('%s Expected %.2f, got %.2f.', $message, $expected, $actual)
        );
    }
}

function assertThrows(callable $callback, string $messageNeedle, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (!str_contains($exception->getMessage(), $messageNeedle)) {
            throw new RuntimeException(
                sprintf(
                    '%s Unexpected exception: %s',
                    $message,
                    $exception->getMessage(),
                )
            );
        }

        return;
    }

    throw new RuntimeException(
        sprintf('%s Expected an exception containing "%s".', $message, $messageNeedle)
    );
}

function paymentToken(string $seed): string
{
    return substr(hash('sha256', $seed . '-' . bin2hex(random_bytes(8))), 0, 40);
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');

$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users ORDER BY id ASC LIMIT 1'
)->fetchColumn();

$memberId = (int) $pdo->query(
    'SELECT id FROM members ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new RuntimeException('An existing user and member are required.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$amortization = new AmortizationService();
$session = new Session();
$userRepository = new UserRepository($database);

$loanService = new LoanService(
    repository: $loanRepository,
    ledger: $ledgerService,
    amortization: $amortization,
    activityLog: $activityService,
    session: $session,
    users: $userRepository,
);

$paymentService = new PaymentService(
    $paymentRepository,
    $ledgerService,
    $ledgerRepository,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
    $database,
);

$loanId = null;
$paymentIds = [];

try {
    $loanId = $loanService->create(
        new LoanData(
            memberId: $memberId,
            loanType: LoanType::PRODUCTIVITY_LOAN,
            collateral: CollateralType::POST_DATED_CHECK,
            principalAmount: 6000.00,
            interestRate: 2.00,
            amortizationType: AmortizationType::STRAIGHT_LINE,
            paymentFrequency: null,
            termsMonths: 3,
            startDate: '2026-09-20',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-09-20');

    // 1. Partial payment: ₱1,000 = ₱120 interest + ₱880 principal.
    $partialToken = paymentToken('partial');
    $partial = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 1000.00,
        remarks: 'Payment operations partial QA',
        idempotencyKey: $partialToken,
    );
    $partialPaymentId = (int) $partial['payment_id'];
    $paymentIds[] = $partialPaymentId;

    assertNear(120.00, (float) $partial['interest_applied'], 'Partial interest allocation.');
    assertNear(880.00, (float) $partial['principal_applied'], 'Partial principal allocation.');
    assertNear(0.00, (float) $partial['excess'], 'Partial payment excess.');
    assertSameValue(false, $partial['loan_fully_paid'], 'Partial payment must not fully pay the loan.');

    $rows = $paymentRepository->amortizations($loanId);
    assertNear(1120.00, (float) $rows[0]['rem_principal'], 'Partial payment remaining principal.');
    assertNear(0.00, (float) $rows[0]['rem_interest'], 'Partial payment remaining interest.');
    assertSameValue('Pending', $rows[0]['status'], 'Partially paid installment should remain Pending.');

    // 2. Idempotent replay: same token must return the same payment and create no duplicate.
    $replay = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 1000.00,
        remarks: 'Payment operations partial QA replay',
        idempotencyKey: $partialToken,
    );

    assertSameValue($partialPaymentId, (int) $replay['payment_id'], 'Idempotent replay payment ID.');
    assertSameValue(
        1,
        count($paymentRepository->paymentsForLoan($loanId)),
        'Idempotent replay must not create a second payment.',
    );

    assertThrows(
        fn () => $paymentService->apply(
            loanId: $loanId,
            amountPaid: 1001.00,
            remarks: 'Invalid idempotency reuse',
            idempotencyKey: $partialToken,
        ),
        'This payment request token was already used for a different payment.',
        'Idempotency token reuse with a different amount must fail.',
    );

    // 3. Exact completion of the first installment: remaining ₱1,120.
    $exact = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 1120.00,
        remarks: 'Payment operations exact installment QA',
        idempotencyKey: paymentToken('exact'),
    );
    $exactPaymentId = (int) $exact['payment_id'];
    $paymentIds[] = $exactPaymentId;

    assertNear(0.00, (float) $exact['interest_applied'], 'Exact installment remaining interest.');
    assertNear(1120.00, (float) $exact['principal_applied'], 'Exact installment remaining principal.');
    assertNear(0.00, (float) $exact['excess'], 'Exact installment excess.');

    $rows = $paymentRepository->amortizations($loanId);
    assertSameValue('Paid', $rows[0]['status'], 'First installment should be Paid after exact completion.');
    assertNear(0.00, (float) $rows[0]['rem_principal'], 'First installment principal after exact payment.');
    assertNear(0.00, (float) $rows[0]['rem_interest'], 'First installment interest after exact payment.');
    assertSameValue(LoanStatus::ACTIVE, $loanService->find($loanId)['loan_status'], 'Loan should remain Active.');

    // 4. Overpayment: remaining scheduled balance is ₱4,240; ₱760 becomes unapplied excess.
    $overpayment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 5000.00,
        remarks: 'Payment operations overpayment QA',
        idempotencyKey: paymentToken('overpayment'),
    );
    $overpaymentId = (int) $overpayment['payment_id'];
    $paymentIds[] = $overpaymentId;

    assertNear(4000.00, (float) $overpayment['principal_applied'], 'Overpayment principal allocation.');
    assertNear(240.00, (float) $overpayment['interest_applied'], 'Overpayment interest allocation.');
    assertNear(760.00, (float) $overpayment['excess'], 'Overpayment excess.');
    assertSameValue(true, $overpayment['loan_fully_paid'], 'Overpayment should fully settle scheduled balances.');
    assertSameValue(LoanStatus::FULLY_PAID, $loanService->find($loanId)['loan_status'], 'Loan should become Fully Paid.');

    $overpaymentVoucher = $ledgerRepository->findBySource('LoanPayment', $overpaymentId);
    if ($overpaymentVoucher === null) {
        throw new RuntimeException('Overpayment Journal Voucher was not created.');
    }

    $overpaymentLines = $ledgerRepository->lines((int) $overpaymentVoucher['id']);
    assertSameValue(4, count($overpaymentLines), 'Overpayment voucher should contain cash, principal, interest, and unapplied lines.');

    $debit = 0.00;
    $credit = 0.00;
    foreach ($overpaymentLines as $line) {
        $debit += (float) $line['debit'];
        $credit += (float) $line['credit'];
    }
    assertNear(5000.00, $debit, 'Overpayment voucher debit total.');
    assertNear(5000.00, $credit, 'Overpayment voucher credit total.');

    // 5. Reverse the payment that completed the loan.
    $beforeReverse = $paymentRepository->amortizations($loanId);
    $paymentService->reverse(
        paymentId: $overpaymentId,
        reason: 'Payment operations reversal QA',
    );

    $reversedPayment = $paymentService->payment($overpaymentId);
    if ($reversedPayment === null || ($reversedPayment['reversed_at'] ?? null) === null) {
        throw new RuntimeException('Reversed payment must retain reversal metadata.');
    }

    $afterReverse = $paymentRepository->amortizations($loanId);
    assertSameValue(LoanStatus::ACTIVE, $loanService->find($loanId)['loan_status'], 'Reversing the final payment must reactivate the loan.');
    assertSameValue('Paid', $afterReverse[0]['status'], 'Previously paid first installment must remain Paid.');
    assertSameValue('Pending', $afterReverse[1]['status'], 'Reversed second installment must become Pending.');
    assertSameValue('Pending', $afterReverse[2]['status'], 'Reversed third installment must become Pending.');
    assertNear((float) $beforeReverse[1]['rem_principal'] + 2000.00, (float) $afterReverse[1]['rem_principal'], 'Second installment principal restored.');
    assertNear((float) $beforeReverse[1]['rem_interest'] + 120.00, (float) $afterReverse[1]['rem_interest'], 'Second installment interest restored.');
    assertNear((float) $beforeReverse[2]['rem_principal'] + 2000.00, (float) $afterReverse[2]['rem_principal'], 'Third installment principal restored.');
    assertNear((float) $beforeReverse[2]['rem_interest'] + 120.00, (float) $afterReverse[2]['rem_interest'], 'Third installment interest restored.');

    $reversalVoucher = $ledgerRepository->findBySource('LoanPaymentReversal', $overpaymentId);
    if ($reversalVoucher === null) {
        throw new RuntimeException('Reversal Journal Voucher was not created.');
    }

    assertSameValue(
        (int) $overpaymentVoucher['id'],
        (int) $reversalVoucher['reversal_of_voucher_id'],
        'Reversal voucher must link to the original voucher.',
    );

    $reversalLines = $ledgerRepository->lines((int) $reversalVoucher['id']);
    assertSameValue(4, count($reversalLines), 'Reversal voucher should invert all original lines.');

    foreach ($overpaymentLines as $index => $originalLine) {
        $reversalLine = $reversalLines[$index];
        assertNear((float) $originalLine['debit'], (float) $reversalLine['credit'], 'Reversal credit must equal original debit.');
        assertNear((float) $originalLine['credit'], (float) $reversalLine['debit'], 'Reversal debit must equal original credit.');
    }

    assertThrows(
        fn () => $paymentService->reverse(
            paymentId: $overpaymentId,
            reason: 'Second reversal must fail',
        ),
        'already been reversed',
        'A second payment reversal must be rejected.',
    );

    // 6. Final settlement after reversal must still work normally.
    $final = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 4240.00,
        remarks: 'Payment operations final settlement QA',
        idempotencyKey: paymentToken('final'),
    );
    $finalPaymentId = (int) $final['payment_id'];
    $paymentIds[] = $finalPaymentId;

    assertNear(4000.00, (float) $final['principal_applied'], 'Final settlement principal.');
    assertNear(240.00, (float) $final['interest_applied'], 'Final settlement interest.');
    assertNear(0.00, (float) $final['excess'], 'Final settlement excess.');
    assertSameValue(true, $final['loan_fully_paid'], 'Final settlement should fully pay the loan.');
    assertSameValue(LoanStatus::FULLY_PAID, $loanService->find($loanId)['loan_status'], 'Final loan status should be Fully Paid.');

    $logs = $pdo->prepare(
        "SELECT action FROM activity_logs
         WHERE subject_type = 'Loan' AND subject_id = :loan_id
         ORDER BY id ASC"
    );
    $logs->execute(['loan_id' => $loanId]);
    $actions = array_column($logs->fetchAll(\PDO::FETCH_ASSOC), 'action');

    foreach (['LOAN_PAYMENT_APPLIED', 'LOAN_PAYMENT_REVERSED', 'LOAN_REACTIVATED', 'LOAN_FULLY_PAID'] as $requiredAction) {
        if (!in_array($requiredAction, $actions, true)) {
            throw new RuntimeException(sprintf('Required activity log %s is missing.', $requiredAction));
        }
    }

    echo PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "ACES PAYMENT OPERATIONS INTEGRATION TEST: PASS" . PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Partial payment              → ₱1,000.00       ✓" . PHP_EOL;
    echo "Idempotent replay            → no duplicate     ✓" . PHP_EOL;
    echo "Idempotency mismatch         → rejected         ✓" . PHP_EOL;
    echo "Exact installment completion → ₱1,120.00       ✓" . PHP_EOL;
    echo "Overpayment                  → ₱760.00 excess   ✓" . PHP_EOL;
    echo "Loan settlement              → Fully Paid       ✓" . PHP_EOL;
    echo "Payment reversal             → balances restored ✓" . PHP_EOL;
    echo "Reversal voucher             → exact inverse     ✓" . PHP_EOL;
    echo "Double reversal              → rejected         ✓" . PHP_EOL;
    echo "Post-reversal settlement     → Fully Paid       ✓" . PHP_EOL;
    echo "Activity logs                → Complete         ✓" . PHP_EOL;
    echo "================================================" . PHP_EOL;
} finally {
    if ($loanId !== null) {
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));

        if ($paymentIds !== []) {
            $statement = $pdo->prepare(
                "DELETE FROM loan_payment_allocations
                 WHERE payment_id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);
        }

        $voucherIds = [];
        if ($paymentIds !== []) {
            $statement = $pdo->prepare(
                "SELECT id FROM journal_vouchers
                 WHERE (source_type = 'LoanPayment' OR source_type = 'LoanPaymentReversal')
                   AND source_id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);
            $voucherIds = array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
        }

        if ($voucherIds !== []) {
            $voucherPlaceholders = implode(',', array_fill(0, count($voucherIds), '?'));

            // Reversal vouchers reference their original voucher with ON DELETE RESTRICT.
            // Delete the reversal first, then the original voucher.
            $statement = $pdo->prepare(
                "DELETE FROM journal_vouchers
                 WHERE id IN ({$voucherPlaceholders})
                   AND reversal_of_voucher_id IS NOT NULL"
            );
            $statement->execute($voucherIds);

            $statement = $pdo->prepare(
                "DELETE FROM journal_vouchers
                 WHERE id IN ({$voucherPlaceholders})"
            );
            $statement->execute($voucherIds);
        }

        if ($paymentIds !== []) {
            $statement = $pdo->prepare(
                "DELETE FROM loan_payments WHERE id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);
        }

        $statement = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan' AND subject_id = :loan_id"
        );
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare(
            'DELETE FROM loan_amortizations WHERE loan_id = :loan_id'
        );
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare(
            'DELETE FROM loans WHERE id = :loan_id'
        );
        $statement->execute(['loan_id' => $loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
