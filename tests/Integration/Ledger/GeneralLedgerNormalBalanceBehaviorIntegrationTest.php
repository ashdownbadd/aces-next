<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

function fail(string $message): never { throw new \RuntimeException($message); }
function assertTrue(bool $condition, string $message): void { if (!$condition) fail($message); }
function assertNear(float $expected, float $actual, string $message): void {
    if (abs($expected - $actual) > 0.005) {
        fail(sprintf('%s Expected %.2f, got %.2f.', $message, $expected, $actual));
    }
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();
if ($userId <= 0) fail('An active user is required.');

$repository = new JournalVoucherRepository($database);
$ledger = new LedgerService($repository);

$cashAccountId = $ledger->accountId('1010');
$incomeAccountId = $ledger->accountId('4010');

$cashMeta = $repository->accountById($cashAccountId);
$incomeMeta = $repository->accountById($incomeAccountId);

assertTrue(($cashMeta['normal_balance'] ?? null) === 'Debit', 'Cash account 1010 must be Debit-normal.');
assertTrue(($incomeMeta['normal_balance'] ?? null) === 'Credit', 'Income account 4010 must be Credit-normal.');

$from = '2099-11-20';
$to = '2099-11-21';

$cashBaseline = $ledger->generalLedger($cashAccountId, $from, $to);
$incomeBaseline = $ledger->generalLedger($incomeAccountId, $from, $to);
$cashOpening = (float) $cashBaseline['opening_balance'];
$incomeOpening = (float) $incomeBaseline['opening_balance'];

$debitMovementId = null;
$creditMovementId = null;

$refBase = 'GL-NORMAL-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

try {
    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER NORMAL-BALANCE BEHAVIOR TEST\n";
    echo "===============================================================\n";

    // Voucher 1: Debit-normal cash is credited 100; Credit-normal income is debited 100.
    $debitMovementId = $ledger->createPending(
        [
            'reference_number' => $refBase . '-OPPOSITE',
            'transaction_date' => $from,
            'particulars' => 'QA normal-balance opposite-side movement',
            'source_type' => 'IntegrationTest',
        ],
        [
            ['account_id' => $cashAccountId, 'debit' => 0.00, 'credit' => 100.00, 'line_description' => 'QA cash credit'],
            ['account_id' => $incomeAccountId, 'debit' => 100.00, 'credit' => 0.00, 'line_description' => 'QA income debit'],
        ],
        $userId,
    );
    $ledger->approve($debitMovementId, $userId, '2099-11-20 10:00:00');
    $ledger->post($debitMovementId, $userId, '2099-11-20 10:01:00');

    // Voucher 2: Reverse the direction with a smaller 40 movement.
    $creditMovementId = $ledger->createPending(
        [
            'reference_number' => $refBase . '-NORMAL',
            'transaction_date' => $to,
            'particulars' => 'QA normal-balance normal-side movement',
            'source_type' => 'IntegrationTest',
        ],
        [
            ['account_id' => $cashAccountId, 'debit' => 40.00, 'credit' => 0.00, 'line_description' => 'QA cash debit'],
            ['account_id' => $incomeAccountId, 'debit' => 0.00, 'credit' => 40.00, 'line_description' => 'QA income credit'],
        ],
        $userId,
    );
    $ledger->approve($creditMovementId, $userId, '2099-11-21 10:00:00');
    $ledger->post($creditMovementId, $userId, '2099-11-21 10:01:00');

    $cash = $ledger->generalLedger($cashAccountId, $from, $to);
    $income = $ledger->generalLedger($incomeAccountId, $from, $to);

    $cashRows = array_values(array_filter(
        $cash['rows'],
        static fn(array $row): bool => in_array((int) $row['voucher_id'], [$debitMovementId, $creditMovementId], true)
    ));
    $incomeRows = array_values(array_filter(
        $income['rows'],
        static fn(array $row): bool => in_array((int) $row['voucher_id'], [$debitMovementId, $creditMovementId], true)
    ));

    assertTrue(count($cashRows) === 2, 'Cash ledger must contain both QA movements.');
    assertTrue(count($incomeRows) === 2, 'Income ledger must contain both QA movements.');

    assertNear(-100.00, (float) $cashRows[0]['running_balance'] - $cashOpening, 'Debit-normal cash must decrease on a credit movement.');
    assertNear(-60.00, (float) $cashRows[1]['running_balance'] - $cashOpening, 'Debit-normal cash must increase by the later debit movement.');

    assertNear(-100.00, (float) $incomeRows[0]['running_balance'] - $incomeOpening, 'Credit-normal income must decrease on a debit movement.');
    assertNear(-60.00, (float) $incomeRows[1]['running_balance'] - $incomeOpening, 'Credit-normal income must increase by the later credit movement.');

    assertNear($cashOpening - 60.00, (float) $cash['closing_balance'], 'Cash closing balance must follow Debit-normal sign logic.');
    assertNear($incomeOpening - 60.00, (float) $income['closing_balance'], 'Income closing balance must follow Credit-normal sign logic.');

    echo "Debit-normal account direction                  ✓\n";
    echo "Credit-normal account direction                 ✓\n";
    echo "Opposite-side movement                         ✓\n";
    echo "Normal-side movement                           ✓\n";
    echo "Running balance sign progression               ✓\n";
    echo "Closing balance sign integrity                 ✓\n";
    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER NORMAL-BALANCE BEHAVIOR TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    $ids = array_values(array_filter([$debitMovementId, $creditMovementId]));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $pdo->prepare("DELETE FROM journal_lines WHERE journal_voucher_id IN ($placeholders)");
        $statement->execute($ids);
        $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ($placeholders)");
        $statement->execute($ids);
    }
}
