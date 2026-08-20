<?php

declare(strict_types=1);

namespace App\Features\Loans\Services;

use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationStatus;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Domain\PaymentFrequency;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Generates and refreshes loan amortization schedules.
 *
 * This service contains calculation logic only. It does not persist rows.
 * Persistence/payment application belongs in the repository/payment layer.
 */
final class AmortizationService
{
    private const PENALTY_RATE = 0.03;

    /**
     * Generate a brand-new schedule from the loan terms.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generate(
        LoanData $loan,
        ?DateTimeImmutable $today = null,
    ): array {
        $this->validate($loan);

        $today ??= new DateTimeImmutable('today');
        $startDate = $this->date($loan->startDate);

        if ($loan->loanType === LoanType::MICRO_FINANCE_LOAN) {
            return $this->generateMicroFinance($loan, $startDate, $today);
        }

        return $this->generateStandard($loan, $startDate, $today);
    }

    /**
     * Refresh statuses and penalties on an existing schedule without wiping
     * paid/payment progress.
     *
     * Existing rows are matched by period. Rows marked Paid remain Paid.
     * Existing remaining amounts are preserved. A penalty is only initialized
     * the first time a row becomes overdue (orig_penalty remains immutable).
     *
     * @param array<int, array<string, mixed>> $existingRows
     * @return array<int, array<string, mixed>>
     */
    public function refresh(
        array $existingRows,
        ?DateTimeImmutable $today = null,
    ): array {
        $today ??= new DateTimeImmutable('today');
        $rows = [];

        foreach ($existingRows as $row) {
            $status = (string) ($row['status'] ?? AmortizationStatus::PENDING);

            if ($status === AmortizationStatus::PAID) {
                $rows[] = $row;
                continue;
            }

            $dueDate = $this->date((string) ($row['due_date'] ?? ''));
            $dueness = $this->statusForDueDate($dueDate, $today);

            $origPenalty = $this->money((float) ($row['orig_penalty'] ?? 0));
            $remPenalty = $this->money((float) ($row['rem_penalty'] ?? 0));

            if ($dueness === AmortizationStatus::OVERDUE && $origPenalty <= 0.0) {
                $origPenalty = $this->calculatePenalty(
                    (float) ($row['principal'] ?? 0),
                    (float) ($row['interest'] ?? 0),
                    $dueDate,
                    $today,
                );

                $remPenalty = $origPenalty;
            }

            if ($remPenalty > 0.0) {
                $status = AmortizationStatus::OVERDUE;
            } else {
                $status = $dueness;
            }

            $row['orig_penalty'] = $origPenalty;
            $row['rem_penalty'] = max(0.0, $remPenalty);
            $row['status'] = $status;

            $rows[] = $row;
        }

        return $rows;
    }

    public function calculatePenalty(
        float $principal,
        float $interest,
        DateTimeInterface $dueDate,
        ?DateTimeImmutable $today = null,
    ): float {
        $today ??= new DateTimeImmutable('today');
        $months = $this->monthsOverdue($dueDate, $today);

        if ($months === 0) {
            return 0.0;
        }

        return $this->money(
            ($principal + $interest) * self::PENALTY_RATE * $months,
        );
    }

    public function monthsOverdue(
        DateTimeInterface $dueDate,
        ?DateTimeImmutable $today = null,
    ): int {
        $today ??= new DateTimeImmutable('today');

        $due = DateTimeImmutable::createFromInterface($dueDate)->setTime(0, 0, 0);
        $current = $today->setTime(0, 0, 0);

        if ($due >= $current) {
            return 0;
        }

        $months = ((int) $current->format('Y') - (int) $due->format('Y')) * 12
            + ((int) $current->format('n') - (int) $due->format('n'));

        return max(1, $months);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateStandard(
        LoanData $loan,
        DateTimeImmutable $startDate,
        DateTimeImmutable $today,
    ): array {
        $terms = $loan->termsMonths;
        $principal = $loan->principalAmount;
        $monthlyRate = $loan->interestRate / 100;
        $principalPerPeriod = $principal / $terms;
        $balance = $principal;
        $rows = [];

        for ($period = 1; $period <= $terms; $period++) {
            $dueDate = $this->addMonths($startDate, $period);

            if ($loan->amortizationType === AmortizationType::STRAIGHT_LINE) {
                $interest = $principal * $monthlyRate;
                $periodPrincipal = $principalPerPeriod;
            } elseif ($loan->amortizationType === AmortizationType::DIMINISHING_BALANCE) {
                $interest = $balance * $monthlyRate;
                $periodPrincipal = $principalPerPeriod;
            } else {
                $interest = $balance * $monthlyRate;
                $periodPrincipal = max(0.0, $loan->manualPayment - $interest);
            }

            $periodPrincipal = min($periodPrincipal, max(0.0, $balance));
            $interest = max(0.0, $interest);
            $balance = max(0.0, $balance - $periodPrincipal);

            $principalMoney = $this->money($periodPrincipal);
            $interestMoney = $this->money($interest);
            $penalty = $this->initialPenalty($principalMoney, $interestMoney, $dueDate, $today);
            $status = $this->statusForDueDate($dueDate, $today);

            $rows[] = $this->row(
                $period,
                $dueDate,
                $principalMoney,
                $interestMoney,
                $penalty,
                $status,
            );
        }

        $this->reconcileFinalPrincipal($rows, $principal);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateMicroFinance(
        LoanData $loan,
        DateTimeImmutable $startDate,
        DateTimeImmutable $today,
    ): array {
        $multiplier = $this->microMultiplier($loan->paymentFrequency);
        $totalPeriods = $loan->termsMonths * $multiplier;
        $principalPerPeriod = $loan->principalAmount / $totalPeriods;
        $ratePerPeriod = ($loan->interestRate / 100) / $multiplier;
        $interestPerPeriod = $loan->principalAmount * $ratePerPeriod;
        $balance = $loan->principalAmount;
        $rows = [];

        for ($period = 1; $period <= $totalPeriods; $period++) {
            $dueDate = $this->addMicroPeriod(
                $startDate,
                $period,
                $loan->paymentFrequency,
            );

            $periodPrincipal = min($principalPerPeriod, max(0.0, $balance));
            $principalMoney = $this->money($periodPrincipal);
            $interestMoney = $this->money($interestPerPeriod);
            $balance = max(0.0, $balance - $periodPrincipal);

            $penalty = $this->initialPenalty($principalMoney, $interestMoney, $dueDate, $today);
            $status = $this->statusForDueDate($dueDate, $today);

            $rows[] = $this->row(
                $period,
                $dueDate,
                $principalMoney,
                $interestMoney,
                $penalty,
                $status,
            );
        }

        return $rows;
    }

    /**
     * Reconcile the final principal installment so the rounded schedule
     * sums to the original principal exactly.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function reconcileFinalPrincipal(
        array &$rows,
        float $originalPrincipal,
    ): void {
        if ($rows === []) {
            return;
        }

        $total = 0.00;

        foreach ($rows as $row) {
            $total += (float) $row['principal'];
        }

        $difference = $this->money($originalPrincipal - $total);

        if (abs($difference) < 0.005) {
            return;
        }

        $lastIndex = array_key_last($rows);
        $lastPrincipal = $this->money(
            max(
                0.0,
                (float) $rows[$lastIndex]['principal'] + $difference,
            )
        );

        $rows[$lastIndex]['principal'] = $lastPrincipal;
        $rows[$lastIndex]['rem_principal'] = $lastPrincipal;
    }

    /** @return array<string, mixed> */
    private function row(
        int $period,
        DateTimeImmutable $dueDate,
        float $principal,
        float $interest,
        float $penalty,
        string $status,
    ): array {
        return [
            'period' => $period,
            'due_date' => $dueDate->format('Y-m-d'),
            'principal' => $principal,
            'interest' => $interest,
            'rem_principal' => $principal,
            'rem_interest' => $interest,
            'rem_penalty' => $penalty,
            'orig_penalty' => $penalty,
            'status' => $status,
            'remarks' => null,
        ];
    }

    private function initialPenalty(
        float $principal,
        float $interest,
        DateTimeImmutable $dueDate,
        DateTimeImmutable $today,
    ): float {
        return $this->calculatePenalty($principal, $interest, $dueDate, $today);
    }

    private function statusForDueDate(
        DateTimeImmutable $dueDate,
        DateTimeImmutable $today,
    ): string {
        $due = $dueDate->setTime(0, 0, 0);
        $current = $today->setTime(0, 0, 0);
        $diffDays = (int) $current->diff($due)->format('%r%a');

        if ($diffDays < 0) {
            return AmortizationStatus::OVERDUE;
        }

        if ($diffDays <= 3) {
            return AmortizationStatus::NEAR_DUE;
        }

        return AmortizationStatus::PENDING;
    }

    private function addMonths(DateTimeImmutable $startDate, int $period): DateTimeImmutable
    {
        return $startDate->modify('+' . $period . ' month');
    }

    private function addMicroPeriod(
        DateTimeImmutable $startDate,
        int $period,
        ?string $frequency,
    ): DateTimeImmutable {
        return match ($frequency) {
            PaymentFrequency::MONTHLY => $startDate->modify('+' . $period . ' month'),
            PaymentFrequency::BI_MONTHLY => $startDate->modify('+' . ($period * 15) . ' days'),
            PaymentFrequency::WEEKLY => $startDate->modify('+' . ($period * 7) . ' days'),
            default => throw new InvalidArgumentException('Invalid Micro-Finance payment frequency.'),
        };
    }

    private function microMultiplier(?string $frequency): int
    {
        return match ($frequency) {
            PaymentFrequency::MONTHLY => 1,
            PaymentFrequency::BI_MONTHLY => 2,
            PaymentFrequency::WEEKLY => 4,
            default => throw new InvalidArgumentException('Micro-Finance payment frequency is required.'),
        };
    }

    private function validate(LoanData $loan): void
    {
        if ($loan->principalAmount <= 0) {
            throw new InvalidArgumentException('Principal amount must be greater than zero.');
        }

        if ($loan->interestRate < 0) {
            throw new InvalidArgumentException('Interest rate cannot be negative.');
        }

        if ($loan->termsMonths <= 0) {
            throw new InvalidArgumentException('Terms must be greater than zero.');
        }

        if ($loan->loanType === LoanType::MICRO_FINANCE_LOAN) {
            $this->microMultiplier($loan->paymentFrequency);
            return;
        }

        if ($loan->amortizationType === null || $loan->amortizationType === '') {
            throw new InvalidArgumentException('Amortization type is required.');
        }

        if ($loan->amortizationType === AmortizationType::MANUAL && ($loan->manualPayment ?? 0) <= 0) {
            throw new InvalidArgumentException('Manual payment must be greater than zero.');
        }
    }

    private function date(string $value): DateTimeImmutable
    {
        if ($value === '') {
            throw new InvalidArgumentException('Start date is required.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('Start date must be a valid YYYY-MM-DD date.');
        }

        return $date;
    }

    private function money(float $value): float
    {
        return round($value + 0.0000000001, 2);
    }
}
