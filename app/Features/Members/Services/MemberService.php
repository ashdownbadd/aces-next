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
}
