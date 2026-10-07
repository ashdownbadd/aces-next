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

$repository = new ActivityLogRepository($database);
$service = new ActivityLogService($repository);

$userRows = $pdo->query(
    'SELECT id FROM users WHERE is_active = 1 ORDER BY id ASC LIMIT 2'
)->fetchAll(PDO::FETCH_COLUMN);

if (count($userRows) < 2) {
    throw new RuntimeException(
        'Two active users are required for activity log filter coverage.'
    );
}

$userA = (int) $userRows[0];
$userB = (int) $userRows[1];

$createdIds = [];

try {
    echo "===============================================================\n";
    echo "ACES ACTIVITY LOG SEARCH/PAGINATION INTEGRATION TEST\n";
    echo "===============================================================\n";

    $fixtures = [
        [
            'user_id' => $userA,
            'action' => 'QA_ACTIVITY_NOISE',
            'description' => 'Noise record one',
            'subject_type' => 'QA',
            'subject_id' => 1,
            'created_at' => '2026-10-01 09:00:00',
        ],
        [
            'user_id' => $userB,
            'action' => 'QA_ACTIVITY_NOISE',
            'description' => 'Noise record two',
            'subject_type' => 'QA',
            'subject_id' => 2,
            'created_at' => '2026-10-02 09:00:00',
        ],
        [
            'user_id' => $userA,
            'action' => 'QA_TARGET_ACTION',
            'description' => 'Unique target searchable record',
            'subject_type' => 'QA',
            'subject_id' => 3,
            'created_at' => '2026-10-03 12:00:00',
        ],
        [
            'user_id' => $userB,
            'action' => 'QA_TARGET_ACTION',
            'description' => 'Target record for combined filters',
            'subject_type' => 'QA',
            'subject_id' => 4,
            'created_at' => '2026-10-04 12:00:00',
        ],
        [
            'user_id' => $userA,
            'action' => 'QA_TARGET_ACTION',
            'description' => 'Target date boundary start',
            'subject_type' => 'QA',
            'subject_id' => 5,
            'created_at' => '2026-10-05 00:00:00',
        ],
        [
            'user_id' => $userB,
            'action' => 'QA_TARGET_ACTION',
            'description' => 'Target date boundary end',
            'subject_type' => 'QA',
            'subject_id' => 6,
            'created_at' => '2026-10-05 23:59:59',
        ],
    ];

    foreach ($fixtures as $fixture) {
        $repository->create(
            userId: $fixture['user_id'],
            action: $fixture['action'],
            description: $fixture['description'],
            subjectType: $fixture['subject_type'],
            subjectId: $fixture['subject_id'],
            ipAddress: '127.0.0.1',
        );

        $id = (int) $pdo->lastInsertId();
        $createdIds[] = $id;

        $statement = $pdo->prepare(
            'UPDATE activity_logs
             SET created_at = :created_at
             WHERE id = :id'
        );
        $statement->execute([
            'created_at' => $fixture['created_at'],
            'id' => $id,
        ]);
    }

    /*
     * Search must operate over the complete matching dataset before
     * pagination. The unique target is intentionally older than the
     * first page of unfiltered records.
     */
    $searchTotal = $service->count(
        search: 'Unique target searchable record',
    );

    assertSameValue(
        1,
        $searchTotal,
        'Search count must find the unique record across the full dataset.',
    );

    $searchRows = $service->all(
        search: 'Unique target searchable record',
        limit: 2,
        offset: 0,
    );

    assertSameValue(
        1,
        count($searchRows),
        'Search must return the matching record even with pagination enabled.',
    );

    assertSameValue(
        $createdIds[2],
        (int) $searchRows[0]['id'],
        'Search must return the correct target record.',
    );

    echo "Search finds records outside first page       ✓\n";

    $actionTotal = $service->count(
        action: 'QA_TARGET_ACTION',
    );

    assertSameValue(
        4,
        $actionTotal,
        'Action filter count must include all matching records.',
    );

    $actionPageOne = $service->all(
        action: 'QA_TARGET_ACTION',
        limit: 2,
        offset: 0,
    );

    $actionPageTwo = $service->all(
        action: 'QA_TARGET_ACTION',
        limit: 2,
        offset: 2,
    );

    assertSameValue(
        2,
        count($actionPageOne),
        'Action filter page one must contain two records.',
    );

    assertSameValue(
        2,
        count($actionPageTwo),
        'Action filter page two must contain the remaining two records.',
    );

    $actionIds = array_merge(
        array_map(
            static fn(array $row): int => (int) $row['id'],
            $actionPageOne,
        ),
        array_map(
            static fn(array $row): int => (int) $row['id'],
            $actionPageTwo,
        ),
    );

    assertSameValue(
        $createdIds[5],
        $actionIds[0],
        'Newest filtered record must appear first.',
    );

    assertSameValue(
        $createdIds[2],
        $actionIds[3],
        'Oldest filtered record must remain reachable on later pages.',
    );

    assertSameValue(
        4,
        count(array_unique($actionIds)),
        'Filtered pagination must not duplicate records across pages.',
    );

    echo "Action filter paginates complete result set     ✓\n";

    $userTotal = $service->count(
        userId: $userA,
    );

    assertTrue(
        $userTotal >= 3,
        'User filter must find the complete history for the selected actor.',
    );

    $userRowsPage = $service->all(
        userId: $userA,
        limit: 2,
        offset: 0,
    );

    assertSameValue(
        2,
        count($userRowsPage),
        'User-filtered pagination must honor the requested page size.',
    );

    foreach ($userRowsPage as $row) {
        assertSameValue(
            $userA,
            (int) $row['user_id'],
            'User filter must exclude records belonging to other users.',
        );
    }

    echo "User filter preserves complete actor history    ✓\n";

    $dateTotal = $service->count(
        action: 'QA_TARGET_ACTION',
        dateFrom: '2026-10-05',
        dateTo: '2026-10-05',
    );

    assertSameValue(
        2,
        $dateTotal,
        'Same-day date filter must include both boundary timestamps.',
    );

    $dateRows = $service->all(
        action: 'QA_TARGET_ACTION',
        dateFrom: '2026-10-05',
        dateTo: '2026-10-05',
        limit: 25,
        offset: 0,
    );

    assertSameValue(
        2,
        count($dateRows),
        'Same-day date filter must return both boundary records.',
    );

    assertSameValue(
        $createdIds[5],
        (int) $dateRows[0]['id'],
        'Date filter must include the end-of-day boundary record.',
    );

    assertSameValue(
        $createdIds[4],
        (int) $dateRows[1]['id'],
        'Date filter must include the start-of-day boundary record.',
    );

    echo "Date filter includes complete day boundaries     ✓\n";

    $combinedTotal = $service->count(
        search: 'Target',
        action: 'QA_TARGET_ACTION',
        userId: $userB,
        dateFrom: '2026-10-04',
        dateTo: '2026-10-05',
    );

    assertSameValue(
        2,
        $combinedTotal,
        'Combined filters must intersect correctly.',
    );

    $combinedRows = $service->all(
        search: 'Target',
        action: 'QA_TARGET_ACTION',
        userId: $userB,
        dateFrom: '2026-10-04',
        dateTo: '2026-10-05',
        limit: 25,
        offset: 0,
    );

    assertSameValue(
        2,
        count($combinedRows),
        'Combined filters must return only intersecting records.',
    );

    foreach ($combinedRows as $row) {
        assertSameValue(
            $userB,
            (int) $row['user_id'],
            'Combined filter must preserve user constraint.',
        );

        assertSameValue(
            'QA_TARGET_ACTION',
            (string) $row['action'],
            'Combined filter must preserve action constraint.',
        );
    }

    echo "Combined search/filter intersection             ✓\n";

    for ($index = 1, $count = count($actionIds); $index < $count; $index++) {
        $previous = $actionPageOne[$index - 1] ?? $actionPageTwo[$index - 3];
        $current = $actionPageOne[$index] ?? $actionPageTwo[$index - 2];

        $previousTime = (string) $previous['created_at'];
        $currentTime = (string) $current['created_at'];

        assertTrue(
            $previousTime >= $currentTime,
            'Filtered records must remain ordered newest first.',
        );
    }

    echo "Filtered ordering remains newest-first          ✓\n";

    echo "===============================================================\n";
    echo "ACES ACTIVITY LOG SEARCH/PAGINATION INTEGRATION TEST: PASS\n";
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
