<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Support\EditSession;
use App\Foundation\Session;

final class EditService
{
    public function __construct(
        private readonly EditSession $session,
        private readonly MemberRepository $members,
        private readonly ActivityLogService $activityLog,
        private readonly Session $userSession,
    ) {}

    /**
     * Load an existing member into the edit session.
     */
    public function start(
        int $memberId,
    ): bool {
        $member = $this->members->find(
            $memberId,
        );

        if ($member === null) {
            return false;
        }

        $this->session->clear();

        $this->session->setMemberId(
            $memberId,
        );

        /*
        |--------------------------------------------------------------------------
        | Membership
        |--------------------------------------------------------------------------
        */

        $this->session->putStep(
            'membership',
            [
                'membership_date' =>
                (string) (
                    $member['membership_date'] ?? ''
                ),

                'membership_type' =>
                (string) (
                    $member['membership_type'] ?? 'regular'
                ),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Personal
        |--------------------------------------------------------------------------
        */

        $this->session->putStep(
            'personal',
            [
                'first_name' =>
                (string) (
                    $member['first_name'] ?? ''
                ),

                'middle_name' =>
                (string) (
                    $member['middle_name'] ?? ''
                ),

                'last_name' =>
                (string) (
                    $member['last_name'] ?? ''
                ),

                'suffix' =>
                (string) (
                    $member['suffix'] ?? ''
                ),

                'birth_date' =>
                (string) (
                    $member['birth_date'] ?? ''
                ),

                'birth_place' =>
                (string) (
                    $member['birth_place'] ?? ''
                ),

                /*
                |--------------------------------------------------------------------------
                | Normalize select values
                |--------------------------------------------------------------------------
                |
                | The form uses lowercase option values:
                |
                | male / female
                | single / married / widowed / separated
                |
                | Normalize the database values so differences in
                | capitalization or accidental surrounding spaces
                | don't cause the select to fall back to its placeholder.
                |
                */

                'sex' =>
                $this->normalizeSelectValue(
                    $member['sex'] ?? ''
                ),

                'civil_status' =>
                $this->normalizeSelectValue(
                    $member['civil_status'] ?? ''
                ),

                'nationality' =>
                (string) (
                    $member['nationality'] ?? ''
                ),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */

        $this->session->putStep(
            'contact',
            [
                'mobile_number' =>
                (string) (
                    $member['mobile_number'] ?? ''
                ),

                'telephone_number' =>
                (string) (
                    $member['telephone_number'] ?? ''
                ),

                'email_address' =>
                (string) (
                    $member['email_address'] ?? ''
                ),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Address
        |--------------------------------------------------------------------------
        */

        $this->session->putStep(
            'address',
            [
                'house_number' =>
                (string) (
                    $member['house_number'] ?? ''
                ),

                'street' =>
                (string) (
                    $member['street'] ?? ''
                ),

                'barangay' =>
                (string) (
                    $member['barangay'] ?? ''
                ),

                'city' =>
                (string) (
                    $member['city'] ?? ''
                ),

                'province' =>
                (string) (
                    $member['province'] ?? ''
                ),

                'zip_code' =>
                (string) (
                    $member['zip_code'] ?? ''
                ),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Livelihood
        |--------------------------------------------------------------------------
        */

        $this->session->putStep(
            'livelihood',
            [
                'employment_status' =>
                $this->normalizeSelectValue(
                    $member['employment_status'] ?? ''
                ),

                'occupation' =>
                (string) (
                    $member['occupation'] ?? ''
                ),

                'employer' =>
                (string) (
                    $member['employer'] ?? ''
                ),

                'monthly_income' =>
                (string) (
                    $member['monthly_income'] ?? ''
                ),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Education
        |--------------------------------------------------------------------------
        */

        $this->session->putStep(
            'education',
            [
                'highest_educational_attainment' =>
                (string) (
                    $member['highest_educational_attainment'] ?? ''
                ),

                'school_name' =>
                (string) (
                    $member['school_name'] ?? ''
                ),

                'graduation_year' =>
                (string) (
                    $member['graduation_year'] ?? ''
                ),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Beneficiaries
        |--------------------------------------------------------------------------
        */

        $originalBeneficiaries =
            $member['beneficiaries'] ?? [];

        $this->session->setBeneficiaries(
            $originalBeneficiaries,
        );

        $this->session->setOriginalBeneficiaries(
            $originalBeneficiaries,
        );

        return true;
    }

    /**
     * Normalize a select value for the wizard.
     */
    private function normalizeSelectValue(
        mixed $value,
    ): string {
        return strtolower(
            trim(
                (string) $value
            )
        );
    }

    /**
     * Save one edit wizard step.
     *
     * @param array<string, mixed> $data
     */
    public function saveStep(
        string $step,
        array $data,
    ): void {
        $this->session->putStep(
            $step,
            $data,
        );
    }

    /**
     * Persist the complete edited member.
     */
    public function update(): void
    {
        $memberId = $this->session->memberId();

        if ($memberId === null) {
            throw new \RuntimeException(
                'No member is currently being edited.'
            );
        }

        $data = $this->session->all();

        $beneficiaries = [];

        foreach (
            $data['beneficiaries'] ?? []
            as $beneficiary
        ) {
            $beneficiaries[] =
                \App\Features\Members\DTOs\BeneficiaryData::fromArray(
                    $beneficiary
                );
        }

        $registration =
            new \App\Features\Members\DTOs\MemberRegistrationData(
                membership: \App\Features\Members\DTOs\MembershipData::fromArray(
                    $data['membership'] ?? []
                ),

                personal: \App\Features\Members\DTOs\PersonalData::fromArray(
                    $data['personal'] ?? []
                ),

                contact: \App\Features\Members\DTOs\ContactData::fromArray(
                    $data['contact'] ?? []
                ),

                address: \App\Features\Members\DTOs\AddressData::fromArray(
                    $data['address'] ?? []
                ),

                livelihood: \App\Features\Members\DTOs\LivelihoodData::fromArray(
                    $data['livelihood'] ?? []
                ),

                education: \App\Features\Members\DTOs\EducationData::fromArray(
                    $data['education'] ?? []
                ),

                beneficiaries: $beneficiaries,
            );

        $this->members->update(
            $memberId,
            $registration,
        );

        $userId = $this->userSession->get('user_id');

        $this->activityLog->record(
            userId: $userId !== null
                ? (int) $userId
                : null,
            action: 'MEMBER_UPDATED',
            description: sprintf(
                'Member #%d was updated.',
                $memberId,
            ),
            subjectType: 'Member',
            subjectId: $memberId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
        );

        $this->clear();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->session->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function step(
        string $step,
    ): array {
        return $this->session->getStep(
            $step,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function beneficiaries(): array
    {
        return $this->session->beneficiaries();
    }

    public function memberId(): ?int
    {
        return $this->session->memberId();
    }

    public function has(): bool
    {
        return $this->session->has();
    }

    public function clear(): void
    {
        $this->session->clear();
    }
}
