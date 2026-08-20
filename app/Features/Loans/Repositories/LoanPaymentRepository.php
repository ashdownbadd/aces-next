<?php

declare(strict_types=1);

namespace App\Features\Loans\Repositories;

use App\Foundation\Repository;
use PDO;
use RuntimeException;

final class LoanPaymentRepository extends Repository
{
    /**
     * Refresh overdue state and accrue the 3% monthly penalty for unpaid rows.
     *
     * A row due on the supplied as-of date is still Pending. Only rows with
     * due_date strictly before the as-of date are overdue.
     */
    public function refreshOverdueStatus(
        int $loanId,
        string $asOfDate,
    ): void {
        $pdo = $this->connection();

        $asOf = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $asOfDate,
        );

        if ($asOf === false || $asOf->format('Y-m-d') !== $asOfDate) {
            throw new \InvalidArgumentException(
                'The overdue as-of date must be YYYY-MM-DD.'
            );
        }

        $statement = $pdo->prepare(
            'SELECT id, due_date, principal, interest,
                    rem_principal, rem_interest, rem_penalty, status
             FROM loan_amortizations
             WHERE loan_id = :loan_id
             ORDER BY period ASC
             FOR UPDATE'
        );

        $pdo->beginTransaction();

        try {
            $statement->execute(['loan_id' => $loanId]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            $update = $pdo->prepare(
                'UPDATE loan_amortizations
                 SET rem_penalty = :rem_penalty,
                     status = :status
                 WHERE id = :id
                   AND loan_id = :loan_id'
            );

            foreach ($rows as $row) {
                if ((string) $row['status'] === 'Paid') {
                    continue;
                }

                $dueDate = \DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    (string) $row['due_date'],
                );

                if ($dueDate === false || $dueDate >= $asOf) {
                    // Due today/future: do not accrue a penalty.
                    if ((float) $row['rem_penalty'] <= 0.005) {
                        $update->execute([
                            'id' => $row['id'],
                            'loan_id' => $loanId,
                            'rem_penalty' => 0.00,
                            'status' => 'Pending',
                        ]);
                    }
                    continue;
                }

                $monthsPastDue = (
                    ((int) $asOf->format('Y') - (int) $dueDate->format('Y')) * 12
                ) + (
                    (int) $asOf->format('n') - (int) $dueDate->format('n')
                );

                $monthsPastDue = max(1, $monthsPastDue);

                $baseAmount = round(
                    (float) $row['principal']
                    + (float) $row['interest'],
                    2,
                );

                $penalty = round(
                    $baseAmount * 0.03 * $monthsPastDue,
                    2,
                );

                $update->execute([
                    'id' => $row['id'],
                    'loan_id' => $loanId,
                    'rem_penalty' => $penalty,
                    'status' => 'Overdue',
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

    /** @return array<int, array<string, mixed>> */
    public function amortizations(int $loanId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, loan_id, period, due_date, principal, interest,
                    rem_principal, rem_interest, rem_penalty, orig_penalty,
                    status, remarks
             FROM loan_amortizations
             WHERE loan_id = :loan_id
             ORDER BY period ASC'
        );
        $statement->execute(['loan_id' => $loanId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $payment
     * @param array<int, array<string, mixed>> $allocations
     * @param array<int, array<string, mixed>> $updatedRows
     */
    public function persistPayment(
        array $payment,
        array $allocations,
        array $updatedRows,
    ): int {
        $pdo = $this->connection();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO loan_payments (
                    loan_id, payment_datetime, amount_paid,
                    penalty_applied, interest_applied, principal_applied,
                    excess, type, remarks, created_by
                 ) VALUES (
                    :loan_id, :payment_datetime, :amount_paid,
                    :penalty_applied, :interest_applied, :principal_applied,
                    :excess, :type, :remarks, :created_by
                 )'
            );

            $statement->execute([
                'loan_id' => $payment['loan_id'],
                'payment_datetime' => $payment['payment_datetime'],
                'amount_paid' => $payment['amount_paid'],
                'penalty_applied' => $payment['penalty_applied'],
                'interest_applied' => $payment['interest_applied'],
                'principal_applied' => $payment['principal_applied'],
                'excess' => $payment['excess'],
                'type' => $payment['type'],
                'remarks' => $payment['remarks'],
                'created_by' => $payment['created_by'],
            ]);

            $paymentId = (int) $pdo->lastInsertId();

            $update = $pdo->prepare(
                'UPDATE loan_amortizations
                 SET rem_principal = :rem_principal,
                     rem_interest = :rem_interest,
                     rem_penalty = :rem_penalty,
                     status = :status,
                     remarks = :remarks
                 WHERE id = :id AND loan_id = :loan_id'
            );

            foreach ($updatedRows as $row) {
                $update->execute([
                    'id' => $row['id'],
                    'loan_id' => $payment['loan_id'],
                    'rem_principal' => $row['rem_principal'],
                    'rem_interest' => $row['rem_interest'],
                    'rem_penalty' => $row['rem_penalty'],
                    'status' => $row['status'],
                    'remarks' => $row['remarks'],
                ]);

                if ($update->rowCount() === 0) {
                    $exists = $pdo->prepare(
                        'SELECT COUNT(*)
                         FROM loan_amortizations
                         WHERE id = :id
                           AND loan_id = :loan_id'
                    );

                    $exists->execute([
                        'id' => $row['id'],
                        'loan_id' => $payment['loan_id'],
                    ]);

                    if ((int) $exists->fetchColumn() !== 1) {
                        throw new RuntimeException(
                            sprintf(
                                'Failed to update amortization row #%d: row does not exist for Loan #%d.',
                                (int) $row['id'],
                                (int) $payment['loan_id'],
                            )
                        );
                    }

                    // MySQL/PDO can report 0 affected rows when the submitted
                    // values are identical to the existing values. That is a
                    // successful no-op, not a persistence failure.
                }
            }

            $allocation = $pdo->prepare(
                'INSERT INTO loan_payment_allocations (
                    payment_id, amortization_id, allocation_type, amount
                 ) VALUES (
                    :payment_id, :amortization_id, :allocation_type, :amount
                 )'
            );

            foreach ($allocations as $item) {
                if ((float) $item['amount'] <= 0.0) {
                    continue;
                }

                $allocation->execute([
                    'payment_id' => $paymentId,
                    'amortization_id' => $item['amortization_id'],
                    'allocation_type' => $item['allocation_type'],
                    'amount' => $item['amount'],
                ]);
            }

            $pdo->commit();
            return $paymentId;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public function findPayment(int $paymentId): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, loan_id, payment_datetime, amount_paid,
                    penalty_applied, interest_applied, principal_applied,
                    excess, type, remarks, created_by, created_at, updated_at,
                    reversed_at, reversed_by, reversal_reason
             FROM loan_payments
             WHERE id = :id'
        );
        $statement->execute(['id' => $paymentId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function allocations(int $paymentId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, payment_id, amortization_id, allocation_type, amount, created_at
             FROM loan_payment_allocations
             WHERE payment_id = :payment_id
             ORDER BY id ASC'
        );
        $statement->execute(['payment_id' => $paymentId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> */
    public function paymentsForLoan(int $loanId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, loan_id, payment_datetime, amount_paid,
                    penalty_applied, interest_applied, principal_applied,
                    excess, type, remarks, created_by, created_at, updated_at,
                    reversed_at, reversed_by, reversal_reason
             FROM loan_payments
             WHERE loan_id = :loan_id
             ORDER BY payment_datetime ASC, id ASC'
        );
        $statement->execute(['loan_id' => $loanId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reversePayment(
        int $paymentId,
        int $userId,
        string $reversedAt,
        string $reason,
    ): array {
        $pdo = $this->connection();
        $pdo->beginTransaction();

        try {
            $paymentStatement = $pdo->prepare(
                'SELECT id, loan_id, reversed_at
                 FROM loan_payments
                 WHERE id = :id
                 FOR UPDATE'
            );
            $paymentStatement->execute(['id' => $paymentId]);
            $payment = $paymentStatement->fetch(PDO::FETCH_ASSOC);

            if ($payment === false) {
                throw new RuntimeException('Payment not found.');
            }

            if ($payment['reversed_at'] !== null) {
                throw new RuntimeException('This payment has already been reversed.');
            }

            $allocationStatement = $pdo->prepare(
                'SELECT amortization_id, allocation_type, amount
                 FROM loan_payment_allocations
                 WHERE payment_id = :payment_id
                 ORDER BY id ASC'
            );
            $allocationStatement->execute(['payment_id' => $paymentId]);
            $allocations = $allocationStatement->fetchAll(PDO::FETCH_ASSOC);

            $rowStatement = $pdo->prepare(
                'SELECT rem_principal, rem_interest, rem_penalty
                 FROM loan_amortizations
                 WHERE id = :id AND loan_id = :loan_id
                 FOR UPDATE'
            );

            $updateRow = $pdo->prepare(
                'UPDATE loan_amortizations
                 SET rem_principal = :rem_principal,
                     rem_interest = :rem_interest,
                     rem_penalty = :rem_penalty,
                     status = :status
                 WHERE id = :id AND loan_id = :loan_id'
            );

            $touched = [];

            foreach ($allocations as $allocation) {
                $amortizationId = (int) $allocation['amortization_id'];

                $rowStatement->execute([
                    'id' => $amortizationId,
                    'loan_id' => $payment['loan_id'],
                ]);
                $row = $rowStatement->fetch(PDO::FETCH_ASSOC);

                if ($row === false) {
                    throw new RuntimeException(
                        sprintf(
                            'Amortization row #%d was not found.',
                            $amortizationId,
                        )
                    );
                }

                $principal = (float) $row['rem_principal'];
                $interest = (float) $row['rem_interest'];
                $penalty = (float) $row['rem_penalty'];
                $amount = (float) $allocation['amount'];

                switch ($allocation['allocation_type']) {
                    case 'Principal':
                        $principal += $amount;
                        break;
                    case 'Interest':
                        $interest += $amount;
                        break;
                    case 'Penalty':
                        $penalty += $amount;
                        break;
                    default:
                        throw new RuntimeException('Unknown payment allocation type.');
                }

                $principal = round(max(0.0, $principal), 2);
                $interest = round(max(0.0, $interest), 2);
                $penalty = round(max(0.0, $penalty), 2);

                $status = $penalty > 0.005
                    ? 'Overdue'
                    : (
                        $principal < 0.005 && $interest < 0.005
                            ? 'Paid'
                            : 'Pending'
                    );

                $updateRow->execute([
                    'id' => $amortizationId,
                    'loan_id' => $payment['loan_id'],
                    'rem_principal' => $principal,
                    'rem_interest' => $interest,
                    'rem_penalty' => $penalty,
                    'status' => $status,
                ]);

                if ($updateRow->rowCount() < 1) {
                    throw new RuntimeException(
                        sprintf(
                            'Failed to restore amortization row #%d.',
                            $amortizationId,
                        )
                    );
                }

                $touched[$amortizationId] = true;
            }

            $reversal = $pdo->prepare(
                'UPDATE loan_payments
                 SET reversed_at = :reversed_at,
                     reversed_by = :reversed_by,
                     reversal_reason = :reversal_reason
                 WHERE id = :id
                   AND reversed_at IS NULL'
            );
            $reversal->execute([
                'id' => $paymentId,
                'reversed_at' => $reversedAt,
                'reversed_by' => $userId,
                'reversal_reason' => $reason,
            ]);

            if ($reversal->rowCount() !== 1) {
                throw new RuntimeException(
                    'The payment could not be marked as reversed.'
                );
            }

            $pdo->commit();

            return [
                'payment_id' => $paymentId,
                'loan_id' => (int) $payment['loan_id'],
                'amortization_rows_restored' => count($touched),
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

}
