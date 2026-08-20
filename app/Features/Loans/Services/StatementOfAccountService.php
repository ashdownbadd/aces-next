<?php

declare(strict_types=1);

namespace App\Features\Loans\Services;

use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use DateTimeImmutable;
use RuntimeException;

final class StatementOfAccountService
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly LoanPaymentRepository $paymentRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(int $loanId): array
    {
        $loan = $this->loanRepository->find($loanId);

        if ($loan === null) {
            throw new RuntimeException('Loan not found.');
        }

        $amortizations = $this->paymentRepository->amortizations($loanId);
        $payments = $this->paymentRepository->paymentsForLoan($loanId);

        $paymentByRow = [];
        $paidRowAmount = [];

        foreach ($payments as $payment) {
            if (($payment['reversed_at'] ?? null) !== null) {
                continue;
            }

            $paymentId = (int) $payment['id'];

            foreach ($this->paymentRepository->allocations($paymentId) as $allocation) {
                $rowId = (int) $allocation['amortization_id'];
                $amount = round((float) $allocation['amount'], 2);

                if (!isset($paymentByRow[$rowId])) {
                    $paymentByRow[$rowId] = [
                        'principal' => 0.00,
                        'interest' => 0.00,
                        'penalty' => 0.00,
                    ];
                }

                $key = strtolower((string) $allocation['allocation_type']);

                if ($key === 'principal') {
                    $paymentByRow[$rowId]['principal'] += $amount;
                } elseif ($key === 'interest') {
                    $paymentByRow[$rowId]['interest'] += $amount;
                } elseif ($key === 'penalty') {
                    $paymentByRow[$rowId]['penalty'] += $amount;
                }
            }
        }

        $today = new DateTimeImmutable('today');
        $rows = [];

        foreach ($amortizations as $row) {
            $rowId = (int) $row['id'];
            $dueDate = new DateTimeImmutable((string) $row['due_date']);
            $status = (string) $row['status'];

            $monthsPastDue = 0;
            if ($dueDate < $today && $status !== 'Paid') {
                $monthsPastDue = (
                    ((int) $today->format('Y') - (int) $dueDate->format('Y')) * 12
                ) + (
                    (int) $today->format('n') - (int) $dueDate->format('n')
                );

                $monthsPastDue = max(1, $monthsPastDue);
            }

            $paymentParts = $paymentByRow[$rowId] ?? [
                'principal' => 0.00,
                'interest' => 0.00,
                'penalty' => 0.00,
            ];

            $paymentAmount = round(
                $paymentParts['principal']
                + $paymentParts['interest']
                + $paymentParts['penalty'],
                2,
            );

            $rows[] = [
                'due_date' => (string) $row['due_date'],
                'principal' => (float) $row['principal'],
                'interest' => (float) $row['interest'],
                'total_amount_due' => round(
                    (float) $row['principal']
                    + (float) $row['interest']
                    + (float) $row['orig_penalty'],
                    2,
                ),
                'payments' => $paymentAmount,
                'months_past_due' => $monthsPastDue,
                'principal_overdue' => $monthsPastDue > 0
                    ? (float) $row['rem_principal']
                    : 0.00,
                'interest_overdue' => $monthsPastDue > 0
                    ? (float) $row['rem_interest']
                    : 0.00,
                'penalty' => $monthsPastDue > 0
                    ? (float) $row['rem_penalty']
                    : 0.00,
                'status' => $status,
            ];
        }

        $totalOverduePrincipal = round(
            array_sum(
                array_column($rows, 'principal_overdue')
            ),
            2,
        );

        $totalOverdueInterest = round(
            array_sum(
                array_column($rows, 'interest_overdue')
            ),
            2,
        );

        $totalPenalty = round(
            array_sum(
                array_column($rows, 'penalty')
            ),
            2,
        );

        $grandTotalOverdue = round(
            $totalOverduePrincipal
            + $totalOverdueInterest
            + $totalPenalty,
            2,
        );

        $totalReceivables = round(
            array_sum(
                array_column($rows, 'total_amount_due')
            ),
            2,
        );

        $totalOutstanding = round(
            array_sum(
                array_map(
                    static fn (array $row): float =>
                        (float) $row['principal_overdue']
                        + (float) $row['interest_overdue']
                        + (float) $row['penalty'],
                    $rows,
                )
            ),
            2,
        );

        return [
            'as_of' => $today->format('Y-m-d'),
            'loan' => $loan,
            'rows' => $rows,
            'total_receivables' => $totalReceivables,
            'total_outstanding' => $totalOutstanding,
            'total_overdue_principal' => $totalOverduePrincipal,
            'total_overdue_interest' => $totalOverdueInterest,
            'total_penalty' => $totalPenalty,
            'grand_total_overdue' => $grandTotalOverdue,
        ];
    }
}
