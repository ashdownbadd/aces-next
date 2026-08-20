<?php

declare(strict_types=1);

namespace App\Features\Loans\Domain;

final class LoanType
{
    public const BRIDGE_FINANCING = 'Bridge Financing';
    public const INVESTMENT_LOAN = 'Investment Loan';
    public const PENSION_LOAN = 'Pension Loan';
    public const PRODUCTIVITY_LOAN = 'Productivity Loan';
    public const PERSONAL_LOAN = 'Personal Loan';
    public const SALARY_LOAN = 'Salary Loan';
    public const MICRO_FINANCE_LOAN = 'Micro-Finance Loan';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::BRIDGE_FINANCING, self::INVESTMENT_LOAN, self::PENSION_LOAN, self::PRODUCTIVITY_LOAN, self::PERSONAL_LOAN, self::SALARY_LOAN, self::MICRO_FINANCE_LOAN];
    }

    public static function isMicroFinance(string $value): bool
    {
        return $value === self::MICRO_FINANCE_LOAN;
    }
}
