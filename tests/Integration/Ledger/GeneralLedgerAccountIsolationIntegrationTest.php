<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;

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

function assertNear(float $expected, float $actual, string $message): void
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

function assertThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    throw new RuntimeException($message);
}

echo "===============================================================\n";
echo "ACES GENERAL LEDGER ACCOUNT ISOLATION INTEGRATION TEST\n";
echo "===============================================================\n";

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

if ($cashAccountId <= 0 || $incomeAccountId <= 0) {
    throw new RuntimeException(
        'Required test accounts 1010 and 4010 were not found.'
    );
}

$prefix = 'GL-ACCOUNT-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

$voucherIds = [];

try {
    /*
     * Capture the opening balance for the exact period under test.
     * Using an all-time closing balance here would be incorrect because
     * transactions after the test period must not affect this period's
     * closing balance.
     */
    $cashBaseline = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-10-20',
        dateTo: '2026-10-21',
    );

    $incomeBaseline = $ledger->generalLedger(
        accountId: $incomeAccountId,
        dateFrom: '2026-10-20',
        dateTo: '2026-10-21',
    );

    $cashBaselineOpening = (float) $cashBaseline['opening_balance'];
    $incomeBaselineOpening = (float) $incomeBaseline['opening_balance'];

    $cashVoucherId = $ledger->createPending(
        voucher: [
            'reference_number' => $prefix . '-CASH',
            'transaction_date' => '2026-10-20',
            'particulars' => 'General Ledger account isolation cash test',
            'source_type' => 'QA',
            'source_id' => null,
        ],
        lines: [
            [
                'account_id' => $cashAccountId,
                'line_description' => 'Cash isolation debit',
                'debit' => 250.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'line_description' => 'Income isolation credit',
                'debit' => 0.00,
                'credit' => 250.00,
            ],
        ],
        createdBy: $userId,
    );

    $voucherIds[] = $cashVoucherId;

    $repository->approve(
        $cashVoucherId,
        $userId,
        '2026-10-20 09:00:00',
    );

    $repository->post(
        $cashVoucherId,
        $userId,
        '2026-10-20 09:01:00',
    );

    $incomeVoucherId = $ledger->createPending(
        voucher: [
            'reference_number' => $prefix . '-INCOME',
            'transaction_date' => '2026-10-21',
            'particulars' => 'General Ledger account isolation income test',
            'source_type' => 'QA',
            'source_id' => null,
        ],
        lines: [
            [
                'account_id' => $incomeAccountId,
                'line_description' => 'Income isolation debit',
                'debit' => 75.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $cashAccountId,
                'line_description' => 'Cash isolation credit',
                'debit' => 0.00,
                'credit' => 75.00,
            ],
        ],
        createdBy: $userId,
    );

    $voucherIds[] = $incomeVoucherId;

    $repository->approve(
        $incomeVoucherId,
        $userId,
        '2026-10-21 09:00:00',
    );

    $repository->post(
        $incomeVoucherId,
        $userId,
        '2026-10-21 09:01:00',
    );

    $cashLedger = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-10-20',
        dateTo: '2026-10-21',
    );

    $incomeLedger = $ledger->generalLedger(
        accountId: $incomeAccountId,
        dateFrom: '2026-10-20',
        dateTo: '2026-10-21',
    );

    $cashRows = $cashLedger['rows'];
    $incomeRows = $incomeLedger['rows'];

    assertSameValue(
        2,
        count($cashRows),
        'Cash General Ledger must contain exactly the two test movements.',
    );

    assertSameValue(
        $cashVoucherId,
        (int) $cashRows[0]['voucher_id'],
        'Cash General Ledger must include the cash test voucher first.',
    );

    assertSameValue(
        $incomeVoucherId,
        (int) $cashRows[1]['voucher_id'],
        'Cash General Ledger must include the income test voucher second.',
    );

    assertNear(
        250.00,
        (float) $cashRows[0]['debit'],
        'Cash account must receive the expected debit.',
    );

    assertNear(
        75.00,
        (float) $cashRows[1]['credit'],
        'Cash account must receive the expected credit.',
    );

    assertNear(
        $cashBaselineOpening + 175.00,
        (float) $cashLedger['closing_balance'],
        'Cash closing balance must include only cash-account test movements.',
    );

    echo "Cash account isolation                         ✓\n";

    assertSameValue(
        2,
        count($incomeRows),
        'Income General Ledger must contain exactly the two test movements.',
    );

    assertSameValue(
        $cashVoucherId,
        (int) $incomeRows[0]['voucher_id'],
        'Income General Ledger must include the cash test voucher first.',
    );

    assertSameValue(
        $incomeVoucherId,
        (int) $incomeRows[1]['voucher_id'],
        'Income General Ledger must include the income test voucher second.',
    );

    assertNear(
        250.00,
        (float) $incomeRows[0]['credit'],
        'Income account must receive the expected credit.',
    );

    assertNear(
        75.00,
        (float) $incomeRows[1]['debit'],
        'Income account must receive the expected debit.',
    );

    assertNear(
        $incomeBaselineOpening + 175.00,
        (float) $incomeLedger['closing_balance'],
        'Income closing balance must include only income-account test movements.',
    );

    echo "Income account isolation                       ✓\n";

    /*
     * The same vouchers appear in both ledgers because they are double-entry
     * vouchers, but each ledger row must represent only the selected account's
     * journal line. The debit/credit assertions above verify this isolation.
     */
    echo "Selected account line isolation                ✓\n";

    assertThrows(
        fn() => $ledger->generalLedger(
            accountId: 999999999,
            dateFrom: '2026-10-20',
            dateTo: '2026-10-21',
        ),
        'A nonexistent account must be rejected.',
    );

    echo "Invalid account rejected                       ✓\n";

    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER ACCOUNT ISOLATION INTEGRATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($voucherIds !== []) {
        $placeholders = implode(
            ',',
            array_fill(0, count($voucherIds), '?'),
        );

        $statement = $pdo->prepare(
            "DELETE FROM journal_lines
             WHERE journal_voucher_id IN ($placeholders)"
        );
        $statement->execute($voucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE id IN ($placeholders)"
        );
        $statement->execute($voucherIds);
    }
}
