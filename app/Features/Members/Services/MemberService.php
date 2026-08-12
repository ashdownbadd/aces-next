<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\Repositories\MemberRepository;

final class MemberService
{
    public function __construct(
        private readonly MemberRepository $repository,
    ) {}

    public function all(): array
    {
        return $this->repository->all();
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    public function nextMemberNumber(): string
    {
        $lastNumber = $this->repository->lastMemberNumber();

        if ($lastNumber === null) {
            return '0001';
        }

        $nextNumber = (int) $lastNumber + 1;

        if ($nextNumber > 9999) {
            throw new \RuntimeException(
                'Member number limit reached. Maximum member number is 9999.'
            );
        }

        return str_pad(
            (string) $nextNumber,
            4,
            '0',
            STR_PAD_LEFT,
        );
    }
}
