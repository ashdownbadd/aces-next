<?php

declare(strict_types=1);

namespace App\Console\Seeders;

use PDO;
use Throwable;

final class LedgerSeeder extends Seeder
{
    /**
     * @var array<int, array{
     *   code: string,
     *   name: string,
     *   type: string,
     *   normal_balance: string,
     *   parent: string|null
     * }>
     */
    private const ACCOUNTS = [
        [
            'code' => '1000',
            'name' => 'Cash and Cash Equivalents',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => null,
        ],
        [
            'code' => '1010',
            'name' => 'Cash on Hand',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => '1000',
        ],
        [
            'code' => '1020',
            'name' => 'Cash in Bank',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => '1000',
        ],
        [
            'code' => '1100',
            'name' => 'Loans Receivable',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => null,
        ],
        [
            'code' => '1110',
            'name' => 'Principal Loans Receivable',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => '1100',
        ],
        [
            'code' => '1120',
            'name' => 'Interest Receivable',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => '1100',
        ],
        [
            'code' => '1130',
            'name' => 'Penalty Receivable',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => '1100',
        ],
        [
            'code' => '1200',
            'name' => 'Other Receivables',
            'type' => 'Asset',
            'normal_balance' => 'Debit',
            'parent' => null,
        ],
        [
            'code' => '2000',
            'name' => 'Total Liabilities',
            'type' => 'Liability',
            'normal_balance' => 'Credit',
            'parent' => null,
        ],
        [
            'code' => '2010',
            'name' => 'Accounts Payable',
            'type' => 'Liability',
            'normal_balance' => 'Credit',
            'parent' => '2000',
        ],
        [
            'code' => '2020',
            'name' => 'Other Payables',
            'type' => 'Liability',
            'normal_balance' => 'Credit',
            'parent' => '2000',
        ],
        [
            'code' => '2100',
            'name' => 'Member Payables',
            'type' => 'Liability',
            'normal_balance' => 'Credit',
            'parent' => '2000',
        ],
        [
            'code' => '3000',
            'name' => 'Cooperative Equity',
            'type' => 'Equity',
            'normal_balance' => 'Credit',
            'parent' => null,
        ],
        [
            'code' => '3010',
            'name' => 'Share Capital',
            'type' => 'Equity',
            'normal_balance' => 'Credit',
            'parent' => '3000',
        ],
        [
            'code' => '3020',
            'name' => "Members' Equity",
            'type' => 'Equity',
            'normal_balance' => 'Credit',
            'parent' => '3000',
        ],
        [
            'code' => '3030',
            'name' => 'Retained Surplus',
            'type' => 'Equity',
            'normal_balance' => 'Credit',
            'parent' => '3000',
        ],
        [
            'code' => '4000',
            'name' => 'Operating Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => null,
        ],
        [
            'code' => '4010',
            'name' => 'Interest Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => '4000',
        ],
        [
            'code' => '4020',
            'name' => 'Penalty Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => '4000',
        ],
        [
            'code' => '4030',
            'name' => 'Other Finance Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => '4000',
        ],
        [
            'code' => '4040',
            'name' => 'Processing Fee Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => '4000',
        ],
        [
            'code' => '4050',
            'name' => 'Insurance Recovery Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => '4000',
        ],
        [
            'code' => '4060',
            'name' => 'Notarial Fee Recovery Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => '4000',
        ],
        [
            'code' => '4100',
            'name' => 'Other Operating Income',
            'type' => 'Income',
            'normal_balance' => 'Credit',
            'parent' => null,
        ],
        [
            'code' => '5000',
            'name' => 'Operating Expenses',
            'type' => 'Expense',
            'normal_balance' => 'Debit',
            'parent' => null,
        ],
        [
            'code' => '5010',
            'name' => 'Administrative Expenses',
            'type' => 'Expense',
            'normal_balance' => 'Debit',
            'parent' => '5000',
        ],
        [
            'code' => '5020',
            'name' => 'Office Expenses',
            'type' => 'Expense',
            'normal_balance' => 'Debit',
            'parent' => '5000',
        ],
        [
            'code' => '5030',
            'name' => 'Bank Charges',
            'type' => 'Expense',
            'normal_balance' => 'Debit',
            'parent' => '5000',
        ],
        [
            'code' => '5040',
            'name' => 'Other Operating Expenses',
            'type' => 'Expense',
            'normal_balance' => 'Debit',
            'parent' => '5000',
        ],
    ];

    public function run(): void
    {
        $pdo = $this->database->connection();

        $pdo->beginTransaction();

        try {
            $existing = $this->loadExistingAccounts($pdo);

            $upsert = $pdo->prepare(
                '
                INSERT INTO accounts
                (
                    parent_id,
                    account_code,
                    account_name,
                    account_type,
                    normal_balance,
                    is_active
                )
                VALUES
                (
                    :parent_id,
                    :account_code,
                    :account_name,
                    :account_type,
                    :normal_balance,
                    TRUE
                )
                ON DUPLICATE KEY UPDATE
                    account_name = VALUES(account_name),
                    account_type = VALUES(account_type),
                    normal_balance = VALUES(normal_balance),
                    is_active = TRUE,
                    parent_id = VALUES(parent_id)
                '
            );

            foreach (self::ACCOUNTS as $account) {
                $parentId = $account['parent'] !== null
                    ? ($existing[$account['parent']] ?? null)
                    : null;

                if (
                    $account['parent'] !== null &&
                    $parentId === null
                ) {
                    throw new \RuntimeException(
                        'Parent account [' . $account['parent']
                        . '] must exist before '
                        . $account['code'] . '.'
                    );
                }

                $upsert->execute([
                    'parent_id' => $parentId,
                    'account_code' => $account['code'],
                    'account_name' => $account['name'],
                    'account_type' => $account['type'],
                    'normal_balance' => $account['normal_balance'],
                ]);

                $existing[$account['code']] = $this->accountId(
                    $pdo,
                    $account['code'],
                );
            }

            $pdo->commit();

            echo
                'Seeded/updated '
                . count(self::ACCOUNTS)
                . ' Ledger accounts.'
                . PHP_EOL;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, int>
     */
    private function loadExistingAccounts(PDO $pdo): array
    {
        $statement = $pdo->query(
            '
            SELECT id, account_code
            FROM accounts
            '
        );

        $accounts = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $accounts[(string) $row['account_code']] =
                (int) $row['id'];
        }

        return $accounts;
    }

    private function accountId(
        PDO $pdo,
        string $code,
    ): int {
        $statement = $pdo->prepare(
            '
            SELECT id
            FROM accounts
            WHERE account_code = :account_code
            LIMIT 1
            '
        );

        $statement->execute([
            'account_code' => $code,
        ]);

        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new \RuntimeException(
                'Account [' . $code . '] was not created.'
            );
        }

        return (int) $id;
    }
}
