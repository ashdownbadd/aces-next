<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
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

    $result = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 2120.00,
        remarks: 'Installment-first QA',
    );

    assertNear(
        0.00,
        (float) $result['penalty_applied'],
        'First installment penalty applied.'
    );

    assertNear(
        120.00,
        (float) $result['interest_applied'],
        'First installment interest applied.'
    );

    assertNear(
        2000.00,
        (float) $result['principal_applied'],
        'First installment principal applied.'
    );

    assertNear(
        0.00,
        (float) $result['excess'],
        'First installment excess.'
    );

    $rows = $paymentRepository->amortizations($loanId);

    assertSameValue(
        3,
        count($rows),
        'Expected three amortization periods.'
    );

    assertNear(
        0.00,
        (float) $rows[0]['rem_principal'],
        'Period 1 principal should be cleared.'
    );

    assertNear(
        0.00,
        (float) $rows[0]['rem_interest'],
        'Period 1 interest should be cleared.'
    );

    assertSameValue(
        'Paid',
        $rows[0]['status'],
        'Period 1 should become Paid.'
    );

    assertNear(
        2000.00,
        (float) $rows[1]['rem_principal'],
        'Period 2 principal must remain untouched.'
    );

    assertNear(
        120.00,
        (float) $rows[1]['rem_interest'],
        'Period 2 interest must remain untouched.'
    );

    assertSameValue(
        'Pending',
        $rows[1]['status'],
        'Period 2 should remain Pending.'
    );

    assertNear(
        0.00,
        (float) $rows[0]['rem_penalty'],
        'Period 1 penalty should be zero.'
    );

    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanStatus::ACTIVE,
        $loan['loan_status'],
        'Loan should remain Active after first installment payment.'
    );

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES INSTALLMENT-FIRST PAYMENT TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Payment: ₱2,120.00                  ✓" . PHP_EOL;
    echo "Period 1 → Paid                     ✓" . PHP_EOL;
    echo "Interest applied: ₱120.00           ✓" . PHP_EOL;
    echo "Principal applied: ₱2,000.00        ✓" . PHP_EOL;
    echo "Period 2 remains Pending             ✓" . PHP_EOL;
    echo "Loan remains Active                  ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

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
