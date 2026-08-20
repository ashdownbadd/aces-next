<?php

declare(strict_types=1);

/**
 * ACES Loan Lifecycle — real MySQL integration test.
 *
 * Run from the ACES project root:
 *   php tests/Integration/Loans/LoanLifecycleIntegrationTest.php
 *
 * This creates one temporary loan, drives it through the complete lifecycle,
 * verifies the database state, then removes every record created by the test.
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanApplicationStatus;
use App\Features\Loans\Domain\LoanStatus;
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

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
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

$userId = (int) $pdo
    ->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')
    ->fetchColumn();

$memberId = (int) $pdo
    ->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')
    ->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException('Integration test requires an existing user.');
}

if ($memberId <= 0) {
    throw new RuntimeException('Integration test requires an existing member.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$amortizationService = new AmortizationService();
$session = new Session();

$loanService = new LoanService(
    repository: $loanRepository,
    amortization: $amortizationService,
    activityLog: $activityService,
    session: $session,
);

$paymentService = new PaymentService(
    repository: $paymentRepository,
    loanRepository: $loanRepository,
    amortization: $amortizationService,
    activityLog: $activityService,
    session: $session,
);

$loanId = null;

try {
    /*
    |--------------------------------------------------------------------------
    | Stage 1 — Create
    |--------------------------------------------------------------------------
    */

    $loanData = new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: date('Y-m-d'),
    );

    $loanId = $loanService->create($loanData);

    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::PENDING,
        $loan['application_status'],
        'Create: application status must be Pending.',
    );

    assertSameValue(
        null,
        $loan['loan_status'],
        'Create: loan status must be null.',
    );

    /*
    |--------------------------------------------------------------------------
    | Stage 2 — Submit
    |--------------------------------------------------------------------------
    */

    $loanService->submit($loanId);
    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::UNDER_REVIEW,
        $loan['application_status'],
        'Submit: application status must be Under Review.',
    );

    assertTrueValue(
        !empty($loan['reviewed_at']),
        'Submit: reviewed_at must be recorded.',
    );

    assertSameValue(
        $userId,
        (int) $loan['reviewed_by'],
        'Submit: reviewed_by must be the acting user.',
    );

    /*
    |--------------------------------------------------------------------------
    | Stage 3 — Review
    |--------------------------------------------------------------------------
    */

    $loanService->review($loanId);
    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::UNDER_REVIEW,
        $loan['application_status'],
        'Review: status must remain Under Review.',
    );

    /*
    |--------------------------------------------------------------------------
    | Stage 4 — Approve
    |--------------------------------------------------------------------------
    */

    $loanService->approve($loanId);
    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        $loan['application_status'],
        'Approve: application status must be Approved.',
    );

    assertTrueValue(
        !empty($loan['approved_at']),
        'Approve: approved_at must be recorded.',
    );

    assertSameValue(
        $userId,
        (int) $loan['approved_by'],
        'Approve: approved_by must be the acting user.',
    );

    /*
    |--------------------------------------------------------------------------
    | Stage 5 — Release + Amortization Persistence
    |--------------------------------------------------------------------------
    */

    $releaseDate = date('Y-m-d');
    $loanService->release($loanId, $releaseDate);

    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        $loan['application_status'],
        'Release: application status must remain Approved.',
    );

    assertSameValue(
        LoanStatus::ACTIVE,
        $loan['loan_status'],
        'Release: loan status must become Active.',
    );

    assertTrueValue(
        !empty($loan['released_at']),
        'Release: released_at must be recorded.',
    );

    assertSameValue(
        $userId,
        (int) $loan['released_by'],
        'Release: released_by must be the acting user.',
    );

    assertSameValue(
        $releaseDate,
        $loan['release_date'],
        'Release: release_date must match the supplied release date.',
    );

    $scheduleStatement = $pdo->prepare(
        'SELECT
            period,
            due_date,
            principal,
            interest,
            rem_principal,
            rem_interest,
            rem_penalty,
            orig_penalty,
            status
         FROM loan_amortizations
         WHERE loan_id = :loan_id
         ORDER BY period ASC'
    );
    $scheduleStatement->execute(['loan_id' => $loanId]);
    $schedule = $scheduleStatement->fetchAll(PDO::FETCH_ASSOC);

    assertSameValue(
        3,
        count($schedule),
        'Release: exactly 3 amortization periods must be persisted.',
    );

    $principalTotal = array_sum(
        array_map(
            static fn (array $row): float => (float) $row['principal'],
            $schedule,
        )
    );

    $interestTotal = array_sum(
        array_map(
            static fn (array $row): float => (float) $row['interest'],
            $schedule,
        )
    );

    assertNear(
        6000.00,
        $principalTotal,
        'Release: total scheduled principal.',
    );

    assertNear(
        360.00,
        $interestTotal,
        'Release: total scheduled interest.',
    );

    foreach ($schedule as $index => $row) {
        assertSameValue(
            $index + 1,
            (int) $row['period'],
            'Release: periods must be sequential.',
        );

        assertNear(
            (float) $row['principal'],
            (float) $row['rem_principal'],
            'Release: remaining principal must initially equal original principal.',
        );

        assertNear(
            (float) $row['interest'],
            (float) $row['rem_interest'],
            'Release: remaining interest must initially equal original interest.',
        );

        assertNear(
            0.00,
            (float) $row['rem_penalty'],
            'Release: new schedule must start with zero penalty.',
        );

        assertNear(
            0.00,
            (float) $row['orig_penalty'],
            'Release: new schedule must start with zero original penalty.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Stage 6 — Full Settlement
    |--------------------------------------------------------------------------
    |
    | 3 months ×:
    |   Principal = ₱2,000
    |   Interest  = ₱120
    |   Payment   = ₱2,120
    |
    | Total = ₱6,360
    |--------------------------------------------------------------------------
    */

    $paymentResult = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 6360.00,
        remarks: 'LIFECYCLE_INTEGRATION_TEST',
    );

    assertNear(
        6360.00,
        (float) $paymentResult['amount_paid'],
        'Settlement: payment amount.',
    );

    assertNear(
        0.00,
        (float) $paymentResult['excess'],
        'Settlement: excess must be zero.',
    );

    assertNear(
        0.00,
        (float) $paymentResult['penalty_applied'],
        'Settlement: no penalty should be applied.',
    );

    assertNear(
        360.00,
        (float) $paymentResult['interest_applied'],
        'Settlement: all interest must be applied.',
    );

    assertNear(
        6000.00,
        (float) $paymentResult['principal_applied'],
        'Settlement: all principal must be applied.',
    );

    /*
    |--------------------------------------------------------------------------
    | Stage 7 — Fully Paid Verification
    |--------------------------------------------------------------------------
    */

    $loan = $loanService->find($loanId);

    assertSameValue(
        LoanStatus::FULLY_PAID,
        $loan['loan_status'],
        'Settlement: loan must become Fully Paid.',
    );

    assertTrueValue(
        !empty($loan['fully_paid_at']),
        'Settlement: fully_paid_at must be recorded.',
    );

    $remainingStatement = $pdo->prepare(
        'SELECT
            COUNT(*) AS row_count,
            COALESCE(SUM(rem_principal), 0) AS rem_principal,
            COALESCE(SUM(rem_interest), 0) AS rem_interest,
            COALESCE(SUM(rem_penalty), 0) AS rem_penalty,
            SUM(
                CASE
                    WHEN status = \'Paid\' THEN 1
                    ELSE 0
                END
            ) AS paid_rows
         FROM loan_amortizations
         WHERE loan_id = :loan_id'
    );
    $remainingStatement->execute(['loan_id' => $loanId]);
    $remaining = $remainingStatement->fetch(PDO::FETCH_ASSOC);

    assertSameValue(
        3,
        (int) $remaining['row_count'],
        'Settlement: schedule row count must remain 3.',
    );

    assertNear(
        0.00,
        (float) $remaining['rem_principal'],
        'Settlement: remaining principal must be zero.',
    );

    assertNear(
        0.00,
        (float) $remaining['rem_interest'],
        'Settlement: remaining interest must be zero.',
    );

    assertNear(
        0.00,
        (float) $remaining['rem_penalty'],
        'Settlement: remaining penalty must be zero.',
    );

    assertSameValue(
        3,
        (int) $remaining['paid_rows'],
        'Settlement: every amortization period must be Paid.',
    );

    /*
    |--------------------------------------------------------------------------
    | Stage 8 — Activity Logs
    |--------------------------------------------------------------------------
    */

    $logStatement = $pdo->prepare(
        'SELECT action
         FROM activity_logs
         WHERE subject_type = \'Loan\'
           AND subject_id = :loan_id
         ORDER BY id ASC'
    );
    $logStatement->execute(['loan_id' => $loanId]);
    $actions = array_column(
        $logStatement->fetchAll(PDO::FETCH_ASSOC),
        'action',
    );

    foreach ([
        'LOAN_CREATED',
        'LOAN_SUBMITTED',
        'LOAN_REVIEWED',
        'LOAN_APPROVED',
        'LOAN_AMORTIZATION_GENERATED',
        'LOAN_RELEASED',
        'LOAN_PAYMENT_APPLIED',
        'LOAN_FULLY_PAID',
    ] as $expectedAction) {
        assertTrueValue(
            in_array($expectedAction, $actions, true),
            "Activity logs: missing {$expectedAction}.",
        );
    }

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LOAN LIFECYCLE INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: {$loanId}" . PHP_EOL;
    echo "Create       → Pending       ✓" . PHP_EOL;
    echo "Submit       → Under Review  ✓" . PHP_EOL;
    echo "Review       → Under Review  ✓" . PHP_EOL;
    echo "Approve      → Approved      ✓" . PHP_EOL;
    echo "Release      → Active        ✓" . PHP_EOL;
    echo "Amortization → 3 periods     ✓" . PHP_EOL;
    echo "Settlement   → ₱6,360.00      ✓" . PHP_EOL;
    echo "Final status → Fully Paid    ✓" . PHP_EOL;
    echo "Activity logs → Complete      ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    if ($loanId !== null) {
        $deleteAllocations = $pdo->prepare(
            'DELETE lpa
             FROM loan_payment_allocations AS lpa
             INNER JOIN loan_payments AS lp
                 ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $deleteAllocations->execute(['loan_id' => $loanId]);

        $deletePayments = $pdo->prepare(
            'DELETE FROM loan_payments
             WHERE loan_id = :loan_id'
        );
        $deletePayments->execute(['loan_id' => $loanId]);

        $deleteActivity = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan'
               AND subject_id = :loan_id"
        );
        $deleteActivity->execute(['loan_id' => $loanId]);

        $deleteAmortizations = $pdo->prepare(
            'DELETE FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );
        $deleteAmortizations->execute(['loan_id' => $loanId]);

        $deleteLoan = $pdo->prepare(
            'DELETE FROM loans
             WHERE id = :loan_id'
        );
        $deleteLoan->execute(['loan_id' => $loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
