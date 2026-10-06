<?php

declare(strict_types=1);

/**
 * ACES Member → Loan status boundary integration test.
 *
 * Confirmed business rules:
 * - A Pending or Under Review loan cannot be approved when its member is Inactive.
 * - An already Active loan remains Active when its member becomes Inactive.
 * - An Inactive member remains obligated to pay an existing Active loan.
 *
 * Run from the project root:
 *   php tests/Integration/Members/MemberLoanStatusBoundaryIntegrationTest.php
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanApplicationStatus;
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

function assertThrows(callable $callback, string $messageNeedle, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($messageNeedle !== '' && !str_contains($exception->getMessage(), $messageNeedle)) {
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
        $messageNeedle,
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
    "SELECT id FROM users WHERE is_active = 1 AND role = 'admin' ORDER BY id ASC LIMIT 1"
)->fetchColumn();
$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new RuntimeException('An active admin user and Active member are required.');
}

$originalMemberStatus = (string) $pdo->query(
    'SELECT status FROM members WHERE id = ' . $memberId
)->fetchColumn();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
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
    new LoanPaymentRepository($database),
    $ledgerService,
    $ledgerRepository,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
    $database,
);

$loanIds = [];
$paymentIds = [];

$makeLoan = static function () use ($loanService, $memberId, &$loanIds): int {
    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: date('Y-m-d'),
    ));

    $loanIds[] = $loanId;

    return $loanId;
};

try {
    echo "============================================================\n";
    echo "ACES MEMBER → LOAN STATUS BOUNDARY INTEGRATION TEST\n";
    echo "============================================================\n";

    // Pending loans are never directly approvable; the member-status boundary
    // applies at the Under Review → Approved decision point.
    $pendingLoanId = $makeLoan();

    assertThrows(
        fn() => $loanService->approve($pendingLoanId),
        'Only loans Under Review can be approved.',
        'Pending loan must not be directly approvable.'
    );

    assertSameValue(
        LoanApplicationStatus::PENDING,
        $loanService->find($pendingLoanId)['application_status'],
        'Direct approval attempt must leave Pending loan unchanged.'
    );

    echo "Pending loan → direct approval blocked ✓\n";

    // Restore Active to submit the application, then deactivate before approval.
    $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = :id")
        ->execute(['id' => $memberId]);
    $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = :id")
        ->execute(['id' => $memberId]);

    $reviewLoanId = $makeLoan();
    $loanService->submit($reviewLoanId);

    $pdo->prepare("UPDATE members SET status = 'Inactive' WHERE id = :id")
        ->execute(['id' => $memberId]);

    assertThrows(
        fn() => $loanService->approve($reviewLoanId),
        'member',
        'Inactive member + Under Review loan → approval must be blocked.'
    );

    assertSameValue(
        LoanApplicationStatus::UNDER_REVIEW,
        $loanService->find($reviewLoanId)['application_status'],
        'Blocked approval must leave Under Review loan unchanged.'
    );

    echo "Inactive member + Under Review loan → approval blocked ✓\n";

    // An already Active loan must survive member deactivation and remain payable.
    $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = :id")
        ->execute(['id' => $memberId]);

    $activeLoanId = $makeLoan();
    $loanService->submit($activeLoanId);
    $loanService->approve($activeLoanId);
    $loanService->release($activeLoanId, date('Y-m-d'));

    $pdo->prepare("UPDATE members SET status = 'Inactive' WHERE id = :id")
        ->execute(['id' => $memberId]);

    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($activeLoanId)['loan_status'],
        'Member deactivation must not deactivate an existing Active loan.'
    );

    echo "Active loan + member deactivated → loan remains Active ✓\n";

    $payment = $paymentService->apply(
        loanId: $activeLoanId,
        amountPaid: 1000.00,
        remarks: 'Inactive member repayment boundary QA',
        idempotencyKey: paymentToken('inactive-member-payment'),
    );
    $paymentIds[] = (int) $payment['payment_id'];

    assertSameValue(
        1000.00,
        (float) $payment['amount_paid'],
        'Inactive member must still be allowed to repay an existing Active loan.'
    );

    assertSameValue(
        LoanStatus::ACTIVE,
        $loanService->find($activeLoanId)['loan_status'],
        'A partial payment by an inactive member must leave the loan Active.'
    );

    echo "Inactive member + Active loan → payment allowed ✓\n";

    echo "============================================================\n";
    echo "ACES MEMBER → LOAN STATUS BOUNDARY INTEGRATION TEST: PASS\n";
    echo "============================================================\n";
} finally {
    if ($paymentIds !== []) {
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));

        $statement = $pdo->prepare(
            "DELETE FROM loan_payment_allocations WHERE payment_id IN ({$placeholders})"
        );
        $statement->execute($paymentIds);

        $statement = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE (source_type = 'LoanPayment' OR source_type = 'LoanPaymentReversal')
               AND source_id IN ({$placeholders})"
        );
        $statement->execute($paymentIds);
        $voucherIds = array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));

        if ($voucherIds !== []) {
            $voucherPlaceholders = implode(',', array_fill(0, count($voucherIds), '?'));

            $statement = $pdo->prepare(
                "DELETE FROM journal_vouchers
                 WHERE id IN ({$voucherPlaceholders})
                   AND reversal_of_voucher_id IS NOT NULL"
            );
            $statement->execute($voucherIds);

            $statement = $pdo->prepare(
                "DELETE FROM journal_vouchers WHERE id IN ({$voucherPlaceholders})"
            );
            $statement->execute($voucherIds);
        }

        $statement = $pdo->prepare(
            "DELETE FROM loan_payments WHERE id IN ({$placeholders})"
        );
        $statement->execute($paymentIds);
    }

    foreach ($loanIds as $loanId) {
        $statement = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE source_type = 'LoanRelease' AND source_id = :loan_id"
        );
        $statement->execute(['loan_id' => $loanId]);
        $voucherIds = array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));

        if ($voucherIds !== []) {
            $voucherPlaceholders = implode(',', array_fill(0, count($voucherIds), '?'));
            $statement = $pdo->prepare(
                "DELETE FROM journal_vouchers WHERE id IN ({$voucherPlaceholders})"
            );
            $statement->execute($voucherIds);
        }

        $statement = $pdo->prepare(
            'DELETE FROM activity_logs WHERE subject_type = \'Loan\' AND subject_id = :loan_id'
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

    $pdo->prepare(
        'UPDATE members SET status = :status WHERE id = :id'
    )->execute([
        'status' => $originalMemberStatus,
        'id' => $memberId,
    ]);

    echo "Cleanup completed for Member #{$memberId}.\n";
}
