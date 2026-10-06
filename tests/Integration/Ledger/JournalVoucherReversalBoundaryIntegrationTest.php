<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
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

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
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

function assertNear(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.005) {
        throw new RuntimeException(sprintf(
            '%s Expected %.2f, got %.2f.',
            $message,
            $expected,
            $actual,
        ));
    }
}

$database = $app->container()->get(Database::class);
$ledger = $app->container()->get(LedgerService::class);
$repository = $app->container()->get(JournalVoucherRepository::class);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

$cash = $ledger->accountId('1010');
$receivable = $ledger->accountId('1110');

if ($userId <= 0 || $cash <= 0 || $receivable <= 0) {
    throw new RuntimeException('Required user or Ledger accounts are missing.');
}

$references = [];

try {
    $makeVoucher = function (string $suffix) use (
        $ledger,
        $userId,
        $cash,
        $receivable,
        &$references,
    ): int {
        $reference = 'QA-JV-REV-' . $suffix . '-' . date('YmdHis') . '-' . random_int(1000, 9999);
        $references[] = $reference;

        return $ledger->createPending(
            voucher: [
                'reference_number' => $reference,
                'transaction_date' => date('Y-m-d'),
                'particulars' => 'QA journal voucher reversal boundary',
                'source_type' => 'QA',
                'source_id' => null,
            ],
            lines: [
                [
                    'account_id' => $cash,
                    'debit' => 500.00,
                    'credit' => 0.00,
                ],
                [
                    'account_id' => $receivable,
                    'debit' => 0.00,
                    'credit' => 500.00,
                ],
            ],
            createdBy: $userId,
        );
    };

    $pendingId = $makeVoucher('PENDING');

    assertThrows(
        fn() => $ledger->createReversalPending(
            originalVoucherId: $pendingId,
            referenceNumber: 'QA-JV-REV-PENDING-CHILD-' . random_int(1000, 9999),
            transactionDate: date('Y-m-d'),
            particulars: 'QA reversal of pending voucher',
            createdBy: $userId,
            sourceType: 'QA',
            sourceId: $pendingId,
        ),
        'Posted',
        'Pending voucher must not be reversible.',
    );
    echo "Pending voucher → reversal blocked ✓\n";

    $approvedId = $makeVoucher('APPROVED');

    $ledger->approve(
        voucherId: $approvedId,
        userId: $userId,
        approvedAt: date('Y-m-d H:i:s'),
    );

    assertSameValue(
        'Approved',
        $repository->find($approvedId)['status'] ?? null,
        'Voucher should be Approved before reversal check.',
    );

    assertThrows(
        fn() => $ledger->createReversalPending(
            originalVoucherId: $approvedId,
            referenceNumber: 'QA-JV-REV-APPROVED-CHILD-' . random_int(1000, 9999),
            transactionDate: date('Y-m-d'),
            particulars: 'QA reversal of approved voucher',
            createdBy: $userId,
            sourceType: 'QA',
            sourceId: $approvedId,
        ),
        'Posted',
        'Approved voucher must not be reversible.',
    );
    echo "Approved voucher → reversal blocked ✓\n";

    $postedId = $makeVoucher('POSTED');

    $ledger->approve(
        voucherId: $postedId,
        userId: $userId,
        approvedAt: date('Y-m-d H:i:s'),
    );

    $ledger->post(
        voucherId: $postedId,
        userId: $userId,
        postedAt: date('Y-m-d H:i:s'),
    );

    $originalLines = $repository->lines($postedId);

    $reversalReference = 'QA-JV-REV-POSTED-CHILD-' . date('YmdHis') . '-' . random_int(1000, 9999);
    $references[] = $reversalReference;

    $reversalId = $ledger->createReversalPending(
        originalVoucherId: $postedId,
        referenceNumber: $reversalReference,
        transactionDate: date('Y-m-d'),
        particulars: 'QA reversal of posted voucher',
        createdBy: $userId,
        sourceType: 'QA',
        sourceId: $postedId,
    );

    $reversal = $repository->find($reversalId);

    assertSameValue(
        'Pending',
        $reversal['status'] ?? null,
        'Reversal voucher should start Pending.',
    );

    assertSameValue(
        $postedId,
        (int) ($reversal['reversal_of_voucher_id'] ?? 0),
        'Reversal must link to the original posted voucher.',
    );

    $reversalLines = $repository->lines($reversalId);

    assertSameValue(
        count($originalLines),
        count($reversalLines),
        'Reversal must contain the same number of lines as the original.',
    );

    $originalDebit = 0.00;
    $originalCredit = 0.00;
    $reversalDebit = 0.00;
    $reversalCredit = 0.00;

    foreach ($originalLines as $line) {
        $originalDebit += (float) $line['debit'];
        $originalCredit += (float) $line['credit'];
    }

    foreach ($reversalLines as $line) {
        $reversalDebit += (float) $line['debit'];
        $reversalCredit += (float) $line['credit'];
    }

    assertNear($originalDebit, $reversalCredit, 'Reversal credit must equal original debit.');
    assertNear($originalCredit, $reversalDebit, 'Reversal debit must equal original credit.');
    assertNear($reversalDebit, $reversalCredit, 'Reversal voucher must remain balanced.');

    echo "Posted voucher → reversal created ✓\n";
    echo "Reversal lines → exact inverse ✓\n";

    echo PHP_EOL;
    echo "============================================================" . PHP_EOL;
    echo "ACES JOURNAL VOUCHER REVERSAL BOUNDARY INTEGRATION TEST: PASS" . PHP_EOL;
    echo "============================================================" . PHP_EOL;
} finally {
    if ($references !== []) {
        $placeholders = implode(',', array_fill(0, count($references), '?'));

        $deleteLines = $pdo->prepare(
            "DELETE jl
             FROM journal_lines AS jl
             INNER JOIN journal_vouchers AS jv
                ON jv.id = jl.journal_voucher_id
             WHERE jv.reference_number IN ({$placeholders})"
        );
        $deleteLines->execute($references);

        // journal_vouchers has a self-referencing reversal_of_voucher_id
        // foreign key, and reversal journal lines reference the reversal
        // voucher. Delete reversal lines, then reversal children, then
        // their original vouchers.
        $deleteReversalLines = $pdo->prepare(
            "DELETE FROM journal_lines
             WHERE journal_voucher_id IN (
                 SELECT id
                 FROM (
                     SELECT id
                     FROM journal_vouchers
                     WHERE reversal_of_voucher_id IN (
                         SELECT id
                         FROM (
                             SELECT id
                             FROM journal_vouchers
                             WHERE reference_number IN ({$placeholders})
                         ) AS originals
                     )
                 ) AS reversal_vouchers
             )"
        );
        $deleteReversalLines->execute($references);

        $deleteChildren = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE reversal_of_voucher_id IN (
                 SELECT id
                 FROM (
                     SELECT id
                     FROM journal_vouchers
                     WHERE reference_number IN ({$placeholders})
                 ) AS originals
             )"
        );
        $deleteChildren->execute($references);

        $deleteVouchers = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE reference_number IN ({$placeholders})"
        );
        $deleteVouchers->execute($references);
    }
}
