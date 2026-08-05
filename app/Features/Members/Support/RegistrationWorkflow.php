<?php

declare(strict_types=1);

namespace App\Features\Members\Support;

final class RegistrationWorkflow
{
    /**
     * @var array<int, array{key: string, label: string}>
     */
    private const STEPS = [
        [
            'key'   => 'membership',
            'label' => 'Membership',
        ],
        [
            'key'   => 'personal',
            'label' => 'Personal',
        ],
        [
            'key'   => 'contact',
            'label' => 'Contact',
        ],
        [
            'key'   => 'address',
            'label' => 'Address',
        ],
        [
            'key'   => 'livelihood',
            'label' => 'Livelihood',
        ],
        [
            'key'   => 'education',
            'label' => 'Education',
        ],
        [
            'key'   => 'beneficiaries',
            'label' => 'Beneficiaries',
        ],
        [
            'key'   => 'review',
            'label' => 'Review',
        ],
    ];

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function all(): array
    {
        return self::STEPS;
    }

    public static function first(): string
    {
        return self::STEPS[0]['key'];
    }

    public static function last(): string
    {
        return self::STEPS[array_key_last(self::STEPS)]['key'];
    }

    public static function isValid(string $step): bool
    {
        foreach (self::STEPS as $item) {
            if ($item['key'] === $step) {
                return true;
            }
        }

        return false;
    }

    public static function next(string $current): ?string
    {
        foreach (self::STEPS as $index => $item) {
            if ($item['key'] === $current) {
                return self::STEPS[$index + 1]['key'] ?? null;
            }
        }

        return null;
    }

    public static function previous(string $current): ?string
    {
        foreach (self::STEPS as $index => $item) {
            if ($item['key'] === $current) {
                return self::STEPS[$index - 1]['key'] ?? null;
            }
        }

        return null;
    }

    public static function label(string $step): string
    {
        foreach (self::STEPS as $item) {
            if ($item['key'] === $step) {
                return $item['label'];
            }
        }

        return '';
    }
}
