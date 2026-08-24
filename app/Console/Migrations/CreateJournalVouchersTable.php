<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateJournalVouchersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE journal_vouchers (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                reference_number VARCHAR(50) NOT NULL UNIQUE,

                transaction_date DATE NOT NULL,

                particulars TEXT NOT NULL,

                source_type VARCHAR(100) NULL,

                source_id INT UNSIGNED NULL,

                status ENUM(
                    'Pending',
                    'Approved',
                    'Rejected'
                ) NOT NULL DEFAULT 'Pending',

                rejection_reason TEXT NULL,

                created_by INT UNSIGNED NOT NULL,

                approved_by INT UNSIGNED NULL,

                approved_at TIMESTAMP NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_journal_vouchers_transaction_date (
                    transaction_date
                ),

                INDEX idx_journal_vouchers_status (
                    status
                ),

                INDEX idx_journal_vouchers_source (
                    source_type,
                    source_id
                ),

                INDEX idx_journal_vouchers_created_by (
                    created_by
                ),

                INDEX idx_journal_vouchers_approved_by (
                    approved_by
                ),

                CONSTRAINT fk_journal_vouchers_created_by
                    FOREIGN KEY (created_by)
                    REFERENCES users(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_journal_vouchers_approved_by
                    FOREIGN KEY (approved_by)
                    REFERENCES users(id)
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
            DROP TABLE IF EXISTS journal_vouchers;
        ");
    }
}
