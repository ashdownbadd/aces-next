<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

function assertNear(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(
            sprintf(
                '%s Expected %.2f, got %.2f.',
                $message,
                $expected,
                $actual,
            )
        );
    }
}

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
$ledgerService = new LedgerService($ledgerRepository);

$cashAccountId = $ledgerService->accountId('1010');
$incomeAccountId = $ledgerService->accountId('4010');

if ($cashAccountId <= 0 || $incomeAccountId <= 0) {
    throw new RuntimeException(
        'Required test accounts 1010 and 4010 were not found.'
    );
}

$voucherIds = [];
$amount = 100.00;

$incomeTotal = static function (array $report): float {
    return (float) ($report['total_income'] ?? 0.00);
};

$accountNetBalance = static function (array $rows, int $accountId): float {
    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $accountId) {
            return round(
                (float) ($row['debit'] ?? 0.00)
                - (float) ($row['credit'] ?? 0.00),
                2,
            );
        }
    }

    return 0.00;
};

$incomeBeforeOctober = $incomeTotal(
    $ledgerService->incomeStatement('2026-10-01', '2026-10-31'),
);
$incomeBeforeNovember = $incomeTotal(
    $ledgerService->incomeStatement('2026-11-01', '2026-11-01'),
);
$trialBalanceBefore = $ledgerService->trialBalance('2026-10-31');
$balanceSheetBefore = $ledgerService->balanceSheet('2026-10-31');

$cashTrialBalanceBefore = $accountNetBalance(
    $trialBalanceBefore['rows'],
    $cashAccountId,
);
$incomeTrialBalanceBefore = $accountNetBalance(
    $trialBalanceBefore['rows'],
    $incomeAccountId,
);
function accountBalance(array $rows, int $accountId, string $field): float
{
    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $accountId) {
            return round((float) ($row[$field] ?? 0.00), 2);
        }
    }

    return 0.00;
}

$cashBalanceSheetBefore = accountBalance(
    $balanceSheetBefore['assets'],
    $cashAccountId,
    'balance',
);

try {
    echo "===============================================================\n";
    echo "ACES FINANCIAL REPORT DATE BOUNDARY INTEGRATION TEST\n";
    echo "===============================================================\n";

    $dates = [
        '2026-09-30',
        '2026-10-01',
        '2026-10-31',
        '2026-11-01',
    ];

    foreach ($dates as $index => $date) {
        $voucherIds[] = $voucherId = $ledgerService->createPending(
            voucher: [
                'reference_number' => sprintf(
                    'QA-DATE-%s-%d',
                    str_replace('-', '', $date),
                    $index + 1,
                ),
                'transaction_date' => $date,
                'particulars' => sprintf(
                    'Financial report date boundary test - %s',
                    $date,
                ),
                'source_type' => 'QA',
                'source_id' => null,
            ],
            lines: [
                [
                    'account_id' => $cashAccountId,
                    'member_id' => null,
                    'loan_id' => null,
                    'line_description' => 'QA date boundary cash debit',
                    'debit' => $amount,
                    'credit' => 0.00,
                ],
                [
                    'account_id' => $incomeAccountId,
                    'member_id' => null,
                    'loan_id' => null,
                    'line_description' => 'QA date boundary income credit',
                    'debit' => 0.00,
                    'credit' => $amount,
                ],
            ],
            createdBy: $userId,
        );

        $ledgerService->approve(
            $voucherId,
            $userId,
            $date . ' 09:00:00',
        );

        $ledgerService->post(
            $voucherId,
            $userId,
            $date . ' 09:01:00',
        );
    }

    $incomeOctober = $ledgerService->incomeStatement(
        '2026-10-01',
        '2026-10-31',
    );

    assertNear(
        $incomeBeforeOctober + 200.00,
        (float) $incomeOctober['total_income'],
        'October Income Statement must include Oct 1 and Oct 31 only.',
    );

    assertNear(
        0.00,
        (float) $incomeOctober['total_expenses'],
        'October Income Statement expenses.',
    );

    $trialBalanceOctober = $ledgerService->trialBalance('2026-10-31');

    $cashOctober = $accountNetBalance(
        $trialBalanceOctober['rows'],
        $cashAccountId,
    );
    $incomeOctoberBalance = $accountNetBalance(
        $trialBalanceOctober['rows'],
        $incomeAccountId,
    );

    assertNear(
        $cashTrialBalanceBefore + 300.00,
        $cashOctober,
        'Trial Balance cash net balance as of Oct 31 must include Sep 30, Oct 1, and Oct 31.',
    );

    assertNear(
        $incomeTrialBalanceBefore - 300.00,
        $incomeOctoberBalance,
        'Trial Balance income net balance as of Oct 31 must include Sep 30, Oct 1, and Oct 31.',
    );

    $balanceSheetOctober = $ledgerService->balanceSheet('2026-10-31');

    $cashBalance = 0.00;

    foreach ($balanceSheetOctober['assets'] as $row) {
        if ((int) $row['id'] === $cashAccountId) {
            $cashBalance = (float) $row['balance'];
        }
    }

    assertNear(
        $cashBalanceSheetBefore + 300.00,
        $cashBalance,
        'Balance Sheet as of Oct 31 must exclude the Nov 1 voucher.',
    );

    $incomeNovember = $ledgerService->incomeStatement(
        '2026-11-01',
        '2026-11-01',
    );

    assertNear(
        $incomeBeforeNovember + 100.00,
        (float) $incomeNovember['total_income'],
        'Nov 1-only Income Statement must include the Nov 1 voucher.',
    );

    echo "Sep 30 → October range excluded           ✓\n";
    echo "Oct 1 → October range included             ✓\n";
    echo "Oct 31 → October range included            ✓\n";
    echo "Nov 1 → October range excluded             ✓\n";
    echo "Trial Balance as-of boundary               ✓\n";
    echo "Balance Sheet as-of boundary               ✓\n";
    echo "Nov 1 single-day range                     ✓\n";
    echo "===============================================================\n";
    echo "ACES FINANCIAL REPORT DATE BOUNDARY INTEGRATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($voucherIds !== []) {
        $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));

        $deleteLines = $pdo->prepare(
            "DELETE FROM journal_lines
             WHERE journal_voucher_id IN ($placeholders)"
        );
        $deleteLines->execute($voucherIds);

        $deleteVouchers = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE id IN ($placeholders)"
        );
        $deleteVouchers->execute($voucherIds);
    }
}
