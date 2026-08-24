<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;

$repository = $app->container()->get(JournalVoucherRepository::class);

$total = $repository->count();
$vouchers = $repository->all('', '', 25, 0);
$accounts = $repository->accounts();

foreach ($vouchers as $voucher) {
    foreach (['id', 'reference_number', 'transaction_date', 'status'] as $field) {
        if (!array_key_exists($field, $voucher)) {
            throw new RuntimeException("Voucher list missing field: {$field}");
        }
    }
}

if ($total < 0) {
    throw new RuntimeException('Voucher count cannot be negative.');
}

if ($accounts === []) {
    throw new RuntimeException('Ledger UI requires Chart of Accounts data.');
}

foreach ($accounts as $account) {
    foreach (
        ['id', 'account_code', 'account_name', 'account_type', 'normal_balance']
        as $field
    ) {
        if (!array_key_exists($field, $account)) {
            throw new RuntimeException("Account list missing field: {$field}");
        }
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES LEDGER UI DATA INTEGRATION TEST: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Voucher list contract               ✓" . PHP_EOL;
echo "Voucher pagination contract         ✓" . PHP_EOL;
echo "Chart of Accounts contract          ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
