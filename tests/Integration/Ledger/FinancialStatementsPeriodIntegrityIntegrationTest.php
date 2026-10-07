<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

function assertSameMoney(float $expected, float $actual, string $message): void
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

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function uniqueReference(string $label): string
{
    return 'FS-PERIOD-' . $label . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
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

$ledgerRepository = new JournalVoucherRepository($database);
$ledger = new LedgerService($ledgerRepository);

$cashAccountId = $ledger->accountId('1010');
$incomeAccountId = $ledger->accountId('4010');

$periodStart = '2099-02-01';
$periodEnd = '2099-02-28';
$priorDate = '2099-01-31';
$laterDate = '2099-03-01';

$baselineOperations = $ledger->incomeStatement($periodStart, $periodEnd);
$baselinePosition = $ledger->balanceSheet($periodEnd);

$baselineAssets = (float) $baselinePosition['total_assets'];
$baselineOperationsSurplus = (float) $baselineOperations['net_surplus'];
$baselinePositionSurplus = (float) $baselinePosition['net_surplus'];

$voucherIds = [];

function postQaVoucher(
    LedgerService $ledger,
    JournalVoucherRepository $repository,
    int $userId,
    int $cashAccountId,
    int $incomeAccountId,
    string $reference,
    string $date,
    float $amount,
): int {
    $voucherId = $ledger->createPending(
        voucher: [
            'reference_number' => $reference,
            'transaction_date' => $date,
            'particulars' => 'Financial statements period integrity QA',
            'source_type' => 'IntegrationTest',
            'source_id' => null,
        ],
        lines: [
            [
                'account_id' => $cashAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA cash movement',
                'debit' => $amount,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA income movement',
                'debit' => 0.00,
                'credit' => $amount,
            ],
        ],
        createdBy: $userId,
    );

    $repository->approve($voucherId, $userId, $date . ' 09:00:00');
    $repository->post($voucherId, $userId, $date . ' 09:01:00');

    return $voucherId;
}

try {
    echo "===============================================================\n";
    echo "ACES FINANCIAL STATEMENTS PERIOD INTEGRITY TEST\n";
    echo "===============================================================\n";

    // Prior-period activity: must be excluded from the February Statement of Operations.
    $voucherIds[] = postQaVoucher(
        $ledger,
        $ledgerRepository,
        $userId,
        $cashAccountId,
        $incomeAccountId,
        uniqueReference('PRIOR'),
        $priorDate,
        300.00,
    );

    // Current-period activity: must be included in February reports.
    $voucherIds[] = postQaVoucher(
        $ledger,
        $ledgerRepository,
        $userId,
        $cashAccountId,
        $incomeAccountId,
        uniqueReference('CURRENT'),
        '2099-02-10',
        450.00,
    );

    // Later activity: must be excluded from February reports.
    $voucherIds[] = postQaVoucher(
        $ledger,
        $ledgerRepository,
        $userId,
        $cashAccountId,
        $incomeAccountId,
        uniqueReference('LATER'),
        $laterDate,
        700.00,
    );

    $operations = $ledger->incomeStatement($periodStart, $periodEnd);
    $position = $ledger->balanceSheet($periodEnd);

    assertSameMoney(
        $baselineOperationsSurplus + 450.00,
        (float) $operations['net_surplus'],
        'Statement of Operations must include only current-period QA income.',
    );
    echo "Statement of Operations → current period only      ✓\n";

    assertSameMoney(
        $baselineAssets + 750.00,
        (float) $position['total_assets'],
        'Statement of Financial Position must include prior and current activity as of period end.',
    );
    echo "Statement of Financial Position → cumulative as-of ✓\n";

    assertSameMoney(
        $baselinePositionSurplus + 750.00,
        (float) $position['net_surplus'],
        'Financial Position net surplus must include posted income through the reporting date.',
    );
    echo "Financial Position net surplus → cumulative        ✓\n";

    assertSameMoney(
        450.00,
        (float) $operations['net_surplus'] - $baselineOperationsSurplus,
        'February Operations movement must exclude prior and later QA activity.',
    );
    echo "Prior/later activity excluded from Operations     ✓\n";

    assertTrue(
        (bool) $position['balanced'],
        'Statement of Financial Position must remain balanced.',
    );
    echo "Accounting equation remains reconciled             ✓\n";

    echo "===============================================================\n";
    echo "ACES FINANCIAL STATEMENTS PERIOD INTEGRITY TEST: PASS\n";
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
