<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

function assertSameMoney(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(sprintf(
            '%s Expected %.2f, got %.2f.',
            $message,
            $expected,
            $actual,
        ));
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();
$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new RuntimeException('An active user and member are required.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityService = new ActivityLogService(new ActivityLogRepository($database));
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
$paymentId = null;
$voucherIds = [];

$today = new DateTimeImmutable('today');
$periodFrom = $today->modify('first day of this month')->format('Y-m-d');
$periodTo = $today->modify('last day of this month')->format('Y-m-d');
$releaseDate = $today->format('Y-m-d');

try {
    echo "===============================================================\n";
    echo "ACES FINANCIAL REPORTING PAYMENT REVERSAL RESTORATION TEST\n";
    echo "===============================================================\n";

    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: \App\Features\Loans\Domain\LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: $releaseDate,
    ));

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, $releaseDate);

    $cashAccountId = $ledgerService->accountId('1010');

    $baselineLedger = $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );
    $baselineTrialBalance = $ledgerService->trialBalance($periodTo);
    $baselineOperations = $ledgerService->incomeStatement($periodFrom, $periodTo);
    $baselinePosition = $ledgerService->balanceSheet($periodTo);

    $payment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 500.00,
        remarks: 'Financial reporting reversal restoration QA',
        idempotencyKey: substr(hash('sha256', 'report-reversal-' . bin2hex(random_bytes(8))), 0, 40),
    );
    $paymentId = (int) $payment['payment_id'];

    $paymentVoucher = $ledgerRepository->findBySource('LoanPayment', $paymentId);
    if ($paymentVoucher === null) {
        throw new RuntimeException('Payment Journal Voucher was not created.');
    }
    $voucherIds[] = (int) $paymentVoucher['id'];

    // PaymentService creates the accounting voucher as Pending.
    // Post it through the normal Journal Voucher lifecycle before
    // asserting that reporting includes the payment.
    $paymentVoucherId = (int) $paymentVoucher['id'];
    $ledgerService->approve(
        voucherId: $paymentVoucherId,
        userId: $userId,
        approvedAt: date('Y-m-d H:i:s'),
    );
    $ledgerService->post(
        voucherId: $paymentVoucherId,
        userId: $userId,
        postedAt: date('Y-m-d H:i:s'),
    );
    $paymentVoucher = $ledgerRepository->find($paymentVoucherId);
    if ($paymentVoucher === null || ($paymentVoucher['status'] ?? null) !== 'Posted') {
        throw new RuntimeException('Payment Journal Voucher was not posted.');
    }

    $afterPaymentLedger = $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );
    echo "Payment voucher diagnostics:\n";
    echo "  Voucher ID: " . (int) $paymentVoucher['id'] . "\n";
    echo "  Status: " . (string) ($paymentVoucher['status'] ?? '') . "\n";
    echo "  Transaction date: " . (string) ($paymentVoucher['transaction_date'] ?? '') . "\n";
    echo "  Period: {$periodFrom} → {$periodTo}\n";

    $paymentLines = $ledgerRepository->lines((int) $paymentVoucher['id']);
    foreach ($paymentLines as $line) {
        echo sprintf(
            "  Line account=%d debit=%.2f credit=%.2f description=%s\n",
            (int) $line['account_id'],
            (float) $line['debit'],
            (float) $line['credit'],
            (string) ($line['line_description'] ?? ''),
        );
    }

    echo sprintf(
        "Cash account ID: %d\n",
        $cashAccountId,
    );
    echo sprintf(
        "Baseline cash closing: %.2f\n",
        (float) $baselineLedger['closing_balance'],
    );
    echo sprintf(
        "After-payment cash closing: %.2f\n",
        (float) $afterPaymentLedger['closing_balance'],
    );

    foreach ($afterPaymentLedger['rows'] as $row) {
        if ((int) ($row['voucher_id'] ?? 0) === (int) $paymentVoucher['id']) {
            echo sprintf(
                "Payment ledger row found: debit=%.2f credit=%.2f date=%s\n",
                (float) $row['debit'],
                (float) $row['credit'],
                (string) $row['transaction_date'],
            );
        }
    }

    assertSameMoney(
        (float) $baselineLedger['closing_balance'] + 500.00,
        (float) $afterPaymentLedger['closing_balance'],
        'Posted payment must increase cash General Ledger by the payment amount.',
    );

    $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'Financial reporting reversal restoration QA',
    );

    $reversalVoucher = $ledgerRepository->findBySource(
        'LoanPaymentReversal',
        $paymentId,
    );
    if ($reversalVoucher === null) {
        throw new RuntimeException('Payment reversal Journal Voucher was not created.');
    }
    $reversalVoucherId = (int) $reversalVoucher['id'];
    $voucherIds[] = $reversalVoucherId;

    $ledgerService->approve(
        voucherId: $reversalVoucherId,
        userId: $userId,
        approvedAt: date('Y-m-d H:i:s'),
    );

    $ledgerService->post(
        voucherId: $reversalVoucherId,
        userId: $userId,
        postedAt: date('Y-m-d H:i:s'),
    );

    $reversalVoucher = $ledgerRepository->find($reversalVoucherId);
    if ($reversalVoucher === null || ($reversalVoucher['status'] ?? null) !== 'Posted') {
        throw new RuntimeException('Payment reversal Journal Voucher was not posted.');
    }

    $restoredLedger = $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );
    $restoredTrialBalance = $ledgerService->trialBalance($periodTo);
    $restoredOperations = $ledgerService->incomeStatement($periodFrom, $periodTo);
    $restoredPosition = $ledgerService->balanceSheet($periodTo);

    assertSameMoney(
        (float) $baselineLedger['closing_balance'],
        (float) $restoredLedger['closing_balance'],
        'Payment reversal must restore the cash General Ledger closing balance.',
    );

    $findRow = static function (array $report, string $code): ?array {
        foreach ($report['rows'] as $row) {
            if ((string) $row['account_code'] === $code) {
                return $row;
            }
        }
        return null;
    };

    foreach (['1010', '4010'] as $code) {
        $before = $findRow($baselineTrialBalance, $code);
        $after = $findRow($restoredTrialBalance, $code);
        $beforeNet = $before === null
            ? 0.00
            : (float) $before['debit'] - (float) $before['credit'];
        $afterNet = $after === null
            ? 0.00
            : (float) $after['debit'] - (float) $after['credit'];
        assertSameMoney(
            $beforeNet,
            $afterNet,
            "Trial Balance account {$code} must be restored after reversal.",
        );
    }

    assertSameMoney(
        (float) $baselineTrialBalance['total_debit'],
        (float) $restoredTrialBalance['total_debit'],
        'Trial Balance debit total must be restored after reversal.',
    );
    assertSameMoney(
        (float) $baselineTrialBalance['total_credit'],
        (float) $restoredTrialBalance['total_credit'],
        'Trial Balance credit total must be restored after reversal.',
    );

    assertSameMoney(
        (float) $baselineOperations['total_income'],
        (float) $restoredOperations['total_income'],
        'Statement of Operations income must be restored after reversal.',
    );
    assertSameMoney(
        (float) $baselineOperations['total_expenses'],
        (float) $restoredOperations['total_expenses'],
        'Statement of Operations expenses must be restored after reversal.',
    );
    assertSameMoney(
        (float) $baselineOperations['net_surplus'],
        (float) $restoredOperations['net_surplus'],
        'Statement of Operations net surplus must be restored after reversal.',
    );

    assertSameMoney(
        (float) $baselinePosition['total_assets'],
        (float) $restoredPosition['total_assets'],
        'Statement of Financial Position assets must be restored after reversal.',
    );
    assertSameMoney(
        (float) $baselinePosition['total_liabilities'],
        (float) $restoredPosition['total_liabilities'],
        'Statement of Financial Position liabilities must be restored after reversal.',
    );
    assertSameMoney(
        (float) $baselinePosition['total_equity'],
        (float) $restoredPosition['total_equity'],
        'Statement of Financial Position equity must be restored after reversal.',
    );
    assertSameMoney(
        (float) $baselinePosition['net_surplus'],
        (float) $restoredPosition['net_surplus'],
        'Financial Position net surplus must be restored after reversal.',
    );

    assertTrue(
        $restoredTrialBalance['balanced'] === true,
        'Trial Balance must remain balanced after reversal.',
    );
    assertTrue(
        $restoredPosition['balanced'] === true,
        'Statement of Financial Position must remain balanced after reversal.',
    );

    echo "Payment posted → reporting movement observed          ✓\n";
    echo "Payment reversal → General Ledger restored           ✓\n";
    echo "Payment reversal → Trial Balance restored            ✓\n";
    echo "Payment reversal → Statement of Operations restored  ✓\n";
    echo "Payment reversal → Financial Position restored       ✓\n";
    echo "Accounting reports remain reconciled                  ✓\n";
    echo "===============================================================\n";
    echo "ACES FINANCIAL REPORTING PAYMENT REVERSAL RESTORATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($paymentId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM loan_payment_allocations WHERE payment_id = :payment_id'
        );
        $statement->execute(['payment_id' => $paymentId]);

        $statement = $pdo->prepare(
            "DELETE FROM journal_lines
             WHERE journal_voucher_id IN (
                 SELECT id FROM journal_vouchers
                 WHERE (source_type = 'LoanPayment' OR source_type = 'LoanPaymentReversal')
                   AND source_id = :payment_id
             )"
        );
        $statement->execute(['payment_id' => $paymentId]);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE source_type = 'LoanPaymentReversal'
               AND source_id = :payment_id"
        );
        $statement->execute(['payment_id' => $paymentId]);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE source_type = 'LoanPayment'
               AND source_id = :payment_id"
        );
        $statement->execute(['payment_id' => $paymentId]);

        $statement = $pdo->prepare(
            'DELETE FROM loan_payments WHERE id = :payment_id'
        );
        $statement->execute(['payment_id' => $paymentId]);
    }

    if ($loanId !== null) {
        $statement = $pdo->prepare(
            "DELETE FROM activity_logs WHERE subject_type = 'Loan' AND subject_id = :loan_id"
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
