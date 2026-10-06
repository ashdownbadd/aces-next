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
        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertThrows(callable $callback, string $needle, string $message): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($needle !== '' && !str_contains($exception->getMessage(), $needle)) {
            throw new RuntimeException(sprintf(
                '%s Unexpected exception: %s',
                $message,
                $exception->getMessage(),
            ));
        }

        return;
    }

    throw new RuntimeException(sprintf(
        '%s Expected an exception%s.',
        $message,
        $needle === '' ? '' : ' containing "' . $needle . '"',
    ));
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');
$database = new Database($config);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException('An active user is required.');
}

$ledgerRepository = new JournalVoucherRepository($database);
$ledgerService = new LedgerService($ledgerRepository);

$cashAccountId = $ledgerService->accountId('1010');
$incomeAccountId = $ledgerService->accountId('4010');

if ($cashAccountId <= 0 || $incomeAccountId <= 0) {
    throw new RuntimeException('Required QA Ledger accounts 1010 and 4010 were not found.');
}

$accountLedger = static function () use (
    $ledgerService,
    $cashAccountId,
): array {
    return $ledgerService->generalLedger(
        accountId: $cashAccountId,
        dateFrom: '2026-01-01',
        dateTo: '2026-12-31',
    )['rows'];
};

$sourceType = 'QA_GeneralLedgerVisibility';
$sourceIds = [
    'pending' => random_int(1000000, 1999999),
    'approved' => random_int(2000000, 2999999),
    'rejected' => random_int(3000000, 3999999),
];

$voucherIds = [];

try {
    echo "============================================================\n";
    echo "ACES JOURNAL VOUCHER → GENERAL LEDGER VISIBILITY INTEGRATION TEST\n";
    echo "============================================================\n";

    $baselineRows = $accountLedger();

    $pendingId = $ledgerService->createPending(
        voucher: [
            'reference_number' => 'QA-GL-PENDING-' . $sourceIds['pending'],
            'transaction_date' => '2026-10-06',
            'particulars' => 'QA pending General Ledger visibility test',
            'source_type' => $sourceType,
            'source_id' => $sourceIds['pending'],
        ],
        lines: [
            [
                'account_id' => $cashAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA pending debit',
                'debit' => 100.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA pending credit',
                'debit' => 0.00,
                'credit' => 100.00,
            ],
        ],
        createdBy: $userId,
    );
    $voucherIds[] = $pendingId;

    assertSameValue(
        'Pending',
        $ledgerRepository->find($pendingId)['status'] ?? null,
        'New journal voucher must begin Pending.',
    );

    assertSameValue(
        count($baselineRows),
        count($accountLedger()),
        'Pending voucher must not appear in the General Ledger.',
    );
    echo "Pending voucher → excluded from General Ledger ✓\n";

    $approvedId = $ledgerService->createPending(
        voucher: [
            'reference_number' => 'QA-GL-APPROVED-' . $sourceIds['approved'],
            'transaction_date' => '2026-10-06',
            'particulars' => 'QA approved General Ledger visibility test',
            'source_type' => $sourceType,
            'source_id' => $sourceIds['approved'],
        ],
        lines: [
            [
                'account_id' => $cashAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA approved debit',
                'debit' => 200.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA approved credit',
                'debit' => 0.00,
                'credit' => 200.00,
            ],
        ],
        createdBy: $userId,
    );
    $voucherIds[] = $approvedId;

    $ledgerService->approve(
        voucherId: $approvedId,
        userId: $userId,
        approvedAt: '2026-10-06 10:00:00',
    );

    assertSameValue(
        'Approved',
        $ledgerRepository->find($approvedId)['status'] ?? null,
        'Approved voucher must transition to Approved.',
    );

    assertSameValue(
        count($baselineRows),
        count($accountLedger()),
        'Approved voucher must not appear in the General Ledger.',
    );
    echo "Approved voucher → excluded from General Ledger ✓\n";

    $rejectedId = $ledgerService->createPending(
        voucher: [
            'reference_number' => 'QA-GL-REJECTED-' . $sourceIds['rejected'],
            'transaction_date' => '2026-10-06',
            'particulars' => 'QA rejected General Ledger visibility test',
            'source_type' => $sourceType,
            'source_id' => $sourceIds['rejected'],
        ],
        lines: [
            [
                'account_id' => $cashAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA rejected debit',
                'debit' => 300.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $incomeAccountId,
                'member_id' => null,
                'loan_id' => null,
                'line_description' => 'QA rejected credit',
                'debit' => 0.00,
                'credit' => 300.00,
            ],
        ],
        createdBy: $userId,
    );
    $voucherIds[] = $rejectedId;

    $ledgerService->reject(
        voucherId: $rejectedId,
        reason: 'QA rejected visibility test',
    );

    assertSameValue(
        'Rejected',
        $ledgerRepository->find($rejectedId)['status'] ?? null,
        'Rejected voucher must transition to Rejected.',
    );

    assertSameValue(
        count($baselineRows),
        count($accountLedger()),
        'Rejected voucher must not appear in the General Ledger.',
    );
    echo "Rejected voucher → excluded from General Ledger ✓\n";

    $ledgerService->post(
        voucherId: $approvedId,
        userId: $userId,
        postedAt: '2026-10-06 10:05:00',
    );

    assertSameValue(
        'Posted',
        $ledgerRepository->find($approvedId)['status'] ?? null,
        'Approved voucher must transition to Posted.',
    );

    assertSameValue(
        count($baselineRows) + 1,
        count($accountLedger()),
        'Posted voucher must appear in the General Ledger.',
    );
    echo "Posted voucher → included in General Ledger ✓\n";

    assertThrows(
        fn() => $ledgerService->post(
            voucherId: $pendingId,
            userId: $userId,
            postedAt: '2026-10-06 10:10:00',
        ),
        'Only Approved journal vouchers can be posted.',
        'Pending voucher must not bypass approval before posting.',
    );
    echo "Pending → direct posting blocked ✓\n";

    assertThrows(
        fn() => $ledgerService->post(
            voucherId: $rejectedId,
            userId: $userId,
            postedAt: '2026-10-06 10:15:00',
        ),
        'Only Approved journal vouchers can be posted.',
        'Rejected voucher must not be posted.',
    );
    echo "Rejected → posting blocked ✓\n";

    echo "============================================================\n";
    echo "ACES JOURNAL VOUCHER → GENERAL LEDGER VISIBILITY INTEGRATION TEST: PASS\n";
    echo "============================================================\n";
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
}
