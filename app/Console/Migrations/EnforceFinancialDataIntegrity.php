<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;
use RuntimeException;

final class EnforceFinancialDataIntegrity extends Migration
{
    public function up(PDO $pdo): void
    {
        /*
         * Fail before ALTER TABLE if existing data would violate a new
         * invariant. This prevents a migration from partially applying.
         */
        $checks = [
            [
                'sql' => '
                    SELECT COUNT(*)
                    FROM loans
                    WHERE principal_amount <= 0
                       OR interest_rate < 0
                       OR terms_months = 0
                       OR processing_fee < 0
                       OR insurance < 0
                       OR notarial_fee < 0
                       OR (manual_payment IS NOT NULL AND manual_payment <= 0)
                       OR (net_proceeds IS NOT NULL AND net_proceeds < 0)
                ',
                'message' =>
                    'Cannot enforce loan amount constraints because invalid loan financial data exists.',
            ],
            [
                'sql' => '
                    SELECT COUNT(*)
                    FROM loan_amortizations
                    WHERE period = 0
                       OR principal < 0
                       OR interest < 0
                       OR rem_principal < 0
                       OR rem_interest < 0
                       OR rem_penalty < 0
                       OR orig_penalty < 0
                ',
                'message' =>
                    'Cannot enforce amortization constraints because invalid schedule data exists.',
            ],
            [
                'sql' => '
                    SELECT COUNT(*)
                    FROM loan_payments
                    WHERE amount_paid <= 0
                       OR penalty_applied < 0
                       OR interest_applied < 0
                       OR principal_applied < 0
                       OR excess < 0
                ',
                'message' =>
                    'Cannot enforce payment constraints because invalid payment data exists.',
            ],
            [
                'sql' => '
                    SELECT COUNT(*)
                    FROM loan_payment_allocations
                    WHERE amount <= 0
                ',
                'message' =>
                    'Cannot enforce payment allocation constraints because invalid allocation data exists.',
            ],
            [
                'sql' => '
                    SELECT COUNT(*)
                    FROM journal_lines
                    WHERE debit < 0 OR credit < 0
                ',
                'message' =>
                    'Cannot enforce journal line constraints because negative debit/credit data exists.',
            ],
        ];

        foreach ($checks as $check) {
            if ((int) $pdo->query($check['sql'])->fetchColumn() > 0) {
                throw new RuntimeException($check['message']);
            }
        }

        $constraints = [
            [
                'table' => 'loans',
                'name' => 'chk_loans_financial_values',
                'expression' => '
                    principal_amount > 0
                    AND interest_rate >= 0
                    AND terms_months > 0
                    AND processing_fee >= 0
                    AND insurance >= 0
                    AND notarial_fee >= 0
                    AND (manual_payment IS NULL OR manual_payment > 0)
                    AND (net_proceeds IS NULL OR net_proceeds >= 0)
                ',
            ],
            [
                'table' => 'loan_amortizations',
                'name' => 'chk_loan_amortizations_amounts',
                'expression' => '
                    period > 0
                    AND principal >= 0
                    AND interest >= 0
                    AND rem_principal >= 0
                    AND rem_interest >= 0
                    AND rem_penalty >= 0
                    AND orig_penalty >= 0
                ',
            ],
            [
                'table' => 'loan_payments',
                'name' => 'chk_loan_payments_amounts',
                'expression' => '
                    amount_paid > 0
                    AND penalty_applied >= 0
                    AND interest_applied >= 0
                    AND principal_applied >= 0
                    AND excess >= 0
                ',
            ],
            [
                'table' => 'loan_payment_allocations',
                'name' => 'chk_loan_payment_allocation_amount',
                'expression' => 'amount > 0',
            ],
            [
                'table' => 'journal_lines',
                'name' => 'chk_journal_lines_nonnegative',
                'expression' => '
                    debit >= 0
                    AND credit >= 0
                ',
            ],
        ];

        foreach ($constraints as $constraint) {
            $pdo->exec(
                sprintf(
                    '
                    ALTER TABLE %s
                    ADD CONSTRAINT %s
                    CHECK (%s)
                    ',
                    $constraint['table'],
                    $constraint['name'],
                    $constraint['expression'],
                )
            );
        }
    }

    public function down(PDO $pdo): void
    {
        $constraints = [
            ['loans', 'chk_loans_financial_values'],
            ['loan_amortizations', 'chk_loan_amortizations_amounts'],
            ['loan_payments', 'chk_loan_payments_amounts'],
            ['loan_payment_allocations', 'chk_loan_payment_allocation_amount'],
            ['journal_lines', 'chk_journal_lines_nonnegative'],
        ];

        foreach ($constraints as [$table, $name]) {
            $pdo->exec(
                "ALTER TABLE {$table} DROP CONSTRAINT {$name}"
            );
        }
    }
}
