<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class AddPaymentReversalFields extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->addColumnIfMissing($pdo, 'loan_payments', 'reversed_at', 'TIMESTAMP NULL');
        $this->addColumnIfMissing($pdo, 'loan_payments', 'reversed_by', 'INT UNSIGNED NULL');
        $this->addColumnIfMissing($pdo, 'loan_payments', 'reversal_reason', 'TEXT NULL');

        $this->addForeignKeyIfMissing(
            $pdo,
            'loan_payments',
            'fk_loan_payments_reversed_by',
            'FOREIGN KEY (reversed_by) REFERENCES users(id) ON DELETE SET NULL',
        );
    }

    public function down(PDO $pdo): void
    {
        $this->dropForeignKeyIfExisting(
            $pdo,
            'loan_payments',
            'fk_loan_payments_reversed_by',
        );

        $this->dropColumnIfExisting($pdo, 'loan_payments', 'reversal_reason');
        $this->dropColumnIfExisting($pdo, 'loan_payments', 'reversed_by');
        $this->dropColumnIfExisting($pdo, 'loan_payments', 'reversed_at');
    }

    private function addColumnIfMissing(
        PDO $pdo,
        string $table,
        string $column,
        string $definition,
    ): void {
        if ($this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec(
            sprintf(
                'ALTER TABLE `%s` ADD COLUMN `%s` %s',
                $table,
                $column,
                $definition,
            )
        );
    }

    private function addForeignKeyIfMissing(
        PDO $pdo,
        string $table,
        string $constraint,
        string $definition,
    ): void {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND CONSTRAINT_NAME = :constraint_name'
        );

        $statement->execute([
            'table_name' => $table,
            'constraint_name' => $constraint,
        ]);

        if ((int) $statement->fetchColumn() > 0) {
            return;
        }

        $pdo->exec(
            sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` %s',
                $table,
                $constraint,
                $definition,
            )
        );
    }

    private function dropForeignKeyIfExisting(
        PDO $pdo,
        string $table,
        string $constraint,
    ): void {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND CONSTRAINT_NAME = :constraint_name'
        );

        $statement->execute([
            'table_name' => $table,
            'constraint_name' => $constraint,
        ]);

        if ((int) $statement->fetchColumn() === 0) {
            return;
        }

        $pdo->exec(
            sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $constraint,
            )
        );
    }

    private function dropColumnIfExisting(
        PDO $pdo,
        string $table,
        string $column,
    ): void {
        if (!$this->columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec(
            sprintf(
                'ALTER TABLE `%s` DROP COLUMN `%s`',
                $table,
                $column,
            )
        );
    }

    private function columnExists(
        PDO $pdo,
        string $table,
        string $column,
    ): bool {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );

        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }
}
