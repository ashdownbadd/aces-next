<?php

require __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Features\Ledger\Repositories\JournalVoucherRepository;

$db = Database::getInstance();

$repository = new JournalVoucherRepository($db);

$result = $repository->trialBalance('2026-10-06');

var_export($result);
