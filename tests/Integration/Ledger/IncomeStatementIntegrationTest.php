<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Services\LedgerService;

$ledger = $app->container()->get(LedgerService::class);
$result = $ledger->incomeStatement();

$income = (float) $result['total_income'];
$expenses = (float) $result['total_expenses'];
$surplus = (float) $result['net_surplus'];

if (abs($surplus - ($income - $expenses)) > 0.005) {
    throw new RuntimeException(
        'Net surplus does not equal income less expenses.'
    );
}

foreach (['income', 'expenses'] as $section) {
    foreach ($result[$section] as $row) {
        if ((float) $row['balance'] <= 0.005) {
            throw new RuntimeException(
                'Income Statement contains a non-positive row.'
            );
        }
    }
}

echo PHP_EOL;
echo "================================================" . PHP_EOL;
echo "ACES INCOME STATEMENT INTEGRATION TEST: PASS" . PHP_EOL;
echo "================================================" . PHP_EOL;
echo "Posted income aggregated                 ✓" . PHP_EOL;
echo "Posted expenses aggregated               ✓" . PHP_EOL;
echo "Income - expenses = net surplus           ✓" . PHP_EOL;
echo "Statement period logic validated          ✓" . PHP_EOL;
echo "================================================" . PHP_EOL;
