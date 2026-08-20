<?php

declare(strict_types=1);

namespace App\Features\Loans\Repositories;

use App\Foundation\Repository;
use PDO;
use RuntimeException;

final class LoanAmortizationRepository extends Repository
{
    /** @return array<int, array<string, mixed>> */
    public function forLoan(int $loanId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT
                id,
                loan_id,
                period,
                due_date,
                principal,
                interest,
                rem_principal,
                rem_interest,
                rem_penalty,
                orig_penalty,
                status,
                remarks,
                created_at,
                updated_at
             FROM loan_amortizations
             WHERE loan_id = :loan_id
             ORDER BY period ASC'
        );

        $statement->execute(['loan_id' => $loanId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countForLoan(int $loanId): int
    {
        $statement = $this->connection()->prepare(
            'SELECT COUNT(*)
             FROM loan_amortizations
             WHERE loan_id = :loan_id'
        );

        $statement->execute(['loan_id' => $loanId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Replace a schedule only when explicitly permitted by the service layer.
     *
     * This method intentionally refuses to operate if payment history exists.
     *
     * @param array<int, array<string, mixed>> $schedule
     */
    public function replaceBeforeFinancialActivity(
        int $loanId,
        array $schedule,
    ): void {
        $pdo = $this->connection();
        $pdo->beginTransaction();

        try {
            $paymentCheck = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM loan_payments
                 WHERE loan_id = :loan_id'
            );
            $paymentCheck->execute(['loan_id' => $loanId]);

            if ((int) $paymentCheck->fetchColumn() > 0) {
                throw new RuntimeException(
                    'Cannot rebuild an amortization schedule after payment activity exists.'
                );
            }

            $delete = $pdo->prepare(
                'DELETE FROM loan_amortizations
                 WHERE loan_id = :loan_id'
            );
            $delete->execute(['loan_id' => $loanId]);

            $insert = $pdo->prepare(
                'INSERT INTO loan_amortizations (
                    loan_id,
                    period,
                    due_date,
                    principal,
                    interest,
                    rem_principal,
                    rem_interest,
                    rem_penalty,
                    orig_penalty,
                    status,
                    remarks
                 ) VALUES (
                    :loan_id,
                    :period,
                    :due_date,
                    :principal,
                    :interest,
                    :rem_principal,
                    :rem_interest,
                    :rem_penalty,
                    :orig_penalty,
                    :status,
                    :remarks
                 )'
            );

            foreach ($schedule as $row) {
                $insert->execute([
                    'loan_id' => $loanId,
                    'period' => $row['period'],
                    'due_date' => $row['due_date'],
                    'principal' => $row['principal'],
                    'interest' => $row['interest'],
                    'rem_principal' => $row['rem_principal'],
                    'rem_interest' => $row['rem_interest'],
                    'rem_penalty' => $row['rem_penalty'],
                    'orig_penalty' => $row['orig_penalty'],
                    'status' => $row['status'],
                    'remarks' => $row['remarks'] ?? null,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
