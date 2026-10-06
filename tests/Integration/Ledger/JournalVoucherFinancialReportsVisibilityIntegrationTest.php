<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertNear(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new \RuntimeException(sprintf(
            '%s Expected %.2f, got %.2f.',
            $message,
            $expected,
            $actual,
        ));
    }
}

function assertThrows(callable $callback, string $needle, string $message): void
{
    try {
        $callback();
    } catch (\Throwable $exception) {
        if ($needle !== '' && !str_contains($exception->getMessage(), $needle)) {
            throw new \RuntimeException(sprintf(
                '%s Unexpected exception: %s',
                $message,
                $exception->getMessage(),
            ));
        }

        return;
    }

    throw new \RuntimeException(sprintf(
        '%s Expected an exception containing "%s".',
        $message,
        $needle,
    ));
}

function findAccountRow(array $rows, string $accountCode): ?array
{
    foreach ($rows as $row) {
        if (($row['account_code'] ?? null) === $accountCode) {
            return $row;
        }
    }

    return null;
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0) {
    throw new \RuntimeException('An active user is required.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = $userId;

$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);

$cashAccountId = $ledgerService->accountId('1010');
$incomeAccountId = $ledgerService->accountId('4010');

if ($cashAccountId <= 0 || $incomeAccountId <= 0) {
    throw new \RuntimeException('Required Ledger accounts 1010 and 4010 are missing.');
}

$reportDate = '2026-10-06';
$amount = 731.00;
$voucherIds = [];

try {
    echo "================================================================\n";
    echo "ACES JOURNAL VOUCHER → FINANCIAL REPORTS VISIBILITY INTEGRATION TEST\n";
    echo "================================================================\n";

    $baselineTrial = $ledgerService->trialBalance($reportDate);
    $baselineIncome = $ledgerService->incomeStatement($reportDate, $reportDate);
    $baselineBalance = $ledgerService->balanceSheet($reportDate);

    $baselineCashTrial = findAccountRow($baselineTrial['rows'], '1010');
    $baselineIncomeTrial = findAccountRow($baselineTrial['rows'], '4010');

    $baselineCash = $baselineCashTrial !== null
        ? (float) $baselineCashTrial['debit'] - (float) $baselineCashTrial['credit']
        : 0.00;

    $baselineIncomeAmount = $baselineIncomeTrial !== null
        ? (float) $baselineIncomeTrial['credit'] - (float) $baselineIncomeTrial['debit']
        : 0.00;

    $baselineIncomeTotal = (float) $baselineIncome['total_income'];

    $baselineCashBalanceRow = findAccountRow($baselineBalance['assets'], '1010');
    $baselineCashBalance = $baselineCashBalanceRow !== null
        ? (float) $baselineCashBalanceRow['balance']
        : 0.00;

    $baselineNetSurplus = (float) $baselineBalance['net_surplus'];

    $createVoucher = static function (
        LedgerService $ledgerService,
        int $cashAccountId,
        int $incomeAccountId,
        int $userId,
        string $reference,
    ): int {
        return $ledgerService->createPending(
            voucher: [
                'reference_number' => $reference,
                'transaction_date' => '2026-10-06',
                'particulars' => 'QA financial report visibility',
            ],
            lines: [
                [
                    'account_id' => $cashAccountId,
                    'member_id' => null,
                    'loan_id' => null,
                    'line_description' => 'QA cash',
                    'debit' => 731.00,
                    'credit' => 0.00,
                ],
                [
                    'account_id' => $incomeAccountId,
                    'member_id' => null,
                    'loan_id' => null,
                    'line_description' => 'QA income',
                    'debit' => 0.00,
                    'credit' => 731.00,
                ],
            ],
            createdBy: $userId,
        );
    };

    $pendingId = $createVoucher(
        $ledgerService,
        $cashAccountId,
        $incomeAccountId,
        $userId,
        'QA-REPORT-PENDING-' . bin2hex(random_bytes(4)),
    );
    $voucherIds[] = $pendingId;

    $pendingTrial = $ledgerService->trialBalance($reportDate);
    $pendingIncome = $ledgerService->incomeStatement($reportDate, $reportDate);
    $pendingBalance = $ledgerService->balanceSheet($reportDate);

    $pendingCashRow = findAccountRow($pendingTrial['rows'], '1010');
    $pendingIncomeRow = findAccountRow($pendingTrial['rows'], '4010');
    $pendingCashBalanceRow = findAccountRow($pendingBalance['assets'], '1010');

    assertNear($baselineCash, $pendingCashRow !== null
        ? (float) $pendingCashRow['debit'] - (float) $pendingCashRow['credit'] : 0.00,
        'Pending voucher must not affect Trial Balance cash.');
    assertNear($baselineIncomeAmount, $pendingIncomeRow !== null
        ? (float) $pendingIncomeRow['credit'] - (float) $pendingIncomeRow['debit'] : 0.00,
        'Pending voucher must not affect Trial Balance income.');
    assertNear($baselineIncomeTotal, (float) $pendingIncome['total_income'],
        'Pending voucher must not affect Income Statement.');
    assertNear($baselineCashBalance, $pendingCashBalanceRow !== null
        ? (float) $pendingCashBalanceRow['balance'] : 0.00,
        'Pending voucher must not affect Balance Sheet cash.');
    assertNear($baselineNetSurplus, (float) $pendingBalance['net_surplus'],
        'Pending voucher must not affect Balance Sheet net surplus.');
    echo "Pending voucher → reports unchanged ✓\n";

    $approvedId = $createVoucher(
        $ledgerService,
        $cashAccountId,
        $incomeAccountId,
        $userId,
        'QA-REPORT-APPROVED-' . bin2hex(random_bytes(4)),
    );
    $voucherIds[] = $approvedId;
    $ledgerService->approve($approvedId, $userId, date('Y-m-d H:i:s'));

    $approvedTrial = $ledgerService->trialBalance($reportDate);
    $approvedIncome = $ledgerService->incomeStatement($reportDate, $reportDate);
    $approvedBalance = $ledgerService->balanceSheet($reportDate);

    $approvedCashRow = findAccountRow($approvedTrial['rows'], '1010');
    $approvedIncomeRow = findAccountRow($approvedTrial['rows'], '4010');
    $approvedCashBalanceRow = findAccountRow($approvedBalance['assets'], '1010');

    assertNear($baselineCash, $approvedCashRow !== null
        ? (float) $approvedCashRow['debit'] - (float) $approvedCashRow['credit'] : 0.00,
        'Approved voucher must not affect Trial Balance cash.');
    assertNear($baselineIncomeAmount, $approvedIncomeRow !== null
        ? (float) $approvedIncomeRow['credit'] - (float) $approvedIncomeRow['debit'] : 0.00,
        'Approved voucher must not affect Trial Balance income.');
    assertNear($baselineIncomeTotal, (float) $approvedIncome['total_income'],
        'Approved voucher must not affect Income Statement.');
    assertNear($baselineCashBalance, $approvedCashBalanceRow !== null
        ? (float) $approvedCashBalanceRow['balance'] : 0.00,
        'Approved voucher must not affect Balance Sheet cash.');
    assertNear($baselineNetSurplus, (float) $approvedBalance['net_surplus'],
        'Approved voucher must not affect Balance Sheet net surplus.');
    echo "Approved voucher → reports unchanged ✓\n";

    $rejectedId = $createVoucher(
        $ledgerService,
        $cashAccountId,
        $incomeAccountId,
        $userId,
        'QA-REPORT-REJECTED-' . bin2hex(random_bytes(4)),
    );
    $voucherIds[] = $rejectedId;
    $ledgerService->reject($rejectedId, 'QA rejection');

    $rejectedTrial = $ledgerService->trialBalance($reportDate);
    $rejectedIncome = $ledgerService->incomeStatement($reportDate, $reportDate);
    $rejectedBalance = $ledgerService->balanceSheet($reportDate);

    $rejectedCashRow = findAccountRow($rejectedTrial['rows'], '1010');
    $rejectedIncomeRow = findAccountRow($rejectedTrial['rows'], '4010');
    $rejectedCashBalanceRow = findAccountRow($rejectedBalance['assets'], '1010');

    assertNear($baselineCash, $rejectedCashRow !== null
        ? (float) $rejectedCashRow['debit'] - (float) $rejectedCashRow['credit'] : 0.00,
        'Rejected voucher must not affect Trial Balance cash.');
    assertNear($baselineIncomeAmount, $rejectedIncomeRow !== null
        ? (float) $rejectedIncomeRow['credit'] - (float) $rejectedIncomeRow['debit'] : 0.00,
        'Rejected voucher must not affect Trial Balance income.');
    assertNear($baselineIncomeTotal, (float) $rejectedIncome['total_income'],
        'Rejected voucher must not affect Income Statement.');
    assertNear($baselineCashBalance, $rejectedCashBalanceRow !== null
        ? (float) $rejectedCashBalanceRow['balance'] : 0.00,
        'Rejected voucher must not affect Balance Sheet cash.');
    assertNear($baselineNetSurplus, (float) $rejectedBalance['net_surplus'],
        'Rejected voucher must not affect Balance Sheet net surplus.');
    echo "Rejected voucher → reports unchanged ✓\n";

    $postedId = $createVoucher(
        $ledgerService,
        $cashAccountId,
        $incomeAccountId,
        $userId,
        'QA-REPORT-POSTED-' . bin2hex(random_bytes(4)),
    );
    $voucherIds[] = $postedId;
    $ledgerService->approve($postedId, $userId, date('Y-m-d H:i:s'));
    $ledgerService->post($postedId, $userId, date('Y-m-d H:i:s'));

    $postedTrial = $ledgerService->trialBalance($reportDate);
    $postedIncome = $ledgerService->incomeStatement($reportDate, $reportDate);
    $postedBalance = $ledgerService->balanceSheet($reportDate);

    $postedCashRow = findAccountRow($postedTrial['rows'], '1010');
    $postedIncomeRow = findAccountRow($postedTrial['rows'], '4010');
    $postedCashBalanceRow = findAccountRow($postedBalance['assets'], '1010');

    assertNear($baselineCash + $amount, $postedCashRow !== null
        ? (float) $postedCashRow['debit'] - (float) $postedCashRow['credit'] : 0.00,
        'Posted voucher must affect Trial Balance cash.');
    assertNear($baselineIncomeAmount + $amount, $postedIncomeRow !== null
        ? (float) $postedIncomeRow['credit'] - (float) $postedIncomeRow['debit'] : 0.00,
        'Posted voucher must affect Trial Balance income.');
    assertNear($baselineIncomeTotal + $amount, (float) $postedIncome['total_income'],
        'Posted voucher must affect Income Statement.');
    assertNear($baselineCashBalance + $amount, $postedCashBalanceRow !== null
        ? (float) $postedCashBalanceRow['balance'] : 0.00,
        'Posted voucher must affect Balance Sheet cash.');
    assertNear($baselineNetSurplus + $amount, (float) $postedBalance['net_surplus'],
        'Posted voucher must affect Balance Sheet net surplus.');
    echo "Posted voucher → reports included ✓\n";

    assertThrows(
        fn() => $ledgerService->post($pendingId, $userId, date('Y-m-d H:i:s')),
        'Only Approved journal vouchers can be posted.',
        'Pending voucher direct posting must remain blocked.',
    );
    echo "Pending → direct posting blocked ✓\n";

    assertThrows(
        fn() => $ledgerService->post($rejectedId, $userId, date('Y-m-d H:i:s')),
        'Only Approved journal vouchers can be posted.',
        'Rejected voucher posting must remain blocked.',
    );
    echo "Rejected → posting blocked ✓\n";

    echo "================================================================\n";
    echo "ACES JOURNAL VOUCHER → FINANCIAL REPORTS VISIBILITY INTEGRATION TEST: PASS\n";
    echo "================================================================\n";
} finally {
    if ($voucherIds !== []) {
        $placeholders = implode(',', array_fill(0, count($voucherIds), '?'));

        $statement = $pdo->prepare(
            "DELETE FROM journal_lines WHERE journal_voucher_id IN ({$placeholders})"
        );
        $statement->execute($voucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers WHERE id IN ({$placeholders})"
        );
        $statement->execute($voucherIds);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
