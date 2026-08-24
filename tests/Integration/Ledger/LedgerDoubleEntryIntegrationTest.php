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

function assertThrows(
    callable $callback,
    string $messageContains,
): void {
    try {
        $callback();
    } catch (Throwable $exception) {
        if (
            $messageContains !== ''
            && !str_contains($exception->getMessage(), $messageContains)
        ) {
            throw new RuntimeException(
                'Unexpected exception message: '
                . $exception->getMessage()
            );
        }

        return;
    }

    throw new RuntimeException(
        "Expected exception containing '{$messageContains}'."
    );
}

$database=$app->container()->get(Database::class);
$ledger=$app->container()->get(LedgerService::class);
$repository=$app->container()->get(JournalVoucherRepository::class);
$pdo=$database->connection();

$userId=(int)$pdo->query(
    'SELECT id FROM users ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException('An existing user is required.');
}

$accountIds=$pdo->query(
    'SELECT account_code,id FROM accounts ORDER BY account_code'
)->fetchAll(PDO::FETCH_KEY_PAIR);

$cash=(int)($accountIds['1010'] ?? 0);
$receivable=(int)($accountIds['1110'] ?? 0);
$income=(int)($accountIds['4010'] ?? 0);

if ($cash<=0 || $receivable<=0 || $income<=0) {
    throw new RuntimeException('Required baseline COA accounts are missing.');
}

$refs=[];

try {
    // 1. Balanced 2-line voucher is accepted.
    $ref='QA-JV-BAL-' . date('YmdHis') . '-' . random_int(1000,9999);
    $refs[]=$ref;

    $voucherId=$ledger->createPending(
        voucher:[
            'reference_number'=>$ref,
            'transaction_date'=>date('Y-m-d'),
            'particulars'=>'QA balanced voucher',
            'source_type'=>'QA',
            'source_id'=>null,
        ],
        lines:[
            [
                'account_id'=>$cash,
                'debit'=>2120.00,
                'credit'=>0.00,
            ],
            [
                'account_id'=>$receivable,
                'debit'=>0.00,
                'credit'=>2120.00,
            ],
        ],
        createdBy:$userId,
    );

    $voucher=$repository->find($voucherId);

    assertTrueValue(
        ($voucher['status'] ?? null)==='Pending',
        'Balanced voucher should start Pending.'
    );

    // 2. Three-line voucher also balances.
    $ref2='QA-JV-3L-' . date('YmdHis') . '-' . random_int(1000,9999);
    $refs[]=$ref2;

    $voucherId2=$ledger->createPending(
        voucher:[
            'reference_number'=>$ref2,
            'transaction_date'=>date('Y-m-d'),
            'particulars'=>'QA loan payment voucher',
            'source_type'=>'QA',
            'source_id'=>null,
        ],
        lines:[
            [
                'account_id'=>$cash,
                'debit'=>2120.00,
            ],
            [
                'account_id'=>$receivable,
                'credit'=>2000.00,
            ],
            [
                'account_id'=>$income,
                'credit'=>120.00,
            ],
        ],
        createdBy:$userId,
    );

    assertTrueValue(
        count($repository->lines($voucherId2))===3,
        'Three journal lines should persist.'
    );

    // 3. Unbalanced voucher blocked.
    assertThrows(
        fn() => $ledger->createPending(
            voucher:[
                'reference_number'=>'QA-JV-BAD-' . date('YmdHis') . '-' . random_int(1000,9999),
                'transaction_date'=>date('Y-m-d'),
                'particulars'=>'QA unbalanced voucher',
            ],
            lines:[
                [
                    'account_id'=>$cash,
                    'debit'=>2120.00,
                    'credit'=>0.00,
                ],
                [
                    'account_id'=>$receivable,
                    'debit'=>0.00,
                    'credit'=>2000.00,
                ],
            ],
            createdBy:$userId,
        ),
        'not balanced',
    );

    // 4. Both-side line blocked.
    assertThrows(
        fn() => $ledger->createPending(
            voucher:[
                'reference_number'=>'QA-JV-BOTH-' . date('YmdHis') . '-' . random_int(1000,9999),
                'transaction_date'=>date('Y-m-d'),
                'particulars'=>'QA both-side line',
            ],
            lines:[
                [
                    'account_id'=>$cash,
                    'debit'=>100.00,
                    'credit'=>100.00,
                ],
                [
                    'account_id'=>$receivable,
                    'debit'=>0.00,
                    'credit'=>0.00,
                ],
            ],
            createdBy:$userId,
        ),
        'either a debit or a credit',
    );

    // 5. Single-line voucher blocked.
    assertThrows(
        fn() => $ledger->createPending(
            voucher:[
                'reference_number'=>'QA-JV-ONE-' . date('YmdHis') . '-' . random_int(1000,9999),
                'transaction_date'=>date('Y-m-d'),
                'particulars'=>'QA single line',
            ],
            lines:[
                [
                    'account_id'=>$cash,
                    'debit'=>100.00,
                    'credit'=>0.00,
                ],
            ],
            createdBy:$userId,
        ),
        'at least two lines',
    );

    // 6. Approved voucher remains approved and cannot be re-approved.
    $ledger->approve(
        voucherId:$voucherId,
        userId:$userId,
        approvedAt:date('Y-m-d H:i:s'),
    );

    $approved=$repository->find($voucherId);
    assertTrueValue(
        ($approved['status'] ?? null)==='Approved',
        'Approved voucher should have Approved status.'
    );

    assertThrows(
        fn()=> $ledger->approve(
            voucherId:$voucherId,
            userId:$userId,
            approvedAt:date('Y-m-d H:i:s'),
        ),
        'Only Pending',
    );

    // 7. Rejected voucher cannot be approved.
    $ref3='QA-JV-REJ-' . date('YmdHis') . '-' . random_int(1000,9999);
    $refs[]=$ref3;

    $rejectedId=$ledger->createPending(
        voucher:[
            'reference_number'=>$ref3,
            'transaction_date'=>date('Y-m-d'),
            'particulars'=>'QA rejected voucher',
        ],
        lines:[
            [
                'account_id'=>$cash,
                'debit'=>50.00,
                'credit'=>0.00,
            ],
            [
                'account_id'=>$receivable,
                'debit'=>0.00,
                'credit'=>50.00,
            ],
        ],
        createdBy:$userId,
    );

    $ledger->reject($rejectedId,'QA rejection');

    $rejected=$repository->find($rejectedId);
    assertTrueValue(
        ($rejected['status'] ?? null)==='Rejected',
        'Rejected voucher should have Rejected status.'
    );

    assertThrows(
        fn()=> $ledger->approve(
            voucherId:$rejectedId,
            userId:$userId,
            approvedAt:date('Y-m-d H:i:s'),
        ),
        'Only Pending',
    );

    echo PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "ACES LEDGER DOUBLE-ENTRY INTEGRATION TEST: PASS" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
    echo "Balanced voucher accepted             ✓" . PHP_EOL;
    echo "Three-line voucher persisted           ✓" . PHP_EOL;
    echo "Unbalanced voucher blocked             ✓" . PHP_EOL;
    echo "Debit + credit same line blocked       ✓" . PHP_EOL;
    echo "Single-line voucher blocked            ✓" . PHP_EOL;
    echo "Approval lifecycle enforced            ✓" . PHP_EOL;
    echo "Rejected voucher cannot be approved    ✓" . PHP_EOL;
    echo "==============================================" . PHP_EOL;
} finally {
    if ($refs !== []) {
        $placeholders=implode(',',array_fill(0,count($refs),'?'));

        $deleteLines=$pdo->prepare(
            "DELETE jl
             FROM journal_lines jl
             INNER JOIN journal_vouchers jv
                ON jv.id = jl.journal_voucher_id
             WHERE jv.reference_number IN ({$placeholders})"
        );
        $deleteLines->execute($refs);

        $deleteVouchers=$pdo->prepare(
            "DELETE FROM journal_vouchers
             WHERE reference_number IN ({$placeholders})"
        );
        $deleteVouchers->execute($refs);
    }
}
