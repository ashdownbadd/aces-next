<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
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
                "%s Expected %s, got %s.",
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
            sprintf("%s Expected %.2f, got %.2f.", $message, $expected, $actual)
        );
    }
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
$memberId = (int) $pdo->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')->fetchColumn();

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
$amortization = new AmortizationService();
$session = new Session();

$loanService = new LoanService(
    $loanRepository,
    $amortization,
    $activityService,
    $session,
);

$paymentService = new PaymentService(
    $paymentRepository,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
);

$loanId = null;

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
            startDate: '2026-08-19',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-08-20');

    $result = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'First monthly payment',
    );

    assertNear(2120.00, (float) $result['amount_paid'], 'Payment amount.');
    assertNear(0.00, (float) $result['penalty_applied'], 'Penalty applied.');
    assertNear(120.00, (float) $result['interest_applied'], 'Interest applied.');
    assertNear(2000.00, (float) $result['principal_applied'], 'Principal applied.');
    assertNear(0.00, (float) $result['excess'], 'Excess.');

    $payments = $paymentService->payments($loanId);
    assertSameValue(1, count($payments), 'Ledger entry count.');
    assertNear(2120.00, (float) $payments[0]['amount_paid'], 'Ledger amount.');
    assertSameValue('First monthly payment', $payments[0]['remarks'], 'Ledger remarks.');

    $loan = $loanService->find($loanId);
    assertSameValue('Active', $loan['loan_status'], 'Loan must remain Active.');

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LOAN PAYMENT UI INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Payment: ₱2,120.00                  ✓" . PHP_EOL;
    echo "Interest applied: ₱120.00           ✓" . PHP_EOL;
    echo "Principal applied: ₱2,000.00        ✓" . PHP_EOL;
    echo "Ledger entry persisted               ✓" . PHP_EOL;
    echo "Loan remains Active                  ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    if ($loanId !== null) {
        $del = $pdo->prepare(
            'DELETE lpa
             FROM loan_payment_allocations lpa
             INNER JOIN loan_payments lp ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $del->execute(['loan_id' => $loanId]);

        $del = $pdo->prepare('DELETE FROM loan_payments WHERE loan_id = :loan_id');
        $del->execute(['loan_id' => $loanId]);

        $del = $pdo->prepare(
            "DELETE FROM activity_logs WHERE subject_type = 'Loan' AND subject_id = :loan_id"
        );
        $del->execute(['loan_id' => $loanId]);

        $del = $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id');
        $del->execute(['loan_id' => $loanId]);

        $del = $pdo->prepare('DELETE FROM loans WHERE id = :loan_id');
        $del->execute(['loan_id' => $loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
