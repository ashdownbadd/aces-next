<?php

declare(strict_types=1);

$title = 'Members';

$members =
    $members ?? [];

$totalMembers =
    $totalMembers ?? 0;

$resultCount =
    $resultCount ?? 0;

$search =
    $search ?? '';

$status =
    $status ?? '';

$currentPage =
    $currentPage ?? 1;

$perPage =
    $perPage ?? 25;

$totalPages =
    $totalPages ?? 1;

$from =
    $from ?? 0;

$to =
    $to ?? 0;

$successMessage =
    $successMessage ?? null;

/**
 * Build a pagination URL while preserving
 * the current search and status filters.
 */
$paginationUrl = static function (
    int $page,
) use (
    $search,
    $status
): string {
    $query = [
        'page' => $page,
    ];

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($status !== '') {
        $query['status'] = $status;
    }

    return '/members?' .
        http_build_query($query);
};

?>

<div class="members">

    <div class="members__header">

        <div>
            <h1 class="members__title">Members</h1>
            <p class="members__description">
                Manage cooperative members.
            </p>
        </div>

        <a
            href="/members/create?new=1"
            class="btn btn--primary members__register">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>

            <span>Register Member</span>

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

    <section class="members__directory">

        <div class="members__directory-top">

            <form
                method="GET"
                action="/members"
                class="members__search-form">

                <label
                    class="members__filter-label"
                    for="members-search">
                    Search
                </label>

                <div class="members__search-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" />
                        <path d="m16 16 4 4" />
                    </svg>

                    <input
                        id="members-search"
                        class="input members__search"
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search members..."
                        autocomplete="off">
                </div>

                <label
                    class="members__filter-label"
                    for="members-status">
                    Status
                </label>

                <div class="members__select-control">
                    <select
                        id="members-status"
                        class="input members__status-filter"
                        name="status"
                        aria-label="Filter members by status"
                        onchange="this.form.submit()">

                        <option
                            value=""
                            <?= $status === '' ? 'selected' : '' ?>>
                            All Statuses
                        </option>

                        <option
                            value="Pending"
                            <?= $status === 'Pending' ? 'selected' : '' ?>>
                            Pending
                        </option>

                        <option
                            value="Active"
                            <?= $status === 'Active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?= $status === 'Inactive' ? 'selected' : '' ?>>
                            Inactive
                        </option>

                    </select>

                </div>

                <noscript>
                    <button
                        type="submit"
                        class="btn btn--secondary">
                        Filter
                    </button>
                </noscript>

            </form>

            <div class="members__directory-meta">

                <span class="members__total">
                    <?= number_format($totalMembers) ?> members
                </span>

                <?php if ($search !== '' || $status !== ''): ?>
                    <a
                        href="/members"
                        class="members__clear-filter">
                        Clear filters
                    </a>
                <?php endif; ?>

            </div>

        </div>

        <div class="members__table-scroll">

            <table class="members__table">

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

                                    <div
                                        class="members__empty-icon"
                                        aria-hidden="true">

                                        <svg viewBox="0 0 24 24">
                                            <path d="M16 19v-1.2A3.8 3.8 0 0 0 12.2 14H7.8A3.8 3.8 0 0 0 4 17.8V19m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-1a3 3 0 1 0 0-6m4 11v-1.4A3.6 3.6 0 0 0 17.8 14" />
                                        </svg>

                                    </div>

                                    <?php if (
                                        $search !== ''
                                        || $status !== ''
                                    ): ?>

                                        <h3>No members found.</h3>

                                        <p>

                                            <?php if ($search !== ''): ?>
                                                No members matched
                                                <strong>
                                                    "<?= htmlspecialchars($search) ?>"
                                                </strong>
                                            <?php endif; ?>

                                            <?php if (
                                                $search !== ''
                                                && $status !== ''
                                            ): ?>
                                                with status
                                            <?php endif; ?>

                                            <?php if ($status !== ''): ?>
                                                <strong>
                                                    <?= htmlspecialchars($status) ?>
                                                </strong>
                                            <?php endif; ?>

                                            .

                                        </p>

                                        <a
                                            href="/members"
                                            class="btn btn--secondary">
                                            Clear Filters
                                        </a>

                                    <?php else: ?>

                                        <h3>
                                            No members have been registered yet.
                                        </h3>

                                        <p>
                                            Register your first cooperative
                                            member to get started.
                                        </p>

                                        <a
                                            href="/members/create?new=1"
                                            class="btn btn--primary">
                                            Register Member
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($members as $member): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $member['member_number'] ?? '—'
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <span class="members__member-name">
                                        <?= htmlspecialchars(
                                            (string) (
                                                $member['full_name'] ?? '—'
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $member['mobile_number'] ?? '—'
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?php
                                    $memberStatus =
                                        (string) ($member['status'] ?? '—');
                                    ?>
                                    <span
                                        class="members__status members__status--<?= htmlspecialchars(strtolower($memberStatus)) ?>">
                                        <span
                                            class="members__status-dot"
                                            aria-hidden="true"></span>
                                        <?= htmlspecialchars($memberStatus) ?>
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
                                                'M d, Y',
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
                                        class="members__view">
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <?php if ($resultCount > 0): ?>

            <div class="members__footer">

                <span>
                    Showing
                    <strong><?= number_format($from) ?></strong>
                    –
                    <strong><?= number_format($to) ?></strong>
                    of
                    <strong><?= number_format($resultCount) ?></strong>
                    <?= $search !== '' || $status !== ''
                        ? 'matching members'
                        : 'members' ?>
                </span>

                <?php if ($totalPages > 1): ?>

                    <nav
                        class="members__pagination"
                        aria-label="Members pagination">

                        <?php if ($currentPage > 1): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    $paginationUrl($currentPage - 1)
                                ) ?>"
                                class="members__pagination-button">
                                Previous
                            </a>

                        <?php else: ?>

                            <span class="members__pagination-button is-disabled">
                                Previous
                            </span>

                        <?php endif; ?>

                        <div class="members__pagination-pages">

                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min(
                                $totalPages,
                                $currentPage + 2
                            );
                            ?>

                            <?php if ($startPage > 1): ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        $paginationUrl(1)
                                    ) ?>"
                                    class="members__page-number">
                                    1
                                </a>

                                <?php if ($startPage > 2): ?>
                                    <span class="members__pagination-ellipsis">
                                        …
                                    </span>
                                <?php endif; ?>

                            <?php endif; ?>

                            <?php for (
                                $page = $startPage;
                                $page <= $endPage;
                                $page++
                            ): ?>

                                <?php if ($page === $currentPage): ?>

                                    <span
                                        class="members__page-number is-current"
                                        aria-current="page">
                                        <?= $page ?>
                                    </span>

                                <?php else: ?>

                                    <a
                                        href="<?= htmlspecialchars(
                                            $paginationUrl($page)
                                        ) ?>"
                                        class="members__page-number">
                                        <?= $page ?>
                                    </a>

                                <?php endif; ?>

                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>

                                <?php if (
                                    $endPage < $totalPages - 1
                                ): ?>
                                    <span class="members__pagination-ellipsis">
                                        …
                                    </span>
                                <?php endif; ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        $paginationUrl($totalPages)
                                    ) ?>"
                                    class="members__page-number">
                                    <?= $totalPages ?>
                                </a>

                            <?php endif; ?>

                        </div>

                        <?php if ($currentPage < $totalPages): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    $paginationUrl($currentPage + 1)
                                ) ?>"
                                class="members__pagination-button">
                                Next
                            </a>

                        <?php else: ?>

                            <span class="members__pagination-button is-disabled">
                                Next
                            </span>

                        <?php endif; ?>

                    </nav>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</div>
