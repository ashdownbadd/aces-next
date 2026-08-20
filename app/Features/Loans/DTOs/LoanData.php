<?php

declare(strict_types=1);

namespace App\Features\Loans\DTOs;

use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\Domain\PaymentFrequency;
use App\Http\Request;

final class LoanData
{
    public function __construct(
        public readonly int $memberId,
        public readonly string $loanType,
        public readonly string $collateral,
        public readonly float $principalAmount,
        public readonly float $interestRate,
        public readonly ?string $amortizationType,
        public readonly ?string $paymentFrequency,
        public readonly int $termsMonths,
        public readonly string $startDate,
        public readonly ?float $manualPayment = null,
        public readonly ?string $tctNo = null,
        public readonly ?string $taxDeclarationNo = null,
        public readonly ?string $realPropertyPaymentStatus = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $loanType = (string) $request->input('loan_type', '');

        return new self(
            memberId: (int) $request->input('member_id', 0),
            loanType: $loanType,
            collateral: (string) $request->input('collateral', ''),
            principalAmount: (float) $request->input('principal_amount', 0),
            interestRate: (float) $request->input('interest_rate', 0),
            amortizationType: $loanType === LoanType::MICRO_FINANCE_LOAN
                ? null
                : self::nullableString($request->input('amortization_type')),
            paymentFrequency: $loanType === LoanType::MICRO_FINANCE_LOAN
                ? self::nullableString($request->input('payment_frequency'))
                : null,
            termsMonths: (int) $request->input('terms_months', 0),
            startDate: (string) $request->input('start_date', ''),
            manualPayment: self::nullableFloat($request->input('manual_payment')),
            tctNo: self::nullableString($request->input('tct_no')),
            taxDeclarationNo: self::nullableString($request->input('tax_declaration_no')),
            realPropertyPaymentStatus: self::nullableString(
                $request->input('real_property_payment_status'),
            ),
            notes: self::nullableString($request->input('notes')),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $loanType = (string) ($data['loan_type'] ?? '');

        return new self(
            memberId: (int) ($data['member_id'] ?? 0),
            loanType: $loanType,
            collateral: (string) ($data['collateral'] ?? ''),
            principalAmount: (float) ($data['principal_amount'] ?? 0),
            interestRate: (float) ($data['interest_rate'] ?? 0),
            amortizationType: $loanType === LoanType::MICRO_FINANCE_LOAN
                ? null
                : self::nullableString($data['amortization_type'] ?? null),
            paymentFrequency: $loanType === LoanType::MICRO_FINANCE_LOAN
                ? self::nullableString($data['payment_frequency'] ?? null)
                : null,
            termsMonths: (int) ($data['terms_months'] ?? 0),
            startDate: (string) ($data['start_date'] ?? ''),
            manualPayment: self::nullableFloat($data['manual_payment'] ?? null),
            tctNo: self::nullableString($data['tct_no'] ?? null),
            taxDeclarationNo: self::nullableString($data['tax_declaration_no'] ?? null),
            realPropertyPaymentStatus: self::nullableString(
                $data['real_property_payment_status'] ?? null,
            ),
            notes: self::nullableString($data['notes'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'loan_type' => $this->loanType,
            'collateral' => $this->collateral,
            'principal_amount' => $this->principalAmount,
            'interest_rate' => $this->interestRate,
            'amortization_type' => $this->amortizationType,
            'payment_frequency' => $this->paymentFrequency,
            'terms_months' => $this->termsMonths,
            'start_date' => $this->startDate,
            'manual_payment' => $this->manualPayment,
            'tct_no' => $this->tctNo,
            'tax_declaration_no' => $this->taxDeclarationNo,
            'real_property_payment_status' => $this->realPropertyPaymentStatus,
            'notes' => $this->notes,
        ];
    }

    public function usesManualAmortization(): bool
    {
        return $this->amortizationType === AmortizationType::MANUAL;
    }

    public function isRealPropertyCollateral(): bool
    {
        return $this->collateral === CollateralType::REAL_PROPERTY;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
