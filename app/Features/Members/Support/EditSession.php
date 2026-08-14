<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use App\Foundation\Session;

final class EditSession
{
    private const KEY = 'member_edit';

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
