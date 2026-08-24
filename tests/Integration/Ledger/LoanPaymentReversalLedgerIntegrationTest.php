<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
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

function assertThrows(callable $callback, string $needle): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (!str_contains($exception->getMessage(), $needle)) {
            throw new RuntimeException(
                'Unexpected exception: ' . $exception->getMessage()
            );
        }

        return;
    }

    throw new RuntimeException(
        "Expected exception containing '{$needle}'."
    );
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
    throw new RuntimeException('Existing user and member are required.');
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
$session = new Session();

$loanService = new LoanService(
    $loanRepository,
    $ledgerService,
    new AmortizationService(),
    $activityService,
    $session,
);

$paymentService = new PaymentService(
    $paymentRepository,
    $ledgerService,
    $ledgerRepository,
    $loanRepository,
    new AmortizationService(),
    $activityService,
    $session,
);

$loanId = null;
$paymentId = null;
$originalVoucherId = null;
$reversalVoucherId = null;

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
            startDate: '2026-08-20',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-08-20');

    $payment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'Reversal integration test',
    );

    $paymentId = (int) $payment['payment_id'];

    $originalVoucher = $ledgerRepository->findBySource(
        'LoanPayment',
        $paymentId,
    );

    if ($originalVoucher === null) {
        throw new RuntimeException(
            'Original Loan payment voucher was not created.'
        );
    }

    $originalVoucherId = (int) $originalVoucher['id'];

    $originalLines = $ledgerRepository->lines($originalVoucherId);

    assertSameValue(3, count($originalLines), 'Original voucher line count.');

    $originalDebit = 0.00;
    $originalCredit = 0.00;

    foreach ($originalLines as $line) {
        $originalDebit += (float) $line['debit'];
        $originalCredit += (float) $line['credit'];
    }

    assertNear(2120.00, $originalDebit, 'Original debit total.');
    assertNear(2120.00, $originalCredit, 'Original credit total.');

    // Snapshot amortization balance before reversal.
    $before = $paymentRepository->amortizations($loanId);

    $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'QA accounting reversal',
    );

    $afterPayment = $paymentService->payment($paymentId);

    assertSameValue(
        $paymentId,
        (int) $afterPayment['id'],
        'Original payment remains in ledger.',
    );

    if ($afterPayment['reversed_at'] === null) {
        throw new RuntimeException(
            'Original payment should have reversal metadata.'
        );
    }

    $after = $paymentRepository->amortizations($loanId);

    assertNear(
        (float) $before[0]['rem_principal'] + 2000.00,
        (float) $after[0]['rem_principal'],
        'Principal restored on reversal.',
    );

    $originalVoucherAfter = $ledgerRepository->find($originalVoucherId);

    assertSameValue(
        'Pending',
        $originalVoucherAfter['status'],
        'Original voucher must remain unchanged and preserved.',
    );

    $reversalVoucher = $ledgerRepository->findBySource(
        'LoanPaymentReversal',
        $paymentId,
    );

    if ($reversalVoucher === null) {
        throw new RuntimeException(
            'Reversal Journal Voucher was not created.'
        );
    }

    $reversalVoucherId = (int) $reversalVoucher['id'];

    assertSameValue(
        $originalVoucherId,
        (int) $reversalVoucher['reversal_of_voucher_id'],
        'Reversal voucher must link to original voucher.',
    );

    assertSameValue(
        'Pending',
        $reversalVoucher['status'],
        'Reversal voucher must begin Pending.',
    );

    $reversalLines = $ledgerRepository->lines($reversalVoucherId);

    assertSameValue(
        3,
        count($reversalLines),
        'Reversal voucher line count.',
    );

    $reversalDebit = 0.00;
    $reversalCredit = 0.00;

    foreach ($reversalLines as $line) {
        $reversalDebit += (float) $line['debit'];
        $reversalCredit += (float) $line['credit'];
    }

    assertNear(2120.00, $reversalDebit, 'Reversal debit total.');
    assertNear(2120.00, $reversalCredit, 'Reversal credit total.');

    // Exact inversion checks.
    foreach ($originalLines as $index => $originalLine) {
        $reversalLine = $reversalLines[$index];

        assertNear(
            (float) $originalLine['debit'],
            (float) $reversalLine['credit'],
            'Reversal credit must equal original debit.',
        );

        assertNear(
            (float) $originalLine['credit'],
            (float) $reversalLine['debit'],
            'Reversal debit must equal original credit.',
        );
    }

    assertThrows(
        fn () => $paymentService->reverse(
            paymentId: $paymentId,
            reason: 'QA second reversal',
        ),
        'already been reversed',
    );

    echo PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "ACES LOAN PAYMENT REVERSAL → LEDGER TEST: PASS" . PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Payment ID: #{$paymentId}" . PHP_EOL;
    echo "Original voucher preserved           ✓" . PHP_EOL;
    echo "Reversal voucher created             ✓" . PHP_EOL;
    echo "Reversal links original              ✓" . PHP_EOL;
    echo "Reversal is balanced                 ✓" . PHP_EOL;
    echo "Debit/Credit exactly inverted        ✓" . PHP_EOL;
    echo "Amortization restored                ✓" . PHP_EOL;
    echo "Second reversal blocked              ✓" . PHP_EOL;
    echo "================================================" . PHP_EOL;
} finally {
    if ($reversalVoucherId !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM journal_lines
             WHERE journal_voucher_id = :id'
        );
        $delete->execute(['id' => $reversalVoucherId]);

        $delete = $pdo->prepare(
            'DELETE FROM journal_vouchers
             WHERE id = :id'
        );
        $delete->execute(['id' => $reversalVoucherId]);
    }

    if ($originalVoucherId !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM journal_lines
             WHERE journal_voucher_id = :id'
        );
        $delete->execute(['id' => $originalVoucherId]);

        $delete = $pdo->prepare(
            'DELETE FROM journal_vouchers
             WHERE id = :id'
        );
        $delete->execute(['id' => $originalVoucherId]);
    }

    if ($loanId !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM activity_logs
             WHERE subject_type = "Loan"
               AND subject_id = :loan_id'
        );
        $delete->execute(['loan_id' => $loanId]);

        $delete = $pdo->prepare(
            'DELETE lpa
             FROM loan_payment_allocations AS lpa
             INNER JOIN loan_payments AS lp
               ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $delete->execute(['loan_id' => $loanId]);

        $delete = $pdo->prepare(
            'DELETE FROM loan_payments
             WHERE loan_id = :loan_id'
        );
        $delete->execute(['loan_id' => $loanId]);

        $delete = $pdo->prepare(
            'DELETE FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );
        $delete->execute(['loan_id' => $loanId]);

        $delete = $pdo->prepare(
            'DELETE FROM loans
             WHERE id = :loan_id'
        );
        $delete->execute(['loan_id' => $loanId]);
    }
}
