<?php

declare(strict_types=1);

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Foundation\Config;
use App\Foundation\Database;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

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

$userId = (int) $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
)->fetchColumn();

if ($userId <= 0) {
    throw new RuntimeException('An active user is required.');
}

$repository = new ActivityLogRepository($database);
$service = new ActivityLogService($repository);

$subjectId = 900000 + random_int(1, 99999);
$action = 'INTEGRITY_AUDIT_APPEND_' . bin2hex(random_bytes(4));
$secondAction = $action . '_SECOND';
$descriptionOne = 'First audit event for append-only integrity QA.';
$descriptionTwo = 'Second audit event for append-only integrity QA.';
$createdIds = [];

try {
    echo "===============================================================\n";
    echo "ACES ACTIVITY LOG INTEGRITY INTEGRATION TEST\n";
    echo "===============================================================\n";

    $beforeCount = $service->count(action: $action);

    $service->record(
        userId: $userId,
        action: $action,
        description: $descriptionOne,
        subjectType: 'IntegrityAudit',
        subjectId: $subjectId,
        ipAddress: '127.0.0.1',
    );

    $rows = $service->all(
        action: $action,
        limit: 10,
        offset: 0,
    );

    $qaRows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => (int) $row['subject_id'] === $subjectId,
    ));

    assertSameValue(
        $beforeCount + 1,
        $service->count(action: $action),
        'First audit event must create exactly one new record.',
    );
    assertSameValue(1, count($qaRows), 'First audit event must be retrievable.');

    $first = $qaRows[0];
    $createdIds[] = (int) $first['id'];

    assertSameValue($userId, (int) $first['user_id'], 'Audit actor must be preserved.');
    assertSameValue($action, (string) $first['action'], 'Machine-readable action must be preserved.');
    assertSameValue($descriptionOne, (string) $first['description'], 'Audit description must be preserved.');
    assertSameValue('IntegrityAudit', (string) $first['subject_type'], 'Audit subject type must be preserved.');
    assertSameValue($subjectId, (int) $first['subject_id'], 'Audit subject ID must be preserved.');
    assertSameValue('127.0.0.1', (string) $first['ip_address'], 'Audit IP address must be preserved.');
    assertTrue((string) $first['created_at'] !== '', 'Audit timestamp must be recorded.');
    echo "Audit actor/action/subject metadata preserved ✓\n";

    $service->record(
        userId: $userId,
        action: $secondAction,
        description: $descriptionTwo,
        subjectType: 'IntegrityAudit',
        subjectId: $subjectId,
        ipAddress: '127.0.0.1',
    );

    $combined = $service->all(
        search: 'append-only integrity QA',
        limit: 10,
        offset: 0,
    );

    $qaCombined = array_values(array_filter(
        $combined,
        static fn(array $row): bool => (int) $row['subject_id'] === $subjectId,
    ));

    assertSameValue(2, count($qaCombined), 'Repeated activity must append a second record.');

    $second = $qaCombined[0];
    $createdIds[] = (int) $second['id'];

    assertTrue(
        count(array_unique($createdIds)) === 2,
        'Repeated activity events must have distinct record IDs.',
    );
    assertSameValue($secondAction, (string) $second['action'], 'Second machine-readable action must be preserved.');
    assertSameValue($descriptionTwo, (string) $second['description'], 'Second audit description must be preserved.');
    echo "Repeated audit event appended without overwrite ✓\n";

    $actionRows = $service->all(
        action: $action,
        limit: 10,
        offset: 0,
    );

    $firstStillPresent = array_filter(
        $actionRows,
        static fn(array $row): bool => (int) $row['id'] === $createdIds[0],
    );

    assertSameValue(1, count($firstStillPresent), 'Original audit event must remain after a later event.');
    echo "Original audit history remains intact ✓\n";

    $userRows = $service->all(
        userId: $userId,
        search: 'append-only integrity QA',
        limit: 10,
        offset: 0,
    );

    $userQaRows = array_filter(
        $userRows,
        static fn(array $row): bool => (int) $row['subject_id'] === $subjectId,
    );

    assertSameValue(2, count($userQaRows), 'User filtering must retain both audit events.');
    echo "Actor filtering preserves complete audit history ✓\n";

    echo "===============================================================\n";
    echo "ACES ACTIVITY LOG INTEGRITY INTEGRATION TEST: PASS\n";
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
