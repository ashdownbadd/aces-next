<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\Support\RegistrationSession;

final class BeneficiaryService
{
    public function __construct(
        private readonly RegistrationSession $session,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->session->beneficiaries();
    }

    /**
     * @param array<string, mixed> $beneficiary
     */
    public function add(array $beneficiary): void
    {
        $beneficiaries = $this->session->beneficiaries();

        $beneficiaries[] = $beneficiary;

        $this->session->setBeneficiaries($beneficiaries);
    }

    /**
     * @param array<string, mixed> $beneficiary
     */
    public function update(
        int $index,
        array $beneficiary,
    ): void {
        $beneficiaries = $this->session->beneficiaries();

        if (! isset($beneficiaries[$index])) {
            return;
        }

        $beneficiaries[$index] = $beneficiary;

        $this->session->setBeneficiaries($beneficiaries);
    }

    public function delete(int $index): void
    {
        $beneficiaries = $this->session->beneficiaries();

        if (! isset($beneficiaries[$index])) {
            return;
        }

        unset($beneficiaries[$index]);

        $this->session->setBeneficiaries(
            array_values($beneficiaries),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $index): ?array
    {
        return $this->session->beneficiaries()[$index] ?? null;
    }

    public function count(): int
    {
        return count(
            $this->session->beneficiaries(),
        );
    }

    public function clear(): void
    {
        $this->session->setBeneficiaries([]);
    }
}
