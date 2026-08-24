<?php

declare(strict_types=1);

namespace App\Features\Ledger\Repositories;

use App\Foundation\Repository;
use PDO;
use RuntimeException;

final class JournalVoucherRepository extends Repository
{
    /**
     * @param array{
     *   reference_number:string,
     *   transaction_date:string,
     *   particulars:string,
     *   source_type:string|null,
     *   source_id:int|null,
     *   created_by:int
     * } $voucher
     * @param array<int, array{
     *   account_id:int,
     *   member_id:int|null,
     *   loan_id:int|null,
     *   line_description:string|null,
     *   debit:float,
     *   credit:float
     * }> $lines
     */
    /**
     * @param array{
     *   reference_number:string,
     *   transaction_date:string,
     *   particulars:string,
     *   source_type:string|null,
     *   source_id:int|null,
     *   created_by:int
     * } $voucher
     * @param array<int, array{
     *   account_id:int,
     *   member_id:int|null,
     *   loan_id:int|null,
     *   line_description:string|null,
     *   debit:float,
     *   credit:float
     * }> $lines
     */
    public function createPending(array $voucher, array $lines): int
    {
        $pdo = $this->connection();
        $ownsTransaction = ! $pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $statement = $pdo->prepare(
                'INSERT INTO journal_vouchers
                 (
                    reference_number,
                    transaction_date,
                    particulars,
                    source_type,
                    source_id,
                    reversal_of_voucher_id,
                    status,
                    created_by
                 )
                 VALUES
                 (
                    :reference_number,
                    :transaction_date,
                    :particulars,
                    :source_type,
                    :source_id,
                    :reversal_of_voucher_id,
                    :status,
                    :created_by
                 )'
            );

            $statement->execute([
                'reference_number' => $voucher['reference_number'],
                'transaction_date' => $voucher['transaction_date'],
                'particulars' => $voucher['particulars'],
                'source_type' => $voucher['source_type'],
                'source_id' => $voucher['source_id'],
                'reversal_of_voucher_id' =>
                $voucher['reversal_of_voucher_id'] ?? null,
                'status' => 'Pending',
                'created_by' => $voucher['created_by'],
            ]);

            $voucherId = (int) $pdo->lastInsertId();

            $line = $pdo->prepare(
                'INSERT INTO journal_lines
                 (
                    journal_voucher_id,
                    account_id,
                    member_id,
                    loan_id,
                    line_description,
                    debit,
                    credit
                 )
                 VALUES
                 (
                    :journal_voucher_id,
                    :account_id,
                    :member_id,
                    :loan_id,
                    :line_description,
                    :debit,
                    :credit
                 )'
            );

            foreach ($lines as $item) {
                $line->execute([
                    'journal_voucher_id' => $voucherId,
                    'account_id' => $item['account_id'],
                    'member_id' => $item['member_id'],
                    'loan_id' => $item['loan_id'],
                    'line_description' => $item['line_description'],
                    'debit' => $item['debit'],
                    'credit' => $item['credit'],
                ]);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $voucherId;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @return array<string,mixed>|null */
    public function findBySource(
        string $sourceType,
        int $sourceId,
    ): ?array {
        $statement = $this->connection()->prepare(
            'SELECT *
             FROM journal_vouchers
             WHERE source_type = :source_type
               AND source_id = :source_id
             ORDER BY id DESC
             LIMIT 1'
        );

        $statement->execute([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function all(
        string $search = '',
        string $status = '',
        int $limit = 25,
        int $offset = 0,
    ): array {
        $sql = "
            SELECT
                jv.id,
                jv.reference_number,
                jv.transaction_date,
                jv.particulars,
                jv.source_type,
                jv.source_id,
                jv.reversal_of_voucher_id,
                jv.status,
                jv.rejection_reason,
                jv.created_by,
                jv.approved_by,
                jv.approved_at,
                jv.posted_by,
                jv.posted_at,
                jv.created_at,
                u.username AS created_by_username,
                au.username AS approved_by_username,
                pu.username AS posted_by_username
            FROM journal_vouchers AS jv
            LEFT JOIN users AS u ON u.id = jv.created_by
            LEFT JOIN users AS au ON au.id = jv.approved_by
            LEFT JOIN users AS pu ON pu.id = jv.posted_by
            WHERE 1 = 1
        ";

        $parameters = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= "
                AND (
                    jv.reference_number LIKE :search_reference
                    OR jv.particulars LIKE :search_particulars
                    OR jv.source_type LIKE :search_source_type
                )
            ";
            $value = '%' . $search . '%';
            $parameters['search_reference'] = $value;
            $parameters['search_particulars'] = $value;
            $parameters['search_source_type'] = $value;
        }

        if ($status !== '') {
            $sql .= " AND jv.status = :status";
            $parameters['status'] = $status;
        }

        $sql .= "
            ORDER BY jv.transaction_date DESC, jv.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $this->connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(
        string $search = '',
        string $status = '',
    ): int {
        $sql = "
            SELECT COUNT(*)
            FROM journal_vouchers AS jv
            WHERE 1 = 1
        ";

        $parameters = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= "
                AND (
                    jv.reference_number LIKE :search_reference
                    OR jv.particulars LIKE :search_particulars
                    OR jv.source_type LIKE :search_source_type
                )
            ";
            $value = '%' . $search . '%';
            $parameters['search_reference'] = $value;
            $parameters['search_particulars'] = $value;
            $parameters['search_source_type'] = $value;
        }

        if ($status !== '') {
            $sql .= " AND jv.status = :status";
            $parameters['status'] = $status;
        }

        $statement = $this->connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function accounts(): array
    {
        $statement = $this->connection()->query(
            'SELECT id, account_code, account_name, account_type,
                    normal_balance, is_active
             FROM accounts
             ORDER BY account_code ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string,mixed>|null */
    public function accountById(int $accountId): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT
                id,
                account_code,
                account_name,
                account_type,
                normal_balance,
                is_active
             FROM accounts
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $accountId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function accountLedger(
        int $accountId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $sql = "
            SELECT
                jv.id AS voucher_id,
                jv.reference_number,
                jv.transaction_date,
                jv.particulars,
                jv.source_type,
                jv.source_id,
                jl.id AS line_id,
                jl.line_description,
                jl.debit,
                jl.credit
            FROM journal_lines AS jl
            INNER JOIN journal_vouchers AS jv
                ON jv.id = jl.journal_voucher_id
            WHERE jl.account_id = :account_id
              AND jv.status = 'Posted'
        ";

        $parameters = [
            'account_id' => $accountId,
        ];

        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND jv.transaction_date >= :date_from";
            $parameters['date_from'] = $dateFrom;
        }

        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND jv.transaction_date <= :date_to";
            $parameters['date_to'] = $dateTo;
        }

        $sql .= "
            ORDER BY jv.transaction_date ASC, jv.id ASC, jl.id ASC
        ";

        $statement = $this->connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR,
            );
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function accountOpeningBalance(
        int $accountId,
        ?string $dateFrom = null,
    ): float {
        if ($dateFrom === null || $dateFrom === '') {
            return 0.00;
        }

        $statement = $this->connection()->prepare(
            "
            SELECT
                COALESCE(SUM(jl.debit), 0) AS debit_total,
                COALESCE(SUM(jl.credit), 0) AS credit_total
            FROM journal_lines AS jl
            INNER JOIN journal_vouchers AS jv
                ON jv.id = jl.journal_voucher_id
            WHERE jl.account_id = :account_id
              AND jv.status = 'Posted'
              AND jv.transaction_date < :date_from
            "
        );

        $statement->execute([
            'account_id' => $accountId,
            'date_from' => $dateFrom,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return round(
            (float) ($row['debit_total'] ?? 0)
            - (float) ($row['credit_total'] ?? 0),
            2,
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function incomeStatement(
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $sql = "
            SELECT
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.normal_balance,
                COALESCE(SUM(jl.debit), 0) AS debit_total,
                COALESCE(SUM(jl.credit), 0) AS credit_total
            FROM accounts AS a
            LEFT JOIN journal_lines AS jl
                ON jl.account_id = a.id
            LEFT JOIN journal_vouchers AS jv
                ON jv.id = jl.journal_voucher_id
               AND jv.status = 'Posted'
        ";

        $parameters = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND jv.transaction_date >= :date_from";
            $parameters['date_from'] = $dateFrom;
        }

        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND jv.transaction_date <= :date_to";
            $parameters['date_to'] = $dateTo;
        }

        $sql .= "
            WHERE a.is_active = 1
              AND a.account_type IN ('Income', 'Expense')
            GROUP BY
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.normal_balance
            ORDER BY a.account_type ASC, a.account_code ASC
        ";

        $statement = $this->connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                PDO::PARAM_STR,
            );
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array<string,mixed>> */
    public function trialBalance(
        ?string $asOfDate = null,
    ): array {
        $sql = "
            SELECT
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.normal_balance,
                COALESCE(SUM(jl.debit), 0) AS debit_total,
                COALESCE(SUM(jl.credit), 0) AS credit_total
            FROM accounts AS a
            LEFT JOIN journal_lines AS jl
                ON jl.account_id = a.id
            LEFT JOIN journal_vouchers AS jv
                ON jv.id = jl.journal_voucher_id
               AND jv.status = 'Posted'
        ";

        $parameters = [];

        if ($asOfDate !== null && $asOfDate !== '') {
            $sql .= " AND jv.transaction_date <= :as_of_date";
            $parameters['as_of_date'] = $asOfDate;
        }

        $sql .= "
            WHERE a.is_active = 1
            GROUP BY
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.normal_balance
            ORDER BY a.account_code ASC
        ";

        $statement = $this->connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                PDO::PARAM_STR,
            );
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array<string,mixed>> */
    public function balanceSheet(
        ?string $asOfDate = null,
    ): array {
        $sql = "
            SELECT
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.normal_balance,
                COALESCE(SUM(jl.debit), 0) AS debit_total,
                COALESCE(SUM(jl.credit), 0) AS credit_total
            FROM accounts AS a
            LEFT JOIN journal_lines AS jl
                ON jl.account_id = a.id
            LEFT JOIN journal_vouchers AS jv
                ON jv.id = jl.journal_voucher_id
               AND jv.status = 'Posted'
        ";

        $parameters = [];

        if ($asOfDate !== null && $asOfDate !== '') {
            $sql .= " AND jv.transaction_date <= :as_of_date";
            $parameters['as_of_date'] = $asOfDate;
        }

        $sql .= "
            WHERE a.is_active = 1
            GROUP BY
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.normal_balance
            ORDER BY a.account_type ASC, a.account_code ASC
        ";

        $statement = $this->connection()->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                PDO::PARAM_STR,
            );
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function accountId(string $accountCode): int
    {
        $statement = $this->connection()->prepare(
            'SELECT id
             FROM accounts
             WHERE account_code = :account_code
               AND is_active = TRUE
             LIMIT 1'
        );

        $statement->execute([
            'account_code' => $accountCode,
        ]);

        $id = $statement->fetchColumn();

        return $id === false ? 0 : (int) $id;
    }

    /** @return array<string,mixed>|null */
    public function find(int $voucherId): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT *
             FROM journal_vouchers
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $voucherId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function lines(int $voucherId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT
                jl.id,
                jl.account_id,
                a.account_code,
                a.account_name,
                jl.member_id,
                jl.loan_id,
                jl.line_description,
                jl.debit,
                jl.credit,
                jl.created_at
             FROM journal_lines AS jl
             INNER JOIN accounts AS a
                ON a.id = jl.account_id
             WHERE jl.journal_voucher_id = :voucher_id
             ORDER BY jl.id ASC'
        );
        $statement->execute(['voucher_id' => $voucherId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approve(
        int $voucherId,
        int $userId,
        string $approvedAt,
    ): void {
        $statement = $this->connection()->prepare(
            "UPDATE journal_vouchers
             SET status = 'Approved',
                 approved_by = :approved_by,
                 approved_at = :approved_at
             WHERE id = :id
               AND status = 'Pending'"
        );

        $statement->execute([
            'id' => $voucherId,
            'approved_by' => $userId,
            'approved_at' => $approvedAt,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException(
                'Only Pending journal vouchers can be approved.'
            );
        }
    }

    public function post(
        int $voucherId,
        int $userId,
        string $postedAt,
    ): void {
        $statement = $this->connection()->prepare(
            "UPDATE journal_vouchers
             SET status = 'Posted',
                 posted_by = :posted_by,
                 posted_at = :posted_at
             WHERE id = :id
               AND status = 'Approved'"
        );

        $statement->execute([
            'id' => $voucherId,
            'posted_by' => $userId,
            'posted_at' => $postedAt,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException(
                'Only Approved journal vouchers can be posted.'
            );
        }
    }

    public function reject(
        int $voucherId,
        string $reason,
    ): void {
        $statement = $this->connection()->prepare(
            "UPDATE journal_vouchers
             SET status = 'Rejected',
                 rejection_reason = :rejection_reason
             WHERE id = :id
               AND status = 'Pending'"
        );

        $statement->execute([
            'id' => $voucherId,
            'rejection_reason' => $reason,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException(
                'Only Pending journal vouchers can be rejected.'
            );
        }
    }
}
