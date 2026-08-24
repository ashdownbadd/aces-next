<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Services\LedgerService;

$ledger = $app->container()->get(LedgerService::class);

$trial = $ledger->trialBalance();
$income = $ledger->incomeStatement();
$balance = $ledger->balanceSheet();

$totalDebit = (float) $trial['total_debit'];
$totalCredit = (float) $trial['total_credit'];

if (abs($totalDebit - $totalCredit) > 0.005) {
    throw new RuntimeException(
        'Trial Balance is not balanced.'
    );
}

$expectedSurplus =
    (float) $income['total_income']
    - (float) $income['total_expenses'];

if (
    abs(
        $expectedSurplus
        - (float) $income['net_surplus']
    ) > 0.005
) {
    throw new RuntimeException(
        'Income Statement surplus does not reconcile.'
    );
}

if (
    abs(
        (float) $balance['total_assets']
        - (float) $balance['liabilities_and_equity']
    ) > 0.005
) {
    throw new RuntimeException(
        'Balance Sheet does not reconcile.'
    );
}

if (
    $trial['balanced'] !== true
    || $balance['balanced'] !== true
) {
    throw new RuntimeException(
        'One or more financial reports report an unexpected unbalanced state.'
    );
}

echo PHP_EOL;
echo "====================================================" . PHP_EOL;
echo "ACES LEDGER REPORTING INTEGRATION TEST: PASS" . PHP_EOL;
echo "====================================================" . PHP_EOL;
echo "Trial Balance reconciles                    ✓" . PHP_EOL;
echo "Income Statement reconciles                 ✓" . PHP_EOL;
echo "Statement of Financial Position reconciles  ✓" . PHP_EOL;
echo "Reporting chain is internally consistent    ✓" . PHP_EOL;
echo "====================================================" . PHP_EOL;
