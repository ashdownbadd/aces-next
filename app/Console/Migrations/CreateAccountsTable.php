<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateAccountsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE accounts (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                parent_id INT UNSIGNED NULL,

                account_code VARCHAR(20) NOT NULL UNIQUE,

                account_name VARCHAR(150) NOT NULL,

                account_type ENUM(
                    'Asset',
                    'Liability',
                    'Equity',
                    'Income',
                    'Expense'
                ) NOT NULL,

                normal_balance ENUM(
                    'Debit',
                    'Credit'
                ) NOT NULL,

                is_active BOOLEAN NOT NULL DEFAULT TRUE,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_accounts_parent (
                    parent_id
                ),

                INDEX idx_accounts_type (
                    account_type
                ),

                INDEX idx_accounts_active (
                    is_active
                ),

                CONSTRAINT fk_accounts_parent
                    FOREIGN KEY (parent_id)
                    REFERENCES accounts(id)
                    ON DELETE SET NULL

            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            DROP TABLE IF EXISTS accounts;
        ");
    }
}
