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

            <h1 class="members__title">
                Members
            </h1>

            <p class="members__description">
                Manage cooperative members.
            </p>

        </div>

        <a
            href="/members/create?new=1"
            class="btn btn--primary">

            Register Member

        </a>

    </div>

    <?php if ($successMessage !== null): ?>

        <div class="alert alert--success">

            <strong>
                Success
            </strong>

            <span>
                <?= htmlspecialchars(
                    $successMessage
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <div class="card">

        <div class="members__toolbar">

            <form
                method="GET"
                action="/members"
                class="members__search-form" data-live-search data-live-search-server="true"-container>

                <input
                    class="input members__search"
                    type="search"
                    name="search" data-live-search data-live-search-target="#members-table-body"
                    value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                    placeholder="Search member..."
                    autocomplete="off">

                <select
                    class="input members__status-filter"
                    name="status"
                    aria-label="Filter members by status"
                    onchange="this.form.submit()">

                    <option
                        value=""
                        <?= $status === ''
                            ? 'selected'
                            : '' ?>>

                        All Statuses

                    </option>

                    <option
                        value="Pending"
                        <?= $status === 'Pending'
                            ? 'selected'
                            : '' ?>>

                        Pending

                    </option>

                    <option
                        value="Active"
                        <?= $status === 'Active'
                            ? 'selected'
                            : '' ?>>

                        Active

                    </option>

                    <option
                        value="Inactive"
                        <?= $status === 'Inactive'
                            ? 'selected'
                            : '' ?>>

                        Inactive

                    </option>

                </select>

                <noscript>

                    <button
                        type="submit"
                        class="btn">

                        Filter

                    </button>

                </noscript>

            </form>

            <span class="members__total">

                Total:
                <?= number_format(
                    $totalMembers
                ) ?>

            </span>

        </div>

        <table class="table">

            <thead>

                <tr>

                    <th>
                        Member #
                    </th>

                    <th>
                        Name
                    </th>

                    <th>
                        Mobile
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Joined
                    </th>

                    <th>
                        Actions
                    </th>

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

                                <?php if (
                                    $search !== '' ||
                                    $status !== ''
                                ): ?>

                                    <h3>
                                        No members found.
                                    </h3>

                                    <p>

                                        <?php if (
                                            $search !== ''
                                        ): ?>

                                            No members matched
                                            <strong>
                                                "<?= htmlspecialchars(
                                                        $search
                                                    ) ?>"
                                            </strong>

                                        <?php endif; ?>

                                        <?php if (
                                            $search !== '' &&
                                            $status !== ''
                                        ): ?>

                                            with status

                                        <?php endif; ?>

                                        <?php if (
                                            $status !== ''
                                        ): ?>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $status
                                                ) ?>
                                            </strong>

                                        <?php endif; ?>

                                        .

                                    </p>

                                    <a
                                        href="/members"
                                        class="btn">

                                        Clear Filters

                                    </a>

                                <?php else: ?>

                                    <h3>
                                        No members have been
                                        registered yet.
                                    </h3>

                                    <p>
                                        Register your first
                                        cooperative member to
                                        get started.
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

                    <?php foreach (
                        $members as $member
                    ): ?>

                        <tr data-live-search-item="true">

                            <td>

                                <?= htmlspecialchars(
                                    (string) (
                                        $member['member_number']
                                        ?? '—'
                                    )
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    (string) (
                                        $member['full_name']
                                        ?? '—'
                                    )
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    (string) (
                                        $member['mobile_number']
                                        ?? '—'
                                    )
                                ) ?>

                            </td>

                            <td>

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        (string) (
                                            $member['status']
                                            ?? '—'
                                        )
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
                                                (string)
                                                $membershipDate
                                            )
                                        )
                                    )
                                    : '—'
                                ?>

                            </td>

                            <td>

                                <a
                                    href="/members/<?= urlencode(
                                                        (string) (
                                                            $member['id']
                                                        )
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

        <?php if ($resultCount > 0): ?>

            <div class="members__footer">

                <span class="members__result-count">
                    Showing
                    <?= number_format($from) ?>
                    –
                    <?= number_format($to) ?>
                    of
                    <?= number_format($resultCount) ?>
                    <?= ($search !== '' || $status !== '')
                        ? 'matching members'
                        : 'members' ?>
                </span>

                <?php if ($totalPages > 1): ?>

                    <nav
                        class="members__pagination"
                        aria-label="Members pagination">

                        <?php if ($currentPage > 1): ?>
                            <a
                                href="<?= htmlspecialchars($paginationUrl($currentPage - 1)) ?>"
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
                            $endPage = min($totalPages, $currentPage + 2);
                            ?>

                            <?php if ($startPage > 1): ?>
                                <a
                                    href="<?= htmlspecialchars($paginationUrl(1)) ?>"
                                    class="members__page-number">
                                    1
                                </a>

                                <?php if ($startPage > 2): ?>
                                    <span class="members__pagination-ellipsis">…</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($page = $startPage; $page <= $endPage; $page++): ?>

                                <?php if ($page === $currentPage): ?>
                                    <span
                                        class="members__page-number is-current"
                                        aria-current="page">
                                        <?= $page ?>
                                    </span>
                                <?php else: ?>
                                    <a
                                        href="<?= htmlspecialchars($paginationUrl($page)) ?>"
                                        class="members__page-number">
                                        <?= $page ?>
                                    </a>
                                <?php endif; ?>

                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>

                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="members__pagination-ellipsis">…</span>
                                <?php endif; ?>

                                <a
                                    href="<?= htmlspecialchars($paginationUrl($totalPages)) ?>"
                                    class="members__page-number">
                                    <?= $totalPages ?>
                                </a>

                            <?php endif; ?>

                        </div>

                        <?php if ($currentPage < $totalPages): ?>
                            <a
                                href="<?= htmlspecialchars($paginationUrl($currentPage + 1)) ?>"
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

    </div>

</div>