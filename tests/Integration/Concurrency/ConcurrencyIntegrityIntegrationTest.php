<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Features\Authentication\Repositories\UserRepository;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException(sprintf(
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
    } catch (\Throwable) {
        return;
    }

    throw new \RuntimeException($message);
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

// Initialize the session before any output so Session can safely configure it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();
$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new \RuntimeException('An active user and member are required.');
}

$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);

$loanService = new LoanService(
    repository: $loanRepository,
    ledger: $ledgerService,
    amortization: new AmortizationService(),
    activityLog: new ActivityLogService(new ActivityLogRepository($database)),
    session: new Session(),
    users: new UserRepository($database),
);

$loanId = null;
$lockPdo = null;

try {
    echo "================================================\n";
    echo "ACES CONCURRENCY INTEGRITY INTEGRATION TEST\n";
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
    if ($before === null) {
        throw new \RuntimeException('Temporary loan could not be loaded.');
    }

    // Represent a competing request that has acquired the same loan row lock.
    // Use a separate Database instance so the competing transaction has a
    // genuinely independent MySQL connection.
    $lockDatabase = new Database($config);
    $lockPdo = $lockDatabase->connection();
    $lockPdo->exec('SET SESSION innodb_lock_wait_timeout = 1');
    $lockPdo->beginTransaction();

    $lock = $lockPdo->prepare(
        'SELECT id, application_status, loan_status
             FROM loans
             WHERE id = :id
             FOR UPDATE'
    );
    $lock->execute(['id' => $loanId]);
    $locked = $lock->fetch(\PDO::FETCH_ASSOC);

    if ($locked === false) {
        throw new \RuntimeException('The temporary loan could not be locked.');
    }

    assertSameValue('Approved', $locked['application_status'], 'Locked loan application status.');
    assertSameValue(null, $locked['loan_status'], 'Locked loan financial status.');

    // The production release path performs an UPDATE on this row. The
    // competing transaction must therefore force the real operation to wait
    // and fail rather than allowing two releases to succeed.
    $pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');

    assertThrows(
        static function () use ($loanService, $loanId): void {
            $loanService->release($loanId, '2026-09-20');
        },
        'A competing transaction must prevent a second release from succeeding.',
    );

    echo "Competing loan release blocked      → serialized ✓\n";

    // Release the competing lock and verify the failed operation left no
    // partial lifecycle or amortization state behind.
    $lockPdo->rollBack();
    $lockPdo = null;

    $after = $loanService->find($loanId);
    if ($after === null) {
        throw new \RuntimeException('Temporary loan disappeared after concurrency test.');
    }

    assertSameValue($before['application_status'], $after['application_status'], 'Application status after contention.');
    assertSameValue($before['loan_status'], $after['loan_status'], 'Loan status after contention.');
    assertSameValue($before['released_at'], $after['released_at'], 'Release timestamp after contention.');
    assertSameValue($before['released_by'], $after['released_by'], 'Release actor after contention.');
    assertSameValue($before['release_date'], $after['release_date'], 'Release date after contention.');

    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM loan_amortizations WHERE loan_id = :loan_id'
    );
    $statement->execute(['loan_id' => $loanId]);
    assertSameValue(0, (int) $statement->fetchColumn(), 'Failed concurrent release must not create amortization rows.');

    echo "Loan state after contention        → unchanged ✓\n";
    echo "Amortization state after contention → unchanged ✓\n";

    // Payment idempotency is additionally protected by a database-level
    // unique constraint, preventing two requests from persisting the same
    // idempotency token as separate payments.
    $indexes = $pdo->query('SHOW INDEX FROM loan_payments')->fetchAll(\PDO::FETCH_ASSOC);
    $hasIdempotencyUniqueIndex = false;

    foreach ($indexes as $index) {
        if (
            (string) ($index['Key_name'] ?? '') === 'uq_loan_payments_idempotency'
            && (int) ($index['Non_unique'] ?? 1) === 0
            && (string) ($index['Column_name'] ?? '') === 'idempotency_key'
        ) {
            $hasIdempotencyUniqueIndex = true;
            break;
        }
    }

    if (! $hasIdempotencyUniqueIndex) {
        throw new \RuntimeException(
            'The loan_payments idempotency_key unique index is missing.'
        );
    }

    echo "Payment idempotency uniqueness      → enforced ✓\n";
    echo "================================================\n";
    echo "ACES CONCURRENCY INTEGRITY INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    if ($lockPdo !== null && $lockPdo->inTransaction()) {
        $lockPdo->rollBack();
    }

    if ($loanId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM activity_logs WHERE subject_type = \'Loan\' AND subject_id = :loan_id'
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
