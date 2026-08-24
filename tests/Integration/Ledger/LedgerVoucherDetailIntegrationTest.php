<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Foundation\Database;

$repository = $app->container()->get(JournalVoucherRepository::class);

$database = $app->container()->get(Database::class);
$pdo = $database->connection();

$voucherId = (int) $pdo->query(
    'SELECT id FROM journal_vouchers ORDER BY id DESC LIMIT 1'
)->fetchColumn();

if ($voucherId <= 0) {
    throw new RuntimeException(
        'No Journal Voucher exists for detail metadata testing.'
    );
}

$voucher = $repository->find($voucherId);

if ($voucher === null) {
    throw new RuntimeException('Voucher detail could not be loaded.');
}

foreach (
    [
        'created_by_username',
        'approved_by_username',
        'posted_by_username',
        'created_at',
        'approved_at',
        'posted_at',
        'reversal_of_voucher_id',
    ] as $field
) {
    if (!array_key_exists($field, $voucher)) {
        throw new RuntimeException(
            "Voucher detail missing field: {$field}"
        );
    }
}

if (
    (int) ($voucher['created_by'] ?? 0) > 0
    && trim((string) ($voucher['created_by_username'] ?? '')) === ''
) {
    throw new RuntimeException(
        'Created By should resolve to the user username.'
    );
}

echo PHP_EOL;
echo "================================================" . PHP_EOL;
echo "ACES LEDGER VOUCHER DETAIL TEST: PASS" . PHP_EOL;
echo "================================================" . PHP_EOL;
echo "Created By metadata resolved           ✓" . PHP_EOL;
echo "Approval metadata fields present       ✓" . PHP_EOL;
echo "Posting metadata fields present        ✓" . PHP_EOL;
echo "Reversal link field present             ✓" . PHP_EOL;
echo "================================================" . PHP_EOL;
