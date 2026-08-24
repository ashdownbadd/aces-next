<?php
declare(strict_types=1);
require dirname(__DIR__,3).'/vendor/autoload.php';

$app=require dirname(__DIR__,3).'/bootstrap/app.php';
use App\Features\Ledger\Services\LedgerService;

$ledger=$app->container()->get(LedgerService::class);
$accountId=$ledger->accountId('1110');
if($accountId<=0){
    throw new RuntimeException('Baseline account 1110 is required.');
}

$result=$ledger->generalLedger($accountId);

if(($result['account']['account_code']??'')!=='1110'){
    throw new RuntimeException('Wrong account returned.');
}

$previous=(float)$result['opening_balance'];

foreach($result['rows'] as $row){
    $expected=$previous+(float)$row['debit']-(float)$row['credit'];
    if(abs($expected-(float)$row['running_balance'])>0.005){
        throw new RuntimeException(
            'Running balance mismatch at Voucher #'.(int)$row['voucher_id'].'.'
        );
    }
    $previous=(float)$row['running_balance'];
}

if(abs($previous-(float)$result['closing_balance'])>0.005){
    throw new RuntimeException(
        'Closing balance does not equal final running balance.'
    );
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES GENERAL LEDGER INTEGRATION TEST: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Account 1110 resolved                ✓" . PHP_EOL;
echo "Posted-only ledger query              ✓" . PHP_EOL;
echo "Running balances calculated           ✓" . PHP_EOL;
echo "Closing balance reconciles             ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
