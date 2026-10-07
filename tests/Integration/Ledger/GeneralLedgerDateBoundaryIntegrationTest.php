<?php

declare(strict_types=1);

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

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
    if (abs($expected - $actual) >= 0.005) {
        throw new RuntimeException(sprintf(
            '%s Expected %.2f, got %.2f.',
            $message,
            $expected,
            $actual,
        ));
    }
}

function assertThrows(
    callable $callback,
    string $message,
    ?string $expectedMessage = null,
): void {
    try {
        $callback();
    } catch (Throwable $exception) {
        if (
            $expectedMessage !== null
            && !str_contains($exception->getMessage(), $expectedMessage)
        ) {
            throw new RuntimeException(sprintf(
                '%s Expected exception containing "%s", got "%s".',
                $message,
                $expectedMessage,
                $exception->getMessage(),
            ));
        }

        return;
    }

    throw new RuntimeException($message);
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

$cashAccountId = $repository->accountId('1010');
$incomeAccountId = $repository->accountId('4010');

if ($cashAccountId <= 0 || $incomeAccountId <= 0) {
    throw new RuntimeException('Required test accounts 1010 and 4010 were not found.');
}

$voucherIds = [];
$prefix = 'GL-DATE-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

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
            'particulars' => 'General Ledger date boundary QA',
            'source_type' => null,
            'source_id' => null,
        ],
        lines: [
            [
                'account_id' => $debitAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'General Ledger date boundary QA',
                'debit' => $amount,
                'credit' => 0.00,
            ],
            [
                'account_id' => $creditAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'General Ledger date boundary QA',
                'debit' => 0.00,
                'credit' => $amount,
            ],
        ],
        createdBy: $userId,
    );

    $ledger->approve(
        $voucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );
    $ledger->post(
        $voucherId,
        $userId,
        date('Y-m-d H:i:s'),
    );

    return $voucherId;
}

function createPendingVoucher(
    LedgerService $ledger,
    int $userId,
    string $reference,
    string $date,
    int $debitAccountId,
    int $creditAccountId,
    float $amount,
): int {
    return $ledger->createPending(
        voucher: [
            'reference_number' => $reference,
            'transaction_date' => $date,
            'particulars' => 'General Ledger status boundary QA',
            'source_type' => null,
            'source_id' => null,
        ],
        lines: [
            [
                'account_id' => $debitAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'General Ledger status boundary QA',
                'debit' => $amount,
                'credit' => 0.00,
            ],
            [
                'account_id' => $creditAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'General Ledger status boundary QA',
                'debit' => 0.00,
                'credit' => $amount,
            ],
        ],
        createdBy: $userId,
    );
}

try {
    $baseline = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-10-01',
        dateTo: '2026-10-31',
    );

    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER DATE BOUNDARY INTEGRATION TEST\n";
    echo "===============================================================\n";

    // Establish a known opening balance before the selected period.
    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        $prefix . '-OPENING',
        '2026-09-30',
        $cashAccountId,
        $incomeAccountId,
        100.00,
    );

    // Selected-period boundaries: both must be included.
    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        $prefix . '-START',
        '2026-10-01',
        $cashAccountId,
        $incomeAccountId,
        50.00,
    );

    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        $prefix . '-END',
        '2026-10-31',
        $incomeAccountId,
        $cashAccountId,
        20.00,
    );

    // Must not appear in the selected period.
    $voucherIds[] = createPostedVoucher(
        $ledger,
        $userId,
        $prefix . '-AFTER',
        '2026-11-01',
        $cashAccountId,
        $incomeAccountId,
        999.00,
    );

    // Non-posted activity must never enter the General Ledger.
    $pendingId = createPendingVoucher(
        $ledger,
        $userId,
        $prefix . '-PENDING',
        '2026-10-15',
        $cashAccountId,
        $incomeAccountId,
        300.00,
    );
    $voucherIds[] = $pendingId;

    $approvedId = createPendingVoucher(
        $ledger,
        $userId,
        $prefix . '-APPROVED',
        '2026-10-16',
        $cashAccountId,
        $incomeAccountId,
        400.00,
    );
    $ledger->approve($approvedId, $userId, date('Y-m-d H:i:s'));
    $voucherIds[] = $approvedId;

    $rejectedId = createPendingVoucher(
        $ledger,
        $userId,
        $prefix . '-REJECTED',
        '2026-10-17',
        $cashAccountId,
        $incomeAccountId,
        500.00,
    );
    $ledger->reject($rejectedId, 'General Ledger date boundary QA');
    $voucherIds[] = $rejectedId;

    $result = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-10-01',
        dateTo: '2026-10-31',
    );

    assertNear(
        100.00,
        (float) $result['opening_balance'],
        'Opening balance must include posted activity before date_from only.',
    );
    echo "Opening balance before Oct 1                    ✓\n";

    $rows = $result['rows'];

    $testRows = array_values(array_filter(
        $rows,
        static fn (array $row): bool => str_starts_with(
            (string) ($row['reference_number'] ?? ''),
            $prefix,
        ),
    ));

    assertSameValue(
        2,
        count($testRows),
        'Only the Oct 1 and Oct 31 test vouchers should appear in the selected period.',
    );

    $testReferences = array_map(
        static fn (array $row): string => (string) $row['reference_number'],
        $testRows,
    );

    assertSameValue(
        [$prefix . '-START', $prefix . '-END'],
        $testReferences,
        'Only the selected-period Posted test vouchers should be returned.',
    );

    assertSameValue('2026-10-01', (string) $testRows[0]['transaction_date'], 'Oct 1 must be included.');
    assertSameValue('2026-10-31', (string) $testRows[1]['transaction_date'], 'Oct 31 must be included.');
    echo "Oct 1 → included                           ✓\n";
    echo "Oct 31 → included                          ✓\n";
    echo "Sep 30 → opening only                      ✓\n";
    echo "Nov 1 → excluded                           ✓\n";
    echo "Pending/Approved/Rejected → excluded      ✓\n";

    $baselineOpening = (float) $baseline['opening_balance'];
    $baselineClosing = (float) $baseline['closing_balance'];

    assertNear(
        $baselineOpening + 100.00 + 50.00,
        (float) $testRows[0]['running_balance'],
        'Oct 1 running balance must include the pre-existing opening baseline plus the Sep 30 and Oct 1 test movements.',
    );
    assertNear(
        $baselineClosing + 100.00 + 30.00,
        (float) $testRows[1]['running_balance'],
        'Oct 31 running balance must equal the pre-existing October closing balance plus the Sep 30 and October test-period net movements.',
    );
    assertNear(
        $baselineClosing + 100.00 + 30.00,
        (float) $result['closing_balance'],
        'Closing balance must equal the pre-existing baseline plus the Sep 30 opening movement and October test-period net movement.',
    );
    echo "Running balance sequence                   ✓\n";
    echo "Closing balance                            ✓\n";

    assertThrows(
        fn() => $ledger->generalLedger(
            accountId: $cashAccountId,
            dateFrom: '2026-10-32',
            dateTo: '2026-10-31',
        ),
        'Invalid date_from must be rejected.',
        'Start date must be a valid YYYY-MM-DD date.',
    );
    echo "Invalid date boundary rejected             ✓\n";

    assertThrows(
        fn() => $ledger->generalLedger(
            accountId: $cashAccountId,
            dateFrom: '2026-11-01',
            dateTo: '2026-10-31',
        ),
        'date_from later than date_to must be rejected.',
        'Start date cannot be later than end date.',
    );
    echo "Reversed date range rejected               ✓\n";

    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER DATE BOUNDARY INTEGRATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($voucherIds !== []) {
        $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));

        $statement = $pdo->prepare(
            "DELETE FROM journal_lines WHERE journal_voucher_id IN ($placeholders)"
        );
        $statement->execute($voucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers WHERE id IN ($placeholders)"
        );
        $statement->execute($voucherIds);
    }
}
