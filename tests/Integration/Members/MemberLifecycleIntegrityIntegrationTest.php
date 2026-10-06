<?php

declare(strict_types=1);

/**
 * ACES Member Lifecycle Integrity — real MySQL integration test.
 *
 * Verifies member status transitions and the member-state boundary for
 * loan creation. This test does not modify production source files.
 *
 * Run from the ACES project root:
 *   php tests/Integration/Members/MemberLifecycleIntegrityIntegrationTest.php
 */

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Authentication\Repositories\UserRepository;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Services\MemberInputValidator;
use App\Features\Members\Services\MemberService;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf(
                '%s Expected %s, got %s.',
                $message,
                var_export($expected, true),
                var_export($actual, true),
            )
        );
    }
}

function assertThrows(callable $callback, string $messageNeedle, string $label): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (
            $messageNeedle !== ''
            && !str_contains($exception->getMessage(), $messageNeedle)
        ) {
            throw new RuntimeException(
                sprintf(
                    '%s Unexpected exception: %s',
                    $label,
                    $exception->getMessage(),
                )
            );
        }

        echo $label . " → blocked ✓\n";
        return;
    }

    throw new RuntimeException($label . ' → operation was accepted unexpectedly.');
}

function loanData(int $memberId): LoanData
{
    return new LoanData(
        memberId: $memberId,
        loanType: LoanType::PRODUCTIVITY_LOAN,
        collateral: CollateralType::POST_DATED_CHECK,
        principalAmount: 6000.00,
        interestRate: 2.00,
        amortizationType: AmortizationType::STRAIGHT_LINE,
        paymentFrequency: null,
        termsMonths: 3,
        startDate: '2026-09-20',
    );
}

$config = new Config();
$config->load($root . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new RuntimeException('An active user and active member are required.');
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
$memberService = new MemberService(
    $memberRepository,
    $activityService,
    $session,
    new MemberInputValidator(),
);

$journalRepository = new JournalVoucherRepository($database);
$loanRepository = new LoanRepository($database);
$loanService = new LoanService(
    repository: $loanRepository,
    ledger: new LedgerService($journalRepository),
    amortization: new AmortizationService(),
    activityLog: $activityService,
    session: $session,
    users: new UserRepository($database),
);

$originalStatus = (string) ($memberRepository->find($memberId)['status'] ?? '');
if ($originalStatus !== 'Active') {
    throw new RuntimeException('Selected test member is no longer Active.');
}

$temporaryLoanIds = [];

try {
    echo "================================================\n";
    echo "ACES MEMBER LIFECYCLE INTEGRITY INTEGRATION TEST\n";
    echo "================================================\n";

    // Active -> Inactive -> Active
    $memberService->changeStatus($memberId, 'Inactive');
    assertSameValue(
        'Inactive',
        $memberRepository->find($memberId)['status'] ?? null,
        'Active -> Inactive transition.',
    );
    echo "Active → Inactive                 ✓\n";

    $memberService->changeStatus($memberId, 'Active');
    assertSameValue(
        'Active',
        $memberRepository->find($memberId)['status'] ?? null,
        'Inactive -> Active transition.',
    );
    echo "Inactive → Active                 ✓\n";

    // Invalid transitions from Active.
    assertThrows(
        fn() => $memberService->changeStatus($memberId, 'Pending'),
        'Cannot change member status from Active to Pending.',
        'Active → Pending',
    );

    assertThrows(
        fn() => $memberService->changeStatus($memberId, 'Active'),
        'Cannot change member status from Active to Active.',
        'Active → Active',
    );

    // Put the test member into Pending as controlled test setup, then verify
    // the two explicitly allowed Pending transitions through the service.
    $memberRepository->updateStatus($memberId, 'Pending');
    assertSameValue(
        'Pending',
        $memberRepository->find($memberId)['status'] ?? null,
        'Test setup must place member in Pending.',
    );

    $memberService->changeStatus($memberId, 'Inactive');
    assertSameValue(
        'Inactive',
        $memberRepository->find($memberId)['status'] ?? null,
        'Pending -> Inactive transition.',
    );
    echo "Pending → Inactive                ✓\n";

    $memberRepository->updateStatus($memberId, 'Pending');
    $memberService->changeStatus($memberId, 'Active');
    assertSameValue(
        'Active',
        $memberRepository->find($memberId)['status'] ?? null,
        'Pending -> Active transition.',
    );
    echo "Pending → Active                  ✓\n";

    // Invalid transitions from Inactive.
    $memberService->changeStatus($memberId, 'Inactive');
    assertThrows(
        fn() => $memberService->changeStatus($memberId, 'Pending'),
        'Cannot change member status from Inactive to Pending.',
        'Inactive → Pending',
    );
    assertThrows(
        fn() => $memberService->changeStatus($memberId, 'Inactive'),
        'Cannot change member status from Inactive to Inactive.',
        'Inactive → Inactive',
    );

    // Restore Active before testing the loan boundary.
    $memberService->changeStatus($memberId, 'Active');

    // Direct service-level loan creation must respect the same member-state
    // boundary enforced by the loan creation UI/search.
    $memberService->changeStatus($memberId, 'Inactive');
    assertThrows(
        function () use ($loanService, $memberId, &$temporaryLoanIds): void {
            try {
                $temporaryLoanIds[] = $loanService->create(loanData($memberId));
            } catch (Throwable $exception) {
                throw $exception;
            }
        },
        'Only Active members can apply for a loan.',
        'Loan creation for Inactive member',
    );

    // Pending is a controlled database setup state because the production
    // lifecycle intentionally has no transition back to Pending.
    $memberRepository->updateStatus($memberId, 'Pending');
    assertThrows(
        function () use ($loanService, $memberId, &$temporaryLoanIds): void {
            try {
                $temporaryLoanIds[] = $loanService->create(loanData($memberId));
            } catch (Throwable $exception) {
                throw $exception;
            }
        },
        'Only Active members can apply for a loan.',
        'Loan creation for Pending member',
    );

    // Restore Active and prove that a valid member remains eligible.
    $memberRepository->updateStatus($memberId, 'Active');
    $activeLoanId = $loanService->create(loanData($memberId));
    $temporaryLoanIds[] = $activeLoanId;
    assertSameValue(
        'Pending',
        $loanRepository->find($activeLoanId)['application_status'] ?? null,
        'Active member loan creation.',
    );
    echo "Loan creation for Active member → allowed ✓\n";

    echo "================================================\n";
    echo "ACES MEMBER LIFECYCLE INTEGRITY INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    if ($temporaryLoanIds !== []) {
        $placeholders = implode(',', array_fill(0, count($temporaryLoanIds), '?'));
        $statement = $pdo->prepare(
            "DELETE FROM loans WHERE id IN ($placeholders)"
        );
        $statement->execute(array_map('intval', $temporaryLoanIds));
    }

    $memberRepository->updateStatus($memberId, $originalStatus);

    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    echo "Cleanup completed for Member #{$memberId}.\n";
}
