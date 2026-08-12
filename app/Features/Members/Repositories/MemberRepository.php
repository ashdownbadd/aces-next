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
     * Retrieve all members for the members list.
     */
    public function all(): array
    {
        $statement = $this->connection()->query(
            "
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

            ORDER BY m.member_number ASC
            "
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the total number of members.
     */
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
                    highest_educational_attainment
                )
                VALUES
                (
                    :member_id,
                    :highest_educational_attainment
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
                        share_percentage,
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
                        :share_percentage,
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
                        'share_percentage' =>
                        $beneficiary->sharePercentage,
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

            me.highest_educational_attainment

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
            share_percentage,
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
