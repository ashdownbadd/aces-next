<?php

declare(strict_types=1);

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Services\LoanService;
use App\Foundation\Database;
use App\Foundation\Session;

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';
$container = $app->container();

$database = $container->get(Database::class);
$pdo = $database->connection();
$session = $container->get(Session::class);
$loanService = $container->get(LoanService::class);
$ledger = $container->get(LedgerService::class);
$voucherRepository = $container->get(JournalVoucherRepository::class);

$assertBlocked = static function (callable $operation, string $label): void {
    try {
        $operation();
    } catch (RuntimeException $exception) {
        echo $label . ' → blocked ✓' . PHP_EOL;
        return;
    }

    throw new RuntimeException($label . ' → operation was not blocked.');
};

$session->put('user_id', null);

$actors = $pdo->query(
    "SELECT id, role FROM users
     WHERE is_active = 1
       AND role IN ('membership', 'accounting', 'loan_officer')
     ORDER BY FIELD(role, 'membership', 'accounting', 'loan_officer'), id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$actorOne = 0;
$actorTwo = 0;
$actorThree = 0;

foreach ($actors as $actor) {
    $role = (string) $actor['role'];

    if ($role === 'membership' && $actorOne === 0) {
        $actorOne = (int) $actor['id'];
    }

    if ($role === 'accounting' && $actorTwo === 0) {
        $actorTwo = (int) $actor['id'];
    }

    if ($role === 'loan_officer' && $actorThree === 0) {
        $actorThree = (int) $actor['id'];
    }
}

if ($actorOne <= 0 || $actorTwo <= 0 || $actorThree <= 0) {
    throw new RuntimeException(
        'Authorization QA requires active membership, accounting, and loan_officer users.'
    );
}

$memberId = (int) $pdo->query(
    "SELECT id FROM members WHERE status = 'Active' ORDER BY id ASC LIMIT 1"
)->fetchColumn();

if ($memberId <= 0) {
    throw new RuntimeException('Authorization QA requires at least one Active member.');
}

$loanId = 0;
$pendingVoucherId = 0;
$rejectedVoucherId = 0;

try {
    echo PHP_EOL;
    echo '================================================' . PHP_EOL;
    echo 'ACES OPERATION AUTHORIZATION INTEGRATION TEST' . PHP_EOL;
    echo '================================================' . PHP_EOL;

    // Membership user creates the loan. Accounting user approves it.
    // A non-admin approver must not be allowed to release their own approval.
    $session->put('user_id', $actorOne);

    $loanId = $loanService->create(
        LoanData::fromArray([
            'member_id' => $memberId,
            'loan_type' => LoanType::all()[0],
            'collateral' => CollateralType::all()[0],
            'principal_amount' => 12000,
            'interest_rate' => 2,
            'amortization_type' => AmortizationType::all()[0],
            'payment_frequency' => null,
            'terms_months' => 3,
            'start_date' => date('Y-m-d'),
            'manual_payment' => null,
            'tct_no' => null,
            'tax_declaration_no' => null,
            'real_property_payment_status' => null,
            'notes' => 'Authorization QA temporary loan',
        ])
    );

    $assertBlocked(
        fn() => $loanService->approve($loanId),
        'Approve Pending Loan',
    );

    $assertBlocked(
        fn() => $loanService->release($loanId),
        'Release Pending Loan',
    );

    $loanService->submit($loanId);
    $loanService->review($loanId);

    // The creator must not approve their own loan. Use the accounting actor
    // as the approving actor so the test reaches the release segregation rule.
    $session->put('user_id', $actorTwo);
    $loanService->approve($loanId);

    $assertBlocked(
        fn() => $loanService->approve($loanId),
        'Approve Approved Loan',
    );

    $session->put('user_id', $actorTwo);
    $assertBlocked(
        fn() => $loanService->release($loanId),
        'Release by Same Approver',
    );

    $session->put('user_id', $actorThree);
    $loanService->release($loanId, date('Y-m-d'));

    $assertBlocked(
        fn() => $loanService->release($loanId, date('Y-m-d')),
        'Release Active Loan',
    );

    echo 'Release by Different Actor → allowed ✓' . PHP_EOL;

    $session->put('user_id', $actorThree);

    $cashAccountId = $voucherRepository->accountId('1010');
    $incomeAccountId = $voucherRepository->accountId('4010');

    if ($cashAccountId <= 0 || $incomeAccountId <= 0) {
        throw new RuntimeException('Authorization QA requires accounts 1010 and 4010.');
    }

    $pendingVoucherId = $voucherRepository->createPending(
        [
            'reference_number' => 'AUTH-QA-' . uniqid('', true),
            'transaction_date' => date('Y-m-d'),
            'particulars' => 'Authorization QA pending voucher',
            'source_type' => 'AuthorizationQA',
            'source_id' => $loanId,
            'reversal_of_voucher_id' => null,
            'created_by' => $actorTwo,
        ],
        [
            [
                'account_id' => $cashAccountId,
                'member_id' => $memberId,
                'loan_id' => $loanId,
                'line_description' => 'Authorization QA debit',
                'debit' => 100,
                'credit' => 0,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => $memberId,
                'loan_id' => $loanId,
                'line_description' => 'Authorization QA credit',
                'debit' => 0,
                'credit' => 100,
            ],
        ],
    );

    $ledger->approve($pendingVoucherId, $actorTwo, date('Y-m-d H:i:s'));
    $assertBlocked(
        fn() => $ledger->approve($pendingVoucherId, $actorTwo, date('Y-m-d H:i:s')),
        'Approve Approved Voucher',
    );

    $ledger->post($pendingVoucherId, $actorTwo, date('Y-m-d H:i:s'));
    $assertBlocked(
        fn() => $ledger->post($pendingVoucherId, $actorTwo, date('Y-m-d H:i:s')),
        'Post Posted Voucher',
    );
    $assertBlocked(
        fn() => $ledger->approve($pendingVoucherId, $actorTwo, date('Y-m-d H:i:s')),
        'Approve Posted Voucher',
    );

    $rejectedVoucherId = $voucherRepository->createPending(
        [
            'reference_number' => 'AUTH-QA-REJECT-' . uniqid('', true),
            'transaction_date' => date('Y-m-d'),
            'particulars' => 'Authorization QA rejected voucher',
            'source_type' => 'AuthorizationQA',
            'source_id' => $loanId,
            'reversal_of_voucher_id' => null,
            'created_by' => $actorTwo,
        ],
        [
            [
                'account_id' => $cashAccountId,
                'member_id' => $memberId,
                'loan_id' => $loanId,
                'line_description' => 'Authorization QA rejected debit',
                'debit' => 50,
                'credit' => 0,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => $memberId,
                'loan_id' => $loanId,
                'line_description' => 'Authorization QA rejected credit',
                'debit' => 0,
                'credit' => 50,
            ],
        ],
    );

    $ledger->reject($rejectedVoucherId, 'Authorization QA rejection');
    $assertBlocked(
        fn() => $ledger->post($rejectedVoucherId, $actorTwo, date('Y-m-d H:i:s')),
        'Post Rejected Voucher',
    );

    echo 'Authenticated Actor → enforced ✓' . PHP_EOL;
    echo '================================================' . PHP_EOL;
    echo 'ACES OPERATION AUTHORIZATION INTEGRATION TEST: PASS' . PHP_EOL;
    echo '================================================' . PHP_EOL;
} finally {
    $session->put('user_id', null);

    if ($rejectedVoucherId > 0) {
        $pdo->prepare('DELETE FROM journal_lines WHERE journal_voucher_id = ?')->execute([$rejectedVoucherId]);
        $pdo->prepare('DELETE FROM journal_vouchers WHERE id = ?')->execute([$rejectedVoucherId]);
    }

    if ($pendingVoucherId > 0) {
        $pdo->prepare('DELETE FROM journal_lines WHERE journal_voucher_id = ?')->execute([$pendingVoucherId]);
        $pdo->prepare('DELETE FROM journal_vouchers WHERE id = ?')->execute([$pendingVoucherId]);
    }

    if ($loanId > 0) {
        $loanVoucherIds = $pdo->prepare(
            "SELECT id FROM journal_vouchers
             WHERE source_type = 'LoanRelease' AND source_id = ?"
        );
        $loanVoucherIds->execute([$loanId]);
        $voucherIds = $loanVoucherIds->fetchAll(PDO::FETCH_COLUMN);

        if ($voucherIds !== []) {
            $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));
            $pdo->prepare(
                "DELETE FROM journal_lines WHERE journal_voucher_id IN ($placeholders)"
            )->execute(array_map('intval', $voucherIds));
            $pdo->prepare(
                "DELETE FROM journal_vouchers WHERE id IN ($placeholders)"
            )->execute(array_map('intval', $voucherIds));
        }

        $pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = ?')->execute([$loanId]);
        $pdo->prepare('DELETE FROM loans WHERE id = ?')->execute([$loanId]);
    }
}
