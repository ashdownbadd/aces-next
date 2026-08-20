<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
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

try {
    $loanId = $loanService->create(
        new \App\Features\Loans\DTOs\LoanData(
            memberId: $memberId,
            loanType: LoanType::SALARY_LOAN,
            collateral: CollateralType::POST_DATED_CHECK,
            principalAmount: 6000.00,
            interestRate: 2.00,
            amortizationType: AmortizationType::STRAIGHT_LINE,
            paymentFrequency: null,
            termsMonths: 3,
            startDate: '2026-06-20',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-06-20');

    $paymentRepository->refreshOverdueStatus(
        $loanId,
        '2026-08-20',
    );

    $rows = $paymentRepository->amortizations($loanId);

    assertSameValue(
        'Overdue',
        $rows[0]['status'],
        'Period 1 should be Overdue.'
    );

    assertSameValue(
        'Pending',
        $rows[1]['status'],
        'Period 2 is due today and should remain Pending.'
    );

    assertSameValue(
        'Pending',
        $rows[2]['status'],
        'Period 3 should remain Pending.'
    );

    assertNear(
        63.60,
        (float) $rows[0]['rem_penalty'],
        'Period 1 penalty after one month.'
    );

    assertNear(
        0.00,
        (float) $rows[1]['rem_penalty'],
        'Period 2 should have no penalty while due today.'
    );

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES OVERDUE + PENALTY INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Period 1 → Overdue                  ✓" . PHP_EOL;
    echo "Period 2 → Pending                  ✓" . PHP_EOL;
    echo "Period 3 → Pending                  ✓" . PHP_EOL;
    echo "Period 1 penalty: ₱63.60            ✓" . PHP_EOL;
    echo "Period 2 penalty: ₱0.00             ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    if ($loanId !== null) {
        $q=$pdo->prepare(
            'DELETE lpa
             FROM loan_payment_allocations lpa
             INNER JOIN loan_payments lp ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $q->execute(['loan_id'=>$loanId]);

        $q = $pdo->prepare(
            'DELETE FROM loan_payments
             WHERE loan_id = :loan_id'
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

        $q=$pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type='Loan'
               AND subject_id=:loan_id"
        );
        $q->execute(['loan_id'=>$loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
