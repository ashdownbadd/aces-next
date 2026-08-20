<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class AmortizationStatus
{
    public const PENDING = 'Pending';
    public const NEAR_DUE = 'Near-Due';
    public const OVERDUE = 'Overdue';
    public const PAID = 'Paid';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::PENDING, self::NEAR_DUE, self::OVERDUE, self::PAID];
    }
}
