<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class AddPaymentIdempotencyAndUnappliedAccount extends Migration
{
    public function up(PDO $pdo): void
    {
        $columns = $pdo->query("SHOW COLUMNS FROM loan_payments LIKE 'idempotency_key'")->fetchAll();
        if ($columns === []) {
            $pdo->exec("ALTER TABLE loan_payments ADD COLUMN idempotency_key VARCHAR(80) NULL AFTER loan_id");
        }
        $indexes = $pdo->query("SHOW INDEX FROM loan_payments WHERE Key_name = 'uq_loan_payments_idempotency'")->fetchAll();
        if ($indexes === []) {
            $pdo->exec("ALTER TABLE loan_payments ADD UNIQUE KEY uq_loan_payments_idempotency (idempotency_key)");
        }
        $pdo->exec("INSERT INTO accounts (parent_id, account_code, account_name, account_type, normal_balance, is_active)
            SELECT 9, '2030', 'Unapplied Loan Payments', 'Liability', 'Credit', 1
            WHERE NOT EXISTS (SELECT 1 FROM accounts WHERE account_code = '2030')");
    }
    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM accounts WHERE account_code = '2030'");
        $pdo->exec("ALTER TABLE loan_payments DROP INDEX uq_loan_payments_idempotency");
        $pdo->exec("ALTER TABLE loan_payments DROP COLUMN idempotency_key");
    }
}
