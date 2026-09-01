<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Members\DTOs\AddressData;
use App\Features\Members\DTOs\BeneficiaryData;
use App\Features\Members\DTOs\ContactData;
use App\Features\Members\DTOs\EducationData;
use App\Features\Members\DTOs\LivelihoodData;
use App\Features\Members\DTOs\MemberRegistrationData;
use App\Features\Members\DTOs\MembershipData;
use App\Features\Members\DTOs\PersonalData;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Support\RegistrationSession;
use App\Features\Members\Support\RegistrationWorkflow;
use App\Foundation\Session;

final class RegistrationService
{
    public function __construct(
        private readonly RegistrationSession $session,
        private readonly MemberRepository $members,
        private readonly ActivityLogService $activityLog,
        private readonly Session $userSession,
    ) {}

    /**
     * Save one wizard step and return the next step.
     *
     * @param array<string, mixed> $data
     */
    public function saveStep(
        string $step,
        array $data,
    ): ?string {
        $this->session->putStep(
            $step,
            $data,
        );

        $this->session->markStep(
            $step,
            'completed',
        );

        return RegistrationWorkflow::next(
            $step,
        );
    }

    /**
     * Return all registration data currently stored
     * in the registration session.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->session->all();
    }

    /**
     * Return one registration step.
     *
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
     * Return the furthest step explicitly completed in this
     * registration session.
     */
    public function highestCompletedStepIndex(): int
    {
        return $this->session->highestCompletedStepIndex();
    }

    /**
     * Complete the registration.
     *
     * The controller provides the generated member number.
     * The repository persists the complete registration
     * in one transaction.
     */
    public function register(
        string $status = 'Pending',
    ): array {
        $registration = $this->buildRegistrationData();

        $result = $this->members->create(
            $registration,
            $status,
        );

        $memberId = (int) $result['id'];
        $memberNumber = (string) $result['member_number'];

        $userId = $this->userSession->get('user_id');

        $userId = $userId !== null
            ? (int) $userId
            : null;

        $this->activityLog->record(
            userId: $userId,
            action: 'MEMBER_CREATED',
            description: sprintf(
                'Member #%s was registered with status %s.',
                $memberNumber,
                $status,
            ),
            subjectType: 'Member',
            subjectId: $memberId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
        );

        foreach ($registration->beneficiaries as $beneficiary) {
            $this->activityLog->record(
                userId: $userId,
                action: 'MEMBER_BENEFICIARY_ADDED',
                description: sprintf(
                    'Beneficiary "%s" was added to Member #%s.',
                    $this->beneficiaryName($beneficiary),
                    $memberNumber,
                ),
                subjectType: 'Member',
                subjectId: $memberId,
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            );
        }

        $this->clear();

        return [
            'id' => $memberId,
            'member_number' => $memberNumber,
        ];
    }

    /**
     * @param BeneficiaryData $beneficiary
     */
    private function beneficiaryName(
        BeneficiaryData $beneficiary,
    ): string {
        $name = trim(
            implode(
                ' ',
                array_filter([
                    $beneficiary->firstName,
                    $beneficiary->middleName,
                    $beneficiary->lastName,
                    $beneficiary->suffix,
                ]),
            ),
        );

        return $name !== '' ? $name : 'Unnamed beneficiary';
    }

    /**
     * Clear all registration data from the session.
     */
    public function clear(): void
    {
        $this->session->clear();
    }

    /**
     * Convert the session arrays into strongly typed DTOs.
     */
    private function buildRegistrationData(): MemberRegistrationData
    {
        $data = $this->session->all();

        $beneficiaries = array_map(
            static function (array $beneficiary): BeneficiaryData {
                return BeneficiaryData::fromArray(
                    $beneficiary,
                );
            },
            $data['beneficiaries'] ?? [],
        );

        return new MemberRegistrationData(
            membership: MembershipData::fromArray(
                $data['membership'] ?? [],
            ),

            personal: PersonalData::fromArray(
                $data['personal'] ?? [],
            ),

            contact: ContactData::fromArray(
                $data['contact'] ?? [],
            ),

            address: AddressData::fromArray(
                $data['address'] ?? [],
            ),

            livelihood: LivelihoodData::fromArray(
                $data['livelihood'] ?? [],
            ),

            education: EducationData::fromArray(
                $data['education'] ?? [],
            ),

            beneficiaries: $beneficiaries,
        );
    }
}
