<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use PDO;

final class MemberNumberGenerator
{
    public function __construct(
        private readonly PDO $connection,
    ) {}

    public function generate(): string
    {
        $statement = $this->connection->query(
            '
                SELECT member_number
                FROM members
                ORDER BY id DESC
                LIMIT 1
            '
        );

        $lastNumber = $statement->fetchColumn();

        if ($lastNumber === false) {
            return '000001';
        }

        return str_pad(
            (string) (((int) $lastNumber) + 1),
            6,
            '0',
            STR_PAD_LEFT,
        );
    }
}
