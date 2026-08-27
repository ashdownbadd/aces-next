<?php

declare(strict_types=1);

namespace App\Features\Members\Repositories;

use App\Features\Members\DTOs\MemberRegistrationData;
use App\Foundation\Repository;
use PDO;
use Throwable;

final class MemberRepository extends Repository
{
    /**
     * Retrieve a paginated list of members.
     *
     * Search fields:
     * - Member number
     * - First name
     * - Middle name
     * - Last name
     * - Full name
     * - Mobile number
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        string $status = '',
        int $limit = 25,
        int $offset = 0,
    ): array {
        $pdo = $this->connection();

        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $sql = "
        SELECT
            m.id,
            m.member_number,
            m.membership_date,
            m.membership_type,
            m.status,

            CONCAT_WS(
                ' ',
                mp.first_name,
                NULLIF(mp.middle_name, ''),
                mp.last_name,
                NULLIF(mp.suffix, '')
            ) AS full_name,

            mc.mobile_number

        FROM members AS m

        LEFT JOIN member_profiles AS mp
            ON mp.member_id = m.id

        LEFT JOIN member_contacts AS mc
            ON mc.member_id = m.id
    ";

        $parameters = [];
        $conditions = [];

        $search = trim($search);
        $status = trim($status);

        if ($search !== '') {
            $conditions[] = "(
                m.member_number LIKE :search_member_number
                OR mp.first_name LIKE :search_first_name
                OR mp.middle_name LIKE :search_middle_name
                OR mp.last_name LIKE :search_last_name
                OR CONCAT_WS(
                    ' ',
                    mp.first_name,
                    NULLIF(mp.middle_name, ''),
                    mp.last_name,
                    NULLIF(mp.suffix, '')
                ) LIKE :search_full_name
                OR mc.mobile_number LIKE :search_mobile
            )";

            $searchValue = '%' . $search . '%';

            $parameters = [
                'search_member_number' => $searchValue,
                'search_first_name' => $searchValue,
                'search_middle_name' => $searchValue,
                'search_last_name' => $searchValue,
                'search_full_name' => $searchValue,
                'search_mobile' => $searchValue,
            ];
        }

        if ($status !== '') {
            $conditions[] = "m.status = :status";
            $parameters['status'] = $status;
        }

        if ($conditions !== []) {
            $sql .= "
            WHERE " . implode("
                AND ", $conditions);
        }

        $sql .= "
        ORDER BY m.member_number ASC
        LIMIT :limit
        OFFSET :offset
    ";

        $statement = $pdo->prepare($sql);

        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR,
            );
        }

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT,
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT,
        );

        $statement->execute();

        return $statement->fetchAll(
            PDO::FETCH_ASSOC,
        );
    }

    /**
     * Return the total number of members matching a search query.
     */
    public function count(
        string $search = '',
        string $status = '',
    ): int {
        $pdo = $this->connection();

        $sql = "
        SELECT COUNT(*)

        FROM members AS m

        LEFT JOIN member_profiles AS mp
            ON mp.member_id = m.id

        LEFT JOIN member_contacts AS mc
            ON mc.member_id = m.id
    ";

        $parameters = [];
        $conditions = [];

        $search = trim($search);
        $status = trim($status);

        if ($search !== '') {
            $conditions[] = "(
                m.member_number LIKE :search_member_number
                OR mp.first_name LIKE :search_first_name
                OR mp.middle_name LIKE :search_middle_name
                OR mp.last_name LIKE :search_last_name
                OR CONCAT_WS(
                    ' ',
                    mp.first_name,
                    NULLIF(mp.middle_name, ''),
                    mp.last_name,
                    NULLIF(mp.suffix, '')
                ) LIKE :search_full_name
                OR mc.mobile_number LIKE :search_mobile
            )";

            $searchValue = '%' . $search . '%';

            $parameters = [
                'search_member_number' => $searchValue,
                'search_first_name' => $searchValue,
                'search_middle_name' => $searchValue,
                'search_last_name' => $searchValue,
                'search_full_name' => $searchValue,
                'search_mobile' => $searchValue,
            ];
        }

        if ($status !== '') {
            $conditions[] = "m.status = :status";
            $parameters['status'] = $status;
        }

        if ($conditions !== []) {
            $sql .= "
            WHERE " . implode("
                AND ", $conditions);
        }

        $statement = $pdo->prepare($sql);

        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }

    /**
     * Return the latest member number.
     */
    public function lastMemberNumber(): ?string
    {
        $statement = $this->connection()->query(
            "
            SELECT member_number
            FROM members
            ORDER BY id DESC
            LIMIT 1
            "
        );

        $memberNumber = $statement->fetchColumn();

        return $memberNumber === false
            ? null
            : (string) $memberNumber;
    }

    /**
     * Persist a complete member registration.
     *
     * Every related record is created inside the same
     * database transaction.
     */
    public function create(
        MemberRegistrationData $registration,
        string $memberNumber,
        string $status = 'Pending',
    ): int {
        $pdo = $this->connection();

        $pdo->beginTransaction();

        try {
            /*
             |--------------------------------------------------------------------------
             | Member
             |--------------------------------------------------------------------------
             */

            $statement = $pdo->prepare(
                "
                INSERT INTO members
                (
                    member_number,
                    membership_date,
                    membership_type,
                    status
                )
                VALUES
                (
                    :member_number,
                    :membership_date,
                    :membership_type,
                    :status
                )
                "
            );

            $statement->execute([
                'member_number' => $memberNumber,
                'membership_date' =>
                $registration->membership->membershipDate,
                'membership_type' =>
                $registration->membership->membershipType,
                'status' => $status,
            ]);

            $memberId = (int) $pdo->lastInsertId();

            /*
             |--------------------------------------------------------------------------
             | Personal Profile
             |--------------------------------------------------------------------------
             */

            $statement = $pdo->prepare(
                "
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
                "
            );

            $statement->execute([
                'member_id' => $memberId,
                'first_name' =>
                $registration->personal->firstName,
                'middle_name' =>
                $this->nullable(
                    $registration->personal->middleName
                ),
                'last_name' =>
                $registration->personal->lastName,
                'suffix' =>
                $this->nullable(
                    $registration->personal->suffix
                ),
                'birth_date' =>
                $this->nullable(
                    $registration->personal->birthDate
                ),
                'birth_place' =>
                $this->nullable(
                    $registration->personal->birthPlace
                ),
                'sex' =>
                $this->nullable(
                    $registration->personal->sex
                ),
                'civil_status' =>
                $this->nullable(
                    $registration->personal->civilStatus
                ),
                'nationality' =>
                $this->nullable(
                    $registration->personal->nationality
                ),
            ]);

            /*
             |--------------------------------------------------------------------------
             | Contact
             |--------------------------------------------------------------------------
             */

            $statement = $pdo->prepare(
                "
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
                "
            );

            $statement->execute([
                'member_id' => $memberId,
                'mobile_number' =>
                $this->nullable(
                    $registration->contact->mobileNumber
                ),
                'telephone_number' =>
                $this->nullable(
                    $registration->contact->telephoneNumber
                ),
                'email_address' =>
                $this->nullable(
                    $registration->contact->emailAddress
                ),
            ]);

            /*
             |--------------------------------------------------------------------------
             | Address
             |--------------------------------------------------------------------------
             */

            $statement = $pdo->prepare(
                "
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
                "
            );

            $statement->execute([
                'member_id' => $memberId,
                'house_number' =>
                $this->nullable(
                    $registration->address->houseNumber
                ),
                'street' =>
                $registration->address->street,
                'barangay' =>
                $registration->address->barangay,
                'city' =>
                $registration->address->city,
                'province' =>
                $registration->address->province,
                'zip_code' =>
                $registration->address->zipCode,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Livelihood
             |--------------------------------------------------------------------------
             */

            $statement = $pdo->prepare(
                "
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
                "
            );

            $statement->execute([
                'member_id' => $memberId,
                'employment_status' =>
                $this->nullable(
                    $registration->livelihood->livelihoodType
                ),
                'occupation' =>
                $this->nullable(
                    $registration->livelihood->occupation
                ),
                'employer' =>
                $this->nullable(
                    $registration->livelihood->employer
                ),
                'monthly_income' =>
                $this->nullable(
                    $registration->livelihood->monthlyIncome
                ),
            ]);

            /*
             |--------------------------------------------------------------------------
             | Education
             |--------------------------------------------------------------------------
             */

            $statement = $pdo->prepare(
                "
                INSERT INTO member_educations
                (
                    member_id,
                    highest_educational_attainment,
                    school_name,
                    graduation_year
                )
                VALUES
                (
                    :member_id,
                    :highest_educational_attainment,
                    :school_name,
                    :graduation_year
                )
                "
            );

            $statement->execute([
                'member_id' => $memberId,
                'highest_educational_attainment' =>
                $this->nullable(
                    $registration
                        ->education
                        ->highestEducationalAttainment
                ),
                'school_name' =>
                $this->nullable(
                    $registration
                        ->education
                        ->schoolName
                ),
                'graduation_year' =>
                $registration
                    ->education
                    ->graduationYear,
            ]);

            /*
             |--------------------------------------------------------------------------
             | Beneficiaries
             |--------------------------------------------------------------------------
             */

            if ($registration->beneficiaries !== []) {
                $statement = $pdo->prepare(
                    "
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
                    "
                );

                foreach ($registration->beneficiaries as $beneficiary) {
                    $statement->execute([
                        'member_id' => $memberId,
                        'first_name' =>
                        $beneficiary->firstName,
                        'middle_name' =>
                        $this->nullable(
                            $beneficiary->middleName
                        ),
                        'last_name' =>
                        $beneficiary->lastName,
                        'suffix' =>
                        $this->nullable(
                            $beneficiary->suffix
                        ),
                        'relationship' =>
                        $beneficiary->relationship,
                        'birth_date' =>
                        $this->nullable(
                            $beneficiary->birthDate
                        ),
                        'remarks' =>
                        $this->nullable(
                            $beneficiary->remarks
                        ),
                    ]);
                }
            }

            $pdo->commit();

            return $memberId;
        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Convert empty strings to NULL for nullable database columns.
     */
    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    /**
     * Update the status of an existing member.
     */
    public function updateStatus(
        int $memberId,
        string $status,
    ): void {
        $statement = $this->connection()->prepare(
            "
            UPDATE members
            SET status = :status
            WHERE id = :id
            "
        );

        $statement->execute([
            'status' => $status,
            'id' => $memberId,
        ]);
    }

    /**
     * Update a complete existing member profile.
     *
     * Every related record is updated inside the same
     * database transaction.
     */
    public function update(
        int $memberId,
        MemberRegistrationData $registration,
    ): void {
        $pdo = $this->connection();

        $pdo->beginTransaction();

        try {
            /*
        |--------------------------------------------------------------------------
        | Member
        |--------------------------------------------------------------------------
        */

            $statement = $pdo->prepare(
                "
            UPDATE members
            SET
                membership_date = :membership_date,
                membership_type = :membership_type
            WHERE id = :id
            "
            );

            $statement->execute([
                'membership_date' =>
                $registration->membership->membershipDate,

                'membership_type' =>
                $registration->membership->membershipType,

                'id' => $memberId,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Personal Profile
        |--------------------------------------------------------------------------
        */

            $statement = $pdo->prepare(
                "
            UPDATE member_profiles
            SET
                first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                suffix = :suffix,
                birth_date = :birth_date,
                birth_place = :birth_place,
                sex = :sex,
                civil_status = :civil_status,
                nationality = :nationality
            WHERE member_id = :member_id
            "
            );

            $statement->execute([
                'first_name' =>
                $registration->personal->firstName,

                'middle_name' =>
                $this->nullable(
                    $registration->personal->middleName
                ),

                'last_name' =>
                $registration->personal->lastName,

                'suffix' =>
                $this->nullable(
                    $registration->personal->suffix
                ),

                'birth_date' =>
                $this->nullable(
                    $registration->personal->birthDate
                ),

                'birth_place' =>
                $this->nullable(
                    $registration->personal->birthPlace
                ),

                'sex' =>
                $this->nullable(
                    $registration->personal->sex
                ),

                'civil_status' =>
                $this->nullable(
                    $registration->personal->civilStatus
                ),

                'nationality' =>
                $this->nullable(
                    $registration->personal->nationality
                ),

                'member_id' => $memberId,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */

            $statement = $pdo->prepare(
                "
            UPDATE member_contacts
            SET
                mobile_number = :mobile_number,
                telephone_number = :telephone_number,
                email_address = :email_address
            WHERE member_id = :member_id
            "
            );

            $statement->execute([
                'mobile_number' =>
                $this->nullable(
                    $registration->contact->mobileNumber
                ),

                'telephone_number' =>
                $this->nullable(
                    $registration->contact->telephoneNumber
                ),

                'email_address' =>
                $this->nullable(
                    $registration->contact->emailAddress
                ),

                'member_id' => $memberId,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Address
        |--------------------------------------------------------------------------
        */

            $statement = $pdo->prepare(
                "
            UPDATE member_addresses
            SET
                house_number = :house_number,
                street = :street,
                barangay = :barangay,
                city = :city,
                province = :province,
                zip_code = :zip_code
            WHERE member_id = :member_id
            "
            );

            $statement->execute([
                'house_number' =>
                $this->nullable(
                    $registration->address->houseNumber
                ),

                'street' =>
                $registration->address->street,

                'barangay' =>
                $registration->address->barangay,

                'city' =>
                $registration->address->city,

                'province' =>
                $registration->address->province,

                'zip_code' =>
                $registration->address->zipCode,

                'member_id' => $memberId,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Livelihood
        |--------------------------------------------------------------------------
        */

            $statement = $pdo->prepare(
                "
            UPDATE member_livelihoods
            SET
                employment_status = :employment_status,
                occupation = :occupation,
                employer = :employer,
                monthly_income = :monthly_income
            WHERE member_id = :member_id
            "
            );

            $statement->execute([
                'employment_status' =>
                $this->nullable(
                    $registration->livelihood->livelihoodType
                ),

                'occupation' =>
                $this->nullable(
                    $registration->livelihood->occupation
                ),

                'employer' =>
                $this->nullable(
                    $registration->livelihood->employer
                ),

                'monthly_income' =>
                $this->nullable(
                    $registration->livelihood->monthlyIncome
                ),

                'member_id' => $memberId,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Education
        |--------------------------------------------------------------------------
        */

            $statement = $pdo->prepare(
                "
            UPDATE member_educations
            SET
                highest_educational_attainment =
                    :highest_educational_attainment,
                school_name = :school_name,
                graduation_year = :graduation_year
            WHERE member_id = :member_id
            "
            );

            $statement->execute([
                'highest_educational_attainment' =>
                $this->nullable(
                    $registration
                        ->education
                        ->highestEducationalAttainment
                ),

                'school_name' =>
                $this->nullable(
                    $registration
                        ->education
                        ->schoolName
                ),

                'graduation_year' =>
                $registration
                    ->education
                    ->graduationYear,

                'member_id' => $memberId,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Beneficiaries
        |--------------------------------------------------------------------------
        |
        | Beneficiaries are treated as a complete collection.
        | We remove the old collection and insert the current one.
        |
        */

            $statement = $pdo->prepare(
                "
            DELETE FROM member_beneficiaries
            WHERE member_id = :member_id
            "
            );

            $statement->execute([
                'member_id' => $memberId,
            ]);

            if ($registration->beneficiaries !== []) {
                $statement = $pdo->prepare(
                    "
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
                "
                );

                foreach ($registration->beneficiaries as $beneficiary) {
                    $statement->execute([
                        'member_id' => $memberId,

                        'first_name' =>
                        $beneficiary->firstName,

                        'middle_name' =>
                        $this->nullable(
                            $beneficiary->middleName
                        ),

                        'last_name' =>
                        $beneficiary->lastName,

                        'suffix' =>
                        $this->nullable(
                            $beneficiary->suffix
                        ),

                        'relationship' =>
                        $beneficiary->relationship,

                        'birth_date' =>
                        $this->nullable(
                            $beneficiary->birthDate
                        ),

                        'remarks' =>
                        $this->nullable(
                            $beneficiary->remarks
                        ),
                    ]);
                }
            }

            $pdo->commit();
        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Retrieve a complete member profile by ID.
     */
    public function find(int $id): ?array
    {
        $pdo = $this->connection();

        $statement = $pdo->prepare(
            "
        SELECT
            m.id,
            m.member_number,
            m.membership_date,
            m.membership_type,
            m.status,

            mp.first_name,
            mp.middle_name,
            mp.last_name,
            mp.suffix,
            mp.birth_date,
            mp.birth_place,
            mp.sex,
            mp.civil_status,
            mp.nationality,

            mc.mobile_number,
            mc.telephone_number,
            mc.email_address,

            ma.house_number,
            ma.street,
            ma.barangay,
            ma.city,
            ma.province,
            ma.zip_code,

            ml.employment_status,
            ml.occupation,
            ml.employer,
            ml.monthly_income,

            me.highest_educational_attainment,
            me.school_name,
            me.graduation_year

        FROM members AS m

        LEFT JOIN member_profiles AS mp
            ON mp.member_id = m.id

        LEFT JOIN member_contacts AS mc
            ON mc.member_id = m.id

        LEFT JOIN member_addresses AS ma
            ON ma.member_id = m.id

        LEFT JOIN member_livelihoods AS ml
            ON ml.member_id = m.id

        LEFT JOIN member_educations AS me
            ON me.member_id = m.id

        WHERE m.id = :id

        LIMIT 1
        "
        );

        $statement->execute([
            'id' => $id,
        ]);

        $member = $statement->fetch(PDO::FETCH_ASSOC);

        if ($member === false) {
            return null;
        }

        $beneficiaryStatement = $pdo->prepare(
            "
        SELECT
            id,
            first_name,
            middle_name,
            last_name,
            suffix,
            relationship,
            birth_date,
            remarks
        FROM member_beneficiaries
        WHERE member_id = :member_id
        ORDER BY id ASC
        "
        );

        $beneficiaryStatement->execute([
            'member_id' => $id,
        ]);

        $member['beneficiaries'] =
            $beneficiaryStatement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return $member;
    }
}
