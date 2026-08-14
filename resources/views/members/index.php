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
 * Build a pagination URL while preserving the search query.
 */
$paginationUrl = static function (
    int $page,
) use (
    $search
): string {
    $query = [
        'page' => $page,
    ];

    if ($search !== '') {
        $query['search'] = $search;
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
                class="members__search-form">

                <input
                    class="input members__search"
                    type="search"
                    name="search"
                    value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                    placeholder="Search member..."
                    autocomplete="off">

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

                                <?php if ($search !== ''): ?>

                                    <h3>
                                        No members found.
                                    </h3>

                                    <p>
                                        No members matched
                                        <strong>
                                            "<?= htmlspecialchars(
                                                    $search
                                                ) ?>"
                                        </strong>.
                                    </p>

                                    <a
                                        href="/members"
                                        class="btn">

                                        Clear Search

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

                                <?= htmlspecialchars(
                                    (string) (
                                        $member['full_name'] ?? '—'
                                    )
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    (string) (
                                        $member['mobile_number'] ?? '—'
                                    )
                                ) ?>

                            </td>

                            <td>

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        (string) (
                                            $member['status'] ?? '—'
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

                <span>

                    Showing
                    <?= number_format($from) ?>
                    –
                    <?= number_format($to) ?>

                    of
                    <?= number_format($resultCount) ?>

                    <?php if ($search !== ''): ?>

                        matching members

                    <?php else: ?>

                        members

                    <?php endif; ?>

                </span>

            </div>

        <?php endif; ?>

        <?php if ($totalPages > 1): ?>

            <nav
                class="members__pagination"
                aria-label="Members pagination">

                <?php if ($currentPage > 1): ?>

                    <a
                        href="<?= htmlspecialchars(
                                    $paginationUrl(
                                        $currentPage - 1
                                    )
                                ) ?>"
                        class="btn btn--sm">

                        ← Previous

                    </a>

                <?php else: ?>

                    <span class="btn btn--sm btn--disabled">
                        ← Previous
                    </span>

                <?php endif; ?>

                <div class="members__pagination-pages">

                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Determine visible page numbers
                    |--------------------------------------------------------------------------
                    */

                    $startPage = max(
                        1,
                        $currentPage - 2,
                    );

                    $endPage = min(
                        $totalPages,
                        $currentPage + 2,
                    );

                    ?>

                    <?php if ($startPage > 1): ?>

                        <a
                            href="<?= htmlspecialchars(
                                        $paginationUrl(1)
                                    ) ?>"
                            class="btn btn--sm">

                            1

                        </a>

                        <?php if ($startPage > 2): ?>

                            <span
                                class="members__pagination-ellipsis">
                                …
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>

                    <?php for (
                        $page = $startPage;
                        $page <= $endPage;
                        $page++
                    ): ?>

                        <?php if (
                            $page === $currentPage
                        ): ?>

                            <span
                                class="btn btn--sm btn--primary"
                                aria-current="page">

                                <?= $page ?>

                            </span>

                        <?php else: ?>

                            <a
                                href="<?= htmlspecialchars(
                                            $paginationUrl(
                                                $page
                                            )
                                        ) ?>"
                                class="btn btn--sm">

                                <?= $page ?>

                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>

                    <?php if (
                        $endPage < $totalPages
                    ): ?>

                        <?php if (
                            $endPage < $totalPages - 1
                        ): ?>

                            <span
                                class="members__pagination-ellipsis">
                                …
                            </span>

                        <?php endif; ?>

                        <a
                            href="<?= htmlspecialchars(
                                        $paginationUrl(
                                            $totalPages
                                        )
                                    ) ?>"
                            class="btn btn--sm">

                            <?= $totalPages ?>

                        </a>

                    <?php endif; ?>

                </div>

                <?php if (
                    $currentPage < $totalPages
                ): ?>

                    <a
                        href="<?= htmlspecialchars(
                                    $paginationUrl(
                                        $currentPage + 1
                                    )
                                ) ?>"
                        class="btn btn--sm">

                        Next →

                    </a>

                <?php else: ?>

                    <span class="btn btn--sm btn--disabled">
                        Next →
                    </span>

                <?php endif; ?>

            </nav>

        <?php endif; ?>

    </div>

</div>