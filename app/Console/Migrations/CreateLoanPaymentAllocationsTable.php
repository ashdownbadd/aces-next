<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateLoanPaymentAllocationsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE loan_payment_allocations (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                payment_id INT UNSIGNED NOT NULL,
                amortization_id INT UNSIGNED NOT NULL,

                allocation_type ENUM(
                    'Penalty',
                    'Interest',
                    'Principal'
                ) NOT NULL,

                amount DECIMAL(15,2) NOT NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_loan_payment_allocations_payment (
                    payment_id
                ),

                INDEX idx_loan_payment_allocations_amortization (
                    amortization_id
                ),

                CONSTRAINT fk_loan_payment_allocations_payment
                    FOREIGN KEY (payment_id)
                    REFERENCES loan_payments(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_loan_payment_allocations_amortization
                    FOREIGN KEY (amortization_id)
                    REFERENCES loan_amortizations(id)
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
            DROP TABLE IF EXISTS loan_payment_allocations;
        ");
    }
}
