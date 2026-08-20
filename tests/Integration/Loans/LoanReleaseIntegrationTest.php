<?php

declare(strict_types=1);

/**
 * ACES Loan Release — real MySQL integration test.
 *
 * Run:
 *   php tests/Integration/Loans/LoanReleaseIntegrationTest.php
 *
 * Verifies:
 *   Approved -> Active
 *   release metadata
 *   amortization persistence
 *   release audit events
 *   cleanup
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanApplicationStatus;
use App\Features\Loans\Domain\LoanStatus;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
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
    throw new RuntimeException(
        'Integration test requires an existing user and member.'
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$loanService = new LoanService(
    $loanRepository,
    new AmortizationService(),
    $activityService,
    new Session(),
);

$loanId = null;

try {
    $loanId = $loanService->create(
        new LoanData(
            memberId: $memberId,
            loanType: LoanType::PRODUCTIVITY_LOAN,
            collateral: CollateralType::POST_DATED_CHECK,
            principalAmount: 12000.00,
            interestRate: 2.00,
            amortizationType: AmortizationType::STRAIGHT_LINE,
            paymentFrequency: null,
            termsMonths: 6,
            startDate: '2026-08-19',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);

    $approved = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        $approved['application_status'],
        'Release test: loan must be Approved before release.'
    );

    $releaseDate = '2026-08-20';

    $loanService->release(
        $loanId,
        $releaseDate,
    );

    $released = $loanService->find($loanId);

    assertSameValue(
        LoanStatus::ACTIVE,
        $released['loan_status'],
        'Release test: loan must become Active.'
    );

    assertSameValue(
        $releaseDate,
        $released['release_date'],
        'Release test: release date must persist.'
    );

    assertSameValue(
        $userId,
        (int) $released['released_by'],
        'Release test: released_by must be the acting user.'
    );

    if (empty($released['released_at'])) {
        throw new RuntimeException(
            'Release test: released_at must be recorded.'
        );
    }

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
        6,
        count($schedule),
        'Release test: exactly 6 amortization rows must be persisted.'
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
        12000.00,
        $principalTotal,
        'Release test: schedule principal total.'
    );

    assertNear(
        1440.00,
        $interestTotal,
        'Release test: schedule interest total.'
    );

    foreach ($schedule as $index => $row) {
        assertSameValue(
            $index + 1,
            (int) $row['period'],
            'Release test: schedule periods must be sequential.'
        );

        assertNear(
            (float) $row['principal'],
            (float) $row['rem_principal'],
            'Release test: remaining principal must start at scheduled principal.'
        );

        assertNear(
            (float) $row['interest'],
            (float) $row['rem_interest'],
            'Release test: remaining interest must start at scheduled interest.'
        );

        assertNear(
            0.00,
            (float) $row['rem_penalty'],
            'Release test: initial remaining penalty must be zero for future due dates.'
        );

        assertNear(
            0.00,
            (float) $row['orig_penalty'],
            'Release test: initial original penalty must be zero for future due dates.'
        );
    }

    $logStatement = $pdo->prepare(
        "SELECT action
         FROM activity_logs
         WHERE subject_type = 'Loan'
           AND subject_id = :loan_id
         ORDER BY id ASC"
    );
    $logStatement->execute(['loan_id' => $loanId]);
    $actions = array_column(
        $logStatement->fetchAll(PDO::FETCH_ASSOC),
        'action'
    );

    foreach ([
        'LOAN_CREATED',
        'LOAN_SUBMITTED',
        'LOAN_APPROVED',
        'LOAN_AMORTIZATION_GENERATED',
        'LOAN_RELEASED',
    ] as $expectedAction) {
        if (!in_array($expectedAction, $actions, true)) {
            throw new RuntimeException(
                "Release test: missing {$expectedAction} activity log."
            );
        }
    }

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LOAN RELEASE INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Approved → Active                     ✓" . PHP_EOL;
    echo "Release date persisted                ✓" . PHP_EOL;
    echo "Release metadata persisted            ✓" . PHP_EOL;
    echo "6 amortization rows persisted         ✓" . PHP_EOL;
    echo "Principal total: ₱12,000.00            ✓" . PHP_EOL;
    echo "Interest total: ₱1,440.00              ✓" . PHP_EOL;
    echo "Release activity logs                 ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
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

        $deleteLogs = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan'
               AND subject_id = :loan_id"
        );
        $deleteLogs->execute(['loan_id' => $loanId]);

        $deleteSchedule = $pdo->prepare(
            'DELETE FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );
        $deleteSchedule->execute(['loan_id' => $loanId]);

        $deleteLoan = $pdo->prepare(
            'DELETE FROM loans
             WHERE id = :loan_id'
        );
        $deleteLoan->execute(['loan_id' => $loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
