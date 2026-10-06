<?php

declare(strict_types=1);

/**
 * ACES Financial Input Integrity — real MySQL integration test.
 *
 * Verifies server-side rejection of manipulated loan/payment financial inputs.
 * This test does not modify production source files or database records.
 *
 * Run from the ACES project root:
 *   php tests/Integration/InputIntegrity/FinancialInputIntegrityIntegrationTest.php
 */

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

function expectRejected(callable $callback, string $messageNeedle, string $label): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (!str_contains($exception->getMessage(), $messageNeedle)) {
            throw new RuntimeException(
                sprintf(
                    '%s → unexpected exception: %s',
                    $label,
                    $exception->getMessage(),
                )
            );
        }

        echo $label . " → rejected ✓\n";
        return;
    }

    throw new RuntimeException($label . ' → input was accepted unexpectedly.');
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

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityService = new ActivityLogService(new ActivityLogRepository($database));
$journalRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($journalRepository);
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
    $journalRepository,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
    $database,
);

$validLoan = static fn(): LoanData => new LoanData(
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

try {
    echo "================================================\n";
    echo "ACES FINANCIAL INPUT INTEGRITY INTEGRATION TEST\n";
    echo "================================================\n";

    // PaymentService validation occurs before any database transaction/write.
    expectRejected(
        fn() => $paymentService->apply(0, 1000.00, null, str_repeat('a', 40)),
        'Invalid loan ID.',
        'Invalid loan ID',
    );

    expectRejected(
        fn() => $paymentService->apply(1, 0.00, null, str_repeat('b', 40)),
        'Payment amount must be greater than zero.',
        'Zero payment amount',
    );

    expectRejected(
        fn() => $paymentService->apply(1, -1.00, null, str_repeat('c', 40)),
        'Payment amount must be greater than zero.',
        'Negative payment amount',
    );

    expectRejected(
        fn() => $paymentService->apply(1, 1000.00, null, 'short-token'),
        'A valid payment request token is required.',
        'Malformed payment request token',
    );

    expectRejected(
        fn() => $paymentService->apply(1, 1000.00, null, str_repeat('x', 81)),
        'A valid payment request token is required.',
        'Oversized payment request token',
    );

    // LoanService validation must reject manipulated financial fields before creation.
    $negativePrincipal = $validLoan();
    $negativePrincipal = LoanData::fromArray(
        array_merge($negativePrincipal->toArray(), ['principal_amount' => -1.00]),
    );
    expectRejected(
        fn() => $loanService->create($negativePrincipal),
        'Principal amount must be greater than zero.',
        'Negative loan principal',
    );

    $zeroInterest = $validLoan();
    $zeroInterest = LoanData::fromArray(
        array_merge($zeroInterest->toArray(), ['interest_rate' => 0.00]),
    );
    expectRejected(
        fn() => $loanService->create($zeroInterest),
        'Interest rate must be greater than zero.',
        'Zero loan interest rate',
    );

    $zeroTerms = $validLoan();
    $zeroTerms = LoanData::fromArray(
        array_merge($zeroTerms->toArray(), ['terms_months' => 0]),
    );
    expectRejected(
        fn() => $loanService->create($zeroTerms),
        'Loan terms must be greater than zero.',
        'Zero loan term',
    );

    $invalidType = $validLoan();
    $invalidType = LoanData::fromArray(
        array_merge($invalidType->toArray(), ['loan_type' => 'ManipulatedLoanType']),
    );
    expectRejected(
        fn() => $loanService->create($invalidType),
        'Invalid loan type.',
        'Invalid loan type',
    );

    $invalidDate = $validLoan();
    $invalidDate = LoanData::fromArray(
        array_merge($invalidDate->toArray(), ['start_date' => 'not-a-date']),
    );
    expectRejected(
        fn() => $loanService->create($invalidDate),
        'Start date must be a valid date.',
        'Invalid loan start date',
    );

    echo "================================================\n";
    echo "ACES FINANCIAL INPUT INTEGRITY INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
