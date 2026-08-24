<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Foundation\Database;

$database = $app->container()->get(Database::class);
$pdo = $database->connection();

$tables = [
    'accounts',
    'journal_vouchers',
    'journal_lines',
];

foreach ($tables as $table) {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = :table_name'
    );

    $statement->execute([
        'table_name' => $table,
    ]);

    if ((int) $statement->fetchColumn() !== 1) {
        throw new RuntimeException(
            "Missing Ledger table: {$table}"
        );
    }
}

$columns = [
    'accounts' => ['account_code', 'account_name', 'account_type', 'normal_balance'],
    'journal_vouchers' => [
        'reference_number',
        'transaction_date',
        'status',
        'created_by',
    ],
    'journal_lines' => [
        'journal_voucher_id',
        'account_id',
        'debit',
        'credit',
    ],
];

foreach ($columns as $table => $tableColumns) {
    foreach ($tableColumns as $column) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND column_name = :column_name'
        );

        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        if ((int) $statement->fetchColumn() !== 1) {
            throw new RuntimeException(
                "Missing {$table}.{$column}"
            );
        }
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES LEDGER SCHEMA INTEGRATION TEST: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Accounts table present              ✓" . PHP_EOL;
echo "Journal vouchers table present      ✓" . PHP_EOL;
echo "Journal lines table present         ✓" . PHP_EOL;
echo "Required columns present            ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
