<?php

declare(strict_types=1);

/**
 * Verifies that member inactivity does not invalidate repayment obligations
 * or prevent reversal of an existing loan payment.
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

$originalMemberStatus = (string) $pdo->query(
    'SELECT status FROM members WHERE id = ' . $memberId
)->fetchColumn();

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
    echo "============================================================\n";
    echo "ACES MEMBER → PAYMENT / REVERSAL BOUNDARY INTEGRATION TEST\n";
    echo "============================================================\n";

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

    $payment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 1000.00,
        remarks: 'Member payment/reversal boundary QA',
        idempotencyKey: paymentToken('member-boundary'),
    );
    $paymentId = (int) $payment['payment_id'];
    $paymentIds[] = $paymentId;

    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($loanId)['loan_status'],
        'Loan must be Active after partial payment.',
    );
    echo "Active loan + payment before inactivity     ✓\n";

    $statement = $pdo->prepare(
        "UPDATE members SET status = 'Inactive' WHERE id = :id"
    );
    $statement->execute(['id' => $memberId]);

    assertSameValue(
        'Inactive',
        $pdo->query('SELECT status FROM members WHERE id = ' . $memberId)->fetchColumn(),
        'Member must be Inactive for boundary test.',
    );

    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($loanId)['loan_status'],
        'Existing Active loan must remain Active after member deactivation.',
    );
    echo "Member deactivated + existing loan remains Active ✓\n";

    $secondPayment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 500.00,
        remarks: 'Inactive member repayment QA',
        idempotencyKey: paymentToken('inactive-payment'),
    );
    $secondPaymentId = (int) $secondPayment['payment_id'];
    $paymentIds[] = $secondPaymentId;

    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($loanId)['loan_status'],
        'Loan must remain Active after inactive-member payment.',
    );
    echo "Inactive member + Active loan payment       ✓\n";

    $paymentService->reverse(
        paymentId: $secondPaymentId,
        reason: 'Boundary reversal QA',
    );

    $storedPayment = $paymentRepository->findPayment($secondPaymentId);
    if ($storedPayment === null || ($storedPayment['reversed_at'] ?? null) === null) {
        throw new RuntimeException('Payment reversal was not persisted.');
    }

    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($loanId)['loan_status'],
        'Reversing a payment on an Active loan must not change loan status.',
    );
    echo "Inactive member + payment reversal           ✓\n";

    echo "============================================================\n";
    echo "ACES MEMBER → PAYMENT / REVERSAL BOUNDARY INTEGRATION TEST: PASS\n";
    echo "============================================================\n";
} finally {
    if ($loanId !== null) {
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));

        if ($paymentIds !== []) {
            $statement = $pdo->prepare(
                "SELECT id FROM journal_vouchers
                 WHERE (source_type = 'LoanPayment' OR source_type = 'LoanPaymentReversal')
                   AND source_id IN ({$placeholders})"
            );
            $statement->execute($paymentIds);
            $voucherIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

            if ($voucherIds !== []) {
                $voucherPlaceholders = implode(',', array_fill(0, count($voucherIds), '?'));
                $delete = $pdo->prepare(
                    "DELETE FROM journal_lines WHERE journal_voucher_id IN ({$voucherPlaceholders})"
                );
                $delete->execute($voucherIds);

                $clearReversalLinks = $pdo->prepare(
                    "UPDATE journal_vouchers
                     SET reversal_of_voucher_id = NULL
                     WHERE id IN ({$voucherPlaceholders})
                       AND reversal_of_voucher_id IS NOT NULL"
                );
                $clearReversalLinks->execute($voucherIds);

                $delete = $pdo->prepare(
                    "DELETE FROM journal_vouchers WHERE id IN ({$voucherPlaceholders})"
                );
                $delete->execute($voucherIds);
            }

            $delete = $pdo->prepare(
                "DELETE FROM loan_payment_allocations WHERE payment_id IN ({$placeholders})"
            );
            $delete->execute($paymentIds);

            $delete = $pdo->prepare(
                "DELETE FROM loan_payments WHERE id IN ({$placeholders})"
            );
            $delete->execute($paymentIds);
        }

        $delete = $pdo->prepare(
            "DELETE FROM activity_logs WHERE subject_type = 'Loan' AND subject_id = ?"
        );
        $delete->execute([$loanId]);

        $delete = $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = ?');
        $delete->execute([$loanId]);

        $delete = $pdo->prepare('DELETE FROM loans WHERE id = ?');
        $delete->execute([$loanId]);
    }

    $restore = $pdo->prepare('UPDATE members SET status = :status WHERE id = :id');
    $restore->execute([
        'status' => $originalMemberStatus,
        'id' => $memberId,
    ]);

    echo "Cleanup completed for Member #{$memberId}.\n";
}
