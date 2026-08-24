<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
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
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$loanService = new LoanService(
    $loanRepository,
    $ledgerService,
    new AmortizationService(),
    $activityService,
    new Session(),
);

$loanId = null;
$voucherId = null;

try {
    $loanId = $loanService->create(
        new LoanData(
            memberId: $memberId,
            loanType: 'Productivity Loan',
            collateral: 'Post-Dated Check',
            principalAmount: 12000.00,
            interestRate: 2.00,
            amortizationType: 'Straight-line',
            paymentFrequency: null,
            termsMonths: 6,
            startDate: '2026-08-20',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-08-20');

    $loan = $loanService->find($loanId);

    assertSameValue(
        'Active',
        $loan['loan_status'],
        'Released loan must become Active.',
    );

    $processingFee = (float) $loan['processing_fee'];
    $insurance = (float) $loan['insurance'];
    $notarialFee = (float) $loan['notarial_fee'];
    $netProceeds = (float) $loan['net_proceeds'];

    assertNear(
        240.00,
        $processingFee,
        'Processing fee.',
    );

    assertNear(
        86.40,
        $insurance,
        'Insurance.',
    );

    assertNear(
        400.00,
        $notarialFee,
        'Notarial fee.',
    );

    assertNear(
        11273.60,
        $netProceeds,
        'Net proceeds.',
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
        'source_type' => 'LoanRelease',
        'source_id' => $loanId,
    ]);

    $voucher = $statement->fetch(PDO::FETCH_ASSOC);

    if ($voucher === false) {
        throw new RuntimeException(
            'No Journal Voucher was created for the Loan release.'
        );
    }

    $voucherId = (int) $voucher['id'];

    assertSameValue(
        'Pending',
        $voucher['status'],
        'Release voucher must begin Pending.',
    );

    assertSameValue(
        $loanId,
        (int) $voucher['source_id'],
        'Release voucher source ID.',
    );

    $lines = $ledgerRepository->lines($voucherId);

    assertSameValue(
        5,
        count($lines),
        'Release voucher line count.',
    );

    $debitTotal = 0.00;
    $creditTotal = 0.00;

    foreach ($lines as $line) {
        $debitTotal += (float) $line['debit'];
        $creditTotal += (float) $line['credit'];
    }

    assertNear(
        12000.00,
        $debitTotal,
        'Release debit total.',
    );

    assertNear(
        12000.00,
        $creditTotal,
        'Release credit total.',
    );

    // Explicit atomicity check: a missing ledger account must roll the
    // operational release back rather than leaving the loan Active.
    //
    // We cannot force that through the normal LoanService without changing
    // the configured COA, so the main test asserts the successful transaction
    // boundary. A separate failure injection test can be added once the
    // Ledger mapping becomes configurable.

    echo PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "ACES LOAN RELEASE → LEDGER INTEGRATION TEST: PASS" . PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Approved → Active                  ✓" . PHP_EOL;
    echo "Journal Voucher created            ✓" . PHP_EOL;
    echo "Voucher status → Pending           ✓" . PHP_EOL;
    echo "Source → LoanRelease               ✓" . PHP_EOL;
    echo "5 accounting lines persisted       ✓" . PHP_EOL;
    echo "Debit total = Credit total         ✓" . PHP_EOL;
    echo "Principal ₱12,000.00               ✓" . PHP_EOL;
    echo "Net proceeds ₱11,273.60             ✓" . PHP_EOL;
    echo "Deductions ₱726.40 accounted       ✓" . PHP_EOL;
    echo "================================================" . PHP_EOL;
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

    if ($loanId !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM activity_logs
             WHERE subject_type = "Loan"
               AND subject_id = :loan_id'
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
