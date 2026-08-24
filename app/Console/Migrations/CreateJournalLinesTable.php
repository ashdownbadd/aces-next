<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateJournalLinesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE journal_lines (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                journal_voucher_id INT UNSIGNED NOT NULL,

                account_id INT UNSIGNED NOT NULL,

                member_id INT UNSIGNED NULL,

                loan_id INT UNSIGNED NULL,

                line_description VARCHAR(255) NULL,

                debit DECIMAL(15,2) NOT NULL DEFAULT 0.00,

                credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_journal_lines_voucher (
                    journal_voucher_id
                ),

                INDEX idx_journal_lines_account (
                    account_id
                ),

                INDEX idx_journal_lines_member (
                    member_id
                ),

                INDEX idx_journal_lines_loan (
                    loan_id
                ),

                CONSTRAINT fk_journal_lines_voucher
                    FOREIGN KEY (journal_voucher_id)
                    REFERENCES journal_vouchers(id)
                    ON DELETE CASCADE,

                CONSTRAINT fk_journal_lines_account
                    FOREIGN KEY (account_id)
                    REFERENCES accounts(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_journal_lines_member
                    FOREIGN KEY (member_id)
                    REFERENCES members(id)
                    ON DELETE SET NULL,

                CONSTRAINT fk_journal_lines_loan
                    FOREIGN KEY (loan_id)
                    REFERENCES loans(id)
                    ON DELETE SET NULL,

                CONSTRAINT chk_journal_lines_one_side
                    CHECK (
                        (debit > 0.00 AND credit = 0.00)
                        OR
                        (credit > 0.00 AND debit = 0.00)
                    )

            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            DROP TABLE IF EXISTS journal_lines;
        ");
    }
}
