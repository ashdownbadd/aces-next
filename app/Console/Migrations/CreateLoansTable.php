<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateLoansTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE loans (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                member_id INT UNSIGNED NOT NULL,

                loan_type ENUM(
                    'Bridge Financing',
                    'Investment Loan',
                    'Pension Loan',
                    'Productivity Loan',
                    'Personal Loan',
                    'Salary Loan',
                    'Micro-Finance Loan'
                ) NOT NULL,

                collateral ENUM(
                    'Post-Dated Check',
                    'Real Property',
                    'Chattels / Movable Assets'
                ) NOT NULL,

                application_status ENUM(
                    'Pending',
                    'Under Review',
                    'Approved',
                    'Rejected'
                ) NOT NULL DEFAULT 'Pending',

                loan_status ENUM(
                    'Active',
                    'Fully Paid'
                ) NULL,

                rejection_reason TEXT NULL,

                principal_amount DECIMAL(15,2) NOT NULL,
                interest_rate DECIMAL(8,4) NOT NULL,

                amortization_type ENUM(
                    'Straight-line',
                    'Diminishing balance',
                    'Manual'
                ) NULL,

                payment_frequency ENUM(
                    'Monthly',
                    'Bi-Monthly',
                    'Weekly'
                ) NULL,

                terms_months INT UNSIGNED NOT NULL,
                start_date DATE NOT NULL,
                release_date DATE NULL,

                manual_payment DECIMAL(15,2) NULL,

                processing_fee DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                insurance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                notarial_fee DECIMAL(15,2) NOT NULL DEFAULT 400.00,
                net_proceeds DECIMAL(15,2) NULL,

                tct_no VARCHAR(100) NULL,
                tax_declaration_no VARCHAR(100) NULL,

                real_property_payment_status ENUM(
                    'Updated',
                    'Not Updated',
                    'Pending'
                ) NULL,

                notes TEXT NULL,

                created_by INT UNSIGNED NOT NULL,
                reviewed_by INT UNSIGNED NULL,
                approved_by INT UNSIGNED NULL,
                released_by INT UNSIGNED NULL,

                reviewed_at TIMESTAMP NULL,
                approved_at TIMESTAMP NULL,
                released_at TIMESTAMP NULL,
                fully_paid_at TIMESTAMP NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_loans_member (
                    member_id
                ),

                INDEX idx_loans_application_status (
                    application_status
                ),

                INDEX idx_loans_loan_status (
                    loan_status
                ),

                INDEX idx_loans_created_by (
                    created_by
                ),

                INDEX idx_loans_reviewed_by (
                    reviewed_by
                ),

                INDEX idx_loans_approved_by (
                    approved_by
                ),

                INDEX idx_loans_released_by (
                    released_by
                ),

                CONSTRAINT fk_loans_member
                    FOREIGN KEY (member_id)
                    REFERENCES members(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_loans_created_by
                    FOREIGN KEY (created_by)
                    REFERENCES users(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_loans_reviewed_by
                    FOREIGN KEY (reviewed_by)
                    REFERENCES users(id)
                    ON DELETE SET NULL,

                CONSTRAINT fk_loans_approved_by
                    FOREIGN KEY (approved_by)
                    REFERENCES users(id)
                    ON DELETE SET NULL,

                CONSTRAINT fk_loans_released_by
                    FOREIGN KEY (released_by)
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
            DROP TABLE IF EXISTS loans;
        ");
    }
}
