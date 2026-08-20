<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Repositories;

use App\Foundation\Database;
use PDO;

final class DashboardRepository
{
    public function __construct(
        private readonly Database $database,
    ) {}

    /** @return array<string, int> */
    public function memberCounts(): array
    {
        $pdo = $this->database->connection();

        $total = (int) $pdo->query(
            'SELECT COUNT(*) FROM members'
        )->fetchColumn();

        $types = $pdo->query(
            'SELECT membership_type, COUNT(*) AS total
             FROM members
             GROUP BY membership_type'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $statuses = $pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM members
             GROUP BY status'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $gender = $pdo->query(
            'SELECT sex, COUNT(*) AS total
             FROM member_profiles
             GROUP BY sex'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'total' => $total,
            'regular' => (int) ($types['Regular'] ?? 0),
            'associate' => (int) ($types['Associate'] ?? 0),
            'active' => (int) ($statuses['Active'] ?? $statuses['active'] ?? 0),
            'inactive' => (int) ($statuses['Inactive'] ?? $statuses['inactive'] ?? 0),
            'female' => (int) ($gender['Female'] ?? 0),
            'male' => (int) ($gender['Male'] ?? 0),
        ];
    }

    /** @return array<string, int> */
    public function loanCounts(): array
    {
        $pdo = $this->database->connection();

        $application = $pdo->query(
            'SELECT application_status, COUNT(*) AS total
             FROM loans
             GROUP BY application_status'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $lifecycle = $pdo->query(
            'SELECT loan_status, COUNT(*) AS total
             FROM loans
             GROUP BY loan_status'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $overdue = (int) $pdo->query(
            "SELECT COUNT(DISTINCT la.loan_id)
             FROM loan_amortizations AS la
             INNER JOIN loans AS l
                 ON l.id = la.loan_id
             WHERE l.loan_status = 'Active'
               AND la.status = 'Overdue'"
        )->fetchColumn();

        return [
            'pending' => (int) ($application['Pending'] ?? 0),
            'under_review' => (int) ($application['Under Review'] ?? 0),
            'approved' => (int) ($application['Approved'] ?? 0),
            'rejected' => (int) ($application['Rejected'] ?? 0),
            'active' => (int) ($lifecycle['Active'] ?? 0),
            'fully_paid' => (int) ($lifecycle['Fully Paid'] ?? 0),
            'overdue' => $overdue,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function negativeEquityMembers(): array
    {
        try {
            $statement = $this->database->connection()->query(
                "SELECT
                    m.id,
                    m.member_number,
                    CONCAT_WS(
                        ' ',
                        mp.first_name,
                        NULLIF(mp.middle_name, ''),
                        mp.last_name,
                        NULLIF(mp.suffix, '')
                    ) AS member_name,
                    (
                        COALESCE(SUM(le.credit), 0)
                        - COALESCE(SUM(le.debit), 0)
                    ) AS balance
                 FROM members AS m
                 LEFT JOIN member_profiles AS mp
                    ON mp.member_id = m.id
                 LEFT JOIN ledger_entries AS le
                    ON le.member_id = m.id
                 LEFT JOIN journal_vouchers AS jv
                    ON jv.id = le.voucher_id
                 WHERE jv.status = 'approved'
                    OR jv.status IS NULL
                 GROUP BY
                    m.id,
                    m.member_number,
                    mp.first_name,
                    mp.middle_name,
                    mp.last_name,
                    mp.suffix
                 HAVING balance < 0
                 ORDER BY balance ASC"
            );

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function pastDueLoans(): array
    {
        $statement = $this->database->connection()->query(
            "SELECT
                l.id,
                l.member_id,
                m.member_number,
                CONCAT_WS(
                    ' ',
                    mp.first_name,
                    NULLIF(mp.middle_name, ''),
                    mp.last_name,
                    NULLIF(mp.suffix, '')
                ) AS member_name,
                COUNT(la.id) AS overdue_periods
             FROM loans AS l
             INNER JOIN members AS m
                ON m.id = l.member_id
             LEFT JOIN member_profiles AS mp
                ON mp.member_id = m.id
             INNER JOIN loan_amortizations AS la
                ON la.loan_id = l.id
             WHERE l.loan_status = 'Active'
               AND la.status = 'Overdue'
             GROUP BY
                l.id,
                l.member_id,
                m.member_number,
                mp.first_name,
                mp.middle_name,
                mp.last_name,
                mp.suffix
             ORDER BY l.id DESC"
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
