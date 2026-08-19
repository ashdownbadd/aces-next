<?php

declare(strict_types=1);

namespace App\Features\ActivityLogs\Repositories;

use App\Foundation\Repository;
use PDO;

final class ActivityLogRepository extends Repository
{

    /**
     * Retrieve one activity log by ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            "
            SELECT
                al.id,
                al.user_id,
                al.action,
                al.description,
                al.subject_type,
                al.subject_id,
                al.ip_address,
                al.created_at,
                u.username,
                CONCAT_WS(
                    ' ',
                    u.first_name,
                    NULLIF(u.middle_name, ''),
                    u.last_name
                ) AS user_name
            FROM activity_logs AS al
            LEFT JOIN users AS u
                ON u.id = al.user_id
            WHERE al.id = :id
            LIMIT 1
            "
        );

        $statement->execute([
            'id' => $id,
        ]);

        $log = $statement->fetch(PDO::FETCH_ASSOC);

        return $log === false ? null : $log;
    }

    /**
     * Retrieve a paginated list of activity logs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        string $action = '',
        int $userId = 0,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $limit = 25,
        int $offset = 0,
    ): array {
        $pdo = $this->connection();

        $sql = "
            SELECT
                al.id,
                al.user_id,
                al.action,
                al.description,
                al.subject_type,
                al.subject_id,
                al.ip_address,
                al.created_at,
                u.username,
                CONCAT_WS(
                    ' ',
                    u.first_name,
                    NULLIF(u.middle_name, ''),
                    u.last_name
                ) AS user_name
            FROM activity_logs AS al
            LEFT JOIN users AS u ON u.id = al.user_id
            WHERE 1 = 1
        ";

        $parameters = [];
        $search = trim($search);

        if ($search !== '') {
            $sql .= "
                AND (
                    al.action LIKE :search_action
                    OR al.description LIKE :search_description
                    OR al.subject_type LIKE :search_subject_type
                    OR u.username LIKE :search_username
                    OR CONCAT_WS(
                        ' ',
                        u.first_name,
                        NULLIF(u.middle_name, ''),
                        u.last_name
                    ) LIKE :search_user_name
                )
            ";

            $value = '%' . $search . '%';
            $parameters['search_action'] = $value;
            $parameters['search_description'] = $value;
            $parameters['search_subject_type'] = $value;
            $parameters['search_username'] = $value;
            $parameters['search_user_name'] = $value;
        }

        if ($action !== '') {
            $sql .= " AND al.action = :action";
            $parameters['action'] = $action;
        }

        if ($userId > 0) {
            $sql .= " AND al.user_id = :user_id";
            $parameters['user_id'] = $userId;
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND al.created_at >= :date_from";
            $parameters['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND al.created_at <= :date_to";
            $parameters['date_to'] = $dateTo . ' 23:59:59';
        }

        $sql .= "
            ORDER BY al.created_at DESC, al.id DESC
            LIMIT :limit OFFSET :offset
        ";

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
     * Count activity logs matching the supplied filters.
     */
    public function count(
        string $search = '',
        string $action = '',
        int $userId = 0,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): int {
        $pdo = $this->connection();

        $sql = "
            SELECT COUNT(*)
            FROM activity_logs AS al
            LEFT JOIN users AS u ON u.id = al.user_id
            WHERE 1 = 1
        ";

        $parameters = [];
        $search = trim($search);

        if ($search !== '') {
            $sql .= "
                AND (
                    al.action LIKE :search_action
                    OR al.description LIKE :search_description
                    OR al.subject_type LIKE :search_subject_type
                    OR u.username LIKE :search_username
                    OR CONCAT_WS(
                        ' ',
                        u.first_name,
                        NULLIF(u.middle_name, ''),
                        u.last_name
                    ) LIKE :search_user_name
                )
            ";

            $value = '%' . $search . '%';
            $parameters['search_action'] = $value;
            $parameters['search_description'] = $value;
            $parameters['search_subject_type'] = $value;
            $parameters['search_username'] = $value;
            $parameters['search_user_name'] = $value;
        }

        if ($action !== '') {
            $sql .= " AND al.action = :action";
            $parameters['action'] = $action;
        }

        if ($userId > 0) {
            $sql .= " AND al.user_id = :user_id";
            $parameters['user_id'] = $userId;
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND al.created_at >= :date_from";
            $parameters['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND al.created_at <= :date_to";
            $parameters['date_to'] = $dateTo . ' 23:59:59';
        }

        $statement = $pdo->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR,
            );
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<int, string>
     */
    public function actions(): array
    {
        $statement = $this->connection()->query(
            "
            SELECT DISTINCT action
            FROM activity_logs
            ORDER BY action ASC
            "
        );

        return array_map(
            static fn(array $row): string => (string) $row['action'],
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function users(): array
    {
        $statement = $this->connection()->query(
            "
            SELECT DISTINCT
                u.id,
                u.username,
                CONCAT_WS(
                    ' ',
                    u.first_name,
                    NULLIF(u.middle_name, ''),
                    u.last_name
                ) AS name
            FROM activity_logs AS al
            INNER JOIN users AS u ON u.id = al.user_id
            ORDER BY u.last_name, u.first_name, u.username
            "
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Record a system activity.
     */
    public function create(
        ?int $userId,
        string $action,
        ?string $description,
        ?string $subjectType,
        ?int $subjectId,
        ?string $ipAddress,
    ): void {
        $statement = $this->connection()->prepare(
            "
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                subject_type,
                subject_id,
                ip_address
            )
            VALUES (
                :user_id,
                :action,
                :description,
                :subject_type,
                :subject_id,
                :ip_address
            )
            "
        );

        $statement->bindValue(
            ':user_id',
            $userId,
            $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT,
        );

        $statement->bindValue(
            ':action',
            $action,
            PDO::PARAM_STR,
        );

        $statement->bindValue(
            ':description',
            $description,
            $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
        );

        $statement->bindValue(
            ':subject_type',
            $subjectType,
            $subjectType === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
        );

        $statement->bindValue(
            ':subject_id',
            $subjectId,
            $subjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT,
        );

        $statement->bindValue(
            ':ip_address',
            $ipAddress,
            $ipAddress === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
        );

        $statement->execute();
    }
}
