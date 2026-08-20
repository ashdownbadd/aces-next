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
            startDate: '2026-08-19',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-08-20');

    $payment = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'Reversal test payment',
    );
    $paymentId = (int) $payment['payment_id'];

    $paymentService->reverse(
        paymentId: $paymentId,
        reason: 'Entered under wrong transaction.',
    );

    $rows = $pdo->prepare(
        'SELECT principal, interest, rem_principal, rem_interest, rem_penalty, status
         FROM loan_amortizations
         WHERE loan_id = :loan_id
         ORDER BY period ASC'
    );
    $rows->execute(['loan_id' => $loanId]);
    $schedule = $rows->fetchAll(PDO::FETCH_ASSOC);

    assertNear(
        (float) $schedule[0]['principal'],
        (float) $schedule[0]['rem_principal'],
        'Period 1 principal must be restored.'
    );

    assertNear(
        (float) $schedule[0]['interest'],
        (float) $schedule[0]['rem_interest'],
        'Period 1 interest must be restored.'
    );

    assertSameValue(
        'Pending',
        $schedule[0]['status'],
        'Period 1 should return to Pending.'
    );

    $paymentCheck = $pdo->prepare(
        'SELECT amount_paid, reversed_at, reversed_by, reversal_reason
         FROM loan_payments
         WHERE id = :id'
    );
    $paymentCheck->execute(['id' => $paymentId]);
    $storedPayment = $paymentCheck->fetch(PDO::FETCH_ASSOC);

    assertNear(
        2120.00,
        (float) $storedPayment['amount_paid'],
        'Original payment amount must remain unchanged.'
    );

    if (empty($storedPayment['reversed_at'])) {
        throw new RuntimeException('Reversed timestamp was not recorded.');
    }

    assertSameValue(
        $userId,
        (int) $storedPayment['reversed_by'],
        'Reversed_by must be recorded.'
    );

    assertSameValue(
        'Entered under wrong transaction.',
        $storedPayment['reversal_reason'],
        'Reversal reason must persist.'
    );

    $loan = $loanService->find($loanId);
    assertSameValue('Active', $loan['loan_status'], 'Loan must remain Active.');

    $logs = $pdo->prepare(
        "SELECT action FROM activity_logs
         WHERE subject_type = 'Loan' AND subject_id = :loan_id
         ORDER BY id ASC"
    );
    $logs->execute(['loan_id' => $loanId]);
    $actions = array_column($logs->fetchAll(PDO::FETCH_ASSOC), 'action');

    if (!in_array('LOAN_PAYMENT_REVERSED', $actions, true)) {
        throw new RuntimeException('LOAN_PAYMENT_REVERSED log is missing.');
    }

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LOAN PAYMENT REVERSAL INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Payment ID: #{$paymentId}" . PHP_EOL;
    echo "Original payment preserved              ✓" . PHP_EOL;
    echo "Reversal metadata persisted             ✓" . PHP_EOL;
    echo "Amortization balances restored          ✓" . PHP_EOL;
    echo "Payment remains in ledger               ✓" . PHP_EOL;
    echo "LOAN_PAYMENT_REVERSED logged            ✓" . PHP_EOL;
    echo "Loan remains Active                     ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    if ($loanId !== null) {
        $q=$pdo->prepare(
            'DELETE lpa FROM loan_payment_allocations lpa
             INNER JOIN loan_payments lp ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare('DELETE FROM loan_payments WHERE loan_id = :loan_id');
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare(
            "DELETE FROM activity_logs WHERE subject_type='Loan' AND subject_id=:loan_id"
        );
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id');
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare('DELETE FROM loans WHERE id = :loan_id');
        $q->execute(['loan_id'=>$loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
