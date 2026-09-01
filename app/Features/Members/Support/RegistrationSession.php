<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use App\Foundation\Session;

final class RegistrationSession
{
    private const KEY = 'member_registration';
    private const STATE_KEY = '_wizard_state';

    private const STATE_DRAFTED = 'drafted';
    private const STATE_SAVED = 'saved';
    private const STATE_COMPLETED = 'completed';

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
     * Mark a step as saved or completed.
     */
    public function markStep(
        string $step,
        string $state = self::STATE_SAVED,
    ): void {
        $allowed = [
            self::STATE_DRAFTED,
            self::STATE_SAVED,
            self::STATE_COMPLETED,
        ];

        if (! in_array($state, $allowed, true)) {
            $state = self::STATE_SAVED;
        }

        $registration = $this->all();
        $states = is_array($registration[self::STATE_KEY] ?? null)
            ? $registration[self::STATE_KEY]
            : [];

        $current = (string) ($states[$step] ?? '');

        $priority = [
            self::STATE_DRAFTED => 1,
            self::STATE_SAVED => 2,
            self::STATE_COMPLETED => 3,
        ];

        if (
            $current === ''
            || ($priority[$state] ?? 0) >= ($priority[$current] ?? 0)
        ) {
            $states[$step] = $state;
        }

        $registration[self::STATE_KEY] = $states;

        $this->session->put(
            self::KEY,
            $registration,
        );
    }

    public function stepState(
        string $step,
    ): ?string {
        $states = $this->all()[self::STATE_KEY] ?? [];

        return is_array($states) && isset($states[$step])
            ? (string) $states[$step]
            : null;
    }

    /**
     * @return array<string, string>
     */
    public function states(): array
    {
        $states = $this->all()[self::STATE_KEY] ?? [];

        return is_array($states)
            ? array_map(
                static fn ($value): string => (string) $value,
                $states,
            )
            : [];
    }

    public function highestCompletedStepIndex(): int
    {
        $highest = -1;
        $states = $this->states();

        foreach (RegistrationWorkflow::all() as $index => $step) {
            if (
                ($states[$step['key']] ?? null)
                === self::STATE_COMPLETED
            ) {
                $highest = $index;
            }
        }

        return $highest;
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
