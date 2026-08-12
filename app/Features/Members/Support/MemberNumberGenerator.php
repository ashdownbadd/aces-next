<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use App\Foundation\Database;

final class MemberNumberGenerator
{
    public function __construct(
        private readonly Database $database,
    ) {}

    public function generate(): string
    {
        $pdo = $this->database->connection();

        $statement = $pdo->query(
            '
                SELECT member_number
                FROM members
                ORDER BY id DESC
                LIMIT 1
            '
        );

        $lastMemberNumber = $statement->fetchColumn();

        if ($lastMemberNumber === false || $lastMemberNumber === null) {
            $nextNumber = 1;
        } else {
            $nextNumber = (int) $lastMemberNumber + 1;
        }

        if ($nextNumber > 9999) {
            throw new \RuntimeException(
                'Member number limit of 9999 has been reached.'
            );
        }

        return str_pad(
            (string) $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
