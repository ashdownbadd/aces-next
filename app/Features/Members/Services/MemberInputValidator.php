<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\DTOs\MemberRegistrationData;
use DateTimeImmutable;
use InvalidArgumentException;

final class MemberInputValidator
{
    /** @param array<string,mixed> $data */
    public function validateStep(string $step, array $data): void
    {
        switch ($step) {
            case 'membership':
                $this->required($data, 'membership_date', 'Membership date');
                $this->date($data['membership_date'] ?? '', 'Membership date');
                $this->allowed($data['membership_type'] ?? '', ['regular','associate'], 'Membership type');
                break;
            case 'personal':
                $this->name($data['first_name'] ?? '', 'First name', true);
                $this->name($data['middle_name'] ?? '', 'Middle name', false);
                $this->name($data['last_name'] ?? '', 'Last name', true);
                $this->max($data['suffix'] ?? '', 30, 'Suffix');
                $this->date($data['birth_date'] ?? '', 'Birth date');
                if ($this->parseDate($data['birth_date'] ?? '') > new DateTimeImmutable('today')) {
                    throw new InvalidArgumentException('Birth date cannot be in the future.');
                }
                $this->required($data, 'birth_place', 'Birth place');
                $this->max($data['birth_place'] ?? '', 150, 'Birth place');
                $this->allowed($data['sex'] ?? '', ['male','female'], 'Sex');
                $this->allowed($data['civil_status'] ?? '', ['single','married','widowed','separated'], 'Civil status');
                $this->name($data['nationality'] ?? '', 'Nationality', true, 80);
                break;
            case 'contact':
                $mobile = trim((string)($data['mobile_number'] ?? ''));
                if ($mobile !== '' && !preg_match('/^\+?[0-9][0-9\s\-()]{6,19}$/', $mobile)) {
                    throw new InvalidArgumentException('Mobile number format is invalid.');
                }
                $phone = trim((string)($data['telephone_number'] ?? ''));
                if ($phone !== '' && !preg_match('/^\+?[0-9][0-9\s\-()]{5,19}$/', $phone)) {
                    throw new InvalidArgumentException('Telephone number format is invalid.');
                }
                $email = trim((string)($data['email_address'] ?? ''));
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Email address format is invalid.');
                }
                $this->max($mobile, 30, 'Mobile number');
                $this->max($phone, 30, 'Telephone number');
                $this->max($email, 254, 'Email address');
                break;
            case 'address':
                foreach (['house_number'=>'House number','street'=>'Street','barangay'=>'Barangay','city'=>'City','province'=>'Province'] as $field=>$label) {
                    $this->required($data, $field, $label);
                    $this->max($data[$field] ?? '', 150, $label);
                }
                $zip = trim((string)($data['zip_code'] ?? ''));
                if (!preg_match('/^[0-9]{4,10}$/', $zip)) {
                    throw new InvalidArgumentException('ZIP code must contain 4 to 10 digits.');
                }
                break;
            case 'livelihood':
                $this->allowed($data['employment_status'] ?? '', ['employed','self_employed','business_owner','ofw','retired','student','unemployed'], 'Employment status');
                $this->max($data['occupation'] ?? '', 150, 'Occupation');
                $this->max($data['employer'] ?? '', 150, 'Employer');
                $income = str_replace(',', '', trim((string)($data['monthly_income'] ?? '')));
                if ($income === '' || !is_numeric($income) || (float)$income < 0 || (float)$income > 999999999.99) {
                    throw new InvalidArgumentException('Monthly income must be a valid non-negative amount.');
                }
                break;
            case 'education':
                $this->allowed($data['highest_educational_attainment'] ?? '', ['no_formal_education','elementary','high_school','senior_high_school','vocational','college','postgraduate','other'], 'Highest educational attainment');
                $this->max($data['school_name'] ?? '', 200, 'School name');
                if (($data['graduation_year'] ?? '') !== '' && (!ctype_digit((string)$data['graduation_year']) || (int)$data['graduation_year'] < 1900 || (int)$data['graduation_year'] > (int)date('Y') + 10)) {
                    throw new InvalidArgumentException('Graduation year is invalid.');
                }
                break;
            case 'beneficiaries':
                $beneficiaries = $data['beneficiaries'] ?? $data;
                if (!is_array($beneficiaries)) {
                    throw new InvalidArgumentException('Beneficiary data is invalid.');
                }
                foreach ($beneficiaries as $beneficiary) {
                    if (!is_array($beneficiary)) continue;
                    $this->name($beneficiary['first_name'] ?? '', 'Beneficiary first name', true);
                    $this->name($beneficiary['last_name'] ?? '', 'Beneficiary last name', true);
                    $this->required($beneficiary, 'relationship', 'Beneficiary relationship');
                    $this->max($beneficiary['relationship'] ?? '', 100, 'Beneficiary relationship');
                    $this->date($beneficiary['birth_date'] ?? '', 'Beneficiary birth date');
                }
                break;
        }
    }

    public function validateRegistration(MemberRegistrationData $registration): void
    {
        $this->validateStep('membership', $registration->membership->toArray());
        $this->validateStep('personal', $registration->personal->toArray());
        $this->validateStep('contact', $registration->contact->toArray());
        $this->validateStep('address', $registration->address->toArray());
        $this->validateStep('livelihood', $registration->livelihood->toArray());
        $this->validateStep('education', $registration->education->toArray());
        $this->validateStep('beneficiaries', array_map(static fn($b) => $b->toArray(), $registration->beneficiaries));
    }

    /** @param array<string,mixed> $data */
    private function required(array $data, string $field, string $label): void
    {
        if (trim((string)($data[$field] ?? '')) === '') throw new InvalidArgumentException("{$label} is required.");
    }
    private function max(mixed $value, int $length, string $label): void
    {
        if (mb_strlen(trim((string)$value)) > $length) throw new InvalidArgumentException("{$label} is too long.");
    }
    private function name(mixed $value, string $label, bool $required, int $max = 100): void
    {
        $value = trim((string)$value);
        if ($required && $value === '') throw new InvalidArgumentException("{$label} is required.");
        if ($value !== '' && (!preg_match("/^[\\p{L}\\p{M} .'-]+$/u", $value) || mb_strlen($value) > $max)) throw new InvalidArgumentException("{$label} contains invalid characters or is too long.");
    }
    private function allowed(mixed $value, array $allowed, string $label): void
    {
        if (!in_array((string)$value, $allowed, true)) throw new InvalidArgumentException("{$label} is invalid.");
    }
    private function date(mixed $value, string $label): void { $this->parseDate((string)$value, $label); }
    private function parseDate(string $value, string $label = 'Date'): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        if ($date === false || $date->format('Y-m-d') !== trim($value)) throw new InvalidArgumentException("{$label} must be a valid YYYY-MM-DD date.");
        return $date;
    }
}
