<?php

declare(strict_types=1);

namespace App\Features\ActivityLogs\Repositories;

use App\Foundation\Repository;
use PDO;

final class ActivityLogRepository extends Repository
{
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
