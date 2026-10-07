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
    if (abs($expected - $actual) > 0.005) fail(sprintf('%s Expected %.2f, got %.2f.', $message, $expected, $actual));
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

$period = '2099-10';
$from = '2099-10-20';
$to = '2099-10-21';

$cashBaseline = $ledger->generalLedger($cashAccountId, $from, $to);
$incomeBaseline = $ledger->generalLedger($incomeAccountId, $from, $to);

$cashOpening = (float) $cashBaseline['opening_balance'];
$incomeOpening = (float) $incomeBaseline['opening_balance'];

$postedId = null;
$pendingId = null;
$approvedId = null;

$refBase = 'GL-TRACE-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

try {
    echo "===============================================================\n";
    echo "ACES JOURNAL VOUCHER → GENERAL LEDGER TRACEABILITY TEST\n";
    echo "===============================================================\n";

    $postedId = $ledger->createPending(
        [
            'reference_number' => $refBase . '-POSTED',
            'transaction_date' => $from,
            'particulars' => 'QA traceability posted voucher',
            'source_type' => 'IntegrationTest',
        ],
        [
            ['account_id' => $cashAccountId, 'debit' => 320.00, 'credit' => 0.00, 'line_description' => 'QA cash trace'],
            ['account_id' => $incomeAccountId, 'debit' => 0.00, 'credit' => 320.00, 'line_description' => 'QA income trace'],
        ],
        $userId,
    );
    $ledger->approve($postedId, $userId, '2099-10-20 10:00:00');
    $ledger->post($postedId, $userId, '2099-10-20 10:01:00');

    $pendingId = $ledger->createPending(
        [
            'reference_number' => $refBase . '-PENDING',
            'transaction_date' => $to,
            'particulars' => 'QA traceability pending voucher',
            'source_type' => 'IntegrationTest',
        ],
        [
            ['account_id' => $cashAccountId, 'debit' => 77.00, 'credit' => 0.00],
            ['account_id' => $incomeAccountId, 'debit' => 0.00, 'credit' => 77.00],
        ],
        $userId,
    );

    $approvedId = $ledger->createPending(
        [
            'reference_number' => $refBase . '-APPROVED',
            'transaction_date' => $to,
            'particulars' => 'QA traceability approved voucher',
            'source_type' => 'IntegrationTest',
        ],
        [
            ['account_id' => $cashAccountId, 'debit' => 88.00, 'credit' => 0.00],
            ['account_id' => $incomeAccountId, 'debit' => 0.00, 'credit' => 88.00],
        ],
        $userId,
    );
    $ledger->approve($approvedId, $userId, '2099-10-21 10:00:00');

    $cash = $ledger->generalLedger($cashAccountId, $from, $to);
    $income = $ledger->generalLedger($incomeAccountId, $from, $to);

    $cashRows = $cash['rows'];
    $incomeRows = $income['rows'];

    $postedCashRows = array_values(array_filter(
        $cashRows,
        static fn(array $row): bool => (int) $row['voucher_id'] === $postedId
    ));
    $postedIncomeRows = array_values(array_filter(
        $incomeRows,
        static fn(array $row): bool => (int) $row['voucher_id'] === $postedId
    ));

    assertTrue(count($postedCashRows) === 1, 'Posted voucher must appear exactly once in cash ledger.');
    assertTrue(count($postedIncomeRows) === 1, 'Posted voucher must appear exactly once in income ledger.');

    $cashRow = $postedCashRows[0];
    $incomeRow = $postedIncomeRows[0];

    assertTrue($cashRow['reference_number'] === $refBase . '-POSTED', 'Cash ledger reference must match posted voucher.');
    assertTrue($cashRow['transaction_date'] === $from, 'Cash ledger date must match posted voucher.');
    assertTrue($cashRow['particulars'] === 'QA traceability posted voucher', 'Cash ledger particulars must match posted voucher.');
    assertNear(320.00, (float) $cashRow['debit'], 'Cash ledger debit.');
    assertNear(0.00, (float) $cashRow['credit'], 'Cash ledger credit.');

    assertTrue($incomeRow['reference_number'] === $refBase . '-POSTED', 'Income ledger reference must match posted voucher.');
    assertNear(0.00, (float) $incomeRow['debit'], 'Income ledger debit.');
    assertNear(320.00, (float) $incomeRow['credit'], 'Income ledger credit.');

    assertTrue(
        !array_filter($cashRows, static fn(array $row): bool => in_array((int) $row['voucher_id'], [$pendingId, $approvedId], true)),
        'Non-posted vouchers must not appear in cash ledger.'
    );
    assertTrue(
        !array_filter($incomeRows, static fn(array $row): bool => in_array((int) $row['voucher_id'], [$pendingId, $approvedId], true)),
        'Non-posted vouchers must not appear in income ledger.'
    );

    $expectedCashClosing = $cashOpening + 320.00;
    $expectedIncomeClosing = $incomeOpening + 320.00;
    assertNear($expectedCashClosing, (float) $cash['closing_balance'], 'Cash closing balance must include only the posted trace voucher.');
    assertNear($expectedIncomeClosing, (float) $income['closing_balance'], 'Income closing balance must include only the posted trace voucher.');

    echo "Posted voucher → cash ledger traceable       ✓\n";
    echo "Posted voucher → income ledger traceable     ✓\n";
    echo "Reference/date/description preserved          ✓\n";
    echo "Debit/credit mapping preserved                ✓\n";
    echo "Pending/Approved → excluded from ledger       ✓\n";
    echo "Closing balances reflect posted activity      ✓\n";
    echo "===============================================================\n";
    echo "ACES JOURNAL VOUCHER → GENERAL LEDGER TRACEABILITY TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    $ids = array_values(array_filter([$postedId, $pendingId, $approvedId]));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $pdo->prepare("DELETE FROM journal_lines WHERE journal_voucher_id IN ($placeholders)");
        $statement->execute($ids);
        $statement = $pdo->prepare("DELETE FROM journal_vouchers WHERE id IN ($placeholders)");
        $statement->execute($ids);
    }
}
