<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Members\Support\EditSession;
use App\Features\Members\Support\RegistrationSession;

final class BeneficiaryService
{
    public function __construct(
        private readonly RegistrationSession $registrationSession,
        private readonly EditSession $editSession,
        private readonly ActivityLogService $activityLog,
    ) {}

    private function activeSession(): EditSession|RegistrationSession
    {
        if ($this->editSession->has()) {
            return $this->editSession;
        }

        return $this->registrationSession;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->activeSession()->beneficiaries();
    }

    /** @param array<string, mixed> $beneficiary */
    public function add(array $beneficiary): void
    {
        $session = $this->activeSession();
        $beneficiaries = $session->beneficiaries();

        $beneficiaries[] = $beneficiary;
        $session->setBeneficiaries($beneficiaries);

        $memberId = $this->editSession->memberId();

        if ($memberId !== null) {
            $this->activityLog->record(
                userId: $this->currentUserId(),
                action: 'MEMBER_BENEFICIARY_ADDED',
                description: sprintf(
                    'Beneficiary "%s" was added to Member #%d.',
                    $this->beneficiaryName($beneficiary),
                    $memberId,
                ),
                subjectType: 'Member',
                subjectId: $memberId,
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            );
        }
    }

    /** @param array<string, mixed> $beneficiary */
    public function update(int $index, array $beneficiary): void
    {
        $session = $this->activeSession();
        $beneficiaries = $session->beneficiaries();

        if (!isset($beneficiaries[$index])) {
            return;
        }

        $existing = $beneficiaries[$index];

        if (isset($existing['id'])) {
            $beneficiary['id'] = (int) $existing['id'];
        }

        $beneficiaries[$index] = $beneficiary;
        $session->setBeneficiaries($beneficiaries);

        $memberId = $this->editSession->memberId();

        if (
            $memberId !== null
            && $this->beneficiaryChanged($existing, $beneficiary)
        ) {
            $this->activityLog->record(
                userId: $this->currentUserId(),
                action: 'MEMBER_BENEFICIARY_UPDATED',
                description: sprintf(
                    'Beneficiary "%s" was updated for Member #%d.',
                    $this->beneficiaryName($beneficiary),
                    $memberId,
                ),
                subjectType: 'Member',
                subjectId: $memberId,
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            );
        }
    }

    public function delete(int $index): void
    {
        $session = $this->activeSession();
        $beneficiaries = $session->beneficiaries();

        if (!isset($beneficiaries[$index])) {
            return;
        }

        $beneficiary = $beneficiaries[$index];

        unset($beneficiaries[$index]);

        $session->setBeneficiaries(
            array_values($beneficiaries),
        );

        $memberId = $this->editSession->memberId();

        if ($memberId !== null) {
            $this->activityLog->record(
                userId: $this->currentUserId(),
                action: 'MEMBER_BENEFICIARY_REMOVED',
                description: sprintf(
                    'Beneficiary "%s" was removed from Member #%d.',
                    $this->beneficiaryName($beneficiary),
                    $memberId,
                ),
                subjectType: 'Member',
                subjectId: $memberId,
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            );
        }
    }

    /** @return array<string, mixed>|null */
    public function find(int $index): ?array
    {
        return $this->activeSession()->beneficiaries()[$index] ?? null;
    }

    public function count(): int
    {
        return count($this->activeSession()->beneficiaries());
    }

    public function clear(): void
    {
        $this->activeSession()->setBeneficiaries([]);
    }

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    private function beneficiaryChanged(array $before, array $after): bool
    {
        foreach ([
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'relationship',
            'birth_date',
            'remarks',
        ] as $field) {
            if (
                (string) ($before[$field] ?? '') !==
                (string) ($after[$field] ?? '')
            ) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $beneficiary */
    private function beneficiaryName(array $beneficiary): string
    {
        $name = trim(
            implode(
                ' ',
                array_filter([
                    $beneficiary['first_name'] ?? '',
                    $beneficiary['middle_name'] ?? '',
                    $beneficiary['last_name'] ?? '',
                    $beneficiary['suffix'] ?? '',
                ]),
            ),
        );

        return $name !== '' ? $name : 'Unnamed beneficiary';
    }

    private function currentUserId(): ?int
    {
        $userId = $_SESSION['user_id'] ?? null;

        return $userId !== null ? (int) $userId : null;
    }
}
