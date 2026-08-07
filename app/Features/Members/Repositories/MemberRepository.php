<?php

declare(strict_types=1);

namespace App\Features\Members\Repositories;

use App\Features\Members\DTOs\MembershipData;
use App\Features\Members\DTOs\PersonalData;
use App\Foundation\Repository;
use PDO;

final class MemberRepository extends Repository
{
    public function all(): array
    {
        $statement = $this->connection()->query(
            '
                SELECT *
                FROM members
                ORDER BY member_number ASC
            '
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(): int
    {
        $statement = $this->connection()->query(
            '
                SELECT COUNT(*)
                FROM members
            '
        );

        return (int) $statement->fetchColumn();
    }

    public function lastMemberNumber(): ?string
    {
        $statement = $this->connection()->query(
            '
                SELECT member_number
                FROM members
                ORDER BY id DESC
                LIMIT 1
            '
        );

        $memberNumber = $statement->fetchColumn();

        return $memberNumber === false
            ? null
            : (string) $memberNumber;
    }

    public function create(
        MembershipData $membership,
        PersonalData $personal,
        string $memberNumber,
        string $status = 'Pending',
    ): int {
        $statement = $this->connection()->prepare(
            '
                INSERT INTO members
                (
                    member_number,
                    membership_date,
                    membership_type,
                    status,

                    first_name,
                    middle_name,
                    last_name,
                    suffix,

                    birth_date,
                    birth_place,
                    sex,
                    civil_status,
                    nationality
                )
                VALUES
                (
                    :member_number,
                    :membership_date,
                    :membership_type,
                    :status,

                    :first_name,
                    :middle_name,
                    :last_name,
                    :suffix,

                    :birth_date,
                    :birth_place,
                    :sex,
                    :civil_status,
                    :nationality
                )
            '
        );

        $statement->execute([
            'member_number' => $memberNumber,

            'membership_date' => $membership->membershipDate,
            'membership_type' => $membership->membershipType,
            'status' => $status,

            'first_name' => $personal->firstName,
            'middle_name' => $personal->middleName,
            'last_name' => $personal->lastName,
            'suffix' => $personal->suffix,

            'birth_date' => $personal->birthDate,
            'birth_place' => $personal->birthPlace,
            'sex' => $personal->sex,
            'civil_status' => $personal->civilStatus,
            'nationality' => $personal->nationality,
        ]);

        return (int) $this->connection()->lastInsertId();
    }
}
