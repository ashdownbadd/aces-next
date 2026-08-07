<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use App\Foundation\Session;

final class RegistrationSession
{
    private const KEY = 'member_registration';

    public function __construct(
        private readonly Session $session,
    ) {}

    /**
     * Store one wizard step.
     *
     * @param array<string, mixed> $data
     */
    public function putStep(
        string $step,
        array $data,
    ): void {
        $registration = $this->all();

        $registration[$step] = $data;

        $this->session->put(
            self::KEY,
            $registration,
        );
    }

    /**
     * Get one wizard step.
     *
     * @return array<string, mixed>
     */
    public function getStep(string $step): array
    {
        return $this->all()[$step] ?? [];
    }

    /**
     * Get the complete registration.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->session->get(
            self::KEY,
            [],
        );
    }

    /**
     * Remove all registration data.
     */
    public function clear(): void
    {
        $this->session->forget(
            self::KEY,
        );
    }

    public function has(): bool
    {
        return $this->session->has(
            self::KEY,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Beneficiaries
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, array<string, mixed>>
     */
    public function beneficiaries(): array
    {
        return $this->all()['beneficiaries'] ?? [];
    }

    /**
     * @param array<int, array<string, mixed>> $beneficiaries
     */
    public function setBeneficiaries(array $beneficiaries): void
    {
        $registration = $this->all();

        $registration['beneficiaries'] = $beneficiaries;

        $this->session->put(
            self::KEY,
            $registration,
        );
    }

    public function clearBeneficiaries(): void
    {
        $registration = $this->all();

        unset($registration['beneficiaries']);

        $this->session->put(
            self::KEY,
            $registration,
        );
    }
}
