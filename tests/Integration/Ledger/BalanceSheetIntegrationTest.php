<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Services\LedgerService;

$ledger = $app->container()->get(LedgerService::class);

$result = $ledger->balanceSheet();

$assets = (float) $result['total_assets'];
$liabilities = (float) $result['total_liabilities'];
$equity = (float) $result['total_equity'];
$liabilitiesAndEquity = (float) $result['liabilities_and_equity'];

if (abs($assets - $liabilitiesAndEquity) > 0.005) {
    throw new RuntimeException(
        sprintf(
            'Statement is unbalanced. Assets: %.2f, Liabilities + Equity: %.2f.',
            $assets,
            $liabilitiesAndEquity,
        )
    );
}

if ($result['balanced'] !== true) {
    throw new RuntimeException(
        'Balance Sheet result reported an unexpected unbalanced state.'
    );
}

if (abs($assets - ($liabilities + $equity)) > 0.005) {
    throw new RuntimeException(
        'Assets do not equal liabilities plus equity.'
    );
}

foreach (['assets', 'liabilities', 'equity'] as $section) {
    foreach ($result[$section] as $row) {
        if (abs((float) $row['balance']) < 0.005) {
            throw new RuntimeException(
                sprintf(
                    'Balance Sheet section %s contains a zero-balance row.',
                    $section,
                )
            );
        }
    }
}

echo PHP_EOL;
echo "================================================" . PHP_EOL;
echo "ACES BALANCE SHEET INTEGRATION TEST: PASS" . PHP_EOL;
echo "================================================" . PHP_EOL;
echo "Posted balances aggregated              ✓" . PHP_EOL;
echo "Income/expense rolled into surplus       ✓" . PHP_EOL;
echo "Assets = liabilities + equity            ✓" . PHP_EOL;
echo "Balanced state reported                  ✓" . PHP_EOL;
echo "================================================" . PHP_EOL;
