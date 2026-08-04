<?php

declare(strict_types=1);

namespace App\Features\Members\Repositories;

use App\Foundation\Repository;
use PDO;

final class MemberRepository extends Repository
{
    public function all(): array
    {
        $statement = $this->connection()->query(
            "
            SELECT *
            FROM members
            ORDER BY member_number ASC
            "
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(): int
    {
        $statement = $this->connection()->query(
            "
            SELECT COUNT(*)
            FROM members
            "
        );

        return (int) $statement->fetchColumn();
    }
}
