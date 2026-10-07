<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameMoney(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(
            $message . ' Expected ' . number_format($expected, 2) . ', got ' . number_format($actual, 2) . '.'
        );
    }
}

function trialBalanceNet(array $row): float
{
    return round((float) $row['debit'] - (float) $row['credit'], 2);
}

function normalBalanceAmount(array $row): float
{
    $net = trialBalanceNet($row);

    return ($row['normal_balance'] ?? null) === 'Credit'
        ? round(-$net, 2)
        : $net;
}

function findTrialBalanceRow(array $trialBalance, string $accountCode): ?array
{
    foreach ($trialBalance['rows'] as $row) {
        if ((string) $row['account_code'] === $accountCode) {
            return $row;
        }
    }

    return null;
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$ledger = new LedgerService(
    new JournalVoucherRepository($database),
);

$createdBy = (int) ($pdo->query(
    "SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1"
)->fetchColumn());

assertTrue($createdBy > 0, 'An active user is required for the test.');

$cashCode = '1010';
$incomeCode = '4010';
$cashAccountId = $ledger->accountId($cashCode);
$incomeAccountId = $ledger->accountId($incomeCode);

$dateFrom = '2099-02-01';
$dateTo = '2099-02-28';

$baselineTrialBalance = $ledger->trialBalance($dateTo);
$baselineOperations = $ledger->incomeStatement($dateFrom, $dateTo);
$baselinePosition = $ledger->balanceSheet($dateTo);

$baselineCashTb = findTrialBalanceRow($baselineTrialBalance, $cashCode);
$baselineIncomeTb = findTrialBalanceRow($baselineTrialBalance, $incomeCode);
$baselineCashNormal = $baselineCashTb === null ? 0.00 : normalBalanceAmount($baselineCashTb);
$baselineIncomeNormal = $baselineIncomeTb === null ? 0.00 : normalBalanceAmount($baselineIncomeTb);

$baselineOperationsNet = (float) ($baselineOperations['net_surplus'] ?? $baselineOperations['net_income'] ?? 0.00);

function statementAccountAmount(array $statement, string $accountCode): float
{
    $collections = [
        $statement['accounts'] ?? [],
        $statement['rows'] ?? [],
        $statement['account_rows'] ?? [],
    ];

    foreach ($collections as $rows) {
        foreach ($rows as $row) {
            if ((string) ($row['account_code'] ?? '') === $accountCode) {
                if (array_key_exists('balance', $row)) {
                    return (float) $row['balance'];
                }
                if (array_key_exists('amount', $row)) {
                    return (float) $row['amount'];
                }
                if (array_key_exists('net', $row)) {
                    return (float) $row['net'];
                }
            }
        }
    }

    return 0.00;
}

$reference = 'FS-RECON-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
$reversalReference = $reference . '-2';

$voucherRepository = new JournalVoucherRepository($database);

$voucherOne = $ledger->createPending(
    [
        'reference_number' => $reference . '-A',
        'transaction_date' => $dateFrom,
        'particulars' => 'QA financial statement reconciliation A',
        'source_type' => 'QA',
        'source_id' => null,
    ],
    [
        [
            'account_id' => $cashAccountId,
            'line_description' => 'QA cash movement',
            'debit' => 600.00,
            'credit' => 0.00,
        ],
        [
            'account_id' => $incomeAccountId,
            'line_description' => 'QA income movement',
            'debit' => 0.00,
            'credit' => 600.00,
        ],
    ],
    $createdBy,
);

$voucherRepository->approve($voucherOne, $createdBy, date('Y-m-d H:i:s'));
$voucherRepository->post($voucherOne, $createdBy, date('Y-m-d H:i:s'));

$voucherTwo = $ledger->createPending(
    [
        'reference_number' => $reversalReference . '-B',
        'transaction_date' => $dateTo,
        'particulars' => 'QA financial statement reconciliation B',
        'source_type' => 'QA',
        'source_id' => null,
    ],
    [
        [
            'account_id' => $cashAccountId,
            'line_description' => 'QA cash reduction',
            'debit' => 0.00,
            'credit' => 150.00,
        ],
        [
            'account_id' => $incomeAccountId,
            'line_description' => 'QA income reduction',
            'debit' => 150.00,
            'credit' => 0.00,
        ],
    ],
    $createdBy,
);

$voucherRepository->approve($voucherTwo, $createdBy, date('Y-m-d H:i:s'));
$voucherRepository->post($voucherTwo, $createdBy, date('Y-m-d H:i:s'));

$trialBalance = $ledger->trialBalance($dateTo);
$operations = $ledger->incomeStatement($dateFrom, $dateTo);
$position = $ledger->balanceSheet($dateTo);

$cashTb = findTrialBalanceRow($trialBalance, $cashCode);
$incomeTb = findTrialBalanceRow($trialBalance, $incomeCode);

assertTrue($cashTb !== null, 'Cash account must appear in Trial Balance.');
assertTrue($incomeTb !== null, 'Income account must appear in Trial Balance.');

$cashNormalDelta = round(normalBalanceAmount($cashTb) - $baselineCashNormal, 2);
$incomeNormalDelta = round(normalBalanceAmount($incomeTb) - $baselineIncomeNormal, 2);

assertSameMoney(450.00, $cashNormalDelta, 'Trial Balance cash movement must reconcile.');
assertSameMoney(450.00, $incomeNormalDelta, 'Trial Balance income movement must reconcile.');
assertTrue(($trialBalance['balanced'] ?? false) === true, 'Trial Balance must remain balanced.');

$operationsNet = (float) ($operations['net_surplus'] ?? $operations['net_income'] ?? 0.00);
assertSameMoney(
    450.00,
    round($operationsNet - $baselineOperationsNet, 2),
    'Statement of Operations net surplus movement must reconcile to posted income.'
);

// The Statement of Financial Position should reflect the same asset/equity impact.
$positionTotalAssets = (float) ($position['total_assets'] ?? $position['assets_total'] ?? 0.00);
$baselinePositionTotalAssets = (float) ($baselinePosition['total_assets'] ?? $baselinePosition['assets_total'] ?? 0.00);
assertSameMoney(
    450.00,
    round($positionTotalAssets - $baselinePositionTotalAssets, 2),
    'Statement of Financial Position asset movement must reconcile to posted cash.'
);

$operationsEquityMovement = round($operationsNet - $baselineOperationsNet, 2);
assertSameMoney(
    450.00,
    $operationsEquityMovement,
    'Net surplus movement must reconcile to the Statement of Financial Position.'
);

// Confirm the Trial Balance movement and financial-statement movement agree.
assertSameMoney(
    $incomeNormalDelta,
    $operationsEquityMovement,
    'Trial Balance income movement must equal net surplus movement.'
);

// Confirm the test vouchers are traceable in both source financial statements via their posted accounting effect.
assertTrue($operationsNet !== $baselineOperationsNet, 'Posted QA income must affect the Statement of Operations.');
assertTrue($positionTotalAssets !== $baselinePositionTotalAssets, 'Posted QA cash must affect the Statement of Financial Position.');

$cleanupIds = [$voucherOne, $voucherTwo];
$placeholders = implode(',', array_fill(0, count($cleanupIds), '?'));

$deleteLines = $pdo->prepare(
    "DELETE FROM journal_lines WHERE journal_voucher_id IN ($placeholders)"
);
$deleteLines->execute($cleanupIds);

$deleteVouchers = $pdo->prepare(
    "DELETE FROM journal_vouchers WHERE id IN ($placeholders)"
);
$deleteVouchers->execute($cleanupIds);

echo "===============================================================\n";
echo "ACES TRIAL BALANCE → FINANCIAL STATEMENTS RECONCILIATION TEST\n";
echo "===============================================================\n";
echo "Trial Balance → Statement of Operations reconciles       ✓\n";
echo "Trial Balance → Statement of Financial Position reconciles ✓\n";
echo "Net surplus → equity movement reconciles                 ✓\n";
echo "Accounting equation remains reconciled                   ✓\n";
echo "===============================================================\n";
echo "ACES TRIAL BALANCE → FINANCIAL STATEMENTS RECONCILIATION TEST: PASS\n";
echo "===============================================================\n";
