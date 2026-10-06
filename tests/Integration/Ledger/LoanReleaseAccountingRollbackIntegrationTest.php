<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Database;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Session;
use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

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
    } catch (Throwable $exception) {
        return;
    }

    throw new RuntimeException($message . ' Expected an exception.');
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
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$amortization = new AmortizationService();
$activityService = new ActivityLogService(new ActivityLogRepository($database));
$session = new Session();
$userRepository = new UserRepository($database);

$loanService = new LoanService(
    repository: $loanRepository,
    ledger: $ledgerService,
    amortization: $amortization,
    activityLog: $activityService,
    session: $session,
    users: $userRepository,
);

$loanId = null;
$triggerName = 'qa_fail_loan_release_accounting';

try {
    echo "============================================================\n";
    echo "ACES LOAN → LEDGER ACCOUNTING ROLLBACK INTEGRATION TEST\n";
    echo "============================================================\n";

    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: 'Straight-line',
        paymentFrequency: null,
        termsMonths: 3,
        startDate: '2026-09-20',
    ));

    $loanService->submit($loanId);
    $loanService->approve($loanId);

    $before = $loanService->find($loanId);
    assertSameValue(null, $before['loan_status'] ?? null, 'Approved loan must not have a financial status before release.');

    $pdo->exec(
        "CREATE TRIGGER {$triggerName}
         BEFORE INSERT ON journal_vouchers
         FOR EACH ROW
         SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Intentional QA accounting failure.'"
    );

    assertThrows(
        fn() => $loanService->release($loanId, '2026-09-20'),
        'Loan release with accounting failure must abort.',
    );

    $after = $loanService->find($loanId);
    assertSameValue(null, $after['loan_status'] ?? null, 'Loan status must roll back when release accounting fails.');
    assertSameValue(null, $after['released_at'] ?? null, 'Release timestamp must roll back when accounting fails.');
    assertSameValue(null, $after['released_by'] ?? null, 'Release actor must roll back when accounting fails.');
    assertSameValue(null, $after['release_date'] ?? null, 'Release date must roll back when accounting fails.');
    echo "Accounting failure                 → transaction aborted ✓\n";
    echo "Loan status                        → restored ✓\n";
    echo "Release metadata                   → restored ✓\n";

    $scheduleCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM loan_amortizations WHERE loan_id = ' . $loanId
    )->fetchColumn();
    assertSameValue(0, $scheduleCount, 'Amortization schedule must roll back when accounting fails.');
    echo "Amortization schedule              → rolled back ✓\n";

    $voucherStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM journal_vouchers
         WHERE source_type = 'LoanRelease'
           AND source_id = :loan_id"
    );
    $voucherStatement->execute(['loan_id' => $loanId]);
    $voucherCount = (int) $voucherStatement->fetchColumn();
    assertSameValue(0, $voucherCount, 'Loan release voucher must roll back when accounting fails.');
    echo "Release journal voucher            → rolled back ✓\n";

    echo "============================================================\n";
    echo "ACES LOAN → LEDGER ACCOUNTING ROLLBACK INTEGRATION TEST: PASS\n";
    echo "============================================================\n";
} finally {
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS {$triggerName}");
    } catch (Throwable) {
        // Best-effort cleanup so the test can still report its primary result.
    }

    if ($loanId !== null) {
        $voucherStatement = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE source_type = 'LoanRelease'
               AND source_id = :loan_id"
        );
        $voucherStatement->execute(['loan_id' => $loanId]);
        $voucherIds = array_map('intval', $voucherStatement->fetchAll(PDO::FETCH_COLUMN));

        if ($voucherIds !== []) {
            $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));
            $statement = $pdo->prepare("DELETE FROM journal_lines WHERE journal_voucher_id IN ({$placeholders})");
            $statement->execute($voucherIds);
            $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ({$placeholders})");
            $statement->execute($voucherIds);
        }

        $statement = $pdo->prepare("DELETE FROM activity_logs WHERE subject_type = 'Loan' AND subject_id = :loan_id");
        $statement->execute(['loan_id' => $loanId]);
        $statement = $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);
        $statement = $pdo->prepare('DELETE FROM loans WHERE id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
