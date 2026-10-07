<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;
use RuntimeException;

function assertNearValue(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(sprintf(
            '%s Expected %.2f, got %.2f.',
            $message,
            $expected,
            $actual,
        ));
    }
}

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

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException('An active user is required.');
}

$repository = new JournalVoucherRepository($database);
$ledger = new LedgerService($repository);

$cashAccountId = $ledger->accountId('1010');
$incomeAccountId = $ledger->accountId('4010');

$voucherIds = [];

function createPostedVoucher(
    LedgerService $ledger,
    int $userId,
    string $reference,
    string $date,
    int $debitAccountId,
    int $creditAccountId,
    float $amount,
): int {
    $voucherId = $ledger->createPending(
        voucher: [
            'reference_number' => $reference,
            'transaction_date' => $date,
            'particulars' => 'General Ledger opening/closing balance integrity QA',
            'source_type' => 'GeneralLedgerBalanceIntegrityQA',
            'source_id' => null,
        ],
        lines: [
            [
                'account_id' => $debitAccountId,
                'line_description' => 'QA debit movement',
                'debit' => $amount,
                'credit' => 0.00,
            ],
            [
                'account_id' => $creditAccountId,
                'line_description' => 'QA credit movement',
                'debit' => 0.00,
                'credit' => $amount,
            ],
        ],
        createdBy: $userId,
    );

    $ledger->approve($voucherId, $userId, $date . ' 09:00:00');
    $ledger->post($voucherId, $userId, $date . ' 09:05:00');

    return $voucherId;
}

try {
    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER OPENING/CLOSING BALANCE INTEGRITY TEST\n";
    echo "===============================================================\n";

    $periodFrom = '2099-01-01';
    $periodTo = '2099-01-03';

    $cashBaseline = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );
    $incomeBaseline = $ledger->generalLedger(
        accountId: $incomeAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );

    $cashOpening = (float) $cashBaseline['opening_balance'];
    $incomeOpening = (float) $incomeBaseline['opening_balance'];

    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        'GL-BAL-20990101-' . bin2hex(random_bytes(4)),
        '2099-01-01',
        $cashAccountId,
        $incomeAccountId,
        100.00,
    );

    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        'GL-BAL-20990102-' . bin2hex(random_bytes(4)),
        '2099-01-02',
        $incomeAccountId,
        $cashAccountId,
        40.00,
    );

    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        'GL-BAL-20990103-' . bin2hex(random_bytes(4)),
        '2099-01-03',
        $cashAccountId,
        $incomeAccountId,
        25.00,
    );

    $cashLedger = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );
    $incomeLedger = $ledger->generalLedger(
        accountId: $incomeAccountId,
        dateFrom: $periodFrom,
        dateTo: $periodTo,
    );

    assertNearValue(
        $cashOpening,
        (float) $cashLedger['opening_balance'],
        'Cash opening balance must remain the period opening balance.',
    );
    assertNearValue(
        $incomeOpening,
        (float) $incomeLedger['opening_balance'],
        'Income opening balance must remain the period opening balance.',
    );
    echo "Opening balances preserved                         ✓\n";

    $cashRows = $cashLedger['rows'];
    $incomeRows = $incomeLedger['rows'];

    assertSameValue(3, count($cashRows), 'Cash ledger must contain three QA movements.');
    assertSameValue(3, count($incomeRows), 'Income ledger must contain three QA movements.');

    $cashExpectedRunning = [
        $cashOpening + 100.00,
        $cashOpening + 60.00,
        $cashOpening + 85.00,
    ];

    $incomeExpectedRunning = [
        $incomeOpening + 100.00,
        $incomeOpening + 60.00,
        $incomeOpening + 85.00,
    ];

    foreach ($cashRows as $index => $row) {
        assertNearValue(
            $cashExpectedRunning[$index],
            (float) $row['running_balance'],
            'Cash running balance at movement ' . ($index + 1) . '.',
        );
    }

    foreach ($incomeRows as $index => $row) {
        assertNearValue(
            $incomeExpectedRunning[$index],
            (float) $row['running_balance'],
            'Income running balance at movement ' . ($index + 1) . '.',
        );
    }

    echo "Running balance sequence                           ✓\n";

    assertNearValue(
        $cashOpening + 85.00,
        (float) $cashLedger['closing_balance'],
        'Cash closing balance must equal opening balance plus net movement.',
    );
    assertNearValue(
        $incomeOpening + 85.00,
        (float) $incomeLedger['closing_balance'],
        'Income closing balance must equal opening balance plus net movement.',
    );
    echo "Closing balances reconcile                          ✓\n";

    assertNearValue(
        85.00,
        (float) $cashLedger['closing_balance'] - $cashOpening,
        'Cash net period movement.',
    );
    assertNearValue(
        85.00,
        (float) $incomeLedger['closing_balance'] - $incomeOpening,
        'Income net period movement.',
    );
    echo "Normal-balance direction                            ✓\n";

    $cashSingleDay = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2099-01-02',
        dateTo: '2099-01-02',
    );

    assertSameValue(1, count($cashSingleDay['rows']), 'Single-day ledger must contain only the Jan 2 movement.');
    assertNearValue(
        (float) $cashSingleDay['opening_balance'] - 40.00,
        (float) $cashSingleDay['closing_balance'],
        'Single-day cash closing balance must apply the Jan 2 credit movement.',
    );
    echo "Single-day balance progression                      ✓\n";

    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER OPENING/CLOSING BALANCE INTEGRITY TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($voucherIds !== []) {
        $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));

        $statement = $pdo->prepare(
            "DELETE FROM journal_lines WHERE journal_voucher_id IN ({$placeholders})"
        );
        $statement->execute($voucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers WHERE id IN ({$placeholders})"
        );
        $statement->execute($voucherIds);
    }
}
