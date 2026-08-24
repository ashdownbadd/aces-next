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
            sprintf(
                '%s Expected %.2f, got %.2f.',
                $message,
                $expected,
                $actual,
            )
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
    throw new RuntimeException(
        'An existing user and member are required.'
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$amortization = new AmortizationService();
$session = new Session();
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);

$loanService = new LoanService(
    $loanRepository,
    $amortization,
    $activityService,
    $session,
);

$paymentService = new PaymentService(
    $paymentRepository,
    $ledgerService,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
);

$loanId = null;
$paymentId = null;
$voucherId = null;
$atomicLoanId = null;
$atomicPaymentId = null;

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

    $result = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'Loan-to-ledger integration test',
    );

    $paymentId = (int) $result['payment_id'];

    assertSameValue(
        2120.00,
        (float) $result['amount_paid'],
        'Payment amount.',
    );

    assertNear(
        120.00,
        (float) $result['interest_applied'],
        'Interest allocation.',
    );

    assertNear(
        2000.00,
        (float) $result['principal_applied'],
        'Principal allocation.',
    );

    $statement = $pdo->prepare(
        'SELECT id, status, source_type, source_id
         FROM journal_vouchers
         WHERE source_type = :source_type
           AND source_id = :source_id
         ORDER BY id DESC
         LIMIT 1'
    );

    $statement->execute([
        'source_type' => 'LoanPayment',
        'source_id' => $paymentId,
    ]);

    $voucher = $statement->fetch(PDO::FETCH_ASSOC);

    if ($voucher === false) {
        throw new RuntimeException(
            'No Journal Voucher was created for the Loan payment.'
        );
    }

    $voucherId = (int) $voucher['id'];

    assertSameValue(
        'Pending',
        $voucher['status'],
        'Loan payment voucher status.',
    );

    assertSameValue(
        'LoanPayment',
        $voucher['source_type'],
        'Voucher source type.',
    );

    assertSameValue(
        $paymentId,
        (int) $voucher['source_id'],
        'Voucher source payment ID.',
    );

    $lines = $ledgerRepository->lines($voucherId);

    assertSameValue(
        3,
        count($lines),
        'Loan payment journal line count.',
    );

    $debit = 0.00;
    $credit = 0.00;

    foreach ($lines as $line) {
        $debit += (float) $line['debit'];
        $credit += (float) $line['credit'];
    }

    assertNear(
        2120.00,
        $debit,
        'Journal debit total.',
    );

    assertNear(
        2120.00,
        $credit,
        'Journal credit total.',
    );

    echo PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "ACES LOAN → LEDGER INTEGRATION TEST: PASS" . PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Payment ID: #{$paymentId}" . PHP_EOL;
    echo "Journal Voucher created             ✓" . PHP_EOL;
    echo "Voucher status → Pending            ✓" . PHP_EOL;
    echo "Source → LoanPayment                ✓" . PHP_EOL;
    echo "3 accounting lines persisted        ✓" . PHP_EOL;
    echo "Debit total = Credit total          ✓" . PHP_EOL;
    echo "Principal ₱2,000 / Interest ₱120   ✓" . PHP_EOL;
    echo "================================================" . PHP_EOL;

    // Explicit atomicity test:
    // force the accounting callback to fail after the payment and
    // amortization updates have already been staged.
    $atomicLoanId = $loanService->create(
        new LoanData(
            memberId: $memberId,
            loanType: LoanType::PRODUCTIVITY_LOAN,
            collateral: CollateralType::POST_DATED_CHECK,
            principalAmount: 3000.00,
            interestRate: 2.00,
            amortizationType: AmortizationType::STRAIGHT_LINE,
            paymentFrequency: null,
            termsMonths: 3,
            startDate: '2026-08-20',
        )
    );

    $loanService->submit($atomicLoanId);
    $loanService->approve($atomicLoanId);
    $loanService->release($atomicLoanId, '2026-08-20');

    $atomicRows = $paymentRepository->amortizations($atomicLoanId);
    $atomicRow = $atomicRows[0];

    $atomicUpdatedRows = $atomicRows;
    $atomicUpdatedRows[0]['rem_principal'] =
        round((float) $atomicUpdatedRows[0]['rem_principal'] - 100.00, 2);
    $atomicUpdatedRows[0]['status'] = 'Pending';

    $atomicPayment = [
        'loan_id' => $atomicLoanId,
        'payment_datetime' => date('Y-m-d H:i:s'),
        'amount_paid' => 100.00,
        'penalty_applied' => 0.00,
        'interest_applied' => 0.00,
        'principal_applied' => 100.00,
        'excess' => 0.00,
        'type' => 'Global',
        'remarks' => 'Atomic rollback test',
        'created_by' => $userId,
    ];

    try {
        $paymentRepository->persistPaymentWithAccounting(
            payment: $atomicPayment,
            allocations: [[
                'amortization_id' => (int) $atomicRow['id'],
                'allocation_type' => 'Principal',
                'amount' => 100.00,
            ]],
            updatedRows: $atomicUpdatedRows,
            accountingCallback: static function (int $persistedPaymentId): void {
                throw new RuntimeException(
                    'Forced accounting failure for atomicity test.'
                );
            },
        );
    } catch (RuntimeException $exception) {
        if (
            !str_contains(
                $exception->getMessage(),
                'Forced accounting failure'
            )
        ) {
            throw $exception;
        }
    }

    $atomicPaymentCountStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM loan_payments
         WHERE loan_id = :loan_id
           AND remarks = :remarks'
    );
    $atomicPaymentCountStatement->execute([
        'loan_id' => $atomicLoanId,
        'remarks' => 'Atomic rollback test',
    ]);

    if ((int) $atomicPaymentCountStatement->fetchColumn() !== 0) {
        throw new RuntimeException(
            'Atomic rollback failed: Loan payment still exists.'
        );
    }

    $atomicRowStatement = $pdo->prepare(
        'SELECT rem_principal
         FROM loan_amortizations
         WHERE id = :id
         LIMIT 1'
    );
    $atomicRowStatement->execute([
        'id' => (int) $atomicRow['id'],
    ]);

    assertNear(
        (float) $atomicRow['rem_principal'],
        (float) $atomicRowStatement->fetchColumn(),
        'Atomic rollback restored amortization balance.',
    );

    echo "Payment + Ledger atomic rollback       ✓" . PHP_EOL;

} finally {
    if ($voucherId !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM journal_lines
             WHERE journal_voucher_id = :voucher_id'
        );
        $delete->execute([
            'voucher_id' => $voucherId,
        ]);

        $delete = $pdo->prepare(
            'DELETE FROM journal_vouchers
             WHERE id = :voucher_id'
        );
        $delete->execute([
            'voucher_id' => $voucherId,
        ]);
    }

    if ($atomicLoanId !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );
        $delete->execute([
            'loan_id' => $atomicLoanId,
        ]);

        $delete = $pdo->prepare(
            'DELETE FROM loans
             WHERE id = :loan_id'
        );
        $delete->execute([
            'loan_id' => $atomicLoanId,
        ]);
    }

    if ($loanId !== null) {
        $delete = $pdo->prepare(
            'DELETE lpa
             FROM loan_payment_allocations AS lpa
             INNER JOIN loan_payments AS lp
                ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $delete->execute([
            'loan_id' => $loanId,
        ]);

        $delete = $pdo->prepare(
            'DELETE FROM loan_payments
             WHERE loan_id = :loan_id'
        );
        $delete->execute([
            'loan_id' => $loanId,
        ]);

        $delete = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan'
               AND subject_id = :loan_id"
        );
        $delete->execute([
            'loan_id' => $loanId,
        ]);

        $delete = $pdo->prepare(
            'DELETE FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );
        $delete->execute([
            'loan_id' => $loanId,
        ]);

        $delete = $pdo->prepare(
            'DELETE FROM loans
             WHERE id = :loan_id'
        );
        $delete->execute([
            'loan_id' => $loanId,
        ]);
    }
}
