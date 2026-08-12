<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

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

final class RegistrationService
{
    public function __construct(
        private readonly RegistrationSession $session,
        private readonly MemberRepository $members,
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
     * Complete the registration.
     *
     * The controller provides the generated member number.
     * The repository persists the complete registration
     * in one transaction.
     */
    public function register(
        string $memberNumber,
        string $status = 'Pending',
    ): int {
        $registration = $this->buildRegistrationData();

        $memberId = $this->members->create(
            $registration,
            $memberNumber,
            $status,
        );

        $this->clear();

        return $memberId;
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
