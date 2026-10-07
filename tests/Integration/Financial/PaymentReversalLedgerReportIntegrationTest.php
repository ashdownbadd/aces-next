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

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function assertThrows(callable $callback, string $needle, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($needle !== '' && !str_contains($exception->getMessage(), $needle)) {
            throw new RuntimeException(sprintf(
                '%s Unexpected exception: %s',
                $message,
                $exception->getMessage(),
            ));
        }

        return;
    }

    throw new RuntimeException(sprintf(
        '%s Expected an exception%s.',
        $message,
        $needle === '' ? '' : sprintf(' containing "%s"', $needle),
    ));
}

function paymentToken(string $seed): string
{
    return substr(hash('sha256', $seed . '-' . bin2hex(random_bytes(8))), 0, 40);
}

function normalizeTrialBalance(array $trialBalance): array
{
    $rows = [];

    foreach ($trialBalance['rows'] as $row) {
        $rows[$row['account_code']] = [
            'debit' => round((float) $row['debit'], 2),
            'credit' => round((float) $row['credit'], 2),
        ];
    }

    ksort($rows);

    return [
        'rows' => $rows,
        'total_debit' => round((float) $trialBalance['total_debit'], 2),
        'total_credit' => round((float) $trialBalance['total_credit'], 2),
        'balanced' => (bool) $trialBalance['balanced'],
    ];
}

function normalizeReport(array $report): array
{
    $normalized = $report;

    if (isset($normalized['income']) && is_array($normalized['income'])) {
        foreach ($normalized['income'] as &$row) {
            $row['balance'] = round((float) $row['balance'], 2);
        }
        unset($row);
    }

    if (isset($normalized['expenses']) && is_array($normalized['expenses'])) {
        foreach ($normalized['expenses'] as &$row) {
            $row['balance'] = round((float) $row['balance'], 2);
        }
        unset($row);
    }

    if (isset($normalized['assets']) && is_array($normalized['assets'])) {
        foreach ($normalized['assets'] as &$row) {
            $row['balance'] = round((float) $row['balance'], 2);
        }
        unset($row);
    }

    if (isset($normalized['liabilities']) && is_array($normalized['liabilities'])) {
        foreach ($normalized['liabilities'] as &$row) {
            $row['balance'] = round((float) $row['balance'], 2);
        }
        unset($row);
    }

    if (isset($normalized['equity']) && is_array($normalized['equity'])) {
        foreach ($normalized['equity'] as &$row) {
            $row['balance'] = round((float) $row['balance'], 2);
        }
        unset($row);
    }

    return $normalized;
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
$activityService = new ActivityLogService(
    new ActivityLogRepository($database),
);
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$amortization = new AmortizationService();
$session = new Session();
$userRepository = new UserRepository($database);

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
$reversalVoucherId = null;

try {
    echo "================================================================\n";
    echo "ACES PAYMENT REVERSAL → LEDGER → REPORT INTEGRATION TEST\n";
    echo "================================================================\n";

    // 1. Create a released loan and capture the financial state immediately
    //    before the payment exists. This is the state a successful reversal
    //    must restore.
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

    $releaseVoucher = $ledgerRepository->findBySource('LoanRelease', $loanId);
    assertTrue($releaseVoucher !== null, 'Loan release must create a Journal Voucher.');
    $releaseVoucherId = (int) $releaseVoucher['id'];
    assertSameValue('Pending', $releaseVoucher['status'], 'Loan release voucher must start Pending.');

    $ledgerService->approve(
        $releaseVoucherId,
        $adminId,
        (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    );
    $ledgerService->post(
        $releaseVoucherId,
        $adminId,
        (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    );

    assertSameValue(
        'Posted',
        $ledgerRepository->find($releaseVoucherId)['status'],
        'Loan release voucher must be Posted before capturing the reversal baseline.',
    );

    $loanBeforePayment = $loanService->find($loanId);
    assertTrue($loanBeforePayment !== null, 'Released loan must exist.');
    assertSameValue(LoanStatus::ACTIVE, $loanBeforePayment['loan_status'], 'Loan must be Active before payment.');

    $amortizationBeforePayment = $paymentRepository->amortizations($loanId);
    assertTrue($amortizationBeforePayment !== [], 'Released loan must have amortization rows.');

    $asOfDate = (new DateTimeImmutable())->format('Y-m-d');
    $trialBeforePayment = normalizeTrialBalance(
        $ledgerService->trialBalance($asOfDate),
    );
    $incomeBeforePayment = normalizeReport(
        $ledgerService->incomeStatement('2026-10-06', $asOfDate),
    );
    $balanceBeforePayment = normalizeReport(
        $ledgerService->balanceSheet($asOfDate),
    );

    echo "Released loan → reversal baseline captured ✓\n";

    // 2. Apply and post a payment.
    $paymentResult = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'Payment reversal ledger/report integration QA',
        idempotencyKey: paymentToken('payment-reversal-ledger-report'),
    );

    $paymentId = (int) $paymentResult['payment_id'];
    assertNear(120.00, (float) $paymentResult['interest_applied'], 'Payment interest allocation.');
    assertNear(2000.00, (float) $paymentResult['principal_applied'], 'Payment principal allocation.');

    $paymentVoucher = $ledgerRepository->findBySource('LoanPayment', $paymentId);
    assertTrue($paymentVoucher !== null, 'Payment must create a Journal Voucher.');
    $paymentVoucherId = (int) $paymentVoucher['id'];
    assertSameValue('Pending', $paymentVoucher['status'], 'Payment voucher must start Pending.');

    $ledgerService->approve($paymentVoucherId, $adminId, $asOfDate . ' 10:02:00');
    $ledgerService->post($paymentVoucherId, $adminId, $asOfDate . ' 10:03:00');

    assertSameValue(
        'Posted',
        $ledgerRepository->find($paymentVoucherId)['status'],
        'Payment voucher must be Posted before reversal.',
    );

    $paymentLines = $ledgerRepository->lines($paymentVoucherId);
    assertTrue(count($paymentLines) >= 2, 'Posted payment voucher must contain journal lines.');

    $paymentAllocationCountStatement = $pdo->prepare(
        'SELECT COUNT(DISTINCT amortization_id)
         FROM loan_payment_allocations
         WHERE payment_id = :payment_id'
    );
    $paymentAllocationCountStatement->execute(['payment_id' => $paymentId]);
    $paymentAllocationCount = (int) $paymentAllocationCountStatement->fetchColumn();

    assertTrue(
        $paymentAllocationCount > 0,
        'Payment must create at least one amortization allocation.'
    );

    echo "Payment → posted Journal Voucher ✓\n";

    // 3. Reverse the payment. The reversal voucher is intentionally Pending
    //    until the test explicitly approves and posts it.
    $reverseResult = $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'End-to-end payment reversal QA',
    );

    assertSameValue($paymentId, (int) $reverseResult['payment_id'], 'Reversal must target the original payment.');
    assertSameValue(
        $paymentAllocationCount,
        (int) $reverseResult['amortization_rows_restored'],
        'Reversal must restore every amortization row touched by the payment.',
    );

    $reversedPayment = $paymentRepository->findPayment($paymentId);
    assertTrue($reversedPayment !== null, 'Reversed payment must remain queryable.');
    assertTrue(
        ($reversedPayment['reversed_at'] ?? null) !== null,
        'Payment must be marked reversed.',
    );

    $amortizationAfterPaymentReversal = $paymentRepository->amortizations($loanId);
    assertSameValue(
        $amortizationBeforePayment,
        $amortizationAfterPaymentReversal,
        'Payment reversal must restore amortization state before posting the reversal voucher.',
    );

    $loanAfterReversal = $loanService->find($loanId);
    assertTrue($loanAfterReversal !== null, 'Loan must remain queryable after reversal.');
    assertSameValue(
        $loanBeforePayment['loan_status'],
        $loanAfterReversal['loan_status'],
        'Reversal must restore the pre-payment loan status.',
    );

    $reversalVoucher = $ledgerRepository->findBySource(
        'LoanPaymentReversal',
        $paymentId,
    );
    assertTrue($reversalVoucher !== null, 'Payment reversal must create a reversal Journal Voucher.');
    $reversalVoucherId = (int) $reversalVoucher['id'];
    assertSameValue('Pending', $reversalVoucher['status'], 'Reversal voucher must start Pending.');
    assertSameValue(
        $paymentVoucherId,
        (int) $reversalVoucher['reversal_of_voucher_id'],
        'Reversal voucher must reference the original payment voucher.',
    );

    // 4. Verify exact inverse journal lines before posting the reversal.
    $reversalLines = $ledgerRepository->lines($reversalVoucherId);
    assertSameValue(
        count($paymentLines),
        count($reversalLines),
        'Reversal voucher must contain the same number of lines as the original.',
    );

    $originalByKey = [];
    foreach ($paymentLines as $line) {
        $key = implode('|', [
            (int) $line['account_id'],
            $line['member_id'] === null ? 'null' : (int) $line['member_id'],
            $line['loan_id'] === null ? 'null' : (int) $line['loan_id'],
        ]);
        $originalByKey[$key] = [
            'debit' => round((float) $line['debit'], 2),
            'credit' => round((float) $line['credit'], 2),
        ];
    }

    foreach ($reversalLines as $line) {
        $key = implode('|', [
            (int) $line['account_id'],
            $line['member_id'] === null ? 'null' : (int) $line['member_id'],
            $line['loan_id'] === null ? 'null' : (int) $line['loan_id'],
        ]);

        assertTrue(isset($originalByKey[$key]), 'Every reversal line must correspond to an original payment line.');
        assertNear(
            $originalByKey[$key]['credit'],
            (float) $line['debit'],
            'Reversal debit must equal original credit.',
        );
        assertNear(
            $originalByKey[$key]['debit'],
            (float) $line['credit'],
            'Reversal credit must equal original debit.',
        );
    }

    echo "Payment reversal → exact inverse Journal Voucher ✓\n";

    // 5. Post the reversal and confirm it reaches the General Ledger.
    $ledgerService->approve($reversalVoucherId, $adminId, $asOfDate . ' 10:04:00');
    $ledgerService->post($reversalVoucherId, $adminId, $asOfDate . ' 10:05:00');

    assertSameValue(
        'Posted',
        $ledgerRepository->find($reversalVoucherId)['status'],
        'Reversal voucher must be Posted.',
    );

    $cashAccountId = $ledgerService->accountId('1010');
    $cashLedger = $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-10-06',
        dateTo: $asOfDate,
    );

    $cashVoucherIds = array_map(
        static fn(array $row): int => (int) $row['voucher_id'],
        $cashLedger['rows'],
    );

    assertTrue(
        in_array($paymentVoucherId, $cashVoucherIds, true),
        'Posted payment voucher must reach Cash ledger.',
    );
    assertTrue(
        in_array($reversalVoucherId, $cashVoucherIds, true),
        'Posted reversal voucher must reach Cash ledger.',
    );

    echo "Posted reversal voucher → General Ledger traceable ✓\n";

    // 6. The complete payment + reversal pair must leave reports exactly as
    //    they were immediately before the payment.
    $trialAfterReversal = normalizeTrialBalance(
        $ledgerService->trialBalance($asOfDate),
    );
    $incomeAfterReversal = normalizeReport(
        $ledgerService->incomeStatement('2026-10-06', $asOfDate),
    );
    $balanceAfterReversal = normalizeReport(
        $ledgerService->balanceSheet($asOfDate),
    );

    assertSameValue(
        $trialBeforePayment,
        $trialAfterReversal,
        'Trial Balance must return to the pre-payment state after reversal.',
    );
    assertSameValue(
        $incomeBeforePayment,
        $incomeAfterReversal,
        'Statement of Operations must return to the pre-payment state after reversal.',
    );
    assertSameValue(
        $balanceBeforePayment,
        $balanceAfterReversal,
        'Statement of Financial Position must return to the pre-payment state after reversal.',
    );

    assertSameValue(true, $trialAfterReversal['balanced'], 'Final Trial Balance must remain balanced.');
    assertSameValue(true, $balanceAfterReversal['balanced'], 'Final Statement of Financial Position must remain balanced.');

    echo "Payment + reversal → Trial Balance restored ✓\n";
    echo "Payment + reversal → Statement of Operations restored ✓\n";
    echo "Payment + reversal → Statement of Financial Position restored ✓\n";

    // 7. Repeated reversal must be rejected without changing financial state.
    $trialBeforeRepeatedReverse = $trialAfterReversal;
    $amortizationBeforeRepeatedReverse = $paymentRepository->amortizations($loanId);

    assertThrows(
        fn() => $paymentService->reverse(
            paymentId: $paymentId,
            reason: 'Repeated reversal must fail',
        ),
        'already been reversed',
        'Repeated payment reversal must fail.',
    );

    assertSameValue(
        $trialBeforeRepeatedReverse,
        normalizeTrialBalance($ledgerService->trialBalance($asOfDate)),
        'Rejected repeated reversal must not change Trial Balance.',
    );
    assertSameValue(
        $amortizationBeforeRepeatedReverse,
        $paymentRepository->amortizations($loanId),
        'Rejected repeated reversal must not change amortization state.',
    );

    echo "Repeated payment reversal → blocked and state unchanged ✓\n";

    echo "================================================================\n";
    echo "ACES PAYMENT REVERSAL → LEDGER → REPORT INTEGRATION TEST: PASS\n";
    echo "================================================================\n";
} finally {
    // Reversal child must be deleted before its original voucher because of
    // journal_vouchers.reversal_of_voucher_id foreign-key protection.
    $voucherIds = array_values(array_filter([
        $reversalVoucherId,
        $paymentVoucherId,
        $releaseVoucherId,
    ], static fn(?int $id): bool => $id !== null && $id > 0));

    if ($voucherIds !== []) {
        $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));

        // Journal lines reference their parent voucher, so remove the lines
        // before removing the vouchers themselves.
        $statement = $pdo->prepare(
            "DELETE FROM journal_lines WHERE journal_voucher_id IN ({$placeholders})"
        );
        $statement->execute($voucherIds);

        // Detach any reversal children created by this test before deleting
        // the voucher rows. This makes cleanup safe regardless of deletion
        // order within the captured test voucher set.
        $statement = $pdo->prepare(
            "UPDATE journal_vouchers
             SET reversal_of_voucher_id = NULL
             WHERE reversal_of_voucher_id IN ({$placeholders})
                OR id IN ({$placeholders})"
        );
        $statement->execute(array_merge($voucherIds, $voucherIds));

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers WHERE id IN ({$placeholders})"
        );
        $statement->execute($voucherIds);
    }

    if ($paymentId !== null) {
        $pdo->prepare(
            'DELETE FROM loan_payment_allocations
             WHERE payment_id = :payment_id'
        )->execute(['payment_id' => $paymentId]);

        $pdo->prepare(
            'DELETE FROM loan_payments WHERE id = :id'
        )->execute(['id' => $paymentId]);
    }

    if ($loanId !== null) {
        $pdo->prepare(
            'DELETE FROM activity_logs
             WHERE subject_type = :subject_type
               AND subject_id = :subject_id'
        )->execute([
            'subject_type' => 'Loan',
            'subject_id' => $loanId,
        ]);

        $pdo->prepare(
            'DELETE FROM loan_amortizations WHERE loan_id = :loan_id'
        )->execute(['loan_id' => $loanId]);

        $pdo->prepare(
            'DELETE FROM loans WHERE id = :id'
        )->execute(['id' => $loanId]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
