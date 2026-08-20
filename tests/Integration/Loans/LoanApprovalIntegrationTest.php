<?php

declare(strict_types=1);

/**
 * ACES Loan Approval / Rejection — real MySQL integration test.
 *
 * Run from the project root:
 *   php tests/Integration/Loans/LoanApprovalIntegrationTest.php
 *
 * Creates temporary loans, exercises Under Review → Approved and
 * Under Review → Rejected, verifies audit records, then cleans up.
 */

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanApplicationStatus;
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

$createdLoanIds = [];

try {
    $makeLoan = static function () use ($loanService, $memberId, &$createdLoanIds): int {
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
                startDate: date('Y-m-d'),
            )
        );

        $createdLoanIds[] = $loanId;

        $loanService->submit($loanId);

        return $loanId;
    };

    // APPROVAL PATH
    $approvedLoanId = $makeLoan();
    $approvedLoan = $loanService->find($approvedLoanId);

    assertSameValue(
        LoanApplicationStatus::UNDER_REVIEW,
        $approvedLoan['application_status'],
        'Approval path: application must be Under Review after submit.'
    );

    $loanService->approve($approvedLoanId);

    $approvedLoan = $loanService->find($approvedLoanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        $approvedLoan['application_status'],
        'Approval path: application must become Approved.'
    );

    assertSameValue(
        $userId,
        (int) $approvedLoan['approved_by'],
        'Approval path: approved_by must be the acting user.'
    );

    assertTrueValue(
        !empty($approvedLoan['approved_at']),
        'Approval path: approved_at must be recorded.'
    );

    // REJECTION PATH
    $rejectedLoanId = $makeLoan();

    try {
        $loanService->reject($rejectedLoanId, '');
        throw new RuntimeException(
            'Rejection path: empty reason should have thrown.'
        );
    } catch (\InvalidArgumentException) {
        // Expected.
    }

    $reason = 'Collateral documentation is incomplete.';
    $loanService->reject($rejectedLoanId, $reason);

    $rejectedLoan = $loanService->find($rejectedLoanId);

    assertSameValue(
        LoanApplicationStatus::REJECTED,
        $rejectedLoan['application_status'],
        'Rejection path: application must become Rejected.'
    );

    assertSameValue(
        $reason,
        $rejectedLoan['rejection_reason'],
        'Rejection path: rejection reason must persist.'
    );

    $logStatement = $pdo->prepare(
        "SELECT action, description
         FROM activity_logs
         WHERE subject_type = 'Loan'
           AND subject_id = :loan_id
         ORDER BY id ASC"
    );

    $logStatement->execute(['loan_id' => $approvedLoanId]);
    $approvedActions = array_column(
        $logStatement->fetchAll(PDO::FETCH_ASSOC),
        'action'
    );

    assertTrueValue(
        in_array('LOAN_CREATED', $approvedActions, true),
        'Approval path: LOAN_CREATED log missing.'
    );

    assertTrueValue(
        in_array('LOAN_SUBMITTED', $approvedActions, true),
        'Approval path: LOAN_SUBMITTED log missing.'
    );

    assertTrueValue(
        in_array('LOAN_APPROVED', $approvedActions, true),
        'Approval path: LOAN_APPROVED log missing.'
    );

    $logStatement->execute(['loan_id' => $rejectedLoanId]);
    $rejectedLogs = $logStatement->fetchAll(PDO::FETCH_ASSOC);
    $rejectedActions = array_column($rejectedLogs, 'action');

    assertTrueValue(
        in_array('LOAN_REJECTED', $rejectedActions, true),
        'Rejection path: LOAN_REJECTED log missing.'
    );

    $rejectionDescriptions = array_column($rejectedLogs, 'description');
    assertTrueValue(
        in_array(
            sprintf(
                'Loan #%d was rejected. Reason: %s',
                $rejectedLoanId,
                $reason,
            ),
            $rejectionDescriptions,
            true,
        ),
        'Rejection path: rejection reason must be included in audit description.'
    );

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LOAN APPROVAL INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Approved Loan: #{$approvedLoanId}  ✓" . PHP_EOL;
    echo "Rejected Loan: #{$rejectedLoanId}  ✓" . PHP_EOL;
    echo "Empty rejection reason blocked         ✓" . PHP_EOL;
    echo "Approval metadata persisted             ✓" . PHP_EOL;
    echo "Rejection reason persisted              ✓" . PHP_EOL;
    echo "Approval/rejection audit logs           ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    if ($createdLoanIds !== []) {
        $placeholders = implode(
            ',',
            array_fill(0, count($createdLoanIds), '?')
        );

        $deleteLogs = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan'
               AND subject_id IN ({$placeholders})"
        );
        $deleteLogs->execute($createdLoanIds);

        $deleteLoans = $pdo->prepare(
            "DELETE FROM loans
             WHERE id IN ({$placeholders})"
        );
        $deleteLoans->execute($createdLoanIds);
    }

    echo "Cleanup completed for temporary Loan IDs: "
        . implode(', ', $createdLoanIds)
        . "." . PHP_EOL;
}
