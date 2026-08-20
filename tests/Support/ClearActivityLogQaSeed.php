<?php

declare(strict_types=1);

/**
 * QA-only cleanup helper.
 *
 * Removes only the synthetic rows created by SeedActivityLogsForQa.php.
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Foundation\Database;

$database = $app
    ->container()
    ->get(Database::class);

$pdo = $database->connection();

$statement = $pdo->prepare(
    "
    DELETE FROM activity_logs
    WHERE ip_address = '127.0.0.1'
      AND description LIKE 'QA pagination seed:%'
    "
);

$statement->execute();

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES ACTIVITY LOG QA SEED: CLEANED" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Rows removed: " . $statement->rowCount() . PHP_EOL;
echo "==============================================" . PHP_EOL;
