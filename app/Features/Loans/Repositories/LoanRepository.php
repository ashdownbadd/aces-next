<?php

declare(strict_types=1);

namespace App\Features\Loans\Repositories;

use App\Foundation\Repository;
use PDO;

final class LoanRepository extends Repository
{
    /**
     * Retrieve a paginated list of loans.
     *
     * Search fields:
     * - Member number
     * - Member name
     * - Loan type
     * - Loan ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        string $applicationStatus = '',
        string $loanStatus = '',
        int $memberId = 0,
        int $limit = 25,
        int $offset = 0,
    ): array {
        $pdo = $this->connection();

        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $search = trim($search);
        $applicationStatus = trim($applicationStatus);
        $loanStatus = trim($loanStatus);

        $sql = '
            SELECT
                l.id,
                l.member_id,
                m.member_number,
                CONCAT_WS(
                    \' \',
                    mp.first_name,
                    NULLIF(mp.middle_name, \'\'),
                    mp.last_name,
                    NULLIF(mp.suffix, \'\')
                ) AS member_name,
                l.loan_type,
                l.collateral,
                l.application_status,
                l.loan_status,
                l.principal_amount,
                l.interest_rate,
                l.amortization_type,
                l.payment_frequency,
                l.terms_months,
                l.start_date,
                l.release_date,
                l.net_proceeds,
                l.created_at,
                l.updated_at
            FROM loans AS l
            INNER JOIN members AS m
                ON m.id = l.member_id
            LEFT JOIN member_profiles AS mp
                ON mp.member_id = m.id
            WHERE 1 = 1
        ';

        $parameters = [];

        if ($search !== '') {
            $sql .= '
                AND (
                    CAST(l.id AS CHAR) LIKE :search_id
                    OR m.member_number LIKE :search_member_number
                    OR CONCAT_WS(
                        \' \',
                        mp.first_name,
                        NULLIF(mp.middle_name, \'\'),
                        mp.last_name,
                        NULLIF(mp.suffix, \'\')
                    ) LIKE :search_member_name
                    OR l.loan_type LIKE :search_loan_type
                )
            ';

            $value = '%' . $search . '%';
            $parameters['search_id'] = $value;
            $parameters['search_member_number'] = $value;
            $parameters['search_member_name'] = $value;
            $parameters['search_loan_type'] = $value;
        }

        if ($applicationStatus !== '') {
            $sql .= ' AND l.application_status = :application_status';
            $parameters['application_status'] = $applicationStatus;
        }

        if ($loanStatus !== '') {
            $sql .= ' AND l.loan_status = :loan_status';
            $parameters['loan_status'] = $loanStatus;
        }

        if ($memberId > 0) {
            $sql .= ' AND l.member_id = :member_id';
            $parameters['member_id'] = $memberId;
        }

        $sql .= '
            ORDER BY l.id DESC
            LIMIT :limit
            OFFSET :offset
        ';

        $statement = $pdo->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR,
            );
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the number of loans matching the supplied filters.
     */
    public function count(
        string $search = '',
        string $applicationStatus = '',
        string $loanStatus = '',
        int $memberId = 0,
    ): int {
        $pdo = $this->connection();

        $search = trim($search);
        $applicationStatus = trim($applicationStatus);
        $loanStatus = trim($loanStatus);

        $sql = '
            SELECT COUNT(*)
            FROM loans AS l
            INNER JOIN members AS m
                ON m.id = l.member_id
            LEFT JOIN member_profiles AS mp
                ON mp.member_id = m.id
            WHERE 1 = 1
        ';

        $parameters = [];

        if ($search !== '') {
            $sql .= '
                AND (
                    CAST(l.id AS CHAR) LIKE :search_id
                    OR m.member_number LIKE :search_member_number
                    OR CONCAT_WS(
                        \' \',
                        mp.first_name,
                        NULLIF(mp.middle_name, \'\'),
                        mp.last_name,
                        NULLIF(mp.suffix, \'\')
                    ) LIKE :search_member_name
                    OR l.loan_type LIKE :search_loan_type
                )
            ';

            $value = '%' . $search . '%';
            $parameters['search_id'] = $value;
            $parameters['search_member_number'] = $value;
            $parameters['search_member_name'] = $value;
            $parameters['search_loan_type'] = $value;
        }

        if ($applicationStatus !== '') {
            $sql .= ' AND l.application_status = :application_status';
            $parameters['application_status'] = $applicationStatus;
        }

        if ($loanStatus !== '') {
            $sql .= ' AND l.loan_status = :loan_status';
            $parameters['loan_status'] = $loanStatus;
        }

        if ($memberId > 0) {
            $sql .= ' AND l.member_id = :member_id';
            $parameters['member_id'] = $memberId;
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }

    /**
     * Retrieve one loan together with its member summary.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            '
            SELECT
                l.id,
                l.member_id,
                m.member_number,
                CONCAT_WS(
                    \' \',
                    mp.first_name,
                    NULLIF(mp.middle_name, \'\'),
                    mp.last_name,
                    NULLIF(mp.suffix, \'\')
                ) AS member_name,
                l.loan_type,
                l.collateral,
                l.application_status,
                l.loan_status,
                l.rejection_reason,
                l.principal_amount,
                l.interest_rate,
                l.amortization_type,
                l.payment_frequency,
                l.terms_months,
                l.start_date,
                l.release_date,
                l.processing_fee,
                l.insurance,
                l.notarial_fee,
                l.net_proceeds,
                l.manual_payment,
                l.tct_no,
                l.tax_declaration_no,
                l.real_property_payment_status,
                l.reviewed_at,
                l.approved_at,
                l.released_at,
                l.fully_paid_at,
                l.created_by,
                l.reviewed_by,
                l.approved_by,
                l.released_by,
                l.notes,
                l.created_at,
                l.updated_at
            FROM loans AS l
            INNER JOIN members AS m
                ON m.id = l.member_id
            LEFT JOIN member_profiles AS mp
                ON mp.member_id = m.id
            WHERE l.id = :id
            LIMIT 1
            '
        );

        $statement->execute(['id' => $id]);

        $loan = $statement->fetch(PDO::FETCH_ASSOC);

        return $loan === false ? null : $loan;
    }

    /**
     * Retrieve all loans belonging to a member.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forMember(int $memberId): array
    {
        $statement = $this->connection()->prepare(
            '
            SELECT
                id,
                member_id,
                loan_type,
                collateral,
                application_status,
                loan_status,
                principal_amount,
                interest_rate,
                amortization_type,
                payment_frequency,
                terms_months,
                start_date,
                release_date,
                net_proceeds,
                created_at,
                updated_at
            FROM loans
            WHERE member_id = :member_id
            ORDER BY id DESC
            '
        );

        $statement->execute(['member_id' => $memberId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Persist a new loan application.
     *
     * The service layer is responsible for validating the lifecycle
     * and preparing the values before they reach this repository.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $statement = $this->connection()->prepare(
            '
            INSERT INTO loans (
                member_id,
                loan_type,
                collateral,
                application_status,
                loan_status,
                rejection_reason,
                principal_amount,
                interest_rate,
                amortization_type,
                payment_frequency,
                terms_months,
                start_date,
                release_date,
                processing_fee,
                insurance,
                notarial_fee,
                net_proceeds,
                manual_payment,
                tct_no,
                tax_declaration_no,
                real_property_payment_status,
                reviewed_at,
                approved_at,
                released_at,
                fully_paid_at,
                created_by,
                reviewed_by,
                approved_by,
                released_by,
                notes
            ) VALUES (
                :member_id,
                :loan_type,
                :collateral,
                :application_status,
                :loan_status,
                :rejection_reason,
                :principal_amount,
                :interest_rate,
                :amortization_type,
                :payment_frequency,
                :terms_months,
                :start_date,
                :release_date,
                :processing_fee,
                :insurance,
                :notarial_fee,
                :net_proceeds,
                :manual_payment,
                :tct_no,
                :tax_declaration_no,
                :real_property_payment_status,
                :reviewed_at,
                :approved_at,
                :released_at,
                :fully_paid_at,
                :created_by,
                :reviewed_by,
                :approved_by,
                :released_by,
                :notes
            )
            '
        );

        $statement->execute([
            'member_id' => $data['member_id'],
            'loan_type' => $data['loan_type'],
            'collateral' => $data['collateral'],
            'application_status' => $data['application_status'],
            'loan_status' => $data['loan_status'],
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'principal_amount' => $data['principal_amount'],
            'interest_rate' => $data['interest_rate'],
            'amortization_type' => $data['amortization_type'],
            'payment_frequency' => $data['payment_frequency'] ?? null,
            'terms_months' => $data['terms_months'],
            'start_date' => $data['start_date'] ?? null,
            'release_date' => $data['release_date'] ?? null,
            'processing_fee' => $data['processing_fee'] ?? null,
            'insurance' => $data['insurance'] ?? null,
            'notarial_fee' => $data['notarial_fee'] ?? null,
            'net_proceeds' => $data['net_proceeds'] ?? null,
            'manual_payment' => $data['manual_payment'] ?? null,
            'tct_no' => $data['tct_no'] ?? null,
            'tax_declaration_no' => $data['tax_declaration_no'] ?? null,
            'real_property_payment_status' => $data['real_property_payment_status'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? null,
            'approved_at' => $data['approved_at'] ?? null,
            'released_at' => $data['released_at'] ?? null,
            'fully_paid_at' => $data['fully_paid_at'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'reviewed_by' => $data['reviewed_by'] ?? null,
            'approved_by' => $data['approved_by'] ?? null,
            'released_by' => $data['released_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    /**
     * Update editable loan/application information.
     *
     * This repository method does not decide whether a loan is editable.
     * That lifecycle rule belongs in LoanService.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->connection()->prepare(
            '
            UPDATE loans
            SET
                member_id = :member_id,
                loan_type = :loan_type,
                collateral = :collateral,
                principal_amount = :principal_amount,
                interest_rate = :interest_rate,
                amortization_type = :amortization_type,
                payment_frequency = :payment_frequency,
                terms_months = :terms_months,
                start_date = :start_date,
                processing_fee = :processing_fee,
                insurance = :insurance,
                notarial_fee = :notarial_fee,
                net_proceeds = :net_proceeds,
                manual_payment = :manual_payment,
                tct_no = :tct_no,
                tax_declaration_no = :tax_declaration_no,
                real_property_payment_status = :real_property_payment_status,
                notes = :notes
            WHERE id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'member_id' => $data['member_id'],
            'loan_type' => $data['loan_type'],
            'collateral' => $data['collateral'],
            'principal_amount' => $data['principal_amount'],
            'interest_rate' => $data['interest_rate'],
            'amortization_type' => $data['amortization_type'],
            'payment_frequency' => $data['payment_frequency'] ?? null,
            'terms_months' => $data['terms_months'],
            'start_date' => $data['start_date'] ?? null,
            'processing_fee' => $data['processing_fee'] ?? null,
            'insurance' => $data['insurance'] ?? null,
            'notarial_fee' => $data['notarial_fee'] ?? null,
            'net_proceeds' => $data['net_proceeds'] ?? null,
            'manual_payment' => $data['manual_payment'] ?? null,
            'tct_no' => $data['tct_no'] ?? null,
            'tax_declaration_no' => $data['tax_declaration_no'] ?? null,
            'real_property_payment_status' => $data['real_property_payment_status'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update the application workflow status and its associated timestamp/user.
     */
    public function updateApplicationStatus(
        int $id,
        string $status,
        ?int $userId = null,
        ?string $timestamp = null,
        ?string $rejectionReason = null,
    ): void {
        $fields = [
            'application_status = :application_status',
        ];

        $parameters = [
            'id' => $id,
            'application_status' => $status,
        ];

        if ($status === 'Under Review') {
            $fields[] = 'reviewed_at = :reviewed_at';
            $fields[] = 'reviewed_by = :reviewed_by';
            $parameters['reviewed_at'] = $timestamp;
            $parameters['reviewed_by'] = $userId;
        }

        if ($status === 'Approved') {
            $fields[] = 'approved_at = :approved_at';
            $fields[] = 'approved_by = :approved_by';
            $parameters['approved_at'] = $timestamp;
            $parameters['approved_by'] = $userId;
            $fields[] = 'rejection_reason = NULL';
        }

        if ($status === 'Rejected') {
            $fields[] = 'rejection_reason = :rejection_reason';
            $parameters['rejection_reason'] = $rejectionReason;
        }

        $statement = $this->connection()->prepare(
            'UPDATE loans SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );

        $statement->execute($parameters);
    }

    /**
     * Mark an approved loan as active after release.
     */
    public function release(
        int $id,
        int $userId,
        string $releasedAt,
        ?string $releaseDate = null,
    ): void {
        $statement = $this->connection()->prepare(
            '
            UPDATE loans
            SET
                loan_status = :loan_status,
                released_at = :released_at,
                released_by = :released_by,
                release_date = :release_date
            WHERE id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'loan_status' => 'Active',
            'released_at' => $releasedAt,
            'released_by' => $userId,
            'release_date' => $releaseDate,
        ]);
    }

    /**
     * Release a loan and persist its first amortization schedule atomically.
     *
     * @param array<int, array<string, mixed>> $schedule
     */
    public function releaseWithSchedule(
        int $id,
        int $userId,
        string $releasedAt,
        string $releaseDate,
        array $schedule,
    ): void {
        $pdo = $this->connection();
        $pdo->beginTransaction();

        try {
            $existing = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM loan_amortizations
                 WHERE loan_id = :loan_id'
            );
            $existing->execute(['loan_id' => $id]);

            if ((int) $existing->fetchColumn() > 0) {
                throw new \RuntimeException(
                    'An amortization schedule already exists for this loan.'
                );
            }

            $update = $pdo->prepare(
                'UPDATE loans
                 SET
                    loan_status = :loan_status,
                    released_at = :released_at,
                    released_by = :released_by,
                    release_date = :release_date
                 WHERE id = :id
                   AND application_status = :application_status
                   AND loan_status IS NULL'
            );

            $update->execute([
                'id' => $id,
                'loan_status' => 'Active',
                'released_at' => $releasedAt,
                'released_by' => $userId,
                'release_date' => $releaseDate,
                'application_status' => 'Approved',
            ]);

            if ($update->rowCount() !== 1) {
                throw new \RuntimeException(
                    'The loan could not be transitioned to Active.'
                );
            }

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
                    'loan_id' => $id,
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

    /**
     * Return a Fully Paid loan to Active after a final payment is reversed.
     */
    public function reactivate(int $id): void
    {
        $statement = $this->connection()->prepare(
            '
            UPDATE loans
            SET
                loan_status = :loan_status,
                fully_paid_at = NULL
            WHERE id = :id
              AND loan_status = :current_status
            '
        );

        $statement->execute([
            'id' => $id,
            'loan_status' => 'Active',
            'current_status' => 'Fully Paid',
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException(
                'The Fully Paid loan could not be reactivated.'
            );
        }
    }

    /**
     * Mark a loan fully paid.
     */
    public function markFullyPaid(
        int $id,
        string $fullyPaidAt,
    ): void {
        $statement = $this->connection()->prepare(
            '
            UPDATE loans
            SET
                loan_status = :loan_status,
                fully_paid_at = :fully_paid_at
            WHERE id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'loan_status' => 'Fully Paid',
            'fully_paid_at' => $fullyPaidAt,
        ]);
    }
}
