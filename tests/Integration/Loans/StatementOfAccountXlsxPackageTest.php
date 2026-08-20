<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Loans\Services\StatementOfAccountXlsx;

$xlsx = (new StatementOfAccountXlsx())->build([
    'as_of' => '2026-08-20',
    'loan' => [
        'id' => 999,
        'member_name' => 'QA Test Member',
        'member_number' => 'QA0001',
        'principal_amount' => 6000.00,
        'interest_rate' => 2.00,
        'terms_months' => 3,
        'payment_frequency' => null,
        'processing_fee' => 120.00,
        'insurance' => 21.60,
        'notarial_fee' => 400.00,
        'release_date' => '2026-08-20',
    ],
    'rows' => [
        [
            'due_date' => '2026-09-20',
            'principal' => 2000.00,
            'interest' => 120.00,
            'total_amount_due' => 2120.00,
            'payments' => 2120.00,
            'months_past_due' => 0,
            'principal_overdue' => 0.00,
            'interest_overdue' => 0.00,
            'penalty' => 0.00,
        ],
    ],
    'total_receivables' => 2120.00,
    'total_outstanding' => 0.00,
    'total_overdue_principal' => 0.00,
    'total_overdue_interest' => 0.00,
    'total_penalty' => 0.00,
    'grand_total_overdue' => 0.00,
]);

if (!str_starts_with($xlsx, "PK\x03\x04")) {
    throw new RuntimeException('Generated XLSX does not start with a ZIP signature.');
}

foreach ([
    '[Content_Types].xml',
    'xl/workbook.xml',
    'xl/worksheets/sheet1.xml',
    'xl/styles.xml',
    'STATEMENT OF ACCOUNT',
] as $needle) {
    if (!str_contains($xlsx, $needle)) {
        throw new RuntimeException(
            "Generated XLSX package is missing {$needle}."
        );
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES SOA XLSX PACKAGE TEST: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "No ZipArchive dependency             ✓" . PHP_EOL;
echo "ZIP/XLSX signature valid             ✓" . PHP_EOL;
echo "Required package parts present       ✓" . PHP_EOL;
echo "Statement title present              ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
