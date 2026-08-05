<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

final class RegistrationData
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [

            'membership' => [
                'membership_date' => '',
                'membership_type' => 'regular',
            ],

            'personal' => [
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
                'suffix' => '',
                'birth_date' => '',
                'birth_place' => '',
                'sex' => '',
                'civil_status' => '',
            ],

            'contact' => [
                'mobile_number' => '',
                'telephone_number' => '',
                'email' => '',
            ],

            'address' => [
                'house_number' => '',
                'street' => '',
                'barangay' => '',
                'municipality' => '',
                'province' => '',
                'zip_code' => '',
            ],

            'employment' => [
                'employment_status' => '',
                'occupation' => '',
                'employer' => '',
                'monthly_income' => '',
            ],

            'education' => [
                'highest_education' => '',
            ],

            'beneficiaries' => [],
        ];
    }
}
