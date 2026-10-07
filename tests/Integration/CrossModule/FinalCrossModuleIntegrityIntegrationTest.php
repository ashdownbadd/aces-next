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
use App\Features\Loans\Domain\LoanStatus;
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

function assertTrueValue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function assertPosted(JournalVoucherRepository $repository, int $voucherId): void
{
    $voucher = $repository->find($voucherId);

    if ($voucher === null) {
        throw new RuntimeException(
            sprintf('Journal Voucher #%d was not found.', $voucherId)
        );
    }

    assertSameValue(
        'Posted',
        $voucher['status'],
        sprintf('Journal Voucher #%d must be Posted.', $voucherId),
    );
}

/**
 * @return array{debit:float,credit:float}
 */
function voucherTotals(
    JournalVoucherRepository $repository,
    int $voucherId,
): array {
    $debit = 0.00;
    $credit = 0.00;

    foreach ($repository->lines($voucherId) as $line) {
        $debit = round($debit + (float) $line['debit'], 2);
        $credit = round($credit + (float) $line['credit'], 2);
    }

    return [
        'debit' => $debit,
        'credit' => $credit,
    ];
}

/**
 * @return array<string,float|bool>
 */
function reportSnapshot(
    LedgerService $ledger,
    int $cashAccountId,
    string $dateFrom,
    string $dateTo,
): array {
    $cashLedger = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $dateFrom,
        dateTo: $dateTo,
    );

    $trialBalance = $ledger->trialBalance($dateTo);
    $incomeStatement = $ledger->incomeStatement($dateFrom, $dateTo);
    $balanceSheet = $ledger->balanceSheet($dateTo);

    return [
        'cash_closing' => (float) $cashLedger['closing_balance'],
        'trial_debit' => (float) $trialBalance['total_debit'],
        'trial_credit' => (float) $trialBalance['total_credit'],
        'income' => (float) $incomeStatement['total_income'],
        'expenses' => (float) $incomeStatement['total_expenses'],
        'net_surplus' => (float) $incomeStatement['net_surplus'],
        'assets' => (float) $balanceSheet['total_assets'],
        'liabilities_equity' => (float) $balanceSheet['liabilities_and_equity'],
        'trial_balanced' => (bool) $trialBalance['balanced'],
        'position_balanced' => (bool) $balanceSheet['balanced'],
    ];
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
    throw new RuntimeException(
        'An active user and active member are required.'
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityService = new ActivityLogService(
    new ActivityLogRepository($database),
);
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
$paymentVoucherId = null;
$reversalVoucherId = null;
$loanReleaseVoucherId = null;

$dateFrom = date('Y-m-d');
$dateTo = $dateFrom;

try {
    echo "===============================================================\n";
    echo "ACES FINAL CROSS-MODULE INTEGRITY INTEGRATION TEST\n";
    echo "===============================================================\n";

    /*
     * MEMBER → LOAN
     */
    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: $dateFrom,
    ));

    $createdLoan = $loanService->find($loanId);

    assertSameValue(
        $memberId,
        (int) $createdLoan['member_id'],
        'Loan must remain attached to the selected member.',
    );
    assertSameValue(
        'Pending',
        $createdLoan['application_status'],
        'New loan must start Pending.',
    );
    echo "Active member → loan created and linked        ✓\n";

    /*
     * LOAN → ACTIVE LOAN → RELEASE ACCOUNTING
     */
    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, $dateFrom);

    $activeLoan = $loanService->find($loanId);

    assertSameValue(
        LoanStatus::ACTIVE,
        $activeLoan['loan_status'],
        'Released loan must be Active.',
    );

    $loanReleaseVoucher = $pdo->prepare(
        "SELECT id
         FROM journal_vouchers
         WHERE source_type = 'LoanRelease'
           AND source_id = :loan_id
         ORDER BY id DESC
         LIMIT 1"
    );
    $loanReleaseVoucher->execute(['loan_id' => $loanId]);
    $loanReleaseVoucherId = (int) $loanReleaseVoucher->fetchColumn();

    assertTrueValue(
        $loanReleaseVoucherId > 0,
        'Loan release must create an accounting voucher.',
    );

    $loanReleaseVoucher = $ledgerRepository->find(
        $loanReleaseVoucherId,
    );

    assertSameValue(
        'Pending',
        $loanReleaseVoucher['status'] ?? null,
        'Loan release accounting voucher must begin Pending.',
    );

    $ledgerService->approve(
        $loanReleaseVoucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    $ledgerService->post(
        $loanReleaseVoucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    assertPosted($ledgerRepository, $loanReleaseVoucherId);

    $releaseTotals = voucherTotals(
        $ledgerRepository,
        $loanReleaseVoucherId,
    );

    assertNear(
        $releaseTotals['debit'],
        $releaseTotals['credit'],
        'Loan release voucher must balance.',
    );

    echo "Loan lifecycle → Active + release voucher      ✓\n";

    /*
     * Capture the complete financial baseline after loan release.
     * Payment/reversal restoration is compared against this state.
     */
    $cashAccountId = $ledgerService->accountId('1010');

    if ($cashAccountId <= 0) {
        throw new RuntimeException('Cash account 1010 was not found.');
    }

    $baseline = reportSnapshot(
        $ledgerService,
        $cashAccountId,
        $dateFrom,
        $dateTo,
    );

    assertTrueValue(
        $baseline['trial_balanced'],
        'Baseline Trial Balance must be balanced.',
    );
    assertTrueValue(
        $baseline['position_balanced'],
        'Baseline Statement of Financial Position must be balanced.',
    );

    /*
     * LOAN → PAYMENT → JOURNAL VOUCHER → REPORTS
     */
    $paymentResult = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 500.00,
        remarks: 'Final cross-module integrity QA payment',
        idempotencyKey: substr(
            hash('sha256', 'final-cross-module-' . bin2hex(random_bytes(8))),
            0,
            40,
        ),
    );

    $paymentId = (int) $paymentResult['payment_id'];

    assertTrueValue(
        $paymentId > 0,
        'Payment must create a payment record.',
    );

    $paymentVoucher = $ledgerRepository->findBySource(
        'LoanPayment',
        $paymentId,
    );

    if ($paymentVoucher === null) {
        throw new RuntimeException(
            'Payment must create a Journal Voucher.'
        );
    }

    $paymentVoucherId = (int) $paymentVoucher['id'];

    $ledgerService->approve(
        $paymentVoucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    $ledgerService->post(
        $paymentVoucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    assertPosted($ledgerRepository, $paymentVoucherId);

    $afterPayment = reportSnapshot(
        $ledgerService,
        $cashAccountId,
        $dateFrom,
        $dateTo,
    );

    assertNear(
        $baseline['cash_closing'] + 500.00,
        $afterPayment['cash_closing'],
        'Posted payment must increase cash General Ledger by ₱500.00.',
    );

    $paymentIncomeStatement = $pdo->prepare(
        "SELECT COALESCE(SUM(jl.credit - jl.debit), 0)
         FROM journal_lines AS jl
         INNER JOIN accounts AS a
             ON a.id = jl.account_id
         WHERE jl.journal_voucher_id = :voucher_id
           AND a.account_type = 'Income'"
    );

    $paymentIncomeStatement->execute([
        'voucher_id' => $paymentVoucherId,
    ]);

    $paymentIncomeMovement = round(
        (float) $paymentIncomeStatement->fetchColumn(),
        2,
    );

    assertNear(
        120.00,
        $paymentIncomeMovement,
        '₱500.00 payment must map ₱120.00 to interest income and the remainder to principal.',
    );

    assertNear(
        $baseline['income'] + $paymentIncomeMovement,
        $afterPayment['income'],
        'Posted payment must increase reporting income by its interest allocation, not the full payment amount.',
    );

    assertTrueValue(
        $afterPayment['trial_balanced'],
        'Trial Balance must remain balanced after payment.',
    );
    assertTrueValue(
        $afterPayment['position_balanced'],
        'Statement of Financial Position must remain balanced after payment.',
    );

    echo "Payment → Posted voucher → reports updated      ✓\n";

    /*
     * JOURNAL VOUCHER → GENERAL LEDGER TRACEABILITY
     */
    $cashLedger = $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $dateFrom,
        dateTo: $dateTo,
    );

    $paymentLedgerRows = array_values(array_filter(
        $cashLedger['rows'],
        static fn (array $row): bool =>
            (int) $row['voucher_id'] === $paymentVoucherId,
    ));

    assertTrueValue(
        $paymentLedgerRows !== [],
        'Posted payment voucher must be traceable in the cash General Ledger.',
    );

    echo "Posted payment → General Ledger traceable      ✓\n";

    /*
     * PAYMENT → REVERSAL
     */
    $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'Final cross-module integrity QA reversal',
    );

    $reversalVoucher = $ledgerRepository->findBySource(
        'LoanPaymentReversal',
        $paymentId,
    );

    if ($reversalVoucher === null) {
        throw new RuntimeException(
            'Payment reversal must create a reversal Journal Voucher.'
        );
    }

    $reversalVoucherId = (int) $reversalVoucher['id'];

    $ledgerService->approve(
        $reversalVoucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    $ledgerService->post(
        $reversalVoucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    assertPosted($ledgerRepository, $reversalVoucherId);

    /*
     * Verify exact inverse journal lines.
     */
    $originalLines = $ledgerRepository->lines($paymentVoucherId);
    $reversalLines = $ledgerRepository->lines($reversalVoucherId);

    assertSameValue(
        count($originalLines),
        count($reversalLines),
        'Payment reversal must preserve the original journal line count.',
    );

    foreach ($originalLines as $index => $originalLine) {
        $reversalLine = $reversalLines[$index];

        assertSameValue(
            (int) $originalLine['account_id'],
            (int) $reversalLine['account_id'],
            'Reversal must preserve account mapping.',
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
    }

    echo "Payment reversal → exact inverse voucher      ✓\n";

    /*
     * RESTORATION ACROSS ALL REPORTING LAYERS
     */
    $afterReversal = reportSnapshot(
        $ledgerService,
        $cashAccountId,
        $dateFrom,
        $dateTo,
    );

    foreach ([
        'cash_closing',
        'trial_debit',
        'trial_credit',
        'income',
        'expenses',
        'net_surplus',
        'assets',
        'liabilities_equity',
    ] as $metric) {
        assertNear(
            (float) $baseline[$metric],
            (float) $afterReversal[$metric],
            sprintf(
                'Payment reversal must restore report metric %s.',
                $metric,
            ),
        );
    }

    assertTrueValue(
        $afterReversal['trial_balanced'],
        'Trial Balance must remain balanced after reversal.',
    );
    assertTrueValue(
        $afterReversal['position_balanced'],
        'Statement of Financial Position must remain balanced after reversal.',
    );

    echo "Reversal → General Ledger restored              ✓\n";
    echo "Reversal → Trial Balance restored               ✓\n";
    echo "Reversal → Statement of Operations restored     ✓\n";
    echo "Reversal → Financial Position restored          ✓\n";

    /*
     * FINAL BUSINESS STATE
     */
    $finalLoan = $loanService->find($loanId);

    assertSameValue(
        LoanStatus::ACTIVE,
        $finalLoan['loan_status'],
        'Reversing a partial payment must leave the loan Active.',
    );

    echo "Loan state after reversal remains Active        ✓\n";

    /*
     * ACTIVITY LOGS
     */
    $actions = $pdo->prepare(
        "SELECT action
         FROM activity_logs
         WHERE subject_type = 'Loan'
           AND subject_id = :loan_id
           AND action IN (
                'LOAN_CREATED',
                'LOAN_SUBMITTED',
                'LOAN_REVIEWED',
                'LOAN_APPROVED',
                'LOAN_AMORTIZATION_GENERATED',
                'LOAN_RELEASED',
                'LOAN_PAYMENT_APPLIED',
                'LOAN_PAYMENT_REVERSED'
           )
         ORDER BY id ASC"
    );
    $actions->execute(['loan_id' => $loanId]);

    $loggedActions = array_values(
        array_unique(
            $actions->fetchAll(PDO::FETCH_COLUMN),
        )
    );

    foreach ([
        'LOAN_CREATED',
        'LOAN_SUBMITTED',
        'LOAN_APPROVED',
        'LOAN_AMORTIZATION_GENERATED',
        'LOAN_RELEASED',
        'LOAN_PAYMENT_APPLIED',
        'LOAN_PAYMENT_REVERSED',
    ] as $requiredAction) {
        assertTrueValue(
            in_array($requiredAction, $loggedActions, true),
            sprintf(
                'Required audit action %s was not recorded.',
                $requiredAction,
            ),
        );
    }

    echo "Complete lifecycle → Activity Logs preserved   ✓\n";

    /*
     * VOUCHER BALANCE + REPORT BALANCE FINAL CHECK
     */
    $paymentTotals = voucherTotals(
        $ledgerRepository,
        $paymentVoucherId,
    );
    $reversalTotals = voucherTotals(
        $ledgerRepository,
        $reversalVoucherId,
    );

    assertNear(
        $paymentTotals['debit'],
        $paymentTotals['credit'],
        'Payment Journal Voucher must balance.',
    );

    assertNear(
        $reversalTotals['debit'],
        $reversalTotals['credit'],
        'Reversal Journal Voucher must balance.',
    );

    assertNear(
        $paymentTotals['debit'],
        $reversalTotals['credit'],
        'Reversal total credit must equal original total debit.',
    );

    assertNear(
        $paymentTotals['credit'],
        $reversalTotals['debit'],
        'Reversal total debit must equal original total credit.',
    );

    echo "Payment/reversal vouchers remain balanced        ✓\n";
    echo "===============================================================\n";
    echo "ACES FINAL CROSS-MODULE INTEGRITY INTEGRATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    /*
     * Delete reversal journal lines before the reversal voucher because
     * reversal_of_voucher_id references the original voucher.
     */
    if ($reversalVoucherId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM journal_lines WHERE journal_voucher_id = :id'
        );
        $statement->execute(['id' => $reversalVoucherId]);

        $statement = $pdo->prepare(
            'DELETE FROM journal_vouchers WHERE id = :id'
        );
        $statement->execute(['id' => $reversalVoucherId]);
    }

    if ($paymentVoucherId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM journal_lines WHERE journal_voucher_id = :id'
        );
        $statement->execute(['id' => $paymentVoucherId]);

        $statement = $pdo->prepare(
            'DELETE FROM journal_vouchers WHERE id = :id'
        );
        $statement->execute(['id' => $paymentVoucherId]);
    }

    if ($loanReleaseVoucherId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM journal_lines WHERE journal_voucher_id = :id'
        );
        $statement->execute(['id' => $loanReleaseVoucherId]);

        $statement = $pdo->prepare(
            'DELETE FROM journal_vouchers WHERE id = :id'
        );
        $statement->execute(['id' => $loanReleaseVoucherId]);
    }

    if ($paymentId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM loan_payment_allocations WHERE payment_id = :id'
        );
        $statement->execute(['id' => $paymentId]);

        $statement = $pdo->prepare(
            'DELETE FROM activity_logs
             WHERE subject_type = "Loan"
               AND subject_id = :loan_id
               AND action IN (
                    "LOAN_PAYMENT_APPLIED",
                    "LOAN_PAYMENT_REVERSED"
               )'
        );
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare(
            'DELETE FROM loan_payments WHERE id = :id'
        );
        $statement->execute(['id' => $paymentId]);
    }

    if ($loanId !== null) {
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
