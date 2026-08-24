<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Services\LedgerService;

$ledger = $app->container()->get(LedgerService::class);

$result = $ledger->trialBalance();

$totalDebit = (float) $result['total_debit'];
$totalCredit = (float) $result['total_credit'];

if (abs($totalDebit - $totalCredit) > 0.005) {
    throw new RuntimeException(
        sprintf(
            'Trial Balance is unbalanced. Debit: %.2f, Credit: %.2f.',
            $totalDebit,
            $totalCredit,
        )
    );
}

if ($result['balanced'] !== true) {
    throw new RuntimeException(
        'Trial Balance result reported an unexpected unbalanced state.'
    );
}

foreach ($result['rows'] as $row) {
    $debit = (float) $row['debit'];
    $credit = (float) $row['credit'];

    if ($debit > 0.005 && $credit > 0.005) {
        throw new RuntimeException(
            sprintf(
                'Account %s has both debit and credit ending balances.',
                $row['account_code'],
            )
        );
    }

    if (abs($debit) < 0.005 && abs($credit) < 0.005) {
        throw new RuntimeException(
            sprintf(
                'Account %s has a zero Trial Balance row.',
                $row['account_code'],
            )
        );
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES TRIAL BALANCE INTEGRATION TEST: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Posted account balances aggregated      ✓" . PHP_EOL;
echo "Every account has one ending side       ✓" . PHP_EOL;
echo "Total debit = total credit              ✓" . PHP_EOL;
echo "Balanced state reported                  ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
