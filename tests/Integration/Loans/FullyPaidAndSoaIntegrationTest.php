<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Domain\AmortizationType;
use App\Features\Loans\Domain\CollateralType;
use App\Features\Loans\Domain\LoanType;
use App\Features\Loans\DTOs\LoanData;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Features\Loans\Services\StatementOfAccountService;
use App\Features\Loans\Services\StatementOfAccountXlsx;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf(
                "%s Expected %s, got %s.",
                $message,
                var_export($expected, true),
                var_export($actual, true),
            )
        );
    }
}

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users ORDER BY id ASC LIMIT 1'
)->fetchColumn();

$memberId = (int) $pdo->query(
    'SELECT id FROM members ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0 || $memberId <= 0) {
    throw new RuntimeException('An existing user and member are required.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $userId;

$loanRepository = new LoanRepository($database);
$paymentRepository = new LoanPaymentRepository($database);
$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);
$amortization = new AmortizationService();
$session = new Session();

$loanService = new LoanService(
    $loanRepository,
    $amortization,
    $activityService,
    $session,
);

$paymentService = new PaymentService(
    $paymentRepository,
    $loanRepository,
    $amortization,
    $activityService,
    $session,
);

$soaService = new StatementOfAccountService(
    $loanRepository,
    $paymentRepository,
);

$soaXlsx = new StatementOfAccountXlsx();

$loanId = null;
$exportPath = null;

try {
    $loanId = $loanService->create(
        new LoanData(
            memberId: $memberId,
            loanType: LoanType::PRODUCTIVITY_LOAN,
            collateral: CollateralType::POST_DATED_CHECK,
            principalAmount: 6000.00,
            interestRate: 2.00,
            amortizationType: AmortizationType::STRAIGHT_LINE,
            paymentFrequency: null,
            termsMonths: 3,
            startDate: '2026-08-20',
        )
    );

    $loanService->submit($loanId);
    $loanService->approve($loanId);
    $loanService->release($loanId, '2026-08-20');

    // In this 3-period flat-interest loan, the true global waterfall
    // settlement amount is all scheduled interest + principal.
    $paymentService->apply(
        loanId: $loanId,
        amountPaid: 6360.00,
        remarks: 'Final settlement',
    );

    $loan = $loanService->find($loanId);

    assertSameValue(
        'Fully Paid',
        $loan['loan_status'],
        'Final payment must make the loan Fully Paid.'
    );

    assertTrueValue(
        !empty($loan['fully_paid_at']),
        'fully_paid_at must be recorded.'
    );

    $rows = $pdo->prepare(
        'SELECT
            rem_principal,
            rem_interest,
            rem_penalty,
            status
         FROM loan_amortizations
         WHERE loan_id = :loan_id
         ORDER BY period ASC'
    );
    $rows->execute(['loan_id' => $loanId]);
    $schedule = $rows->fetchAll(PDO::FETCH_ASSOC);

    foreach ($schedule as $row) {
        assertTrueValue(
            abs((float) $row['rem_principal']) < 0.005,
            'Final settlement: remaining principal must be zero.'
        );
        assertTrueValue(
            abs((float) $row['rem_interest']) < 0.005,
            'Final settlement: remaining interest must be zero.'
        );
        assertTrueValue(
            abs((float) $row['rem_penalty']) < 0.005,
            'Final settlement: remaining penalty must be zero.'
        );
        assertSameValue(
            'Paid',
            $row['status'],
            'Final settlement: every period must be Paid.'
        );
    }

    $soa = $soaService->build($loanId);

    assertSameValue(
        3,
        count($soa['rows']),
        'SOA must contain all amortization periods.'
    );

    assertTrueValue(
        $soa['total_receivables'] > 0,
        'SOA total receivables must be positive.'
    );

    assertSameValue(
        0.00,
        round((float) $soa['total_outstanding'], 2),
        'Fully paid SOA outstanding must be zero.'
    );

    $xlsx = $soaXlsx->build($soa);
    assertTrueValue(
        strlen($xlsx) > 1000,
        'XLSX export must contain data.'
    );

    $exportPath = tempnam(sys_get_temp_dir(), 'soa_test_');
    file_put_contents($exportPath, $xlsx);

    assertTrueValue(
        str_starts_with($xlsx, "PK\x03\x04"),
        'Generated SOA must start with a valid ZIP local-file signature.'
    );

    foreach ([
        '[Content_Types].xml',
        'xl/workbook.xml',
        'xl/worksheets/sheet1.xml',
        'xl/styles.xml',
    ] as $entry) {
        assertTrueValue(
            str_contains($xlsx, $entry),
            "Generated SOA package must contain {$entry}."
        );
    }

    assertTrueValue(
        str_contains($xlsx, 'STATEMENT OF ACCOUNT'),
        'SOA package must contain the statement title.'
    );

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES FULLY PAID + SOA INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Loan ID: #{$loanId}" . PHP_EOL;
    echo "Final status → Fully Paid             ✓" . PHP_EOL;
    echo "All amortization rows Paid            ✓" . PHP_EOL;
    echo "SOA rows generated                    ✓" . PHP_EOL;
    echo "Outstanding = ₱0.00                   ✓" . PHP_EOL;
    echo "XLSX package valid                    ✓" . PHP_EOL;
    echo "Statement title present               ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;

} finally {
    if ($exportPath !== null) {
        @unlink($exportPath);
    }

    if ($loanId !== null) {
        $q=$pdo->prepare(
            'DELETE lpa FROM loan_payment_allocations lpa
             INNER JOIN loan_payments lp ON lp.id = lpa.payment_id
             WHERE lp.loan_id = :loan_id'
        );
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare('DELETE FROM loan_payments WHERE loan_id = :loan_id');
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare(
            "DELETE FROM activity_logs WHERE subject_type='Loan' AND subject_id=:loan_id"
        );
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare('DELETE FROM loan_amortizations WHERE loan_id = :loan_id');
        $q->execute(['loan_id'=>$loanId]);

        $q=$pdo->prepare('DELETE FROM loans WHERE id = :loan_id');
        $q->execute(['loan_id'=>$loanId]);

        echo "Cleanup completed for temporary Loan #{$loanId}." . PHP_EOL;
    }
}
