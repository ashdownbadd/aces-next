<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class LoanStatus
{
    public const ACTIVE = 'Active';
    public const FULLY_PAID = 'Fully Paid';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::ACTIVE, self::FULLY_PAID];
    }
}
