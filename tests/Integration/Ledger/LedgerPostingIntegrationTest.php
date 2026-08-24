<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Database;

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf(
                '%s Expected %s, got %s.',
                $message,
                var_export($expected, true),
                var_export($actual, true),
            )
        );
    }
}

function assertThrows(callable $callback, string $messageContains): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if (
            $messageContains !== ''
            && !str_contains($exception->getMessage(), $messageContains)
        ) {
            throw new RuntimeException(
                'Unexpected exception message: ' . $exception->getMessage()
            );
        }

        return;
    }

    throw new RuntimeException(
        "Expected exception containing '{$messageContains}'."
    );
}

$database = $app->container()->get(Database::class);
$ledger = $app->container()->get(LedgerService::class);
$repository = $app->container()->get(JournalVoucherRepository::class);
$pdo = $database->connection();

$userId = (int) $pdo->query(
    'SELECT id FROM users ORDER BY id ASC LIMIT 1'
)->fetchColumn();

$cash = $ledger->accountId('1010');
$receivable = $ledger->accountId('1110');

if ($userId <= 0 || $cash <= 0 || $receivable <= 0) {
    throw new RuntimeException(
        'Required user or Ledger accounts are missing.'
    );
}

$references = [];

try {
    $reference = 'QA-POST-' . date('YmdHis') . '-' . random_int(1000, 9999);
    $references[] = $reference;

    $voucherId = $ledger->createPending(
        voucher: [
            'reference_number' => $reference,
            'transaction_date' => date('Y-m-d'),
            'particulars' => 'QA posting lifecycle',
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

    $ledger->approve(
        voucherId: $voucherId,
        userId: $userId,
        approvedAt: date('Y-m-d H:i:s'),
    );

    assertSameValue(
        'Approved',
        $repository->find($voucherId)['status'],
        'Voucher should be Approved before posting.',
    );

    $ledger->post(
        voucherId: $voucherId,
        userId: $userId,
        postedAt: date('Y-m-d H:i:s'),
    );

    $posted = $repository->find($voucherId);

    assertSameValue(
        'Posted',
        $posted['status'],
        'Voucher should be Posted after posting.',
    );

    assertSameValue(
        $userId,
        (int) $posted['posted_by'],
        'Posting user should persist.',
    );

    assertTrueValue(
        $posted['posted_at'] !== null,
        'Posting timestamp should persist.',
    );

    assertThrows(
        fn() => $ledger->post(
            voucherId: $voucherId,
            userId: $userId,
            postedAt: date('Y-m-d H:i:s'),
        ),
        'Only Approved',
    );

    assertThrows(
        fn() => $ledger->approve(
            voucherId: $voucherId,
            userId: $userId,
            approvedAt: date('Y-m-d H:i:s'),
        ),
        'Only Pending',
    );

    assertThrows(
        fn() => $ledger->reject(
            voucherId: $voucherId,
            reason: 'QA second path',
        ),
        'Only Pending',
    );

    $rejectedRef = 'QA-POST-REJ-' . date('YmdHis') . '-' . random_int(1000, 9999);
    $references[] = $rejectedRef;

    $rejectedId = $ledger->createPending(
        voucher: [
            'reference_number' => $rejectedRef,
            'transaction_date' => date('Y-m-d'),
            'particulars' => 'QA rejected posting',
        ],
        lines: [
            [
                'account_id' => $cash,
                'debit' => 100.00,
                'credit' => 0.00,
            ],
            [
                'account_id' => $receivable,
                'debit' => 0.00,
                'credit' => 100.00,
            ],
        ],
        createdBy: $userId,
    );

    $ledger->reject(
        voucherId: $rejectedId,
        reason: 'QA rejected posting',
    );

    assertThrows(
        fn() => $ledger->post(
            voucherId: $rejectedId,
            userId: $userId,
            postedAt: date('Y-m-d H:i:s'),
        ),
        'Only Approved',
    );

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LEDGER POSTING INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Pending → Approved                    ✓" . PHP_EOL;
    echo "Approved → Posted                     ✓" . PHP_EOL;
    echo "Posting metadata persisted             ✓" . PHP_EOL;
    echo "Posted cannot be re-posted             ✓" . PHP_EOL;
    echo "Posted cannot be re-approved           ✓" . PHP_EOL;
    echo "Posted cannot be rejected              ✓" . PHP_EOL;
    echo "Rejected voucher cannot be posted      ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
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

        $deleteVouchers = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE reference_number IN ({$placeholders})"
        );
        $deleteVouchers->execute($references);
    }
}
