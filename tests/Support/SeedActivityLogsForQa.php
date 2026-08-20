<?php

declare(strict_types=1);

/**
 * QA-only helper.
 *
 * Adds synthetic activity rows against existing members so the
 * Activity Logs pagination/filter UI can be tested without changing
 * member, loan, or other business data.
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Foundation\Database;

$database = $app
    ->container()
    ->get(Database::class);

$pdo = $database->connection();

$adminUserId = (int) ($pdo->query(
    'SELECT id FROM users ORDER BY id ASC LIMIT 1'
)->fetchColumn() ?: 0);

if ($adminUserId <= 0) {
    throw new RuntimeException(
        'No user exists. The QA activity seed requires at least one user.'
    );
}

$memberIds = $pdo->query(
    'SELECT id FROM members ORDER BY id ASC LIMIT 100'
)->fetchAll(PDO::FETCH_COLUMN);

if ($memberIds === []) {
    throw new RuntimeException(
        'No members exist. Seed members before running this QA helper.'
    );
}

$actions = [
    'MEMBER_UPDATED',
    'MEMBER_STATUS_CHANGED',
    'MEMBER_BENEFICIARY_ADDED',
    'MEMBER_BENEFICIARY_UPDATED',
    'MEMBER_BENEFICIARY_REMOVED',
];

$statement = $pdo->prepare(
    '
    INSERT INTO activity_logs (
        user_id,
        action,
        description,
        subject_type,
        subject_id,
        ip_address,
        created_at
    )
    VALUES (
        :user_id,
        :action,
        :description,
        :subject_type,
        :subject_id,
        :ip_address,
        :created_at
    )
    '
);

$pdo->beginTransaction();

try {
    /*
     * Generate 75 rows so the default 25-row page size gives
     * exactly three full QA pages, in addition to any existing
     * activity logs already present.
     */
    for ($i = 0; $i < 75; $i++) {
        $memberId = (int) $memberIds[$i % count($memberIds)];
        $action = $actions[$i % count($actions)];

        $statement->execute([
            'user_id' => $adminUserId,
            'action' => $action,
            'description' => sprintf(
                'QA pagination seed: %s for Member #%d.',
                $action,
                $memberId,
            ),
            'subject_type' => 'Member',
            'subject_id' => $memberId,
            'ip_address' => '127.0.0.1',
            'created_at' => date(
                'Y-m-d H:i:s',
                time() - ($i * 10),
            ),
        ]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "ACES ACTIVITY LOG QA SEED: COMPLETE" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Rows inserted: 75" . PHP_EOL;
echo "Existing business data changed: 0" . PHP_EOL;
echo "Target table: activity_logs" . PHP_EOL;
echo "==============================================" . PHP_EOL;
