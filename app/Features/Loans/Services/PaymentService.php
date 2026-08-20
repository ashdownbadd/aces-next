<?php

declare(strict_types=1);

namespace App\Features\Loans\Services;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Domain\AmortizationStatus;
use App\Features\Loans\Domain\LoanStatus;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Foundation\Session;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly LoanPaymentRepository $repository,
        private readonly LoanRepository $loanRepository,
        private readonly AmortizationService $amortization,
        private readonly ActivityLogService $activityLog,
        private readonly Session $session,
    ) {}

    /** @return array<string, mixed> */
    public function apply(
        int $loanId,
        float $amountPaid,
        ?string $remarks = null,
    ): array {
        if ($loanId <= 0) {
            throw new InvalidArgumentException('Invalid loan ID.');
        }

        $amountPaid = $this->money($amountPaid);

        if ($amountPaid <= 0.0) {
            return [
                'payment_id' => null,
                'amount_paid' => 0.00,
                'penalty_applied' => 0.00,
                'interest_applied' => 0.00,
                'principal_applied' => 0.00,
                'excess' => 0.00,
                'loan_fully_paid' => false,
            ];
        }

        $loan = $this->loanRepository->find($loanId);

        if ($loan === null) {
            throw new RuntimeException('Loan not found.');
        }

        if (($loan['loan_status'] ?? null) !== LoanStatus::ACTIVE) {
            throw new RuntimeException(
                'Payments can only be applied to Active loans.'
            );
        }

        $rows = $this->repository->amortizations($loanId);

        if ($rows === []) {
            throw new RuntimeException(
                'The loan does not have an amortization schedule.'
            );
        }

        $refreshedRows = $this->amortization->refresh($rows);

        $unpaidRows = array_values(
            array_filter(
                $refreshedRows,
                static fn (array $row): bool =>
                    ($row['status'] ?? null) !== AmortizationStatus::PAID,
            )
        );

        if ($unpaidRows === []) {
            return [
                'payment_id' => null,
                'amount_paid' => $amountPaid,
                'penalty_applied' => 0.00,
                'interest_applied' => 0.00,
                'principal_applied' => 0.00,
                'excess' => $amountPaid,
                'loan_fully_paid' => true,
            ];
        }

        $remaining = $amountPaid;
        $updatedRows = $refreshedRows;
        $allocations = [];
        $appliedByRow = [];

        foreach ($unpaidRows as $row) {
            $appliedByRow[(int) $row['id']] = [
                'penalty' => 0.00,
                'interest' => 0.00,
                'principal' => 0.00,
            ];
        }

        // INSTALLMENT-FIRST PAYMENT RULE
        //
        // A payment settles the earliest unpaid installment first.
        // Within that installment the order is:
        //   1. Penalty
        //   2. Interest
        //   3. Principal
        //
        // Only after an installment is completely settled do we advance to
        // the next unpaid installment.

        foreach ($unpaidRows as $row) {
            if ($remaining <= 0.0) {
                break;
            }

            $id = (int) $row['id'];

            // Penalty first for this installment.
            $take = min(
                $remaining,
                $this->money((float) $row['rem_penalty']),
            );

            if ($take > 0.0) {
                $appliedByRow[$id]['penalty'] = $take;
                $remaining = $this->money($remaining - $take);

                $allocations[] = [
                    'amortization_id' => $id,
                    'allocation_type' => 'Penalty',
                    'amount' => $take,
                ];
            }

            if ($remaining <= 0.0) {
                break;
            }

            // Interest second for this installment.
            $take = min(
                $remaining,
                $this->money((float) $row['rem_interest']),
            );

            if ($take > 0.0) {
                $appliedByRow[$id]['interest'] = $take;
                $remaining = $this->money($remaining - $take);

                $allocations[] = [
                    'amortization_id' => $id,
                    'allocation_type' => 'Interest',
                    'amount' => $take,
                ];
            }

            if ($remaining <= 0.0) {
                break;
            }

            // Principal last for this installment.
            $take = min(
                $remaining,
                $this->money((float) $row['rem_principal']),
            );

            if ($take > 0.0) {
                $appliedByRow[$id]['principal'] = $take;
                $remaining = $this->money($remaining - $take);

                $allocations[] = [
                    'amortization_id' => $id,
                    'allocation_type' => 'Principal',
                    'amount' => $take,
                ];
            }
        }

        $totalPenalty = 0.00;
        $totalInterest = 0.00;
        $totalPrincipal = 0.00;

        foreach ($updatedRows as &$row) {
            $id = (int) $row['id'];

            if (!isset($appliedByRow[$id])) {
                continue;
            }

            $applied = $appliedByRow[$id];

            $row['rem_penalty'] = $this->money(
                max(
                    0.0,
                    (float) $row['rem_penalty']
                    - $applied['penalty'],
                ),
            );

            $row['rem_interest'] = $this->money(
                max(
                    0.0,
                    (float) $row['rem_interest']
                    - $applied['interest'],
                ),
            );

            $row['rem_principal'] = $this->money(
                max(
                    0.0,
                    (float) $row['rem_principal']
                    - $applied['principal'],
                ),
            );

            $totalPenalty = $this->money(
                $totalPenalty + $applied['penalty'],
            );

            $totalInterest = $this->money(
                $totalInterest + $applied['interest'],
            );

            $totalPrincipal = $this->money(
                $totalPrincipal + $applied['principal'],
            );

            $fullyPaid = $this->isZero((float) $row['rem_principal'])
                && $this->isZero((float) $row['rem_interest'])
                && $this->isZero((float) $row['rem_penalty']);

            if ($fullyPaid) {
                $row['status'] = AmortizationStatus::PAID;
            } elseif ((float) $row['rem_penalty'] > 0.0) {
                $row['status'] = AmortizationStatus::OVERDUE;
            } else {
                $row['status'] = AmortizationStatus::PENDING;
            }
        }
        unset($row);

        $excess = $this->money($remaining);
        $allPaid = true;

        foreach ($updatedRows as $row) {
            if (
                !$this->isZero((float) $row['rem_principal'])
                || !$this->isZero((float) $row['rem_interest'])
                || !$this->isZero((float) $row['rem_penalty'])
            ) {
                $allPaid = false;
                break;
            }
        }

        $actorId = $this->actorId();

        $paymentId = $this->repository->persistPayment(
            payment: [
                'loan_id' => $loanId,
                'payment_datetime' => $this->now(),
                'amount_paid' => $amountPaid,
                'penalty_applied' => $totalPenalty,
                'interest_applied' => $totalInterest,
                'principal_applied' => $totalPrincipal,
                'excess' => $excess,
                'type' => 'Global',
                'remarks' => $remarks !== null ? trim($remarks) : null,
                'created_by' => $actorId,
            ],
            allocations: $allocations,
            updatedRows: $updatedRows,
        );

        if ($allPaid) {
            $this->loanRepository->markFullyPaid($loanId, $this->now());

            $this->activityLog->record(
                userId: $actorId,
                action: 'LOAN_FULLY_PAID',
                description: sprintf(
                    'Loan #%d was fully paid.',
                    $loanId,
                ),
                subjectType: 'Loan',
                subjectId: $loanId,
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            );
        }

        $this->activityLog->record(
            userId: $actorId,
            action: 'LOAN_PAYMENT_APPLIED',
            description: sprintf(
                'Payment #%d of ₱%s was applied to Loan #%d using the installment-first payment rule.',
                $paymentId,
                number_format($amountPaid, 2),
                $loanId,
            ),
            subjectType: 'Loan',
            subjectId: $loanId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
        );

        return [
            'payment_id' => $paymentId,
            'amount_paid' => $amountPaid,
            'penalty_applied' => $totalPenalty,
            'interest_applied' => $totalInterest,
            'principal_applied' => $totalPrincipal,
            'excess' => $excess,
            'loan_fully_paid' => $allPaid,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function payments(int $loanId): array
    {
        return $this->repository->paymentsForLoan($loanId);
    }

    /** @return array<string, mixed>|null */
    public function payment(int $paymentId): ?array
    {
        return $this->repository->findPayment($paymentId);
    }

    /** @return array<int, array<string, mixed>> */
    public function allocations(int $paymentId): array
    {
        return $this->repository->allocations($paymentId);
    }

    public function reverse(
        int $paymentId,
        string $reason,
    ): array {
        if ($paymentId <= 0) {
            throw new InvalidArgumentException('Invalid payment ID.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException(
                'A reversal reason is required.'
            );
        }

        $payment = $this->repository->findPayment($paymentId);

        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        if (($payment['reversed_at'] ?? null) !== null) {
            throw new RuntimeException(
                'This payment has already been reversed.'
            );
        }

        $loanId = (int) ($payment['loan_id'] ?? 0);

        if ($loanId <= 0) {
            throw new RuntimeException('The payment has an invalid loan.');
        }

        $loan = $this->loanRepository->find($loanId);

        if ($loan === null) {
            throw new RuntimeException('Loan not found.');
        }

        $result = $this->repository->reversePayment(
            paymentId: $paymentId,
            userId: $this->actorId(),
            reversedAt: $this->now(),
            reason: $reason,
        );

        if (($loan['loan_status'] ?? null) === LoanStatus::FULLY_PAID) {
            $this->loanRepository->reactivate($loanId);

            $this->activityLog->record(
                userId: $this->actorId(),
                action: 'LOAN_REACTIVATED',
                description: sprintf(
                    'Loan #%d returned to Active after Payment #%d was reversed.',
                    $loanId,
                    $paymentId,
                ),
                subjectType: 'Loan',
                subjectId: $loanId,
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            );
        }

        $this->activityLog->record(
            userId: $this->actorId(),
            action: 'LOAN_PAYMENT_REVERSED',
            description: sprintf(
                'Payment #%d on Loan #%d was reversed. Reason: %s',
                $paymentId,
                $loanId,
                $reason,
            ),
            subjectType: 'Loan',
            subjectId: $loanId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
        );

        return $result;
    }

    private function actorId(): int
    {
        $userId = $this->session->get('user_id');

        if ($userId === null || (int) $userId <= 0) {
            throw new RuntimeException(
                'An authenticated user is required for payment actions.'
            );
        }

        return (int) $userId;
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function money(float $value): float
    {
        return round($value + 0.000000001, 2);
    }

    private function isZero(float $value): bool
    {
        return abs($value) < 0.005;
    }
}
