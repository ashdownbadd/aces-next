<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\Support\EditSession;
use App\Features\Members\Support\RegistrationSession;

final class BeneficiaryService
{
    public function __construct(
        private readonly RegistrationSession $registrationSession,
        private readonly EditSession $editSession,
    ) {}

    /**
     * Return the session belonging to the current wizard mode.
     *
     * Existing member edits use EditSession; new registrations use
     * RegistrationSession.
     */
    private function activeSession(): EditSession|RegistrationSession
    {
        if ($this->editSession->has()) {
            return $this->editSession;
        }

        return $this->registrationSession;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->activeSession()->beneficiaries();
    }

    /**
     * @param array<string, mixed> $beneficiary
     */
    public function add(array $beneficiary): void
    {
        $session = $this->activeSession();
        $beneficiaries = $session->beneficiaries();

        $beneficiaries[] = $beneficiary;

        $session->setBeneficiaries($beneficiaries);
    }

    /**
     * @param array<string, mixed> $beneficiary
     */
    public function update(
        int $index,
        array $beneficiary,
    ): void {
        $session = $this->activeSession();
        $beneficiaries = $session->beneficiaries();

        if (! isset($beneficiaries[$index])) {
            return;
        }

        $existing = $beneficiaries[$index];

        if (isset($existing['id'])) {
            $beneficiary['id'] = (int) $existing['id'];
        }

        $beneficiaries[$index] = $beneficiary;

        $session->setBeneficiaries($beneficiaries);
    }

    public function delete(int $index): void
    {
        $session = $this->activeSession();
        $beneficiaries = $session->beneficiaries();

        if (! isset($beneficiaries[$index])) {
            return;
        }

        unset($beneficiaries[$index]);

        $session->setBeneficiaries(
            array_values($beneficiaries),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $index): ?array
    {
        return $this->activeSession()->beneficiaries()[$index] ?? null;
    }

    public function count(): int
    {
        return count(
            $this->session->beneficiaries(),
        );
    }

    public function clear(): void
    {
        $this->activeSession()->setBeneficiaries([]);
    }
}
