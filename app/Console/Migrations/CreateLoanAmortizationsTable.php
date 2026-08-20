<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateLoanAmortizationsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE loan_amortizations (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                loan_id INT UNSIGNED NOT NULL,

                period INT UNSIGNED NOT NULL,
                due_date DATE NOT NULL,

                principal DECIMAL(15,2) NOT NULL,
                interest DECIMAL(15,2) NOT NULL,

                rem_principal DECIMAL(15,2) NOT NULL,
                rem_interest DECIMAL(15,2) NOT NULL,

                rem_penalty DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                orig_penalty DECIMAL(15,2) NOT NULL DEFAULT 0.00,

                status ENUM(
                    'Pending',
                    'Near-Due',
                    'Overdue',
                    'Paid'
                ) NOT NULL DEFAULT 'Pending',

                remarks TEXT NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY uq_loan_amortizations_period (
                    loan_id,
                    period
                ),

                INDEX idx_loan_amortizations_loan (
                    loan_id
                ),

                INDEX idx_loan_amortizations_due_date (
                    due_date
                ),

                INDEX idx_loan_amortizations_status (
                    status
                ),

                CONSTRAINT fk_loan_amortizations_loan
                    FOREIGN KEY (loan_id)
                    REFERENCES loans(id)
                    ON DELETE CASCADE

            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            DROP TABLE IF EXISTS loan_amortizations;
        ");
    }
}
