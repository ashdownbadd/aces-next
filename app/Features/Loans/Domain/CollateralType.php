<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class CollateralType
{
    public const POST_DATED_CHECK = 'Post-Dated Check';
    public const REAL_PROPERTY = 'Real Property';
    public const CHATTELS_MOVABLE_ASSETS = 'Chattels / Movable Assets';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::POST_DATED_CHECK, self::REAL_PROPERTY, self::CHATTELS_MOVABLE_ASSETS];
    }
}
