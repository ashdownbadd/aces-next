<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class PaymentFrequency
{
    public const MONTHLY = 'Monthly';
    public const BI_MONTHLY = 'Bi-Monthly';
    public const WEEKLY = 'Weekly';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::MONTHLY, self::BI_MONTHLY, self::WEEKLY];
    }
}
