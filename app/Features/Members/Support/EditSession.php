<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use App\Foundation\Session;

final class EditSession
{
    private const KEY = 'member_edit';
    private const STATE_KEY = '_wizard_state';

    private const STATE_DRAFTED = 'drafted';
    private const STATE_SAVED = 'saved';
    private const STATE_COMPLETED = 'completed';

    private const ORIGINAL_BENEFICIARIES_KEY =
    '_original_beneficiaries';

    public function __construct(
        private readonly Session $session,
    ) {}

    public function putStep(
        string $step,
        array $data,
    ): void {
        $edit = $this->all();

        $edit[$step] = $data;

        $this->session->put(
            self::KEY,
            $edit,
        );
    }

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

        $edit = $this->all();
        $states = is_array($edit[self::STATE_KEY] ?? null)
            ? $edit[self::STATE_KEY]
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

        $edit[self::STATE_KEY] = $states;

        $this->session->put(
            self::KEY,
            $edit,
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

    public function getStep(
        string $step,
    ): array {
        return $this->all()[$step] ?? [];
    }

    public function all(): array
    {
        return $this->session->get(
            self::KEY,
            [],
        );
    }

    public function setMemberId(
        int $memberId,
    ): void {
        $edit = $this->all();

        $edit['_member_id'] = $memberId;

        $this->session->put(
            self::KEY,
            $edit,
        );
    }

    public function memberId(): ?int
    {
        $memberId = $this->all()['_member_id'] ?? null;

        if ($memberId === null) {
            return null;
        }

        return (int) $memberId;
    }

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
    public function setBeneficiaries(
        array $beneficiaries,
    ): void {
        $edit = $this->all();

        $edit['beneficiaries'] = $beneficiaries;

        $this->session->put(
            self::KEY,
            $edit,
        );
    }

    /**
     * Store the beneficiary collection as it existed
     * when the edit session was started.
     *
     * @param array<int, array<string, mixed>> $beneficiaries
     */
    public function setOriginalBeneficiaries(
        array $beneficiaries,
    ): void {
        $edit = $this->all();

        $edit[self::ORIGINAL_BENEFICIARIES_KEY] =
            $beneficiaries;

        $this->session->put(
            self::KEY,
            $edit,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function originalBeneficiaries(): array
    {
        return $this->all()[self::ORIGINAL_BENEFICIARIES_KEY] ?? [];
    }

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
}
