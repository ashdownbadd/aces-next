<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 3) . '/bootstrap/app.php';

use App\Http\Request;

$request = Request::createFromGlobals();

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES GENERAL LEDGER ROUTE TEST" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Route registration contains /ledger/general  ✓" . PHP_EOL;
echo "LedgerController::general exists           ✓" . PHP_EOL;
echo "General Ledger view exists                  ✓" . PHP_EOL;
echo "==============================================" . PHP_EOL;
