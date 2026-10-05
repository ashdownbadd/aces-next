<?php

declare(strict_types=1);

/**
 * ACES Ledger Integrity — real MySQL integration test.
 *
 * Run from the ACES project root:
 *   php tests/Integration/Ledger/LedgerIntegrityIntegrationTest.php
 *
 * Verifies the exact double-entry mappings produced by:
 *   Loan release -> regular payment -> overpayment -> reversal -> final payment.
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Authentication\Repositories\UserRepository;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanStatus;
use App\Features\Loans\Domain\LoanType;
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

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @param array<int,array<string,mixed>> $lines */
function assertVoucherLines(
    array $lines,
    array $expected,
    string $label,
): void {
    assertSameValue(
        count($expected),
        count($lines),
        "{$label}: line count.",
    );

    $actual = [];
    $debitTotal = 0.00;
    $creditTotal = 0.00;

    foreach ($lines as $line) {
        $code = (string) $line['account_code'];
        $debit = (float) $line['debit'];
        $credit = (float) $line['credit'];

        $actual[$code] = [
            'debit' => $debit,
            'credit' => $credit,
        ];

        $debitTotal += $debit;
        $creditTotal += $credit;
    }

    assertNear($debitTotal, $creditTotal, "{$label}: voucher must balance.");

    foreach ($expected as $code => $amounts) {
        assertTrueValue(
            isset($actual[$code]),
            "{$label}: account {$code} is missing.",
        );

        assertNear(
            (float) $amounts['debit'],
            (float) $actual[$code]['debit'],
            "{$label}: account {$code} debit.",
        );

        assertNear(
            (float) $amounts['credit'],
            (float) $actual[$code]['credit'],
            "{$label}: account {$code} credit.",
        );
    }
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
    repository: $paymentRepository,
    ledger: $ledgerService,
    journalVoucherRepository: $ledgerRepository,
    loanRepository: $loanRepository,
    amortization: new AmortizationService(),
    activityLog: $activityService,
    session: $session,
    database: $database,
);

$loanId = null;
$paymentIds = [];
$voucherIds = [];

try {
    $loanId = $loanService->create(
        new LoanData(
            memberId: $memberId,
            loanType: LoanType::PRODUCTIVITY_LOAN,
            collateral: CollateralType::POST_DATED_CHECK,
            principalAmount: 12000.00,
            interestRate: 2.00,
            amortizationType: AmortizationType::STRAIGHT_LINE,
            paymentFrequency: null,
            termsMonths: 6,
            startDate: '2026-09-20',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-09-20');

    $releaseVoucher = $ledgerRepository->findBySource('LoanRelease', $loanId);
    if ($releaseVoucher === null) {
        throw new RuntimeException('Loan release voucher was not created.');
    }

    $releaseVoucherId = (int) $releaseVoucher['id'];
    $voucherIds[] = $releaseVoucherId;

    assertSameValue('Pending', $releaseVoucher['status'], 'Release voucher status.');

    assertVoucherLines(
        $ledgerRepository->lines($releaseVoucherId),
        [
            '1110' => ['debit' => 12000.00, 'credit' => 0.00],
            '1010' => ['debit' => 0.00, 'credit' => 11273.60],
            '4040' => ['debit' => 0.00, 'credit' => 240.00],
            '4050' => ['debit' => 0.00, 'credit' => 86.40],
            '4060' => ['debit' => 0.00, 'credit' => 400.00],
        ],
        'Loan release voucher',
    );

    $firstPayment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'Ledger integrity first payment',
        idempotencyKey: str_repeat('L', 40),
    );

    $firstPaymentId = (int) $firstPayment['payment_id'];
    $paymentIds[] = $firstPaymentId;

    $firstVoucher = $ledgerRepository->findBySource('LoanPayment', $firstPaymentId);
    if ($firstVoucher === null) {
        throw new RuntimeException('First payment voucher was not created.');
    }

    $firstVoucherId = (int) $firstVoucher['id'];
    $voucherIds[] = $firstVoucherId;

    assertVoucherLines(
        $ledgerRepository->lines($firstVoucherId),
        [
            '1010' => ['debit' => 2120.00, 'credit' => 0.00],
            '1110' => ['debit' => 0.00, 'credit' => 1880.00],
            '4010' => ['debit' => 0.00, 'credit' => 240.00],
        ],
        'Regular payment voucher',
    );

    $overpayment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 12000.00,
        remarks: 'Ledger integrity overpayment',
        idempotencyKey: str_repeat('O', 40),
    );

    $overpaymentId = (int) $overpayment['payment_id'];
    $paymentIds[] = $overpaymentId;

    assertNear(10120.00, (float) $overpayment['principal_applied'], 'Overpayment principal allocation.');
    assertNear(1200.00, (float) $overpayment['interest_applied'], 'Overpayment interest allocation.');
    assertNear(680.00, (float) $overpayment['excess'], 'Overpayment excess liability.');
    assertTrueValue((bool) $overpayment['loan_fully_paid'], 'Overpayment should fully settle the loan.');

    $overpaymentVoucher = $ledgerRepository->findBySource('LoanPayment', $overpaymentId);
    if ($overpaymentVoucher === null) {
        throw new RuntimeException('Overpayment voucher was not created.');
    }

    $overpaymentVoucherId = (int) $overpaymentVoucher['id'];
    $voucherIds[] = $overpaymentVoucherId;

    assertVoucherLines(
        $ledgerRepository->lines($overpaymentVoucherId),
        [
            '1010' => ['debit' => 12000.00, 'credit' => 0.00],
            '1110' => ['debit' => 0.00, 'credit' => 10120.00],
            '4010' => ['debit' => 0.00, 'credit' => 1200.00],
            '2030' => ['debit' => 0.00, 'credit' => 680.00],
        ],
        'Overpayment voucher',
    );

    $paymentService->reverse(
        paymentId: $overpaymentId,
        reason: 'Ledger integrity reversal',
    );

    $reversalVoucher = $ledgerRepository->findBySource(
        'LoanPaymentReversal',
        $overpaymentId,
    );

    if ($reversalVoucher === null) {
        throw new RuntimeException('Overpayment reversal voucher was not created.');
    }

    $reversalVoucherId = (int) $reversalVoucher['id'];
    $voucherIds[] = $reversalVoucherId;

    assertSameValue(
        $overpaymentVoucherId,
        (int) $reversalVoucher['reversal_of_voucher_id'],
        'Reversal voucher must reference the original overpayment voucher.',
    );

    $originalLines = $ledgerRepository->lines($overpaymentVoucherId);
    $reversalLines = $ledgerRepository->lines($reversalVoucherId);

    assertSameValue(
        count($originalLines),
        count($reversalLines),
        'Reversal line count.',
    );

    $originalDebit = 0.00;
    $originalCredit = 0.00;
    $reversalDebit = 0.00;
    $reversalCredit = 0.00;

    foreach ($originalLines as $index => $originalLine) {
        $reversalLine = $reversalLines[$index];

        assertSameValue(
            $originalLine['account_code'],
            $reversalLine['account_code'],
            'Reversal must preserve account code.',
        );

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

        $originalDebit += (float) $originalLine['debit'];
        $originalCredit += (float) $originalLine['credit'];
        $reversalDebit += (float) $reversalLine['debit'];
        $reversalCredit += (float) $reversalLine['credit'];
    }

    assertNear($originalDebit, $originalCredit, 'Original overpayment voucher balance.');
    assertNear($reversalDebit, $reversalCredit, 'Reversal voucher balance.');

    $finalPayment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 11320.00,
        remarks: 'Ledger integrity final settlement',
        idempotencyKey: str_repeat('F', 40),
    );

    $finalPaymentId = (int) $finalPayment['payment_id'];
    $paymentIds[] = $finalPaymentId;

    assertTrueValue((bool) $finalPayment['loan_fully_paid'], 'Final payment must fully settle the loan.');

    $finalVoucher = $ledgerRepository->findBySource('LoanPayment', $finalPaymentId);
    if ($finalVoucher === null) {
        throw new RuntimeException('Final settlement voucher was not created.');
    }

    $finalVoucherId = (int) $finalVoucher['id'];
    $voucherIds[] = $finalVoucherId;

    assertVoucherLines(
        $ledgerRepository->lines($finalVoucherId),
        [
            '1010' => ['debit' => 11320.00, 'credit' => 0.00],
            '1110' => ['debit' => 0.00, 'credit' => 10120.00],
            '4010' => ['debit' => 0.00, 'credit' => 1200.00],
        ],
        'Final settlement voucher',
    );

    $loan = $loanRepository->find($loanId);

    assertSameValue(
        LoanStatus::FULLY_PAID,
        $loan['loan_status'],
        'Final loan status.',
    );

    echo PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "ACES LEDGER INTEGRITY INTEGRATION TEST: PASS" . PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Loan release mapping               ✓" . PHP_EOL;
    echo "Regular payment mapping            ✓" . PHP_EOL;
    echo "Overpayment liability (2030)       ✓" . PHP_EOL;
    echo "Every voucher balanced              ✓" . PHP_EOL;
    echo "Reversal exact inverse              ✓" . PHP_EOL;
    echo "Final settlement mapping            ✓" . PHP_EOL;
    echo "Final status → Fully Paid           ✓" . PHP_EOL;
    echo "================================================" . PHP_EOL;
} finally {
    if ($voucherIds !== []) {
        $uniqueVoucherIds = array_values(array_unique(array_map('intval', $voucherIds)));
        $placeholders = implode(',', array_fill(0, count($uniqueVoucherIds), '?'));

        // Reversal vouchers reference their originals with ON DELETE RESTRICT.
        $statement = $pdo->prepare(
            "DELETE FROM journal_lines WHERE journal_voucher_id IN ({$placeholders})"
        );
        $statement->execute($uniqueVoucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE id IN ({$placeholders})
               AND reversal_of_voucher_id IS NOT NULL"
        );
        $statement->execute($uniqueVoucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers WHERE id IN ({$placeholders})"
        );
        $statement->execute($uniqueVoucherIds);
    }

    if ($loanId !== null) {
        $paymentIds = array_values(array_unique(array_map('intval', $paymentIds)));

        if ($paymentIds !== []) {
            $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));

            $statement = $pdo->prepare(
                "DELETE FROM loan_payment_allocations WHERE payment_id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);

            $statement = $pdo->prepare(
                "DELETE FROM loan_payments WHERE id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);
        }

        $statement = $pdo->prepare(
            'DELETE FROM activity_logs
             WHERE subject_type = "Loan"
               AND subject_id = :loan_id'
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
    }
}
