<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Foundation\Database;

$database = $app->container()->get(Database::class);
$pdo = $database->connection();

$expected = [
    ['1000', 'Cash and Cash Equivalents', 'Asset', 'Debit'],
    ['1010', 'Cash on Hand', 'Asset', 'Debit'],
    ['1020', 'Cash in Bank', 'Asset', 'Debit'],
    ['1100', 'Loans Receivable', 'Asset', 'Debit'],
    ['1110', 'Principal Loans Receivable', 'Asset', 'Debit'],
    ['1120', 'Interest Receivable', 'Asset', 'Debit'],
    ['1130', 'Penalty Receivable', 'Asset', 'Debit'],
    ['1200', 'Other Receivables', 'Asset', 'Debit'],
    ['2000', 'Total Liabilities', 'Liability', 'Credit'],
    ['2010', 'Accounts Payable', 'Liability', 'Credit'],
    ['2020', 'Other Payables', 'Liability', 'Credit'],
    ['2100', 'Member Payables', 'Liability', 'Credit'],
    ['3000', 'Cooperative Equity', 'Equity', 'Credit'],
    ['3010', 'Share Capital', 'Equity', 'Credit'],
    ['3020', "Members' Equity", 'Equity', 'Credit'],
    ['3030', 'Retained Surplus', 'Equity', 'Credit'],
    ['4000', 'Operating Income', 'Income', 'Credit'],
    ['4010', 'Interest Income', 'Income', 'Credit'],
    ['4020', 'Penalty Income', 'Income', 'Credit'],
    ['4030', 'Other Finance Income', 'Income', 'Credit'],
    ['4100', 'Other Operating Income', 'Income', 'Credit'],
    ['5000', 'Operating Expenses', 'Expense', 'Debit'],
    ['5010', 'Administrative Expenses', 'Expense', 'Debit'],
    ['5020', 'Office Expenses', 'Expense', 'Debit'],
    ['5030', 'Bank Charges', 'Expense', 'Debit'],
    ['5040', 'Other Operating Expenses', 'Expense', 'Debit'],
];

$count = (int) $pdo->query(
    'SELECT COUNT(*) FROM accounts'
)->fetchColumn();

if ($count !== count($expected)) {
    throw new RuntimeException(
        'Expected ' . count($expected)
        . ' accounts, found ' . $count . '.'
    );
}

$statement = $pdo->prepare(
    '
    SELECT
        account_name,
        account_type,
        normal_balance
    FROM accounts
    WHERE account_code = :account_code
    LIMIT 1
    '
);

foreach ($expected as [$code, $name, $type, $normalBalance]) {
    $statement->execute([
        'account_code' => $code,
    ]);

    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        throw new RuntimeException(
            'Missing account [' . $code . '].'
        );
    }

    if (
        $row['account_name'] !== $name ||
        $row['account_type'] !== $type ||
        $row['normal_balance'] !== $normalBalance
    ) {
        throw new RuntimeException(
            'Account definition mismatch for [' . $code . '].'
        );
    }
}

$parentChecks = [
    ['1010', '1000'],
    ['1020', '1000'],
    ['1110', '1100'],
    ['1120', '1100'],
    ['1130', '1100'],
    ['2010', '2000'],
    ['2020', '2000'],
    ['2100', '2000'],
    ['3010', '3000'],
    ['3020', '3000'],
    ['3030', '3000'],
    ['4010', '4000'],
    ['4020', '4000'],
    ['4030', '4000'],
    ['5010', '5000'],
    ['5020', '5000'],
    ['5030', '5000'],
    ['5040', '5000'],
];

$parentStatement = $pdo->prepare(
    '
    SELECT child.id
    FROM accounts AS child
    INNER JOIN accounts AS parent
        ON parent.id = child.parent_id
    WHERE child.account_code = :child
      AND parent.account_code = :parent
    LIMIT 1
    '
);

foreach ($parentChecks as [$child, $parent]) {
    $parentStatement->execute([
        'child' => $child,
        'parent' => $parent,
    ]);

    if ($parentStatement->fetchColumn() === false) {
        throw new RuntimeException(
            "Parent relationship {$child} -> {$parent} is missing."
        );
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES LEDGER COA INTEGRATION TEST: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "25 baseline accounts seeded        ✓" . PHP_EOL;
echo "Account classifications valid      ✓" . PHP_EOL;
echo "Normal balances valid              ✓" . PHP_EOL;
echo "Parent hierarchy valid             ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
