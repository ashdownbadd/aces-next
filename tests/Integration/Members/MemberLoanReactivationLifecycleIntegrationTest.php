<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanApplicationStatus;
use App\Features\Loans\Domain\LoanStatus;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Services\MemberService;
use App\Features\Members\Services\MemberInputValidator;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

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

function assertThrows(callable $callback, string $needle, string $message): void
{
    try {
        $callback();
    } catch (\Throwable $exception) {
        if ($needle !== '' && !str_contains($exception->getMessage(), $needle)) {
            throw new \RuntimeException(sprintf(
                '%s Unexpected exception: %s',
                $message,
                $exception->getMessage(),
            ));
        }

        return;
    }

    throw new \RuntimeException(sprintf(
        '%s Expected an exception containing "%s".',
        $message,
        $needle,
    ));
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
    throw new \RuntimeException(
        'An active user and member are required.',
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$memberRepository = new MemberRepository($database);
$activityService = new ActivityLogService(
    new ActivityLogRepository($database),
);
$session = new Session();
$memberValidator = new MemberInputValidator();

$memberService = new MemberService(
    repository: $memberRepository,
    activityLog: $activityService,
    session: $session,
    validator: $memberValidator,
);

$loanRepository = new LoanRepository($database);
$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);
$amortization = new AmortizationService();
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

try {
    echo "===============================================================\n";
    echo "ACES MEMBER → LOAN REACTIVATION LIFECYCLE INTEGRATION TEST\n";
    echo "===============================================================\n";

    $memberBefore = $memberService->find($memberId);

    assertSameValue(
        'Active',
        (string) ($memberBefore['status'] ?? ''),
        'Test member must start Active.',
    );

    $loanId = $loanService->create(new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: '2026-10-07',
    ));

    assertSameValue(
        LoanApplicationStatus::PENDING,
        (string) $loanService->find($loanId)['application_status'],
        'Loan created for Active member must start Pending.',
    );

    echo "Active member → loan created Pending       ✓\n";

    $memberService->changeStatus($memberId, 'Inactive');

    assertSameValue(
        'Inactive',
        (string) ($memberService->find($memberId)['status'] ?? ''),
        'Member must become Inactive.',
    );

    assertSameValue(
        LoanApplicationStatus::PENDING,
        (string) $loanService->find($loanId)['application_status'],
        'Deactivating member must not alter Pending loan application status.',
    );

    echo "Active → Inactive → Pending loan unchanged ✓\n";

    $loanWhileInactive = $loanService->find($loanId);

    assertSameValue(
        LoanApplicationStatus::PENDING,
        (string) ($loanWhileInactive['application_status'] ?? ''),
        'Pending loan must remain Pending while member is Inactive.',
    );

    $memberService->changeStatus($memberId, 'Active');

    assertSameValue(
        'Active',
        (string) ($memberService->find($memberId)['status'] ?? ''),
        'Inactive member must reactivate to Active.',
    );

    assertSameValue(
        LoanApplicationStatus::PENDING,
        (string) $loanService->find($loanId)['application_status'],
        'Reactivation must not alter the existing Pending loan.',
    );

    echo "Inactive → Active → Pending loan preserved  ✓\n";

    $loanService->submit($loanId);

    assertSameValue(
        LoanApplicationStatus::UNDER_REVIEW,
        (string) $loanService->find($loanId)['application_status'],
        'Reactivated member loan must be submittable for review.',
    );

    $loanService->approve($loanId);

    assertSameValue(
        LoanApplicationStatus::APPROVED,
        (string) $loanService->find($loanId)['application_status'],
        'Reactivated member loan must be approvable.',
    );

    echo "Reactivated member → loan review/approval    ✓\n";

    $loanService->release($loanId, '2026-10-07');

    assertSameValue(
        LoanStatus::ACTIVE,
        (string) $loanService->find($loanId)['loan_status'],
        'Approved loan must become Active after release.',
    );

    echo "Approved loan → Active                     ✓\n";

    $memberService->changeStatus($memberId, 'Inactive');

    assertSameValue(
        'Inactive',
        (string) ($memberService->find($memberId)['status'] ?? ''),
        'Active member must become Inactive.',
    );

    assertSameValue(
        LoanStatus::ACTIVE,
        (string) $loanService->find($loanId)['loan_status'],
        'Deactivating member must not close or alter an Active loan.',
    );

    echo "Active loan + member deactivation preserved ✓\n";

    $memberService->changeStatus($memberId, 'Active');

    assertSameValue(
        'Active',
        (string) ($memberService->find($memberId)['status'] ?? ''),
        'Inactive member must reactivate to Active.',
    );

    assertSameValue(
        LoanStatus::ACTIVE,
        (string) $loanService->find($loanId)['loan_status'],
        'Reactivation must not alter the existing Active loan.',
    );

    echo "Member reactivation + Active loan preserved   ✓\n";

    echo "===============================================================\n";
    echo "ACES MEMBER → LOAN REACTIVATION LIFECYCLE INTEGRATION TEST: PASS\n";
    echo "===============================================================\n";
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

    if ($memberId > 0) {
        $statement = $pdo->prepare(
            "UPDATE members
             SET status = 'Active'
             WHERE id = :member_id"
        );
        $statement->execute(['member_id' => $memberId]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
