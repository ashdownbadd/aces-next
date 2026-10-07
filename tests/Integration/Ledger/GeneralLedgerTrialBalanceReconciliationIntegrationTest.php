<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Config;
use App\Foundation\Database;

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$ledger = new LedgerService(
    new JournalVoucherRepository($database),
);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$assertNear = static function (
    float $expected,
    float $actual,
    string $message,
    float $epsilon = 0.005,
): void {
    if (abs($expected - $actual) >= $epsilon) {
        throw new RuntimeException(
            $message . " Expected {$expected}, got {$actual}."
        );
    }
};

$findTrialBalanceRow = static function (
    array $trialBalance,
    string $accountCode,
): ?array {
    foreach ($trialBalance['rows'] as $row) {
        if ((string) $row['account_code'] === $accountCode) {
            return $row;
        }
    }

    return null;
};

$normalBalanceValue = static function (array $row): float {
    $debit = (float) ($row['debit'] ?? 0.00);
    $credit = (float) ($row['credit'] ?? 0.00);

    return (string) $row['normal_balance'] === 'Debit'
        ? round($debit - $credit, 2)
        : round($credit - $debit, 2);
};

$cashAccountId = $ledger->accountId('1010');
$incomeAccountId = $ledger->accountId('4010');

$dateFrom = '2099-01-01';
$dateTo = '2099-01-31';

$cashBaselineLedger = $ledger->generalLedger(
    accountId: $cashAccountId,
    dateFrom: $dateFrom,
    dateTo: $dateTo,
);

$incomeBaselineLedger = $ledger->generalLedger(
    accountId: $incomeAccountId,
    dateFrom: $dateFrom,
    dateTo: $dateTo,
);

$trialBalanceBaseline = $ledger->trialBalance($dateTo);

$cashBaselineTrial = $findTrialBalanceRow(
    $trialBalanceBaseline,
    '1010',
);

$incomeBaselineTrial = $findTrialBalanceRow(
    $trialBalanceBaseline,
    '4010',
);

$cashBaselineTrialValue = $cashBaselineTrial === null
    ? 0.00
    : $normalBalanceValue($cashBaselineTrial);

$incomeBaselineTrialValue = $incomeBaselineTrial === null
    ? 0.00
    : $normalBalanceValue($incomeBaselineTrial);

$createdVoucherIds = [];

$unique = date('YmdHis') . '-' . random_int(100000, 999999);
$createdBy = 1;

try {
    $voucherRepository = new JournalVoucherRepository($database);

    $firstVoucherId = $ledger->createPending(
        [
            'reference_number' => 'GL-TB-RECON-' . $unique . '-A',
            'transaction_date' => '2099-01-10',
            'particulars' => 'QA GL-TB reconciliation debit movement',
            'source_type' => 'Manual',
            'source_id' => null,
        ],
        [
            [
                'account_id' => $cashAccountId,
                'line_description' => 'QA cash debit movement',
                'debit' => 400.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'line_description' => 'QA income credit movement',
                'debit' => 0.00,
                'credit' => 400.00,
            ],
        ],
        $createdBy,
    );

    $createdVoucherIds[] = $firstVoucherId;

    $voucherRepository->approve(
        $firstVoucherId,
        $createdBy,
        '2099-01-10 10:00:00',
    );
    $voucherRepository->post(
        $firstVoucherId,
        $createdBy,
        '2099-01-10 10:01:00',
    );

    $secondVoucherId = $ledger->createPending(
        [
            'reference_number' => 'GL-TB-RECON-' . $unique . '-B',
            'transaction_date' => '2099-01-20',
            'particulars' => 'QA GL-TB reconciliation opposite movement',
            'source_type' => 'Manual',
            'source_id' => null,
        ],
        [
            [
                'account_id' => $cashAccountId,
                'line_description' => 'QA cash credit movement',
                'debit' => 0.00,
                'credit' => 125.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'line_description' => 'QA income debit movement',
                'debit' => 125.00,
                'credit' => 0.00,
            ],
        ],
        $createdBy,
    );

    $createdVoucherIds[] = $secondVoucherId;

    $voucherRepository->approve(
        $secondVoucherId,
        $createdBy,
        '2099-01-20 10:00:00',
    );
    $voucherRepository->post(
        $secondVoucherId,
        $createdBy,
        '2099-01-20 10:01:00',
    );

    $cashLedger = $ledger->generalLedger(
        accountId: $cashAccountId,
        dateFrom: $dateFrom,
        dateTo: $dateTo,
    );

    $incomeLedger = $ledger->generalLedger(
        accountId: $incomeAccountId,
        dateFrom: $dateFrom,
        dateTo: $dateTo,
    );

    $trialBalance = $ledger->trialBalance($dateTo);

    $cashTrial = $findTrialBalanceRow($trialBalance, '1010');
    $incomeTrial = $findTrialBalanceRow($trialBalance, '4010');

    $assert($cashTrial !== null, 'Cash account must appear in Trial Balance.');
    $assert($incomeTrial !== null, 'Income account must appear in Trial Balance.');

    $cashLedgerDelta = round(
        (float) $cashLedger['closing_balance']
        - (float) $cashBaselineLedger['closing_balance'],
        2,
    );

    $incomeLedgerDelta = round(
        (float) $incomeLedger['closing_balance']
        - (float) $incomeBaselineLedger['closing_balance'],
        2,
    );

    $cashTrialDelta = round(
        $normalBalanceValue($cashTrial) - $cashBaselineTrialValue,
        2,
    );

    $incomeTrialDelta = round(
        $normalBalanceValue($incomeTrial) - $incomeBaselineTrialValue,
        2,
    );

    $assertNear(
        275.00,
        $cashLedgerDelta,
        'Cash General Ledger closing balance must reflect the net QA movement.',
    );

    $assertNear(
        275.00,
        $incomeLedgerDelta,
        'Income General Ledger closing balance must reflect the net QA movement.',
    );

    $assertNear(
        $cashLedgerDelta,
        $cashTrialDelta,
        'Cash General Ledger closing balance must reconcile to Trial Balance.',
    );

    $assertNear(
        $incomeLedgerDelta,
        $incomeTrialDelta,
        'Income General Ledger closing balance must reconcile to Trial Balance.',
    );

    $assertNear(
        (float) $cashLedger['closing_balance'],
        (float) $cashBaselineLedger['closing_balance'] + $cashTrialDelta,
        'Cash Trial Balance movement must equal the General Ledger closing movement.',
    );

    $assertNear(
        (float) $incomeLedger['closing_balance'],
        (float) $incomeBaselineLedger['closing_balance'] + $incomeTrialDelta,
        'Income Trial Balance movement must equal the General Ledger closing movement.',
    );

    $assert(
        $trialBalance['balanced'] === true,
        'Trial Balance must remain balanced after the posted QA movements.',
    );

    $cashReferences = array_map(
        static fn (array $row): string => (string) $row['reference_number'],
        $cashLedger['rows'],
    );

    $incomeReferences = array_map(
        static fn (array $row): string => (string) $row['reference_number'],
        $incomeLedger['rows'],
    );

    $assert(
        in_array('GL-TB-RECON-' . $unique . '-A', $cashReferences, true)
            && in_array('GL-TB-RECON-' . $unique . '-B', $cashReferences, true),
        'Both posted QA vouchers must be traceable in the cash General Ledger.',
    );

    $assert(
        in_array('GL-TB-RECON-' . $unique . '-A', $incomeReferences, true)
            && in_array('GL-TB-RECON-' . $unique . '-B', $incomeReferences, true),
        'Both posted QA vouchers must be traceable in the income General Ledger.',
    );

    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER → TRIAL BALANCE RECONCILIATION TEST\n";
    echo "===============================================================\n";
    echo "Cash General Ledger → Trial Balance reconciles       ✓\n";
    echo "Income General Ledger → Trial Balance reconciles     ✓\n";
    echo "Net QA movement preserved across both reports        ✓\n";
    echo "Posted vouchers traceable in both reports             ✓\n";
    echo "Trial Balance remains balanced                        ✓\n";
    echo "===============================================================\n";
    echo "ACES GENERAL LEDGER → TRIAL BALANCE RECONCILIATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($createdVoucherIds !== []) {
        $placeholders = implode(',', array_fill(0, count($createdVoucherIds), '?'));

        $statement = $pdo->prepare(
            "DELETE FROM journal_lines WHERE journal_voucher_id IN ({$placeholders})"
        );
        $statement->execute($createdVoucherIds);

        $statement = $pdo->prepare(
            "DELETE FROM journal_vouchers WHERE id IN ({$placeholders})"
        );
        $statement->execute($createdVoucherIds);
    }
}
