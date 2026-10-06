<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Authentication\Repositories\UserRepository;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
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
        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertThrows(callable $callback, string $needle, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($needle !== '' && !str_contains($exception->getMessage(), $needle)) {
            throw new RuntimeException(sprintf(
                '%s Unexpected exception: %s',
                $message,
                $exception->getMessage(),
            ));
        }

        return;
    }

    throw new RuntimeException(sprintf(
        '%s Expected an exception containing "%s".',
        $message,
        $needle,
    ));
}

function paymentToken(string $seed): string
{
    return substr(hash('sha256', $seed . '-' . bin2hex(random_bytes(8))), 0, 40);
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
$paymentRepository = new LoanPaymentRepository($database);
$activityService = new ActivityLogService(new ActivityLogRepository($database));
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$amortization = new AmortizationService();
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

$paymentService = new PaymentService(
    $paymentRepository,
    $ledgerService,
    $ledgerRepository,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
    $database,
);

$loanId = null;
$triggerName = 'aces_test_force_payment_accounting_failure';

try {
    echo "================================================\n";
    echo "ACES PAYMENT TRANSACTION ROLLBACK INTEGRATION TEST\n";
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
    $loanService->release($loanId, '2026-09-20');

    $loanBefore = $loanService->find($loanId);
    if ($loanBefore === null) {
        throw new RuntimeException('Temporary loan could not be reloaded.');
    }

    $amortizationsBefore = $paymentRepository->amortizations($loanId);
    $paymentsBefore = count($paymentRepository->paymentsForLoan($loanId));

    $voucherCountBeforeStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM journal_vouchers
         WHERE source_type = 'LoanPayment' AND source_id IN (
             SELECT id FROM loan_payments WHERE loan_id = :loan_id
         )"
    );
    $voucherCountBeforeStatement->execute(['loan_id' => $loanId]);
    $paymentVoucherCountBefore = (int) $voucherCountBeforeStatement->fetchColumn();

    $activityCountBeforeStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM activity_logs
         WHERE subject_type = 'Loan' AND subject_id = :loan_id
           AND action = 'LOAN_PAYMENT_APPLIED'"
    );
    $activityCountBeforeStatement->execute(['loan_id' => $loanId]);
    $paymentActivityCountBefore = (int) $activityCountBeforeStatement->fetchColumn();

    $pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
    $pdo->exec(
        "CREATE TRIGGER `{$triggerName}`
         BEFORE INSERT ON journal_vouchers
         FOR EACH ROW
         SIGNAL SQLSTATE '45000'
         SET MESSAGE_TEXT = 'ACES TEST: forced payment accounting failure'"
    );

    assertThrows(
        fn() => $paymentService->apply(
            loanId: $loanId,
            amountPaid: 1000.00,
            remarks: 'Payment transaction rollback QA',
            idempotencyKey: paymentToken('payment-rollback'),
        ),
        'ACES TEST: forced payment accounting failure',
        'Forced accounting failure must abort the payment transaction.',
    );

    echo "Accounting failure                → transaction aborted ✓\n";

    $paymentsAfter = count($paymentRepository->paymentsForLoan($loanId));
    assertSameValue(
        $paymentsBefore,
        $paymentsAfter,
        'Payment record count must be restored after accounting failure.',
    );
    echo "Payment record                   → rolled back ✓\n";

    $allocationCountStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM loan_payment_allocations AS lpa
         INNER JOIN loan_payments AS lp ON lp.id = lpa.payment_id
         WHERE lp.loan_id = :loan_id"
    );
    $allocationCountStatement->execute(['loan_id' => $loanId]);
    $allocationCountAfter = (int) $allocationCountStatement->fetchColumn();

    $allocationCountBeforeStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM loan_payment_allocations AS lpa
         INNER JOIN loan_payments AS lp ON lp.id = lpa.payment_id
         WHERE lp.loan_id = :loan_id"
    );
    $allocationCountBeforeStatement->execute(['loan_id' => $loanId]);
    $allocationCountBefore = (int) $allocationCountBeforeStatement->fetchColumn();

    assertSameValue(
        $allocationCountBefore,
        $allocationCountAfter,
        'Payment allocations must be restored after accounting failure.',
    );
    echo "Payment allocations              → rolled back ✓\n";

    $amortizationsAfter = $paymentRepository->amortizations($loanId);
    assertSameValue(
        $amortizationsBefore,
        $amortizationsAfter,
        'Amortization balances and statuses must be restored after accounting failure.',
    );
    echo "Amortization changes             → rolled back ✓\n";

    $voucherCountAfterStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM journal_vouchers
         WHERE source_type = 'LoanPayment'
           AND source_id IN (
               SELECT id FROM loan_payments WHERE loan_id = :loan_id
           )"
    );
    $voucherCountAfterStatement->execute(['loan_id' => $loanId]);
    $paymentVoucherCountAfter = (int) $voucherCountAfterStatement->fetchColumn();

    assertSameValue(
        $paymentVoucherCountBefore,
        $paymentVoucherCountAfter,
        'Payment journal vouchers must not survive an accounting failure.',
    );
    echo "Payment journal voucher           → rolled back ✓\n";

    $activityCountAfterStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM activity_logs
         WHERE subject_type = 'Loan' AND subject_id = :loan_id
           AND action = 'LOAN_PAYMENT_APPLIED'"
    );
    $activityCountAfterStatement->execute(['loan_id' => $loanId]);
    $paymentActivityCountAfter = (int) $activityCountAfterStatement->fetchColumn();

    assertSameValue(
        $paymentActivityCountBefore,
        $paymentActivityCountAfter,
        'Payment activity log must not be recorded after an accounting failure.',
    );
    echo "Payment activity log              → rolled back ✓\n";

    $loanAfter = $loanService->find($loanId);
    assertSameValue(
        $loanBefore['loan_status'],
        $loanAfter['loan_status'],
        'Loan status must remain unchanged after accounting failure.',
    );
    echo "Loan status                       → unchanged ✓\n";

    echo "================================================\n";
    echo "ACES PAYMENT TRANSACTION ROLLBACK INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
    } catch (Throwable $exception) {
        // Preserve the original test failure if cleanup itself cannot drop the test trigger.
    }

    if ($loanId !== null) {
        $statement = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE source_type = 'LoanPayment'
               AND source_id IN (
                   SELECT id FROM loan_payments WHERE loan_id = :loan_id
               )"
        );
        $statement->execute(['loan_id' => $loanId]);
        $paymentVoucherIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

        if ($paymentVoucherIds !== []) {
            $placeholders = implode(',', array_fill(0, count($paymentVoucherIds), '?'));
            $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ({$placeholders})");
            $statement->execute($paymentVoucherIds);
        }

        $statement = $pdo->prepare(
            "DELETE FROM loan_payment_allocations
             WHERE payment_id IN (
                 SELECT id FROM loan_payments WHERE loan_id = :loan_id
             )"
        );
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare('DELETE FROM loan_payments WHERE loan_id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare(
            "DELETE FROM activity_logs
             WHERE subject_type = 'Loan' AND subject_id = :loan_id"
        );
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);

        $statement = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE source_type = 'LoanRelease' AND source_id = :loan_id"
        );
        $statement->execute(['loan_id' => $loanId]);
        $releaseVoucherIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

        if ($releaseVoucherIds !== []) {
            $placeholders = implode(',', array_fill(0, count($releaseVoucherIds), '?'));
            $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ({$placeholders})");
            $statement->execute($releaseVoucherIds);
        }

        $statement = $pdo->prepare('DELETE FROM loans WHERE id = :loan_id');
        $statement->execute(['loan_id' => $loanId]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
