<?php

declare(strict_types=1);

namespace App\Features\Loans\Services;

use App\Features\Ledger\Services\LedgerService;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanApplicationStatus;
use App\Features\Loans\Domain\LoanStatus;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Domain\PaymentFrequency;
use App\Features\Loans\Repositories\LoanRepository;
use App\Foundation\Session;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class LoanService
{
    public function __construct(
        private readonly LoanRepository $repository,
        private readonly LedgerService $ledger,
        private readonly AmortizationService $amortization,
        private readonly ActivityLogService $activityLog,
        private readonly Session $session,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        string $applicationStatus = '',
        string $loanStatus = '',
        int $memberId = 0,
        int $limit = 25,
        int $offset = 0,
    ): array {
        return $this->repository->all(
            $search,
            $applicationStatus,
            $loanStatus,
            $memberId,
            $limit,
            $offset,
        );
    }

    public function count(
        string $search = '',
        string $applicationStatus = '',
        string $loanStatus = '',
        int $memberId = 0,
    ): int {
        return $this->repository->count(
            $search,
            $applicationStatus,
            $loanStatus,
            $memberId,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new loan application in Pending status.
     *
     * Amortization generation is intentionally handled by the future
     * AmortizationService so loan lifecycle logic stays separate from
     * financial calculation logic.
     */
    public function create(LoanData $loan): int
    {
        $this->validateLoanData($loan);

        $actorId = $this->actorId();
        $now = $this->now();
        $data = $this->prepareLoanData($loan);

        $data['application_status'] = LoanApplicationStatus::PENDING;
        $data['loan_status'] = null;
        $data['rejection_reason'] = null;
        $data['reviewed_at'] = null;
        $data['approved_at'] = null;
        $data['released_at'] = null;
        $data['fully_paid_at'] = null;
        $data['created_by'] = $actorId;
        $data['reviewed_by'] = null;
        $data['approved_by'] = null;
        $data['released_by'] = null;
        $data['created_at'] = $now;

        $loanId = $this->repository->create($data);

        $this->log(
            action: 'LOAN_CREATED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was created for Member #%d.',
                $loanId,
                $loan->memberId,
            ),
            userId: $actorId,
        );

        return $loanId;
    }

    /**
     * Update a loan while its application is still editable.
     */
    public function update(int $loanId, LoanData $loan): void
    {
        $existing = $this->requireLoan($loanId);
        $status = (string) ($existing['application_status'] ?? '');

        if (! in_array($status, [
            LoanApplicationStatus::PENDING,
            LoanApplicationStatus::UNDER_REVIEW,
        ], true)) {
            throw new RuntimeException(
                'Only Pending or Under Review loans can be edited.'
            );
        }

        $this->validateLoanData($loan);

        $this->repository->update(
            $loanId,
            $this->prepareLoanData($loan),
        );

        $this->log(
            action: 'LOAN_UPDATED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was updated while its application was %s.',
                $loanId,
                $status,
            ),
        );
    }

    /**
     * Submit a Pending application for review.
     */
    public function submit(int $loanId): void
    {
        $loan = $this->requireLoan($loanId);
        $this->assertStatus(
            $loan,
            LoanApplicationStatus::PENDING,
            'Only Pending loans can be submitted for review.',
        );

        $now = $this->now();
        $actorId = $this->actorId();

        $this->repository->updateApplicationStatus(
            $loanId,
            LoanApplicationStatus::UNDER_REVIEW,
            $actorId,
            $now,
        );

        $this->log(
            action: 'LOAN_SUBMITTED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was submitted for review.',
                $loanId,
            ),
            userId: $actorId,
        );
    }

    /**
     * Record the review event without changing the Under Review status.
     * Approval or rejection is a separate action.
     */
    public function review(int $loanId): void
    {
        $loan = $this->requireLoan($loanId);
        $this->assertStatus(
            $loan,
            LoanApplicationStatus::UNDER_REVIEW,
            'Only loans Under Review can be reviewed.',
        );

        $this->log(
            action: 'LOAN_REVIEWED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was reviewed and is awaiting approval or rejection.',
                $loanId,
            ),
        );
    }

    /**
     * Approve a loan that is Under Review.
     */
    public function approve(int $loanId): void
    {
        $loan = $this->requireLoan($loanId);
        $this->assertStatus(
            $loan,
            LoanApplicationStatus::UNDER_REVIEW,
            'Only loans Under Review can be approved.',
        );

        $actorId = $this->actorId();

        $this->repository->updateApplicationStatus(
            $loanId,
            LoanApplicationStatus::APPROVED,
            $actorId,
            $this->now(),
        );

        $this->log(
            action: 'LOAN_APPROVED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was approved.',
                $loanId,
            ),
            userId: $actorId,
        );
    }

    /**
     * Reject a loan that is Under Review. A reason is mandatory.
     */
    public function reject(int $loanId, string $reason): void
    {
        $loan = $this->requireLoan($loanId);
        $this->assertStatus(
            $loan,
            LoanApplicationStatus::UNDER_REVIEW,
            'Only loans Under Review can be rejected.',
        );

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        $actorId = $this->actorId();

        $this->repository->updateApplicationStatus(
            $loanId,
            LoanApplicationStatus::REJECTED,
            $actorId,
            $this->now(),
            $reason,
        );

        $this->log(
            action: 'LOAN_REJECTED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was rejected. Reason: %s',
                $loanId,
                $reason,
            ),
            userId: $actorId,
        );
    }

    /**
     * Release an approved loan, persist its amortization schedule, and make
     * it Active as one database transaction.
     *
     * The loan remains Approved if schedule persistence or the status update
     * fails.
     */
    public function release(
        int $loanId,
        ?string $releaseDate = null,
    ): void {
        $loan = $this->requireLoan($loanId);
        $this->assertStatus(
            $loan,
            LoanApplicationStatus::APPROVED,
            'Only approved loans can be released.',
        );

        if (($loan['loan_status'] ?? null) !== null) {
            throw new RuntimeException(
                'This loan has already entered a financial lifecycle state.'
            );
        }

        $actorId = $this->actorId();
        $releaseDate ??= $this->today();

        $this->validateDate($releaseDate, 'Release date');

        // The amortization start date follows the release date for the
        // released loan record. The original application data is retained.
        $loanForSchedule = $loan;
        $loanForSchedule['start_date'] = $releaseDate;

        $scheduleData = LoanData::fromArray([
            'member_id' => (int) $loanForSchedule['member_id'],
            'loan_type' => (string) $loanForSchedule['loan_type'],
            'collateral' => (string) $loanForSchedule['collateral'],
            'principal_amount' => (float) $loanForSchedule['principal_amount'],
            'interest_rate' => (float) $loanForSchedule['interest_rate'],
            'amortization_type' => $loanForSchedule['amortization_type'] !== null
                ? (string) $loanForSchedule['amortization_type']
                : null,
            'payment_frequency' => $loanForSchedule['payment_frequency'] !== null
                ? (string) $loanForSchedule['payment_frequency']
                : null,
            'terms_months' => (int) $loanForSchedule['terms_months'],
            'start_date' => $releaseDate,
            'manual_payment' => $loanForSchedule['manual_payment'] !== null
                ? (float) $loanForSchedule['manual_payment']
                : null,
            'tct_no' => $loanForSchedule['tct_no'] ?? null,
            'tax_declaration_no' => $loanForSchedule['tax_declaration_no'] ?? null,
            'real_property_payment_status' => $loanForSchedule['real_property_payment_status'] ?? null,
            'notes' => $loanForSchedule['notes'] ?? null,
        ]);

        $schedule = $this->amortization->generate(
            $scheduleData,
            new DateTimeImmutable('today'),
        );

        if ($schedule === []) {
            throw new RuntimeException(
                'The loan amortization schedule could not be generated.'
            );
        }

        $this->repository->releaseWithSchedule(
            id: $loanId,
            userId: $actorId,
            releasedAt: $this->now(),
            releaseDate: $releaseDate,
            schedule: $schedule,
            accountingCallback: function () use (
                $loanId,
                $actorId,
                $loan,
                $releaseDate,
            ): void {
                $this->createLoanReleaseJournalVoucher(
                    loan: $loan,
                    loanId: $loanId,
                    actorId: $actorId,
                    releaseDate: $releaseDate,
                );
            },
        );

        $this->log(
            action: 'LOAN_AMORTIZATION_GENERATED',
            loanId: $loanId,
            description: sprintf(
                'Amortization schedule with %d periods was generated for Loan #%d.',
                count($schedule),
                $loanId,
            ),
            userId: $actorId,
        );

        $this->log(
            action: 'LOAN_RELEASED',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was released and became Active on %s.',
                $loanId,
                $releaseDate,
            ),
            userId: $actorId,
        );
    }

    /**
     * Mark an Active loan Fully Paid after the payment engine confirms that
     * all outstanding principal, interest, and penalty amounts are zero.
     */
    public function markFullyPaid(int $loanId): void
    {
        $loan = $this->requireLoan($loanId);

        if (($loan['loan_status'] ?? null) !== LoanStatus::ACTIVE) {
            throw new RuntimeException(
                'Only Active loans can be marked Fully Paid.'
            );
        }

        $this->repository->markFullyPaid(
            $loanId,
            $this->now(),
        );

        $this->log(
            action: 'LOAN_FULLY_PAID',
            loanId: $loanId,
            description: sprintf(
                'Loan #%d was fully paid.',
                $loanId,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Create the initial accounting representation of a released loan.
     *
     * Current release deductions are mapped to dedicated income/recovery
     * accounts as a provisional ACES mapping:
     * - processing fee -> 4040
     * - insurance -> 4050
     * - notarial fee -> 4060
     *
     * The mapping should be confirmed against the cooperative's accounting
     * policy before production posting.
     *
     * @param array<string,mixed> $loan
     */
    private function createLoanReleaseJournalVoucher(
        array $loan,
        int $loanId,
        int $actorId,
        string $releaseDate,
    ): void {
        $principal = round(
            (float) $loan['principal_amount'],
            2,
        );

        $processingFee = round(
            (float) ($loan['processing_fee'] ?? 0.00),
            2,
        );

        $insurance = round(
            (float) ($loan['insurance'] ?? 0.00),
            2,
        );

        $notarialFee = round(
            (float) ($loan['notarial_fee'] ?? 0.00),
            2,
        );

        $netProceeds = round(
            (float) ($loan['net_proceeds'] ?? 0.00),
            2,
        );

        if ($principal <= 0.00 || $netProceeds < 0.00) {
            throw new RuntimeException(
                'Invalid loan release accounting amounts.'
            );
        }

        $cashAccount = $this->ledger->accountId('1010');
        $principalAccount = $this->ledger->accountId('1110');
        $processingFeeAccount = $this->ledger->accountId('4040');
        $insuranceAccount = $this->ledger->accountId('4050');
        $notarialAccount = $this->ledger->accountId('4060');

        foreach ([
            '1010' => $cashAccount,
            '1110' => $principalAccount,
            '4040' => $processingFeeAccount,
            '4050' => $insuranceAccount,
            '4060' => $notarialAccount,
        ] as $code => $accountId) {
            if ($accountId <= 0) {
                throw new RuntimeException(
                    sprintf(
                        'Ledger account %s is not configured.',
                        $code,
                    )
                );
            }
        }

        $lines = [
            [
                'account_id' => $principalAccount,
                'member_id' => (int) $loan['member_id'],
                'loan_id' => $loanId,
                'line_description' => 'Principal released to member',
                'debit' => $principal,
                'credit' => 0.00,
            ],
            [
                'account_id' => $cashAccount,
                'member_id' => (int) $loan['member_id'],
                'loan_id' => $loanId,
                'line_description' => 'Net loan proceeds paid to member',
                'debit' => 0.00,
                'credit' => $netProceeds,
            ],
        ];

        if ($processingFee > 0.005) {
            $lines[] = [
                'account_id' => $processingFeeAccount,
                'member_id' => (int) $loan['member_id'],
                'loan_id' => $loanId,
                'line_description' => 'Processing fee withheld from release',
                'debit' => 0.00,
                'credit' => $processingFee,
            ];
        }

        if ($insurance > 0.005) {
            $lines[] = [
                'account_id' => $insuranceAccount,
                'member_id' => (int) $loan['member_id'],
                'loan_id' => $loanId,
                'line_description' => 'Insurance deduction withheld from release',
                'debit' => 0.00,
                'credit' => $insurance,
            ];
        }

        if ($notarialFee > 0.005) {
            $lines[] = [
                'account_id' => $notarialAccount,
                'member_id' => (int) $loan['member_id'],
                'loan_id' => $loanId,
                'line_description' => 'Notarial fee withheld from release',
                'debit' => 0.00,
                'credit' => $notarialFee,
            ];
        }

        /*
         * The individual release components must reconcile to the
         * principal amount exactly.
         */
        $creditTotal = 0.00;

        foreach ($lines as $line) {
            $creditTotal += (float) $line['credit'];
        }

        if (abs($principal - $creditTotal) > 0.005) {
            throw new RuntimeException(
                sprintf(
                    'Loan release accounting does not balance. Principal: %.2f, Credits: %.2f.',
                    $principal,
                    $creditTotal,
                )
            );
        }

        $this->ledger->createPending(
            voucher: [
                'reference_number' => sprintf(
                    'LR-%d-%s',
                    $loanId,
                    strtoupper(
                        substr(
                            bin2hex(random_bytes(3)),
                            0,
                            6,
                        ),
                    ),
                ),
                'transaction_date' => $releaseDate,
                'particulars' => sprintf(
                    'Loan #%d release accounting.',
                    $loanId,
                ),
                'source_type' => 'LoanRelease',
                'source_id' => $loanId,
            ],
            lines: $lines,
            createdBy: $actorId,
        );
    }

    private function prepareLoanData(LoanData $loan): array
    {
        $processingFee = round(
            $loan->principalAmount * 0.02,
            2,
        );

        $insurance = round(
            ($loan->principalAmount / 1000) * 1.2 * $loan->termsMonths,
            2,
        );

        $notarialFee = 400.00;
        $netProceeds = round(
            $loan->principalAmount
                - $processingFee
                - $insurance
                - $notarialFee,
            2,
        );

        return [
            ...$loan->toArray(),
            'processing_fee' => $processingFee,
            'insurance' => $insurance,
            'notarial_fee' => $notarialFee,
            'net_proceeds' => $netProceeds,
        ];
    }

    private function validateLoanData(LoanData $loan): void
    {
        if ($loan->memberId <= 0) {
            throw new InvalidArgumentException('A valid member is required.');
        }

        $this->assertAllowed(
            $loan->loanType,
            LoanType::all(),
            'Invalid loan type.',
        );

        $this->assertAllowed(
            $loan->collateral,
            CollateralType::all(),
            'Invalid collateral type.',
        );

        if ($loan->principalAmount <= 0) {
            throw new InvalidArgumentException(
                'Principal amount must be greater than zero.'
            );
        }

        if ($loan->interestRate <= 0) {
            throw new InvalidArgumentException(
                'Interest rate must be greater than zero.'
            );
        }

        if ($loan->termsMonths <= 0) {
            throw new InvalidArgumentException(
                'Loan terms must be greater than zero.'
            );
        }

        $this->validateDate($loan->startDate, 'Start date');

        if (LoanType::isMicroFinance($loan->loanType)) {
            if ($loan->paymentFrequency === null) {
                throw new InvalidArgumentException(
                    'Payment frequency is required for Micro-Finance Loan.'
                );
            }

            $this->assertAllowed(
                $loan->paymentFrequency,
                PaymentFrequency::all(),
                'Invalid payment frequency.',
            );

            if ($loan->amortizationType !== null) {
                throw new InvalidArgumentException(
                    'Micro-Finance Loan must not specify an amortization type.'
                );
            }
        } else {
            if ($loan->amortizationType === null) {
                throw new InvalidArgumentException(
                    'Amortization type is required.'
                );
            }

            $this->assertAllowed(
                $loan->amortizationType,
                AmortizationType::all(),
                'Invalid amortization type.',
            );

            if ($loan->paymentFrequency !== null) {
                throw new InvalidArgumentException(
                    'Payment frequency is only used for Micro-Finance Loan.'
                );
            }

            if (
                $loan->amortizationType === AmortizationType::MANUAL
                && ($loan->manualPayment === null || $loan->manualPayment <= 0)
            ) {
                throw new InvalidArgumentException(
                    'Manual payment must be greater than zero for Manual amortization.'
                );
            }
        }

        if (
            $loan->amortizationType !== AmortizationType::MANUAL
            && $loan->manualPayment !== null
        ) {
            throw new InvalidArgumentException(
                'Manual payment is only allowed for Manual amortization.'
            );
        }

        if ($loan->collateral === CollateralType::REAL_PROPERTY) {
            if ($loan->realPropertyPaymentStatus !== null) {
                $this->assertAllowed(
                    $loan->realPropertyPaymentStatus,
                    ['Updated', 'Not Updated', 'Pending'],
                    'Invalid real property payment status.',
                );
            }
        } elseif (
            $loan->tctNo !== null
            || $loan->taxDeclarationNo !== null
            || $loan->realPropertyPaymentStatus !== null
        ) {
            throw new InvalidArgumentException(
                'Real property fields are only allowed for Real Property collateral.'
            );
        }
    }

    /**
     * @param list<string> $allowed
     */
    private function assertAllowed(
        string $value,
        array $allowed,
        string $message,
    ): void {
        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * @param array<string, mixed> $loan
     */
    private function assertStatus(
        array $loan,
        string $expected,
        string $message,
    ): void {
        if (($loan['application_status'] ?? null) !== $expected) {
            throw new RuntimeException($message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requireLoan(int $loanId): array
    {
        if ($loanId <= 0) {
            throw new InvalidArgumentException('Invalid loan ID.');
        }

        $loan = $this->repository->find($loanId);

        if ($loan === null) {
            throw new RuntimeException('Loan not found.');
        }

        return $loan;
    }

    private function actorId(): int
    {
        $userId = $this->session->get('user_id');

        if ($userId === null || (int) $userId <= 0) {
            throw new RuntimeException(
                'An authenticated user is required for this loan action.'
            );
        }

        return (int) $userId;
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function today(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d');
    }

    private function validateDate(string $date, string $label): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException(
                sprintf('%s must be a valid YYYY-MM-DD date.', $label)
            );
        }
    }

    private function log(
        string $action,
        int $loanId,
        string $description,
        ?int $userId = null,
    ): void {
        $this->activityLog->record(
            userId: $userId ?? $this->actorId(),
            action: $action,
            description: $description,
            subjectType: 'Loan',
            subjectId: $loanId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
        );
    }
}
