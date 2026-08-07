<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

final class MemberRegistrationData
{
    /**
     * @param BeneficiaryData[] $beneficiaries
     */
    public function __construct(
        public readonly MembershipData $membership,
        public readonly PersonalData $personal,
        public readonly ContactData $contact,
        public readonly AddressData $address,
        public readonly LivelihoodData $livelihood,
        public readonly EducationData $education,
        public readonly array $beneficiaries,
    ) {}
}
