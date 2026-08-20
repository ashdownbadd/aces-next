<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateLoanPaymentsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE loan_payments (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                loan_id INT UNSIGNED NOT NULL,

                payment_datetime TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                amount_paid DECIMAL(15,2) NOT NULL,
                penalty_applied DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                interest_applied DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                principal_applied DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                excess DECIMAL(15,2) NOT NULL DEFAULT 0.00,

                type VARCHAR(50) NOT NULL DEFAULT 'Global',
                remarks TEXT NULL,

                created_by INT UNSIGNED NOT NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_loan_payments_loan (
                    loan_id
                ),

                INDEX idx_loan_payments_datetime (
                    payment_datetime
                ),

                INDEX idx_loan_payments_created_by (
                    created_by
                ),

                CONSTRAINT fk_loan_payments_loan
                    FOREIGN KEY (loan_id)
                    REFERENCES loans(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_loan_payments_created_by
                    FOREIGN KEY (created_by)
                    REFERENCES users(id)
                    ON DELETE RESTRICT

            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            DROP TABLE IF EXISTS loan_payments;
        ");
    }
}
