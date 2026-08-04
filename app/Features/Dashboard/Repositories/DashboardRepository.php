<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Repositories;

final readonly class DashboardRepository
{
    public function stats(): array
    {
        return [
            'members' => 0,
            'activeLoans' => 0,
            'savings' => 0,
            'shareCapital' => 0,
        ];
    }
}
