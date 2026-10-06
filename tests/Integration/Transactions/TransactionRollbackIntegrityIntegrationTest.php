<?php

declare(strict_types=1);

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

function assertThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    throw new RuntimeException(
        $message . ' Expected an exception.'
    );
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new RuntimeException('An active user and member are required.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$activityService = new ActivityLogService(
    new ActivityLogRepository($database)
);
$amortization = new AmortizationService();
$session = new Session();
$userRepository = new UserRepository($database);

$loanService = new LoanService(
    repository: $loanRepository,
    ledger: new App\Features\Ledger\Services\LedgerService(
        new App\Features\Ledger\Repositories\JournalVoucherRepository($database)
    ),
    amortization: $amortization,
    activityLog: $activityService,
    session: $session,
    users: $userRepository,
);

$loanId = null;

try {
    echo "================================================\n";
    echo "ACES TRANSACTION ROLLBACK INTEGRITY INTEGRATION TEST\n";
    echo "================================================\n";

    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: '2026-09-20',
    ));

    $loanService->submit($loanId);
    $loanService->approve($loanId);

    $before = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        $before['application_status'],
        'Loan must be Approved before rollback test.',
    );

    assertSameValue(
        null,
        $before['loan_status'],
        'Loan status must be null before rollback test.',
    );

    $schedule = [
        [
            'period' => 1,
            'due_date' => '2026-10-20',
            'principal' => 2000.00,
            'interest' => 120.00,
            'rem_principal' => 4000.00,
            'rem_interest' => 240.00,
            'rem_penalty' => 0.00,
            'orig_penalty' => 0.00,
            'status' => 'Pending',
            'remarks' => null,
        ],
        [
            // Deliberately duplicate period 1.
            // The first row is inserted, then the unique constraint must fail
            // on this row after the loan has already been changed to Active.
            'period' => 1,
            'due_date' => '2026-11-20',
            'principal' => 2000.00,
            'interest' => 120.00,
            'rem_principal' => 2000.00,
            'rem_interest' => 120.00,
            'rem_penalty' => 0.00,
            'orig_penalty' => 0.00,
            'status' => 'Pending',
            'remarks' => null,
        ],
    ];

    assertThrows(
        fn() => $loanRepository->releaseWithSchedule(
            id: $loanId,
            userId: $userId,
            releasedAt: '2026-09-20 10:00:00',
            releaseDate: '2026-09-20',
            schedule: $schedule,
        ),
        'Failed loan release must roll back all database writes.',
    );

    $after = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        $after['application_status'],
        'Rollback must preserve the Approved application status.',
    );

    assertSameValue(
        null,
        $after['loan_status'],
        'Rollback must restore the pre-release loan status.',
    );

    assertSameValue(
        null,
        $after['released_at'],
        'Rollback must clear the release timestamp.',
    );

    assertSameValue(
        null,
        $after['released_by'],
        'Rollback must clear the release actor.',
    );

    assertSameValue(
        null,
        $after['release_date'],
        'Rollback must clear the release date.',
    );

    $amortizationCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM loan_amortizations WHERE loan_id = ' . $loanId
    )->fetchColumn();

    assertSameValue(
        0,
        $amortizationCount,
        'Rollback must remove every partially inserted amortization row.',
    );

    echo "Partial loan release failure      → rolled back ✓\n";
    echo "Loan status restored              → Approved / null ✓\n";
    echo "Release metadata restored         → unchanged ✓\n";
    echo "Amortization rows after failure   → 0 ✓\n";

    echo "================================================\n";
    echo "ACES TRANSACTION ROLLBACK INTEGRITY INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    if ($loanId !== null) {
        $statement = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan'
               AND subject_id = :loan_id"
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
