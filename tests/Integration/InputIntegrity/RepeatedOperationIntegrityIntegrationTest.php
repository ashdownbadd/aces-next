<?php

declare(strict_types=1);

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

function assertThrows(callable $callback, string $needle, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (!str_contains($exception->getMessage(), $needle)) {
            throw new RuntimeException(sprintf(
                '%s Unexpected exception: %s',
                $message,
                $exception->getMessage(),
            ));
        }
        return;
    }

    throw new RuntimeException(sprintf(
        '%s Expected an exception containing "%s".',
        $message,
        $needle,
    ));
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
$paymentIds = [];

try {
    echo "================================================\n";
    echo "ACES REPEATED OPERATION INTEGRITY INTEGRATION TEST\n";
    echo "================================================\n";

    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: '2026-09-20',
    ));

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-09-20');

    $beforePayments = count($paymentRepository->paymentsForLoan($loanId));
    $token = paymentToken('repeat-payment');

    $first = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 1000.00,
        remarks: 'Repeated operation integrity QA',
        idempotencyKey: $token,
    );
    $paymentId = (int) $first['payment_id'];
    $paymentIds[] = $paymentId;

    assertSameValue($beforePayments + 1, count($paymentRepository->paymentsForLoan($loanId)), 'First payment must create exactly one payment.');
    assertNear(1000.00, (float) $first['amount_paid'], 'First payment amount.');

    $replay = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 1000.00,
        remarks: 'Repeated operation integrity replay',
        idempotencyKey: $token,
    );

    assertSameValue($paymentId, (int) $replay['payment_id'], 'Repeated payment must return the original payment.');
    assertSameValue($beforePayments + 1, count($paymentRepository->paymentsForLoan($loanId)), 'Repeated payment must not create a duplicate.');
    echo "Same payment request replay       → no duplicate ✓\n";

    assertThrows(
        fn() => $paymentService->apply(
            loanId: $loanId,
            amountPaid: 1001.00,
            remarks: 'Changed amount with reused token',
            idempotencyKey: $token,
        ),
        'This payment request token was already used for a different payment.',
        'Changing a payment while reusing its token must fail.',
    );
    assertSameValue($beforePayments + 1, count($paymentRepository->paymentsForLoan($loanId)), 'Rejected token reuse must not create a payment.');
    echo "Reused token with changed amount → blocked ✓\n";

    $beforeReverse = $paymentRepository->amortizations($loanId);
    $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'Repeated operation integrity reversal QA',
    );
    $afterReverse = $paymentRepository->amortizations($loanId);

    assertThrows(
        fn() => $paymentService->reverse(
            paymentId: $paymentId,
            reason: 'Repeated reversal must fail',
        ),
        'already been reversed',
        'Repeated payment reversal must fail.',
    );
    $afterSecondReverse = $paymentRepository->amortizations($loanId);
    assertSameValue($afterReverse, $afterSecondReverse, 'Rejected second reversal must not alter amortization balances.');
    echo "Repeated payment reversal        → blocked ✓\n";

    $statusBeforeSecondRelease = $loanService->find($loanId)['loan_status'];
    assertSameValue(LoanStatus::ACTIVE, $statusBeforeSecondRelease, 'Released loan must be Active before duplicate release test.');

    assertThrows(
        fn() => $loanService->release($loanId, '2026-09-20'),
        'already',
        'Repeated loan release must fail.',
    );
    assertSameValue(
        $statusBeforeSecondRelease,
        $loanService->find($loanId)['loan_status'],
        'Rejected duplicate release must not change loan status.',
    );
    echo "Repeated loan release             → blocked ✓\n";

    $voucher = $ledgerRepository->findBySource('LoanPayment', $paymentId);
    if ($voucher === null) {
        throw new RuntimeException('Payment voucher was not created.');
    }

    $voucherId = (int) $voucher['id'];
    $voucherBefore = $ledgerRepository->find($voucherId);
    if ($voucherBefore === null) {
        throw new RuntimeException('Payment voucher could not be reloaded.');
    }

    assertSameValue(
        'Pending',
        $voucherBefore['status'],
        'Payment voucher must be Pending before the repeated approval test.',
    );

    $ledgerService->approve(
        $voucherId,
        (int) $userId,
        date('Y-m-d H:i:s'),
    );

    $voucherApproved = $ledgerRepository->find($voucherId);
    if ($voucherApproved === null) {
        throw new RuntimeException('Approved payment voucher could not be reloaded.');
    }

    assertSameValue(
        'Approved',
        $voucherApproved['status'],
        'Initial voucher approval must move the voucher to Approved.',
    );

    assertThrows(
        fn() => $ledgerService->approve(
            $voucherId,
            (int) $userId,
            date('Y-m-d H:i:s'),
        ),
        'Only Pending journal vouchers can be approved.',
        'Repeated voucher approval must fail.',
    );

    $voucherAfter = $ledgerRepository->find($voucherId);
    assertSameValue(
        $voucherApproved['status'],
        $voucherAfter['status'],
        'Rejected repeated voucher approval must not alter status.',
    );
    echo "Repeated voucher approval         → blocked ✓\n";

    $remainingPayments = count($paymentRepository->paymentsForLoan($loanId));
    assertSameValue($beforePayments + 1, $remainingPayments, 'Final payment count must remain unchanged after rejected repeats.');
    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($loanId)['loan_status'],
        'Loan must remain Active after rejected repeated operations.',
    );
    echo "Financial state after repeats     → unchanged ✓\n";

    echo "================================================\n";
    echo "ACES REPEATED OPERATION INTEGRITY INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    if ($loanId !== null) {
        if ($paymentIds !== []) {
            $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
            $statement = $pdo->prepare("DELETE FROM loan_payment_allocations WHERE payment_id IN ({$placeholders})");
            $statement->execute($paymentIds);

            $statement = $pdo->prepare(
                "SELECT id FROM journal_vouchers
                 WHERE (source_type = 'LoanPayment' OR source_type = 'LoanPaymentReversal')
                   AND source_id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);
            $voucherIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

            if ($voucherIds !== []) {
                $voucherPlaceholders = implode(',', array_fill(0, count($voucherIds), '?'));
                $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ({$voucherPlaceholders}) AND reversal_of_voucher_id IS NOT NULL");
                $statement->execute($voucherIds);
                $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ({$voucherPlaceholders})");
                $statement->execute($voucherIds);
            }

            $statement = $pdo->prepare("DELETE FROM loan_payments WHERE id IN ({$placeholders})");
            $statement->execute($paymentIds);
        }

        $statement = $pdo->prepare("DELETE FROM activity_logs WHERE subject_type = 'Loan' AND subject_id = :loan_id");
        $statement->execute(['loan_id' => $loanId]);
        $statement = $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);
        $statement = $pdo->prepare('DELETE FROM loans WHERE id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
