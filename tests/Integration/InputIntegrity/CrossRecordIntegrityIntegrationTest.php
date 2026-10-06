<?php

declare(strict_types=1);

use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Foundation\Database;
use App\Foundation\Session;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$root = dirname(__DIR__, 3);
$app = require $root . '/bootstrap/app.php';
$container = $app->container();

$database = $container->get(Database::class);
$pdo = $database->connection();
$session = $container->get(Session::class);
$loanService = $container->get(LoanService::class);
$paymentService = $container->get(PaymentService::class);

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . ' Expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true) . '.'
        );
    }
}

function paymentToken(string $seed): string
{
    return substr(hash('sha256', $seed . '-' . bin2hex(random_bytes(8))), 0, 40);
}

$actors = $pdo->query(
    "SELECT id, role FROM users
     WHERE is_active = 1
       AND role IN ('membership', 'accounting', 'loan_officer')
     ORDER BY FIELD(role, 'membership', 'accounting', 'loan_officer'), id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$creator = $approver = $releaser = 0;
foreach ($actors as $actor) {
    $role = (string) $actor['role'];
    if ($role === 'membership' && $creator === 0) {
        $creator = (int) $actor['id'];
    }
    if ($role === 'accounting' && $approver === 0) {
        $approver = (int) $actor['id'];
    }
    if ($role === 'loan_officer' && $releaser === 0) {
        $releaser = (int) $actor['id'];
    }
}

assertTrue(
    $creator > 0 && $approver > 0 && $releaser > 0,
    'Cross-record QA requires active membership, accounting, and loan_officer users.'
);

$members = $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 2"
)->fetchAll(PDO::FETCH_COLUMN);

assertTrue(count($members) >= 2, 'Cross-record QA requires at least two active members.');

$memberA = (int) $members[0];
$memberB = (int) $members[1];
$loanA = 0;
$loanB = 0;
$paymentB = 0;

$loanTypes = LoanType::all();
$collateralTypes = CollateralType::all();
$amortizationTypes = AmortizationType::all();

$makeLoan = static function (int $memberId) use ($loanService, $loanTypes, $collateralTypes, $amortizationTypes): int {
    return $loanService->create(
        LoanData::fromArray([
            'member_id' => $memberId,
            'loan_type' => $loanTypes[0],
            'collateral' => $collateralTypes[0],
            'principal_amount' => 6000,
            'interest_rate' => 2,
            'amortization_type' => $amortizationTypes[0],
            'payment_frequency' => null,
            'terms_months' => 3,
            'start_date' => date('Y-m-d'),
            'manual_payment' => null,
            'tct_no' => null,
            'tax_declaration_no' => null,
            'real_property_payment_status' => null,
            'notes' => 'Cross-record integrity QA temporary loan',
        ])
    );
};

try {
    echo "================================================\n";
    echo "ACES CROSS-RECORD INTEGRITY INTEGRATION TEST\n";
    echo "================================================\n";

    $session->put('user_id', $creator);
    $loanA = $makeLoan($memberA);
    $loanB = $makeLoan($memberB);

    $loanRowA = $pdo->query(
        'SELECT id, member_id FROM loans WHERE id = ' . $loanA
    )->fetch(PDO::FETCH_ASSOC);
    $loanRowB = $pdo->query(
        'SELECT id, member_id FROM loans WHERE id = ' . $loanB
    )->fetch(PDO::FETCH_ASSOC);

    assertSameValue($memberA, (int) $loanRowA['member_id'], 'Loan A member ownership.');
    assertSameValue($memberB, (int) $loanRowB['member_id'], 'Loan B member ownership.');
    assertTrue($loanA !== $loanB, 'Temporary loans must have distinct IDs.');
    echo "Loan/member ownership mapping       → isolated ✓\n";

    $session->put('user_id', $approver);
    $loanService->submit($loanA);
    $loanService->review($loanA);
    $loanService->approve($loanA);
    $loanService->submit($loanB);
    $loanService->review($loanB);
    $loanService->approve($loanB);
    echo "Independent loan workflows          → isolated ✓\n";

    $session->put('user_id', $releaser);
    $loanService->release($loanA, date('Y-m-d'));
    $loanService->release($loanB, date('Y-m-d'));

    $beforeA = $pdo->prepare(
        'SELECT rem_principal, rem_interest, status FROM loan_amortizations
         WHERE loan_id = :loan_id ORDER BY period ASC LIMIT 1'
    );
    $beforeA->execute(['loan_id' => $loanA]);
    $beforeRowA = $beforeA->fetch(PDO::FETCH_ASSOC);

    $beforePaymentCountA = (int) $pdo->query(
        'SELECT COUNT(*) FROM loan_payments WHERE loan_id = ' . $loanA
    )->fetchColumn();

    $session->put('user_id', $releaser);
    $payment = $paymentService->apply(
        loanId: $loanB,
        amountPaid: 1000.00,
        remarks: 'Cross-record integrity QA payment',
        idempotencyKey: paymentToken('cross-record-b'),
    );
    $paymentB = (int) $payment['payment_id'];

    $paymentRow = $pdo->prepare(
        'SELECT id, loan_id FROM loan_payments WHERE id = :id LIMIT 1'
    );
    $paymentRow->execute(['id' => $paymentB]);
    $paymentRow = $paymentRow->fetch(PDO::FETCH_ASSOC);

    assertSameValue($loanB, (int) $paymentRow['loan_id'], 'Payment must remain attached to Loan B.');
    assertTrue(
        (int) $paymentRow['loan_id'] !== $loanA,
        'Payment for Loan B must never be attached to Loan A.'
    );
    echo "Payment targets exact loan            → isolated ✓\n";

    $afterPaymentCountA = (int) $pdo->query(
        'SELECT COUNT(*) FROM loan_payments WHERE loan_id = ' . $loanA
    )->fetchColumn();

    assertSameValue(
        $beforePaymentCountA,
        $afterPaymentCountA,
        'Payment against Loan B must not create a payment for Loan A.'
    );
    echo "Payment cannot cross loan boundary    → blocked ✓\n";

    $afterA = $pdo->prepare(
        'SELECT rem_principal, rem_interest, status FROM loan_amortizations
         WHERE loan_id = :loan_id ORDER BY period ASC LIMIT 1'
    );
    $afterA->execute(['loan_id' => $loanA]);
    $afterRowA = $afterA->fetch(PDO::FETCH_ASSOC);

    assertSameValue($beforeRowA['rem_principal'], $afterRowA['rem_principal'], 'Loan A principal changed unexpectedly.');
    assertSameValue($beforeRowA['rem_interest'], $afterRowA['rem_interest'], 'Loan A interest changed unexpectedly.');
    assertSameValue($beforeRowA['status'], $afterRowA['status'], 'Loan A installment status changed unexpectedly.');
    echo "Loan A balance unaffected by Loan B  → isolated ✓\n";

    $counts = $pdo->query(
        'SELECT loan_id, COUNT(*) AS payment_count
         FROM loan_payments
         WHERE loan_id IN (' . $loanA . ', ' . $loanB . ')
         GROUP BY loan_id
         ORDER BY loan_id'
    )->fetchAll(PDO::FETCH_KEY_PAIR);

    assertSameValue(0, (int) ($counts[$loanA] ?? 0), 'Loan A must have zero QA payments.');
    assertSameValue(1, (int) ($counts[$loanB] ?? 0), 'Loan B must have exactly one QA payment.');
    echo "Final payment distribution             → isolated ✓\n";

    echo "================================================\n";
    echo "ACES CROSS-RECORD INTEGRITY INTEGRATION TEST: PASS\n";
    echo "================================================\n";
} finally {
    if ($paymentB > 0) {
        $pdo->prepare('DELETE FROM loan_payment_allocations WHERE payment_id = :id')->execute(['id' => $paymentB]);
        $pdo->prepare('DELETE FROM loan_payments WHERE id = :id')->execute(['id' => $paymentB]);
    }

    foreach ([$loanA, $loanB] as $loanId) {
        if ($loanId <= 0) {
            continue;
        }

        $voucherIds = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE source_type = 'LoanRelease' AND source_id = :loan_id"
        );
        $voucherIds->execute(['loan_id' => $loanId]);
        $ids = $voucherIds->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $voucherId) {
            $pdo->prepare('DELETE FROM journal_lines WHERE journal_voucher_id = :id')->execute(['id' => $voucherId]);
            $pdo->prepare('DELETE FROM journal_vouchers WHERE id = :id')->execute(['id' => $voucherId]);
        }

        $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id')->execute(['loan_id' => $loanId]);
        $pdo->prepare('DELETE FROM loans WHERE id = :loan_id')->execute(['loan_id' => $loanId]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
