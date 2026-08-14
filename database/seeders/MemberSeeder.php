<?php

declare(strict_types=1);

namespace App\Console\Seeders;

use PDO;
use Throwable;

final class MemberSeeder extends Seeder
{
    private const MEMBER_COUNT = 100;

    public function run(): void
    {
        $pdo = $this->database->connection();

        $pdo->beginTransaction();

        try {
            $memberNumbers = $this->existingMemberNumbers($pdo);

            $nextNumber = $this->nextMemberNumber(
                $memberNumbers,
            );

            for (
                $index = 0;
                $index < self::MEMBER_COUNT;
                $index++
            ) {
                $memberNumber = str_pad(
                    (string) $nextNumber,
                    4,
                    '0',
                    STR_PAD_LEFT,
                );

                $this->createMember(
                    $pdo,
                    $memberNumber,
                    $index,
                );

                $nextNumber++;
            }

            $pdo->commit();

            echo
            'Seeded '
                . self::MEMBER_COUNT
                . ' test members.'
                . PHP_EOL;
        } catch (Throwable $exception) {
            $pdo->rollBack();

            throw $exception;
        }
    }

    /**
     * @return array<string, true>
     */
    private function existingMemberNumbers(
        PDO $pdo,
    ): array {
        $statement = $pdo->query(
            '
            SELECT member_number
            FROM members
            '
        );

        $numbers = [];

        foreach (
            $statement->fetchAll(PDO::FETCH_COLUMN)
            as $number
        ) {
            $numbers[(string) $number] = true;
        }

        return $numbers;
    }

    /**
     * Find the next available numeric member number.
     *
     * @param array<string, true> $existingNumbers
     */
    private function nextMemberNumber(
        array $existingNumbers,
    ): int {
        $number = 1;

        while (
            isset(
                $existingNumbers[str_pad(
                    (string) $number,
                    4,
                    '0',
                    STR_PAD_LEFT,
                )]
            )
        ) {
            $number++;
        }

        return $number;
    }

    private function createMember(
        PDO $pdo,
        string $memberNumber,
        int $index,
    ): void {
        $firstNames = [
            'Juan',
            'Maria',
            'Pedro',
            'Ana',
            'Carlos',
            'Sofia',
            'Miguel',
            'Angela',
            'Jose',
            'Gabriel',
            'Patricia',
            'Daniel',
            'Andrea',
            'Mark',
            'Christine',
            'Michael',
            'Nicole',
            'Joshua',
            'Camille',
            'Nathan',
        ];

        $middleNames = [
            'Santos',
            'Reyes',
            'Garcia',
            'Mendoza',
            'Bautista',
            'Cruz',
            'Navarro',
            'Ramos',
            'Castillo',
            'Flores',
        ];

        $lastNames = [
            'Dela Cruz',
            'Santos',
            'Reyes',
            'Garcia',
            'Mendoza',
            'Bautista',
            'Cruz',
            'Navarro',
            'Ramos',
            'Castillo',
            'Flores',
            'Aquino',
            'Torres',
            'Rivera',
            'Fernandez',
        ];

        $firstName = $firstNames[$index % count($firstNames)];

        $middleName = $middleNames[$index % count($middleNames)];

        $lastName = $lastNames[$index % count($lastNames)];

        $sex = in_array(
            $firstName,
            [
                'Maria',
                'Ana',
                'Sofia',
                'Angela',
                'Patricia',
                'Andrea',
                'Christine',
                'Nicole',
                'Camille',
            ],
            true,
        )
            ? 'Female'
            : 'Male';

        $civilStatuses = [
            'Single',
            'Married',
            'Widowed',
            'Separated',
        ];

        $civilStatus = $civilStatuses[$index % count($civilStatuses)];

        $membershipTypes = [
            'Regular',
            'Associate',
        ];

        $membershipType = $membershipTypes[$index % count($membershipTypes)];

        $statuses = [
            'Active',
            'Active',
            'Active',
            'Pending',
            'Inactive',
        ];

        $status = $statuses[$index % count($statuses)];

        $membershipDate = date(
            'Y-m-d',
            strtotime(
                '-' . ($index % 1200) . ' days'
            ),
        );

        $birthDate = date(
            'Y-m-d',
            strtotime(
                '-' . (25 + ($index % 45))
                    . ' years',
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Member
        |--------------------------------------------------------------------------
        */

        $statement = $pdo->prepare(
            '
            INSERT INTO members
            (
                member_number,
                membership_type,
                membership_date,
                status
            )
            VALUES
            (
                :member_number,
                :membership_type,
                :membership_date,
                :status
            )
            '
        );

        $statement->execute([
            'member_number' =>
            $memberNumber,

            'membership_type' =>
            $membershipType,

            'membership_date' =>
            $membershipDate,

            'status' =>
            $status,
        ]);

        $memberId = (int) $pdo->lastInsertId();

        /*
        |--------------------------------------------------------------------------
        | Personal Profile
        |--------------------------------------------------------------------------
        */

        $statement = $pdo->prepare(
            '
            INSERT INTO member_profiles
            (
                member_id,
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
                :member_id,
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
            'member_id' =>
            $memberId,

            'first_name' =>
            $firstName,

            'middle_name' =>
            $middleName,

            'last_name' =>
            $lastName,

            'suffix' =>
            null,

            'birth_date' =>
            $birthDate,

            'birth_place' =>
            'Quezon City',

            'sex' =>
            $sex,

            'civil_status' =>
            $civilStatus,

            'nationality' =>
            'Filipino',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */

        $mobileNumber =
            '09'
            . str_pad(
                (string) (170000000 + $index),
                9,
                '0',
                STR_PAD_LEFT,
            );

        $statement = $pdo->prepare(
            '
            INSERT INTO member_contacts
            (
                member_id,
                mobile_number,
                telephone_number,
                email_address
            )
            VALUES
            (
                :member_id,
                :mobile_number,
                :telephone_number,
                :email_address
            )
            '
        );

        $statement->execute([
            'member_id' =>
            $memberId,

            'mobile_number' =>
            $mobileNumber,

            'telephone_number' =>
            null,

            'email_address' =>
            strtolower(
                $firstName
                    . '.'
                    . str_replace(
                        ' ',
                        '',
                        $lastName,
                    )
                    . $memberNumber
                    . '@example.test'
            ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Address
        |--------------------------------------------------------------------------
        */

        $statement = $pdo->prepare(
            '
            INSERT INTO member_addresses
            (
                member_id,
                house_number,
                street,
                barangay,
                city,
                province,
                zip_code
            )
            VALUES
            (
                :member_id,
                :house_number,
                :street,
                :barangay,
                :city,
                :province,
                :zip_code
            )
            '
        );

        $statement->execute([
            'member_id' =>
            $memberId,

            'house_number' =>
            (string) (100 + $index),

            'street' =>
            'Sample Street',

            'barangay' =>
            'Barangay Central',

            'city' =>
            'Quezon City',

            'province' =>
            'Metro Manila',

            'zip_code' =>
            '1100',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Livelihood
        |--------------------------------------------------------------------------
        */

        $employmentStatuses = [
            'employed',
            'self_employed',
            'business_owner',
            'ofw',
            'retired',
            'student',
            'unemployed',
        ];

        $employmentStatus =
            $employmentStatuses[$index % count(
                $employmentStatuses
            )];

        $income =
            15000 + (
                ($index % 10) * 2500
            );

        $statement = $pdo->prepare(
            '
            INSERT INTO member_livelihoods
            (
                member_id,
                employment_status,
                occupation,
                employer,
                monthly_income
            )
            VALUES
            (
                :member_id,
                :employment_status,
                :occupation,
                :employer,
                :monthly_income
            )
            '
        );

        $statement->execute([
            'member_id' =>
            $memberId,

            'employment_status' =>
            $employmentStatus,

            'occupation' =>
            $employmentStatus === 'student'
                ? 'Student'
                : 'Employee',

            'employer' =>
            $employmentStatus === 'employed'
                ? 'Sample Company'
                : null,

            'monthly_income' =>
            $income,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Education
        |--------------------------------------------------------------------------
        */

        $educationLevels = [
            'Elementary',
            'High School',
            'Senior High School',
            'College',
            'Postgraduate',
        ];

        $statement = $pdo->prepare(
            '
            INSERT INTO member_educations
            (
                member_id,
                highest_educational_attainment
            )
            VALUES
            (
                :member_id,
                :education
            )
            '
        );

        $statement->execute([
            'member_id' =>
            $memberId,

            'education' =>
            $educationLevels[$index % count(
                $educationLevels
            )],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Beneficiaries
        |--------------------------------------------------------------------------
        |
        | We intentionally do NOT insert share_percentage.
        | The current feature no longer uses it.
        |
        */

        if ($index % 3 !== 0) {
            $this->createBeneficiary(
                $pdo,
                $memberId,
                $index,
            );
        }

        if ($index % 5 === 0) {
            $this->createBeneficiary(
                $pdo,
                $memberId,
                $index + 1000,
            );
        }
    }

    private function createBeneficiary(
        PDO $pdo,
        int $memberId,
        int $index,
    ): void {
        $firstNames = [
            'John',
            'Mary',
            'James',
            'Elizabeth',
            'Robert',
            'Jennifer',
            'William',
            'Linda',
        ];

        $lastNames = [
            'Dela Cruz',
            'Santos',
            'Reyes',
            'Garcia',
            'Mendoza',
            'Bautista',
        ];

        $relationships = [
            'Spouse',
            'Child',
            'Parent',
            'Sibling',
        ];

        $statement = $pdo->prepare(
            '
            INSERT INTO member_beneficiaries
            (
                member_id,
                first_name,
                middle_name,
                last_name,
                suffix,
                relationship,
                birth_date,
                remarks
            )
            VALUES
            (
                :member_id,
                :first_name,
                :middle_name,
                :last_name,
                :suffix,
                :relationship,
                :birth_date,
                :remarks
            )
            '
        );

        $statement->execute([
            'member_id' =>
            $memberId,

            'first_name' =>
            $firstNames[$index % count(
                $firstNames
            )],

            'middle_name' =>
            'Test',

            'last_name' =>
            $lastNames[$index % count(
                $lastNames
            )],

            'suffix' =>
            null,

            'relationship' =>
            $relationships[$index % count(
                $relationships
            )],

            'birth_date' =>
            date(
                'Y-m-d',
                strtotime(
                    '-'
                        . (10 + ($index % 50))
                        . ' years'
                ),
            ),

            'remarks' =>
            'Test beneficiary',
        ]);
    }
}
