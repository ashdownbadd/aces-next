<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

use App\Foundation\Session;

final class EditSession
{
    private const KEY = 'member_edit';

    public function __construct(
        private readonly Session $session,
    ) {}

    /**
     * Store one edit wizard step.
     *
     * @param array<string, mixed> $data
     */
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

    /**
     * Get one edit wizard step.
     *
     * @return array<string, mixed>
     */
    public function getStep(
        string $step,
    ): array {
        return $this->all()[$step] ?? [];
    }

    /**
     * Get the complete edit session.
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
     * Store the member being edited.
     */
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

    /**
     * Return the member currently being edited.
     */
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
