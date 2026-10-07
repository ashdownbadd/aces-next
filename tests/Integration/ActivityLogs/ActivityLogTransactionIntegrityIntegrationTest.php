<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Foundation\Config;
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

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$config = new Config();
$config->load(dirname(__DIR__, 3) . '/config');

$database = new Database($config);
$pdo = $database->connection();

$activityRepository = new ActivityLogRepository($database);
$activityService = new ActivityLogService($activityRepository);

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException('An active user is required.');
}

$createdIds = [];

try {
    echo "===============================================================\n";
    echo "ACES ACTIVITY LOG TRANSACTION INTEGRITY INTEGRATION TEST\n";
    echo "===============================================================\n";

    /*
     * Successful operation boundary:
     * an audit event recorded inside an active DB transaction must
     * remain after the transaction commits.
     */
    $pdo->beginTransaction();

    $activityService->record(
        userId: $userId,
        action: 'QA_ACTIVITY_TX_COMMIT',
        description: 'Committed activity transaction fixture.',
        subjectType: 'QA',
        subjectId: 1001,
        ipAddress: '127.0.0.1',
    );

    $pdo->commit();

    $committed = $activityRepository->all(
        action: 'QA_ACTIVITY_TX_COMMIT',
        userId: $userId,
        limit: 10,
        offset: 0,
    );

    assertTrue(
        count($committed) >= 1,
        'Committed activity event must persist.',
    );

    $committedId = (int) $committed[0]['id'];
    $createdIds[] = $committedId;

    echo "Successful transaction → audit retained     ✓\n";

    /*
     * Failed operation boundary:
     * the audit event must roll back with the transaction rather than
     * surviving as an orphan audit record.
     */
    $beforeRollback = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_ROLLBACK',
        userId: $userId,
    );

    $pdo->beginTransaction();

    $activityService->record(
        userId: $userId,
        action: 'QA_ACTIVITY_TX_ROLLBACK',
        description: 'This audit event must roll back.',
        subjectType: 'QA',
        subjectId: 1002,
        ipAddress: '127.0.0.1',
    );

    $insideTransaction = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_ROLLBACK',
        userId: $userId,
    );

    assertSameValue(
        $beforeRollback + 1,
        $insideTransaction,
        'Audit event must exist while the transaction is active.',
    );

    $pdo->rollBack();

    $afterRollback = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_ROLLBACK',
        userId: $userId,
    );

    assertSameValue(
        $beforeRollback,
        $afterRollback,
        'Rolled-back operation must not leave an orphan audit event.',
    );

    echo "Failed transaction → audit rolled back      ✓\n";

    /*
     * Multiple audit events must share the same transaction boundary.
     * If the operation fails, none of its audit events may survive.
     */
    $beforeBatch = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_BATCH',
        userId: $userId,
    );

    $pdo->beginTransaction();

    $activityService->record(
        userId: $userId,
        action: 'QA_ACTIVITY_TX_BATCH',
        description: 'First event in failed operation.',
        subjectType: 'QA',
        subjectId: 1003,
        ipAddress: '127.0.0.1',
    );

    $activityService->record(
        userId: $userId,
        action: 'QA_ACTIVITY_TX_BATCH',
        description: 'Second event in failed operation.',
        subjectType: 'QA',
        subjectId: 1004,
        ipAddress: '127.0.0.1',
    );

    $duringBatch = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_BATCH',
        userId: $userId,
    );

    assertSameValue(
        $beforeBatch + 2,
        $duringBatch,
        'Both audit events must exist before rollback.',
    );

    $pdo->rollBack();

    $afterBatch = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_BATCH',
        userId: $userId,
    );

    assertSameValue(
        $beforeBatch,
        $afterBatch,
        'Rollback must remove every audit event from the failed operation.',
    );

    echo "Failed multi-event transaction → all rolled back ✓\n";

    /*
     * Existing audit history must remain untouched when a later
     * transaction fails.
     */
    $historyBefore = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_COMMIT',
        userId: $userId,
    );

    $pdo->beginTransaction();

    $activityService->record(
        userId: $userId,
        action: 'QA_ACTIVITY_TX_OTHER',
        description: 'Unrelated event in failed transaction.',
        subjectType: 'QA',
        subjectId: 1005,
        ipAddress: '127.0.0.1',
    );

    $pdo->rollBack();

    $historyAfter = $activityRepository->count(
        action: 'QA_ACTIVITY_TX_COMMIT',
        userId: $userId,
    );

    assertSameValue(
        $historyBefore,
        $historyAfter,
        'Existing committed audit history must survive unrelated rollback.',
    );

    echo "Existing audit history remains intact        ✓\n";

    echo "===============================================================\n";
    echo "ACES ACTIVITY LOG TRANSACTION INTEGRITY INTEGRATION TEST: PASS\n";
    echo "===============================================================\n";
} finally {
    if ($createdIds !== []) {
        $placeholders = implode(',', array_fill(0, count($createdIds), '?'));
        $statement = $pdo->prepare(
            "DELETE FROM activity_logs WHERE id IN ({$placeholders})"
        );
        $statement->execute($createdIds);
    }
}
