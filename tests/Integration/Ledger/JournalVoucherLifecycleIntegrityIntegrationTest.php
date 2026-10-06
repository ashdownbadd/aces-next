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
        $reference = 'QA-JV-LIFE-' . $suffix . '-' . date('YmdHis') . '-' . random_int(1000, 9999);
        $references[] = $reference;

        return $ledger->createPending(
            voucher: [
                'reference_number' => $reference,
                'transaction_date' => date('Y-m-d'),
                'particulars' => 'QA journal voucher lifecycle integrity',
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
    $pending = $repository->find($pendingId);
    assertSameValue('Pending', $pending['status'] ?? null, 'New voucher should start Pending.');
    assertSameValue(null, $pending['approved_by'] ?? null, 'Pending voucher should have no approver.');
    assertSameValue(null, $pending['approved_at'] ?? null, 'Pending voucher should have no approval timestamp.');
    assertSameValue(null, $pending['posted_by'] ?? null, 'Pending voucher should have no poster.');
    assertSameValue(null, $pending['posted_at'] ?? null, 'Pending voucher should have no posting timestamp.');
    echo "Create → Pending ✓\n";

    $approvedAt = date('Y-m-d H:i:s');
    $ledger->approve($pendingId, $userId, $approvedAt);
    $approved = $repository->find($pendingId);
    assertSameValue('Approved', $approved['status'] ?? null, 'Pending voucher should become Approved.');
    assertSameValue($userId, (int) ($approved['approved_by'] ?? 0), 'Approval actor must be recorded.');
    assertSameValue($approvedAt, $approved['approved_at'] ?? null, 'Approval timestamp must be recorded.');
    echo "Pending → Approved ✓\n";

    $postedAt = date('Y-m-d H:i:s');
    $ledger->post($pendingId, $userId, $postedAt);
    $posted = $repository->find($pendingId);
    assertSameValue('Posted', $posted['status'] ?? null, 'Approved voucher should become Posted.');
    assertSameValue($userId, (int) ($posted['posted_by'] ?? 0), 'Posting actor must be recorded.');
    assertSameValue($postedAt, $posted['posted_at'] ?? null, 'Posting timestamp must be recorded.');
    echo "Approved → Posted ✓\n";

    $rejectedId = $makeVoucher('REJECTED');
    $reason = 'QA lifecycle rejection';
    $ledger->reject($rejectedId, $reason);
    $rejected = $repository->find($rejectedId);
    assertSameValue('Rejected', $rejected['status'] ?? null, 'Pending voucher should become Rejected.');
    assertSameValue($reason, $rejected['rejection_reason'] ?? null, 'Rejection reason must be recorded.');
    echo "Pending → Rejected ✓\n";

    $invalidPendingPost = $makeVoucher('INVALID-PENDING-POST');
    assertThrows(
        fn() => $ledger->post($invalidPendingPost, $userId, date('Y-m-d H:i:s')),
        'Only Approved',
        'Pending → Posted must be blocked.',
    );
    echo "Pending → Posted blocked ✓\n";

    assertThrows(
        fn() => $ledger->approve($rejectedId, $userId, date('Y-m-d H:i:s')),
        'Only Pending',
        'Rejected → Approved must be blocked.',
    );
    echo "Rejected → Approved blocked ✓\n";

    assertThrows(
        fn() => $ledger->post($rejectedId, $userId, date('Y-m-d H:i:s')),
        'Only Approved',
        'Rejected → Posted must be blocked.',
    );
    echo "Rejected → Posted blocked ✓\n";

    assertThrows(
        fn() => $ledger->reject($pendingId, 'Invalid post-rejection'),
        'Only Pending',
        'Posted → Rejected must be blocked.',
    );
    assertThrows(
        fn() => $ledger->approve($pendingId, $userId, date('Y-m-d H:i:s')),
        'Only Pending',
        'Posted → Approved must be blocked.',
    );
    assertThrows(
        fn() => $ledger->post($pendingId, $userId, date('Y-m-d H:i:s')),
        'Only Approved',
        'Posted → Posted must be blocked.',
    );
    $postedAfterInvalid = $repository->find($pendingId);
    assertSameValue('Posted', $postedAfterInvalid['status'] ?? null, 'Posted voucher status must remain Posted.');
    assertSameValue($userId, (int) ($postedAfterInvalid['approved_by'] ?? 0), 'Posted voucher approval metadata must remain unchanged.');
    assertSameValue($approvedAt, $postedAfterInvalid['approved_at'] ?? null, 'Posted voucher approval timestamp must remain unchanged.');
    assertSameValue($userId, (int) ($postedAfterInvalid['posted_by'] ?? 0), 'Posted voucher posting metadata must remain unchanged.');
    assertSameValue($postedAt, $postedAfterInvalid['posted_at'] ?? null, 'Posted voucher posting timestamp must remain unchanged.');
    echo "Posted terminal state protected ✓\n";

    assertThrows(
        fn() => $ledger->reject($rejectedId, 'Second rejection'),
        'Only Pending',
        'Rejected → Rejected must be blocked.',
    );
    assertSameValue($reason, $repository->find($rejectedId)['rejection_reason'] ?? null, 'Rejected voucher reason must remain unchanged.');
    echo "Rejected terminal state protected ✓\n";

    echo PHP_EOL;
    echo "============================================================" . PHP_EOL;
    echo "ACES JOURNAL VOUCHER LIFECYCLE INTEGRITY INTEGRATION TEST: PASS" . PHP_EOL;
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

        $deleteVouchers = $pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE reference_number IN ({$placeholders})"
        );
        $deleteVouchers->execute($references);
    }
}
