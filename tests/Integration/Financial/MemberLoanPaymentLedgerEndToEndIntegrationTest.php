<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Authentication\Repositories\UserRepository;
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
        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertNear(float $expected, float $actual, string $message): void
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

function accountRow(array $rows, string $accountCode): array
{
    foreach ($rows as $row) {
        if (($row['account_code'] ?? null) === $accountCode) {
            return $row;
        }
    }

    throw new RuntimeException(
        sprintf('Account %s was not found in the Trial Balance.', $accountCode)
    );
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');

$database = new Database($config);
$pdo = $database->connection();

$adminId = (int) $pdo->query(
    "SELECT id FROM users WHERE role IN ('admin', 'administrator') ORDER BY id ASC LIMIT 1"
)->fetchColumn();

$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($adminId <= 0 || $memberId <= 0) {
    throw new RuntimeException(
        'An administrator user and an Active member are required.'
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $adminId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$session = new Session();
$userRepository = new UserRepository($database);
$amortization = new AmortizationService();

$loanService = new LoanService(
    $loanRepository,
    $ledgerService,
    $amortization,
    $activityService,
    $session,
    $userRepository,
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
$releaseVoucherId = null;
$paymentVoucherId = null;

$reportDate = (new DateTimeImmutable())->format('Y-m-d');
$baselineTrial = $ledgerService->trialBalance($reportDate);
$baselineRows = [];
foreach ($baselineTrial['rows'] as $row) {
    $baselineRows[$row['account_code']] = $row;
}

try {
    echo "================================================================\n";
    echo "ACES MEMBER → LOAN → PAYMENT → LEDGER END-TO-END INTEGRATION TEST\n";
    echo "================================================================\n";

    // 1. Active member enters the financial workflow.
    $member = $pdo->prepare(
        'SELECT id, status FROM members WHERE id = :id LIMIT 1'
    );
    $member->execute(['id' => $memberId]);
    $memberRow = $member->fetch(PDO::FETCH_ASSOC);

    assertTrue($memberRow !== false, 'Test member must exist.');
    assertSameValue('Active', $memberRow['status'], 'Starting member status.');
    echo "Active member → financial workflow allowed ✓\n";

    // 2. Create, submit, approve, and release the loan.
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
            startDate: '2026-10-06',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-10-06');

    $loan = $loanService->find($loanId);

    assertTrue($loan !== null, 'Released loan must exist.');
    assertSameValue('Approved', $loan['application_status'], 'Loan application status after release.');
    assertSameValue('Active', $loan['loan_status'], 'Loan status after release.');
    assertNear(6000.00, (float) $loan['principal_amount'], 'Loan principal.');

    $scheduleStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM loan_amortizations WHERE loan_id = :loan_id'
    );
    $scheduleStatement->execute(['loan_id' => $loanId]);
    assertSameValue(3, (int) $scheduleStatement->fetchColumn(), 'Amortization schedule count.');

    echo "Loan → Active + amortization created ✓\n";

    // 3. Loan release must create its accounting voucher and keep it traceable.
    $voucherStatement = $pdo->prepare(
        'SELECT id, status, source_type, source_id
         FROM journal_vouchers
         WHERE source_type = :source_type
           AND source_id = :source_id
         ORDER BY id DESC
         LIMIT 1'
    );
    $voucherStatement->execute([
        'source_type' => 'LoanRelease',
        'source_id' => $loanId,
    ]);

    $releaseVoucher = $voucherStatement->fetch(PDO::FETCH_ASSOC);
    assertTrue($releaseVoucher !== false, 'Loan release must create a Journal Voucher.');

    $releaseVoucherId = (int) $releaseVoucher['id'];
    assertSameValue('Pending', $releaseVoucher['status'], 'Release voucher initial status.');
    assertSameValue($loanId, (int) $releaseVoucher['source_id'], 'Release voucher source ID.');

    $releaseLines = $ledgerRepository->lines($releaseVoucherId);
    $releaseDebit = 0.00;
    $releaseCredit = 0.00;
    foreach ($releaseLines as $line) {
        $releaseDebit += (float) $line['debit'];
        $releaseCredit += (float) $line['credit'];
    }
    assertNear(6000.00, $releaseDebit, 'Release voucher debit total.');
    assertNear(6000.00, $releaseCredit, 'Release voucher credit total.');

    $ledgerService->approve($releaseVoucherId, $adminId, '2026-10-06 10:00:00');
    $ledgerService->post($releaseVoucherId, $adminId, '2026-10-06 10:01:00');

    $postedRelease = $ledgerService->find($releaseVoucherId);
    assertSameValue('Posted', $postedRelease['status'], 'Release voucher final status.');
    echo "Loan release → posted Journal Voucher ✓\n";

    // 4. Record a payment against the Active loan.
    $paymentResult = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'End-to-end member loan payment ledger test',
        idempotencyKey: 'E2E-MEMBER-LOAN-PAYMENT-20261006-0001',
    );

    $paymentId = (int) $paymentResult['payment_id'];

    assertSameValue(2120.00, (float) $paymentResult['amount_paid'], 'Payment amount.');
    assertNear(120.00, (float) $paymentResult['interest_applied'], 'Payment interest allocation.');
    assertNear(2000.00, (float) $paymentResult['principal_applied'], 'Payment principal allocation.');
    assertNear(0.00, (float) $paymentResult['excess'], 'Payment excess.');

    $paymentVoucherStatement = $pdo->prepare(
        'SELECT id, status, source_type, source_id
         FROM journal_vouchers
         WHERE source_type = :source_type
           AND source_id = :source_id
         ORDER BY id DESC
         LIMIT 1'
    );
    $paymentVoucherStatement->execute([
        'source_type' => 'LoanPayment',
        'source_id' => $paymentId,
    ]);

    $paymentVoucher = $paymentVoucherStatement->fetch(PDO::FETCH_ASSOC);
    assertTrue($paymentVoucher !== false, 'Loan payment must create a Journal Voucher.');

    $paymentVoucherId = (int) $paymentVoucher['id'];
    assertSameValue('Pending', $paymentVoucher['status'], 'Payment voucher initial status.');
    assertSameValue($paymentId, (int) $paymentVoucher['source_id'], 'Payment voucher source ID.');

    $paymentLines = $ledgerRepository->lines($paymentVoucherId);
    $paymentDebit = 0.00;
    $paymentCredit = 0.00;
    foreach ($paymentLines as $line) {
        $paymentDebit += (float) $line['debit'];
        $paymentCredit += (float) $line['credit'];
    }
    assertNear(2120.00, $paymentDebit, 'Payment voucher debit total.');
    assertNear(2120.00, $paymentCredit, 'Payment voucher credit total.');

    $ledgerService->approve($paymentVoucherId, $adminId, '2026-10-06 10:02:00');
    $ledgerService->post($paymentVoucherId, $adminId, '2026-10-06 10:03:00');

    $postedPayment = $ledgerService->find($paymentVoucherId);
    assertSameValue('Posted', $postedPayment['status'], 'Payment voucher final status.');

    echo "Active loan → payment allocated ✓\n";
    echo "Payment → posted Journal Voucher ✓\n";

    // 5. Confirm the posted accounting activity reaches the General Ledger.
    $cashAccountId = $ledgerService->accountId('1010');
    $receivableAccountId = $ledgerService->accountId('1110');
    $incomeAccountId = $ledgerService->accountId('4010');

    $cashLedger = $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-10-06',
        dateTo: $reportDate,
    );

    $loanLedger = $ledgerService->generalLedger(
        accountId: $receivableAccountId,
        dateFrom: '2026-10-06',
        dateTo: $reportDate,
    );

    $incomeLedger = $ledgerService->generalLedger(
        accountId: $incomeAccountId,
        dateFrom: '2026-10-06',
        dateTo: $reportDate,
    );

    $cashVoucherIds = array_map(
        static fn(array $row): int => (int) $row['voucher_id'],
        $cashLedger['rows'],
    );

    $loanVoucherIds = array_map(
        static fn(array $row): int => (int) $row['voucher_id'],
        $loanLedger['rows'],
    );

    $incomeVoucherIds = array_map(
        static fn(array $row): int => (int) $row['voucher_id'],
        $incomeLedger['rows'],
    );

    assertTrue(in_array($releaseVoucherId, $cashVoucherIds, true), 'Posted release voucher must reach Cash ledger.');
    assertTrue(in_array($paymentVoucherId, $cashVoucherIds, true), 'Posted payment voucher must reach Cash ledger.');
    assertTrue(in_array($releaseVoucherId, $loanVoucherIds, true), 'Posted release voucher must reach Loans Receivable ledger.');
    assertTrue(in_array($paymentVoucherId, $loanVoucherIds, true), 'Posted payment voucher must reach Loans Receivable ledger.');
    assertTrue(in_array($paymentVoucherId, $incomeVoucherIds, true), 'Posted payment voucher must reach Interest Income ledger.');

    echo "Posted vouchers → General Ledger traceable ✓\n";

    // 6. Confirm report-level deltas from this end-to-end workflow.
    $finalTrial = $ledgerService->trialBalance($reportDate);

    $finalRows = [];
    foreach ($finalTrial['rows'] as $row) {
        $finalRows[$row['account_code']] = $row;
    }

    $codes = ['1010', '1110', '4010', '4040', '4050', '4060'];
    foreach ($codes as $code) {
        if (! isset($finalRows[$code])) {
            throw new RuntimeException("Trial Balance account {$code} missing from final result.");
        }
    }

    $baselineCashDebit = (float) ($baselineRows['1010']['debit'] ?? 0.00);
    $baselineCashCredit = (float) ($baselineRows['1010']['credit'] ?? 0.00);
    $baselineLoanDebit = (float) ($baselineRows['1110']['debit'] ?? 0.00);
    $baselineIncomeCredit = (float) ($baselineRows['4010']['credit'] ?? 0.00);
    $baselineProcessingCredit = (float) ($baselineRows['4040']['credit'] ?? 0.00);
    $baselineInsuranceCredit = (float) ($baselineRows['4050']['credit'] ?? 0.00);
    $baselineNotarialCredit = (float) ($baselineRows['4060']['credit'] ?? 0.00);

    assertNear(
        $baselineCashCredit + 3338.40,
        (float) $finalRows['1010']['credit'],
        'Cash Trial Balance delta.',
    );
    assertNear(
        $baselineLoanDebit + 4000.00,
        (float) $finalRows['1110']['debit'],
        'Loans Receivable Trial Balance delta.',
    );
    assertNear(
        $baselineIncomeCredit + 120.00,
        (float) $finalRows['4010']['credit'],
        'Interest Income Trial Balance delta.',
    );
    assertNear(
        $baselineProcessingCredit + 120.00,
        (float) $finalRows['4040']['credit'],
        'Processing Fee Trial Balance delta.',
    );
    assertNear(
        $baselineInsuranceCredit + 21.60,
        (float) $finalRows['4050']['credit'],
        'Insurance Trial Balance delta.',
    );
    assertNear(
        $baselineNotarialCredit + 400.00,
        (float) $finalRows['4060']['credit'],
        'Notarial Fee Trial Balance delta.',
    );

    assertSameValue(true, $finalTrial['balanced'], 'Final Trial Balance must remain balanced.');

    echo "End-to-end Trial Balance reconciliation ✓\n";
    echo "================================================================\n";
    echo "ACES MEMBER → LOAN → PAYMENT → LEDGER END-TO-END INTEGRATION TEST: PASS\n";
    echo "================================================================\n";
} finally {
    if ($paymentId !== null) {
        $pdo->prepare(
            'DELETE FROM loan_payment_allocations
             WHERE payment_id = :payment_id'
        )->execute(['payment_id' => $paymentId]);
    }

    if ($paymentVoucherId !== null) {
        $pdo->prepare(
            'DELETE FROM journal_vouchers WHERE id = :id'
        )->execute(['id' => $paymentVoucherId]);
    }

    if ($releaseVoucherId !== null) {
        $pdo->prepare(
            'DELETE FROM journal_vouchers WHERE id = :id'
        )->execute(['id' => $releaseVoucherId]);
    }

    if ($paymentId !== null) {
        $pdo->prepare(
            'DELETE FROM loan_payments WHERE id = :id'
        )->execute(['id' => $paymentId]);
    }

    if ($loanId !== null) {
        $pdo->prepare(
            'DELETE FROM loan_amortizations WHERE loan_id = :loan_id'
        )->execute(['loan_id' => $loanId]);

        $pdo->prepare(
            'DELETE FROM loans WHERE id = :id'
        )->execute(['id' => $loanId]);
    }
}
