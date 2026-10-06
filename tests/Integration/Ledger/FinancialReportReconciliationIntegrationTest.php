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
        throw new RuntimeException(sprintf(
            '%s Expected %.2f, got %.2f.',
            $message,
            $expected,
            $actual,
        ));
    }
}

function accountNet(array $rows, string $accountCode, string $debitKey = 'debit', string $creditKey = 'credit'): float
{
    foreach ($rows as $row) {
        if (($row['account_code'] ?? null) === $accountCode) {
            return (float) $row[$debitKey] - (float) $row[$creditKey];
        }
    }

    return 0.00;
}

function balanceRow(array $rows, string $accountCode): float
{
    foreach ($rows as $row) {
        if (($row['account_code'] ?? null) === $accountCode) {
            return (float) $row['balance'];
        }
    }

    return 0.00;
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

$ledger = new LedgerService(new JournalVoucherRepository($database));
$cashAccountId = $ledger->accountId('1010');
$incomeAccountId = $ledger->accountId('4010');
$reportDate = '2026-10-06';
$amount = 947.00;
$voucherId = null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

try {
    echo "================================================================\n";
    echo "ACES FINANCIAL REPORT RECONCILIATION INTEGRATION TEST\n";
    echo "================================================================\n";

    $baselineTrial = $ledger->trialBalance($reportDate);
    $baselineIncome = $ledger->incomeStatement($reportDate, $reportDate);
    $baselineBalance = $ledger->balanceSheet($reportDate);

    $baselineTrialCash = accountNet($baselineTrial['rows'], '1010');
    $baselineTrialIncome = accountNet(
        $baselineTrial['rows'],
        '4010',
        'debit',
        'credit',
    ) * -1;
    $baselineIncomeTotal = (float) $baselineIncome['total_income'];
    $baselineNetSurplus = (float) $baselineBalance['net_surplus'];
    $baselineCashBalance = balanceRow($baselineBalance['assets'], '1010');
    $baselineAssets = (float) $baselineBalance['total_assets'];
    $baselineLiabilitiesAndEquity = (float) $baselineBalance['liabilities_and_equity'];

    $voucherId = $ledger->createPending(
        voucher: [
            'reference_number' => 'QA-RECON-' . bin2hex(random_bytes(4)),
            'transaction_date' => $reportDate,
            'particulars' => 'QA financial report reconciliation',
        ],
        lines: [
            [
                'account_id' => $cashAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA reconciliation cash',
                'debit' => $amount,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA reconciliation income',
                'debit' => 0.00,
                'credit' => $amount,
            ],
        ],
        createdBy: $userId,
    );

    $ledger->approve($voucherId, $userId, date('Y-m-d H:i:s'));
    $ledger->post($voucherId, $userId, date('Y-m-d H:i:s'));

    $trial = $ledger->trialBalance($reportDate);
    $income = $ledger->incomeStatement($reportDate, $reportDate);
    $balance = $ledger->balanceSheet($reportDate);

    $trialCash = accountNet($trial['rows'], '1010');
    $trialIncome = accountNet($trial['rows'], '4010', 'debit', 'credit') * -1;
    $incomeTotal = (float) $income['total_income'];
    $netSurplus = (float) $balance['net_surplus'];
    $cashBalance = balanceRow($balance['assets'], '1010');
    $assets = (float) $balance['total_assets'];
    $liabilitiesAndEquity = (float) $balance['liabilities_and_equity'];

    assertNear($baselineTrialCash + $amount, $trialCash,
        'Posted cash debit must flow identically into Trial Balance.');
    echo "Posted cash → Trial Balance reconciles ✓\n";

    assertNear($baselineTrialIncome + $amount, $trialIncome,
        'Posted income credit must flow identically into Trial Balance.');
    echo "Posted income → Trial Balance reconciles ✓\n";

    assertNear($baselineIncomeTotal + $amount, $incomeTotal,
        'Posted income must flow into Statement of Operations.');
    echo "Posted income → Statement of Operations reconciles ✓\n";

    assertNear($baselineCashBalance + $amount, $cashBalance,
        'Posted cash must flow into Statement of Financial Position.');
    echo "Posted cash → Statement of Financial Position reconciles ✓\n";

    assertNear($baselineNetSurplus + $amount, $netSurplus,
        'Posted income must flow into Statement of Financial Position net surplus.');
    echo "Net surplus → Statement of Financial Position reconciles ✓\n";

    assertNear($baselineAssets + $amount, $assets,
        'Posted transaction must increase total assets by its debit.');
    assertNear($baselineLiabilitiesAndEquity + $amount, $liabilitiesAndEquity,
        'Posted transaction must increase liabilities and equity by the same amount.');
    assertNear($assets, $liabilitiesAndEquity,
        'Statement of Financial Position must remain balanced.');
    assertNear((float) $trial['total_debit'], (float) $trial['total_credit'],
        'Trial Balance must remain balanced after posting.');
    echo "Cross-report accounting equation reconciles ✓\n";

    echo "================================================================\n";
    echo "ACES FINANCIAL REPORT RECONCILIATION INTEGRATION TEST: PASS\n";
    echo "================================================================\n";
} finally {
    if ($voucherId !== null) {
        $statement = $pdo->prepare(
            'DELETE FROM journal_lines WHERE journal_voucher_id = :id'
        );
        $statement->execute(['id' => $voucherId]);

        $statement = $pdo->prepare(
            'DELETE FROM journal_vouchers WHERE id = :id'
        );
        $statement->execute(['id' => $voucherId]);
    }

    unset($_SESSION['user_id']);
}
