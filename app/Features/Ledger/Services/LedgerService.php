<?php

declare(strict_types=1);

namespace App\Features\Ledger\Services;

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use InvalidArgumentException;
use RuntimeException;

final class LedgerService
{
    public function __construct(
        private readonly JournalVoucherRepository $repository,
    ) {}

    /**
     * @param array{
     *   reference_number:string,
     *   transaction_date:string,
     *   particulars:string,
     *   source_type?:string|null,
     *   source_id?:int|null
     * } $voucher
     * @param array<int,array{
     *   account_id:int,
     *   member_id?:int|null,
     *   loan_id?:int|null,
     *   line_description?:string|null,
     *   debit?:float,
     *   credit?:float
     * }> $lines
     */
    public function accountId(string $accountCode): int
    {
        $accountCode = trim($accountCode);

        if ($accountCode === '') {
            throw new InvalidArgumentException(
                'A Ledger account code is required.'
            );
        }

        return $this->repository->accountId($accountCode);
    }

    public function createPending(
        array $voucher,
        array $lines,
        int $createdBy,
    ): int {
        $createdBy = (int) $createdBy;

        if ($createdBy <= 0) {
            throw new InvalidArgumentException(
                'An authenticated user is required to create a journal voucher.'
            );
        }

        $reference = trim((string) ($voucher['reference_number'] ?? ''));
        $date = trim((string) ($voucher['transaction_date'] ?? ''));
        $particulars = trim((string) ($voucher['particulars'] ?? ''));

        if ($reference === '') {
            throw new InvalidArgumentException(
                'Journal voucher reference number is required.'
            );
        }

        if ($date === '') {
            throw new InvalidArgumentException(
                'Journal voucher transaction date is required.'
            );
        }

        if ($particulars === '') {
            throw new InvalidArgumentException(
                'Journal voucher particulars are required.'
            );
        }

        $normalized = $this->normalizeLines($lines);
        $this->assertBalanced($normalized);

        return $this->repository->createPending(
            voucher: [
                'reference_number' => $reference,
                'transaction_date' => $date,
                'particulars' => $particulars,
                'source_type' => isset($voucher['source_type'])
                    ? $this->nullableString($voucher['source_type'])
                    : null,
                'source_id' => isset($voucher['source_id'])
                    ? $this->nullableInt($voucher['source_id'])
                    : null,
                'created_by' => $createdBy,
            ],
            lines: $normalized,
        );
    }

    /** @return array<string,mixed>|null */
    public function find(int $voucherId): ?array
    {
        return $this->repository->find($voucherId);
    }

    /** @return array<int,array<string,mixed>> */
    public function lines(int $voucherId): array
    {
        return $this->repository->lines($voucherId);
    }

    public function approve(
        int $voucherId,
        int $userId,
        string $approvedAt,
    ): void {
        if ($voucherId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException(
                'Valid voucher and user IDs are required.'
            );
        }

        $voucher = $this->repository->find($voucherId);

        if ($voucher === null) {
            throw new RuntimeException('Journal voucher not found.');
        }

        if (($voucher['status'] ?? null) !== 'Pending') {
            throw new RuntimeException(
                'Only Pending journal vouchers can be approved.'
            );
        }

        // Re-read the lines at approval time so a future implementation
        // cannot approve an unbalanced voucher accidentally.
        $lines = $this->repository->lines($voucherId);
        $this->assertBalanced(
            $this->normalizePersistedLines($lines)
        );

        $this->repository->approve(
            $voucherId,
            $userId,
            $approvedAt,
        );
    }

    public function reject(
        int $voucherId,
        string $reason,
    ): void {
        $reason = trim($reason);

        if ($voucherId <= 0) {
            throw new InvalidArgumentException(
                'Invalid journal voucher ID.'
            );
        }

        if ($reason === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        $voucher = $this->repository->find($voucherId);

        if ($voucher === null) {
            throw new RuntimeException('Journal voucher not found.');
        }

        if (($voucher['status'] ?? null) !== 'Pending') {
            throw new RuntimeException(
                'Only Pending journal vouchers can be rejected.'
            );
        }

        $this->repository->reject($voucherId, $reason);
    }

    /**
     * @param array<int,array<string,mixed>> $lines
     * @return array<int,array{
     *   account_id:int,
     *   member_id:int|null,
     *   loan_id:int|null,
     *   line_description:string|null,
     *   debit:float,
     *   credit:float
     * }>
     */
    /**
     * @return array{
     *   account:array<string,mixed>,
     *   opening_balance:float,
     *   rows:array<int,array<string,mixed>>,
     *   closing_balance:float
     * }
     */
    /**
     * Build the Trial Balance from Posted account activity.
     *
     * Each account receives one ending balance: debit OR credit.
     * This is independent of its normal balance so unusual balances
     * remain visible rather than being hidden.
     *
     * @return array{
     *   rows:array<int,array<string,mixed>>,
     *   total_debit:float,
     *   total_credit:float,
     *   balanced:bool
     * }
     */
    public function trialBalance(
        ?string $asOfDate = null,
    ): array {
        if ($asOfDate !== null && $asOfDate !== '') {
            $date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $asOfDate,
            );

            if (
                $date === false
                || $date->format('Y-m-d') !== $asOfDate
            ) {
                throw new InvalidArgumentException(
                    'Trial Balance date must be a valid YYYY-MM-DD date.'
                );
            }
        }

        $sourceRows = $this->repository->trialBalance($asOfDate);

        $rows = [];
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($sourceRows as $row) {
            $debitActivity = round(
                (float) $row['debit_total'],
                2,
            );

            $creditActivity = round(
                (float) $row['credit_total'],
                2,
            );

            $net = round(
                $debitActivity - $creditActivity,
                2,
            );

            if (abs($net) < 0.005) {
                continue;
            }

            $endingDebit = $net > 0
                ? $net
                : 0.00;

            $endingCredit = $net < 0
                ? abs($net)
                : 0.00;

            $totalDebit = round(
                $totalDebit + $endingDebit,
                2,
            );

            $totalCredit = round(
                $totalCredit + $endingCredit,
                2,
            );

            $rows[] = [
                'id' => (int) $row['id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_type' => $row['account_type'],
                'normal_balance' => $row['normal_balance'],
                'debit' => $endingDebit,
                'credit' => $endingCredit,
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balanced' => abs($totalDebit - $totalCredit) < 0.005,
        ];
    }

    public function generalLedger(
        int $accountId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        if ($accountId <= 0) {
            throw new InvalidArgumentException(
                'A valid Ledger account is required.'
            );
        }

        $account = $this->repository->accountById($accountId);

        if ($account === null) {
            throw new RuntimeException(
                'Ledger account not found.'
            );
        }

        $normalBalance = (string) $account['normal_balance'];

        $signedOpening = $this->repository->accountOpeningBalance(
            $accountId,
            $dateFrom,
        );

        $openingBalance = $normalBalance === 'Debit'
            ? round($signedOpening, 2)
            : round(-$signedOpening, 2);

        $runningBalance = $openingBalance;

        $rows = $this->repository->accountLedger(
            $accountId,
            $dateFrom,
            $dateTo,
        );

        foreach ($rows as &$row) {
            $debit = round((float) $row['debit'], 2);
            $credit = round((float) $row['credit'], 2);

            $movement = $normalBalance === 'Debit'
                ? $debit - $credit
                : $credit - $debit;

            $runningBalance = round(
                $runningBalance + $movement,
                2,
            );

            $row['running_balance'] = $runningBalance;
        }
        unset($row);

        return [
            'account' => $account,
            'opening_balance' => $openingBalance,
            'rows' => $rows,
            'closing_balance' => $runningBalance,
        ];
    }

    private function normalizeLines(array $lines): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException(
                'A journal voucher must contain at least two lines.'
            );
        }

        $normalized = [];

        foreach ($lines as $index => $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = $this->money((float) ($line['debit'] ?? 0.0));
            $credit = $this->money((float) ($line['credit'] ?? 0.0));

            if ($accountId <= 0) {
                throw new InvalidArgumentException(
                    "Journal line #{$index} has an invalid account."
                );
            }

            $hasDebit = $debit > 0.0;
            $hasCredit = $credit > 0.0;

            if (($hasDebit && $hasCredit) || (!$hasDebit && !$hasCredit)) {
                throw new InvalidArgumentException(
                    "Journal line #{$index} must contain either a debit or a credit, but not both."
                );
            }

            $normalized[] = [
                'account_id' => $accountId,
                'member_id' => $this->nullableInt($line['member_id'] ?? null),
                'loan_id' => $this->nullableInt($line['loan_id'] ?? null),
                'line_description' => $this->nullableString(
                    $line['line_description'] ?? null,
                ),
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        if (count($normalized) < 2) {
            throw new InvalidArgumentException(
                'A journal voucher must contain at least two lines.'
            );
        }

        return $normalized;
    }

    /** @param array<int,array<string,mixed>> $lines */
    private function normalizePersistedLines(array $lines): array
    {
        return array_map(
            fn (array $line): array => [
                'account_id' => (int) $line['account_id'],
                'member_id' => $this->nullableInt($line['member_id'] ?? null),
                'loan_id' => $this->nullableInt($line['loan_id'] ?? null),
                'line_description' => $this->nullableString(
                    $line['line_description'] ?? null,
                ),
                'debit' => $this->money((float) $line['debit']),
                'credit' => $this->money((float) $line['credit']),
            ],
            $lines,
        );
    }

    /** @param array<int,array{debit:float,credit:float}> $lines */
    private function assertBalanced(array $lines): void
    {
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($lines as $line) {
            $debitTotal = $this->money(
                $debitTotal + (float) $line['debit']
            );
            $creditTotal = $this->money(
                $creditTotal + (float) $line['credit']
            );
        }

        if (abs($debitTotal - $creditTotal) > 0.005) {
            throw new InvalidArgumentException(
                sprintf(
                    'Journal voucher is not balanced. Debits: %.2f, Credits: %.2f.',
                    $debitTotal,
                    $creditTotal,
                )
            );
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function money(float $value): float
    {
        return round($value + 0.000000001, 2);
    }
}
