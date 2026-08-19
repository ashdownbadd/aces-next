<?php

$actionLabels = [
    'MEMBER_CREATED' => 'Member Created',
    'MEMBER_UPDATED' => 'Member Updated',
    'MEMBER_STATUS_CHANGED' => 'Member Status Changed',
    'MEMBER_BENEFICIARY_ADDED' => 'Beneficiary Added',
    'MEMBER_BENEFICIARY_UPDATED' => 'Beneficiary Updated',
    'MEMBER_BENEFICIARY_REMOVED' => 'Beneficiary Removed',
];

$formatActionLabel = static function (string $action) use ($actionLabels): string {
    if (isset($actionLabels[$action])) {
        return $actionLabels[$action];
    }

    return ucwords(
        strtolower(
            str_replace('_', ' ', $action)
        )
    );
};

$subjectType = (string) ($log['subject_type'] ?? '');
$subjectId = (int) ($log['subject_id'] ?? 0);
$isMemberSubject =
    strcasecmp($subjectType, 'Member') === 0
    && $subjectId > 0;

$userName = trim((string) ($log['user_name'] ?? ''));
if ($userName === '') {
    $userName = trim((string) ($log['username'] ?? ''));
}
if ($userName === '') {
    $userName = 'System';
}

?>

<div class="activity-logs">

    <div class="activity-logs__detail">

        <div class="page-header">
            <div>
                <a href="/activity-logs">← Back to Activity Logs</a>
                <h1>Activity Log Details</h1>
                <p>Detailed information about this recorded activity.</p>
            </div>
        </div>

        <div class="card">
            <div class="page-section">
                <div class="activity-logs__detail-header">
                    <div>
                        <span class="activity-logs__detail-label">Action</span>
                        <h2>
                            <?= htmlspecialchars(
                                $formatActionLabel((string) ($log['action'] ?? ''))
                            ) ?>
                        </h2>
                    </div>

                    <span class="activity-logs__detail-id">
                        Log #<?= (int) ($log['id'] ?? 0) ?>
                    </span>
                </div>

                <dl class="activity-logs__detail-list">
                    <div>
                        <dt>Date / Time</dt>
                        <dd>
                            <?= htmlspecialchars(
                                date(
                                    'M d, Y h:i A',
                                    strtotime((string) ($log['created_at'] ?? ''))
                                )
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Performed By</dt>
                        <dd><?= htmlspecialchars($userName) ?></dd>
                    </div>

                    <div>
                        <dt>Description</dt>
                        <dd>
                            <?= htmlspecialchars(
                                (string) ($log['description'] ?? '—')
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Subject</dt>
                        <dd>
                            <?php if ($isMemberSubject): ?>
                                <a href="/members/<?= $subjectId ?>">
                                    Member #<?= $subjectId ?>
                                </a>
                            <?php elseif ($subjectType !== ''): ?>
                                <?= htmlspecialchars($subjectType) ?>
                                <?php if ($subjectId > 0): ?>
                                    #<?= $subjectId ?>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </dd>
                    </div>

                    <div>
                        <dt>IP Address</dt>
                        <dd>
                            <?= htmlspecialchars(
                                (string) ($log['ip_address'] ?? '—')
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Database ID</dt>
                        <dd><?= (int) ($log['id'] ?? 0) ?></dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>

</div>