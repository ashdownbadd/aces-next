<?php

declare(strict_types=1);

/**
 * ACES Payment Waterfall — real MySQL integration test.
 *
 * IMPORTANT:
 * - Run this from the ACES project root.
 * - It writes temporary records to the configured database.
 * - It cleans up every record it creates.
 * - It does NOT modify production PHP source files.
 *
 * Run:
 *   php tests/Integration/Loans/PaymentWaterfallIntegrationTest.php
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\PaymentService;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

function assertNear(
    float $expected,
    float $actual,
    string $message,
): void {
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(
            sprintf(
                "%s Expected %.2f, got %.2f.",
                $message,
                $expected,
                $actual,
            )
        );
    }
}

function assertSameValue(
    mixed $expected,
    mixed $actual,
    string $message,
): void {
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

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');

$database = new Database($config);
$pdo = $database->connection();

/*
|--------------------------------------------------------------------------
| Find valid FK parents from the real database.
|--------------------------------------------------------------------------
*/

$userId = (int) $pdo
    ->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')
    ->fetchColumn();

$memberId = (int) $pdo
    ->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')
    ->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException(
        'Integration test requires at least one existing user.'
    );
}

if ($memberId <= 0) {
    throw new RuntimeException(
        'Integration test requires at least one existing member.'
    );
}

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

$_SESSION = [];
session_start();
$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$amortizationService = new AmortizationService();
$session = new Session();

$paymentService = new PaymentService(
    repository: $paymentRepository,
    loanRepository: $loanRepository,
    amortization: $amortizationService,
    activityLog: $activityService,
    session: $session,
);

$loanId = null;
$paymentIds = [];
$activityIds = [];

try {
    /*
    |--------------------------------------------------------------------------
    | Create temporary Active loan.
    |--------------------------------------------------------------------------
    */

    $insertLoan = $pdo->prepare(
        'INSERT INTO loans (
            member_id,
            loan_type,
            collateral,
            application_status,
            loan_status,
            principal_amount,
            interest_rate,
            amortization_type,
            payment_frequency,
            terms_months,
            start_date,
            release_date,
            created_by,
            released_by,
            released_at
        ) VALUES (
            :member_id,
            :loan_type,
            :collateral,
            :application_status,
            :loan_status,
            :principal_amount,
            :interest_rate,
            :amortization_type,
            :payment_frequency,
            :terms_months,
            :start_date,
            :release_date,
            :created_by,
            :released_by,
            NOW()
        )'
    );

    $insertLoan->execute([
        'member_id' => $memberId,
        'loan_type' => 'Productivity Loan',
        'collateral' => 'Post-Dated Check',
        'application_status' => 'Approved',
        'loan_status' => 'Active',
        'principal_amount' => 6000.00,
        'interest_rate' => 2.00,
        'amortization_type' => 'Straight-line',
        'payment_frequency' => 'Monthly',
        'terms_months' => 3,
        'start_date' => '2026-05-01',
        'release_date' => '2026-05-01',
        'created_by' => $userId,
        'released_by' => $userId,
    ]);

    $loanId = (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Create three deliberately overdue rows.
    |--------------------------------------------------------------------------
    |
    | Penalties:
    |   P1 = 100
    |   P2 = 200
    |   P3 = 300
    |
    | Interest:
    |   P1 = 100
    |   P2 = 200
    |   P3 = 300
    |
    | Principal:
    |   P1 = 1000
    |   P2 = 2000
    |   P3 = 3000
    |--------------------------------------------------------------------------
    */

    $insertAmortization = $pdo->prepare(
        'INSERT INTO loan_amortizations (
            loan_id,
            period,
            due_date,
            principal,
            interest,
            rem_principal,
            rem_interest,
            rem_penalty,
            orig_penalty,
            status,
            remarks
        ) VALUES (
            :loan_id,
            :period,
            :due_date,
            :principal,
            :interest,
            :rem_principal,
            :rem_interest,
            :rem_penalty,
            :orig_penalty,
            :status,
            :remarks
        )'
    );

    $rows = [
        [1, 1000.00, 100.00, 100.00],
        [2, 2000.00, 200.00, 200.00],
        [3, 3000.00, 300.00, 300.00],
    ];

    foreach ($rows as [$period, $principal, $interest, $penalty]) {
        $insertAmortization->execute([
            'loan_id' => $loanId,
            'period' => $period,
            'due_date' => '2026-01-01',
            'principal' => $principal,
            'interest' => $interest,
            'rem_principal' => $principal,
            'rem_interest' => $interest,
            'rem_penalty' => $penalty,
            'orig_penalty' => $penalty,
            'status' => 'Overdue',
            'remarks' => 'INTEGRATION_TEST',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 1 — Penalty waterfall
    |--------------------------------------------------------------------------
    */

    $result = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 150.00,
        remarks: 'INTEGRATION_TEST_1',
    );

    $paymentIds[] = (int) $result['payment_id'];

    assertNear(150.00, (float) $result['penalty_applied'],
        'Test 1: payment must apply only to penalties.');
    assertNear(0.00, (float) $result['interest_applied'],
        'Test 1: interest must remain untouched.');
    assertNear(0.00, (float) $result['principal_applied'],
        'Test 1: principal must remain untouched.');
    assertNear(0.00, (float) $result['excess'],
        'Test 1: no excess expected.');

    $allocations = $paymentRepository->allocations($paymentIds[0]);

    assertSameValue(2, count($allocations),
        'Test 1: expected exactly two penalty allocations.');

    $amortizationIds = [];
    foreach ($pdo->query(
        'SELECT id, period
         FROM loan_amortizations
         WHERE loan_id = ' . (int) $loanId . '
         ORDER BY period ASC'
    )->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $amortizationIds[(int) $row['period']] = (int) $row['id'];
    }

    assertSameValue(
        $amortizationIds[1],
        (int) $allocations[0]['amortization_id'],
        'Test 1: first allocation must target Period 1.'
    );
    assertSameValue('Penalty', $allocations[0]['allocation_type'],
        'Test 1: first allocation type must be Penalty.');
    assertNear(100.00, (float) $allocations[0]['amount'],
        'Test 1: Period 1 penalty allocation.');

    assertSameValue(
        $amortizationIds[2],
        (int) $allocations[1]['amortization_id'],
        'Test 1: second allocation must target Period 2.'
    );
    assertSameValue('Penalty', $allocations[1]['allocation_type'],
        'Test 1: second allocation type must be Penalty.');
    assertNear(50.00, (float) $allocations[1]['amount'],
        'Test 1: Period 2 penalty allocation.');

    /*
    |--------------------------------------------------------------------------
    | TEST 2 — All penalties before interest
    |--------------------------------------------------------------------------
    |
    | Remaining penalties:
    |   P1 = 0
    |   P2 = 150
    |   P3 = 300
    |
    | Payment = 500
    |
    | Expected:
    |   Penalty = 450
    |   Interest = 50
    |   Principal = 0
    |--------------------------------------------------------------------------
    */

    $result = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 500.00,
        remarks: 'INTEGRATION_TEST_2',
    );

    $paymentIds[] = (int) $result['payment_id'];

    assertNear(450.00, (float) $result['penalty_applied'],
        'Test 2: all remaining penalties must be cleared first.');
    assertNear(50.00, (float) $result['interest_applied'],
        'Test 2: only remaining money may begin clearing interest.');
    assertNear(0.00, (float) $result['principal_applied'],
        'Test 2: principal must remain untouched.');

    $allocations = $paymentRepository->allocations($paymentIds[1]);

    $penaltyIndexes = [];
    $interestIndexes = [];

    foreach ($allocations as $index => $allocation) {
        if ($allocation['allocation_type'] === 'Penalty') {
            $penaltyIndexes[] = $index;
        }

        if ($allocation['allocation_type'] === 'Interest') {
            $interestIndexes[] = $index;
        }
    }

    if (
        $penaltyIndexes === []
        || $interestIndexes === []
        || max($penaltyIndexes) >= min($interestIndexes)
    ) {
        throw new RuntimeException(
            'Test 2: interest was allocated before all penalties were cleared.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEST 3 — All interest before principal
    |--------------------------------------------------------------------------
    |
    | Remaining interest:
    |   P1 = 50
    |   P2 = 200
    |   P3 = 300
    |
    | Total = 550
    |
    | Payment = 600
    |
    | Expected:
    |   Interest = 550
    |   Principal = 50
    |--------------------------------------------------------------------------
    */

    $result = $paymentService->apply(
        loanId: $loanId,
        amountPaid: 600.00,
        remarks: 'INTEGRATION_TEST_3',
    );

    $paymentIds[] = (int) $result['payment_id'];

    assertNear(0.00, (float) $result['penalty_applied'],
        'Test 3: no penalties should remain.');
    assertNear(550.00, (float) $result['interest_applied'],
        'Test 3: all remaining interest must be cleared.');
    assertNear(50.00, (float) $result['principal_applied'],
        'Test 3: principal begins only after all interest.');

    $allocations = $paymentRepository->allocations($paymentIds[2]);

    $interestCount = 0;
    $principalCount = 0;
    $firstPrincipalIndex = null;

    foreach ($allocations as $index => $allocation) {
        if ($allocation['allocation_type'] === 'Interest') {
            $interestCount++;
        }

        if ($allocation['allocation_type'] === 'Principal') {
            $principalCount++;
            $firstPrincipalIndex ??= $index;
        }
    }

    assertSameValue(3, $interestCount,
        'Test 3: interest must span all three periods.');
    assertSameValue(1, $principalCount,
        'Test 3: principal should begin only after all interest.');
    assertSameValue(3, (int) $firstPrincipalIndex,
        'Test 3: first principal allocation should be after the three interest allocations.');

    /*
    |--------------------------------------------------------------------------
    | DATABASE VERIFICATION
    |--------------------------------------------------------------------------
    */

    $paymentCheck = $pdo->prepare(
        'SELECT
            COUNT(*) AS payment_count,
            SUM(amount_paid) AS amount_paid,
            SUM(penalty_applied) AS penalty_applied,
            SUM(interest_applied) AS interest_applied,
            SUM(principal_applied) AS principal_applied,
            SUM(excess) AS excess
         FROM loan_payments
         WHERE loan_id = :loan_id'
    );
    $paymentCheck->execute(['loan_id' => $loanId]);
    $paymentTotals = $paymentCheck->fetch(PDO::FETCH_ASSOC);

    assertSameValue(3, (int) $paymentTotals['payment_count'],
        'Database: exactly three payment records must exist.');
    assertNear(1250.00, (float) $paymentTotals['amount_paid'],
        'Database: total recorded payment amount.');
    assertNear(600.00, (float) $paymentTotals['penalty_applied'],
        'Database: total penalty allocation.');
    assertNear(600.00, (float) $paymentTotals['interest_applied'],
        'Database: total interest allocation.');
    assertNear(50.00, (float) $paymentTotals['principal_applied'],
        'Database: total principal allocation.');
    assertNear(0.00, (float) $paymentTotals['excess'],
        'Database: total excess.');

    $amortCheck = $pdo->prepare(
        'SELECT
            period,
            rem_principal,
            rem_interest,
            rem_penalty,
            orig_penalty,
            status
         FROM loan_amortizations
         WHERE loan_id = :loan_id
         ORDER BY period ASC'
    );
    $amortCheck->execute(['loan_id' => $loanId]);
    $finalRows = $amortCheck->fetchAll(PDO::FETCH_ASSOC);

    assertNear(950.00, (float) $finalRows[0]['rem_principal'],
        'Database: Period 1 remaining principal.');
    assertNear(0.00, (float) $finalRows[0]['rem_interest'],
        'Database: Period 1 remaining interest.');
    assertNear(0.00, (float) $finalRows[0]['rem_penalty'],
        'Database: Period 1 remaining penalty.');
    assertNear(100.00, (float) $finalRows[0]['orig_penalty'],
        'Database: Period 1 original penalty must remain immutable.');

    assertNear(2000.00, (float) $finalRows[1]['rem_principal'],
        'Database: Period 2 principal must remain untouched.');
    assertNear(0.00, (float) $finalRows[1]['rem_interest'],
        'Database: Period 2 interest.');
    assertNear(0.00, (float) $finalRows[1]['rem_penalty'],
        'Database: Period 2 penalty.');
    assertNear(200.00, (float) $finalRows[1]['orig_penalty'],
        'Database: Period 2 original penalty must remain immutable.');

    assertNear(3000.00, (float) $finalRows[2]['rem_principal'],
        'Database: Period 3 principal must remain untouched.');
    assertNear(0.00, (float) $finalRows[2]['rem_interest'],
        'Database: Period 3 interest.');
    assertNear(0.00, (float) $finalRows[2]['rem_penalty'],
        'Database: Period 3 penalty.');
    assertNear(300.00, (float) $finalRows[2]['orig_penalty'],
        'Database: Period 3 original penalty must remain immutable.');

    $activityCheck = $pdo->prepare(
        "SELECT COUNT(*)
         FROM activity_logs
         WHERE subject_type = 'Loan'
           AND subject_id = :loan_id
           AND action = 'LOAN_PAYMENT_APPLIED'
           AND description LIKE '%Payment #%'
        "
    );
    $activityCheck->execute(['loan_id' => $loanId]);

    assertSameValue(3, (int) $activityCheck->fetchColumn(),
        'Activity log: one payment log must be created per payment.');

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES PAYMENT WATERFALL INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: {$loanId}" . PHP_EOL;
    echo "Payments tested: 3" . PHP_EOL;
    echo "Total paid: ₱1,250.00" . PHP_EOL;
    echo "Penalty applied: ₱600.00" . PHP_EOL;
    echo "Interest applied: ₱600.00" . PHP_EOL;
    echo "Principal applied: ₱50.00" . PHP_EOL;
    echo "Excess: ₱0.00" . PHP_EOL;
    echo "Waterfall: Penalty → Interest → Principal" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    /*
    |--------------------------------------------------------------------------
    | CLEANUP
    |--------------------------------------------------------------------------
    |
    | Delete only the temporary loan and all records belonging to it.
    | Child records are deleted first because of foreign keys.
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
               AND subject_id = :loan_id
               AND action = 'LOAN_PAYMENT_APPLIED'"
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
