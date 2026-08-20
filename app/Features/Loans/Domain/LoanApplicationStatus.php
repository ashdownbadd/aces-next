<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class LoanApplicationStatus
{
    public const PENDING = 'Pending';
    public const UNDER_REVIEW = 'Under Review';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::PENDING, self::UNDER_REVIEW, self::APPROVED, self::REJECTED];
    }
}
