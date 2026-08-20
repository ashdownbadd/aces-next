<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class AmortizationType
{
    public const STRAIGHT_LINE = 'Straight-line';
    public const DIMINISHING_BALANCE = 'Diminishing balance';
    public const MANUAL = 'Manual';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::STRAIGHT_LINE, self::DIMINISHING_BALANCE, self::MANUAL];
    }
}
