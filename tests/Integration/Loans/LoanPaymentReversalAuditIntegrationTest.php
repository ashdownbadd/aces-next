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

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
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
$paymentId = null;

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

    $payment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'QA6 audit',
    );

    $paymentId = (int) $payment['payment_id'];

    $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'QA6 reversal',
    );

    $query = $pdo->prepare(
        "SELECT action, description
         FROM activity_logs
         WHERE subject_type = 'Loan'
           AND subject_id = :loan_id
           AND action IN (
               'LOAN_PAYMENT_REVERSED',
               'LOAN_REACTIVATED'
           )
         ORDER BY id ASC"
    );
    $query->execute(['loan_id' => $loanId]);

    $logs = $query->fetchAll(PDO::FETCH_ASSOC);
    $actions = array_column($logs, 'action');

    assertTrueValue(
        in_array('LOAN_PAYMENT_REVERSED', $actions, true),
        'LOAN_PAYMENT_REVERSED was not persisted.'
    );

    assertTrueValue(
        !in_array('LOAN_REACTIVATED', $actions, true),
        'LOAN_REACTIVATED must not be created for a normal payment reversal.'
    );

    echo PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "ACES PAYMENT REVERSAL AUDIT TEST: PASS" . PHP_EOL;
    echo "================================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Payment ID: #{$paymentId}" . PHP_EOL;
    echo "LOAN_PAYMENT_REVERSED persisted       ✓" . PHP_EOL;
    echo "Normal reversal does not reactivate    ✓" . PHP_EOL;
    echo "================================================" . PHP_EOL;

} finally {
    if ($loanId !== null) {
        $q = $pdo->prepare(
            'DELETE lpa
             FROM loan_payment_allocations lpa
             INNER JOIN loan_payments lp
                 ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $q->execute(['loan_id' => $loanId]);

        $q = $pdo->prepare(
            'DELETE FROM loan_payments
             WHERE loan_id = :loan_id'
        );
        $q->execute(['loan_id' => $loanId]);

        $q = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan'
               AND subject_id = :loan_id"
        );
        $q->execute(['loan_id' => $loanId]);

        $q = $pdo->prepare(
            'DELETE FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );
        $q->execute(['loan_id' => $loanId]);

        $q = $pdo->prepare(
            'DELETE FROM loans
             WHERE id = :loan_id'
        );
        $q->execute(['loan_id' => $loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
