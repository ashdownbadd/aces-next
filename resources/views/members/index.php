<?php

declare(strict_types=1);

$title = 'Members';

$members = $members ?? [];
$totalMembers = $totalMembers ?? count($members);
$successMessage = $successMessage ?? null;

?>

<div class="members">

    <div class="members__header">

        <div>

            <h1 class="members__title">
                Members
            </h1>

            <p class="members__description">
                Manage cooperative members.
            </p>

        </div>

        <a
            href="/members/create"
            class="btn btn--primary">

            Register Member

        </a>

    </div>

    <?php if ($successMessage !== null): ?>

        <div class="alert alert--success">

            <strong>Success</strong>

            <span>
                <?= htmlspecialchars($successMessage) ?>
            </span>

        </div>

    <?php endif; ?>

    <div class="card">

        <div class="members__toolbar">

            <input
                class="input members__search"
                type="search"
                placeholder="Search member..."
                id="member-search">

            <span class="members__total">
                Total: <?= number_format($totalMembers) ?>
            </span>

        </div>

        <table class="table">

            <thead>

                <tr>

                    <th>Member #</th>

                    <th>Name</th>

                    <th>Mobile</th>

                    <th>Status</th>

                    <th>Joined</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody id="members-table-body">

                <?php if ($members === []): ?>

                    <tr>

                        <td colspan="6">

                            <div class="members__empty">

                                <div class="members__empty-icon">
                                    👥
                                </div>

                                <h3>
                                    No members have been registered yet.
                                </h3>

                                <p>
                                    Register your first cooperative member to get started.
                                </p>

                                <a
                                    href="/members/create"
                                    class="btn btn--primary">

                                    Register Member

                                </a>

                            </div>

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($members as $member): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($member['member_number'] ?? '—')
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($member['full_name'] ?? '—')
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($member['mobile_number'] ?? '—')
                                ) ?>
                            </td>

                            <td>

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        (string) ($member['status'] ?? '—')
                                    ) ?>

                                </span>

                            </td>

                            <td>
                                <?php
                                $membershipDate =
                                    $member['membership_date'] ?? null;
                                ?>

                                <?= $membershipDate
                                    ? htmlspecialchars(
                                        date(
                                            'F j, Y',
                                            strtotime(
                                                (string) $membershipDate
                                            )
                                        )
                                    )
                                    : '—'
                                ?>
                            </td>

                            <td>

                                <a
                                    href="/members/<?= urlencode(
                                                        (string) $member['id']
                                                    ) ?>"
                                    class="btn btn--sm">

                                    View

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

        <div class="members__footer">

            Showing
            <?= number_format(count($members)) ?>
            of
            <?= number_format($totalMembers) ?>
            members

        </div>

    </div>

</div>