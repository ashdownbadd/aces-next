<?php

declare(strict_types=1);

$member = $member ?? [];

$beneficiaries = $member['beneficiaries'] ?? [];
$loans = $loans ?? [];

$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;

$fullName = trim(
    implode(
        ' ',
        array_filter([
            $member['first_name'] ?? '',
            $member['middle_name'] ?? '',
            $member['last_name'] ?? '',
            $member['suffix'] ?? '',
        ]),
    ),
);

$display = static function (
    mixed $value,
): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return htmlspecialchars((string) $value);
};

$formatDate = static function (
    mixed $value,
): string {
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime((string) $value);

    if ($timestamp === false) {
        return '—';
    }

    return htmlspecialchars(
        date('F j, Y', $timestamp),
    );
};

$formatMoney = static function (
    mixed $value,
): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return '₱' . number_format(
        (float) $value,
        2,
    );
};

$formatLabel = static function (
    mixed $value,
): string {
    if ($value === null || $value === '') {
        return '—';
    }

    $value = str_replace(
        '_',
        ' ',
        (string) $value,
    );

    return htmlspecialchars(
        ucwords($value),
    );
};

$status = strtolower(
    (string) ($member['status'] ?? ''),
);

$statusLabel = $formatLabel(
    $member['status'] ?? null,
);

$memberId = (int) ($member['id'] ?? 0);
?>

<div class="member-profile">

    <header class="member-profile__header">

        <div class="member-profile__header-main">

            <a
                href="/members"
                class="member-profile__back">
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path d="M15 6 9 12l6 6" />
                </svg>
                <span>Back to Members</span>
            </a>

            <div class="member-profile__identity">

                <div class="member-profile__avatar" aria-hidden="true">
                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $fullName !== ''
                                    ? $fullName
                                    : 'Member',
                                0,
                                1,
                            ),
                        ),
                    ) ?>
                </div>

                <div>
                    <h1 class="member-profile__title">
                        <?= $display(
                            $fullName !== ''
                                ? $fullName
                                : 'Member Profile',
                        ) ?>
                    </h1>

                    <div class="member-profile__meta">
                        <span>
                            Member No.
                            <strong>
                                <?= $display(
                                    $member['member_number'] ?? null,
                                ) ?>
                            </strong>
                        </span>

                        <span
                            class="member-profile__status member-profile__status--<?= htmlspecialchars(
                                $status !== '' ? $status : 'unknown',
                            ) ?>">
                            <span
                                class="member-profile__status-dot"
                                aria-hidden="true"></span>
                            <?= $statusLabel ?>
                        </span>
                    </div>
                </div>

            </div>

        </div>

        <a
            href="/members/<?= $memberId ?>/edit"
            class="btn btn--primary">
            Edit Member
        </a>

    </header>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert--success member-profile__alert">
            <div class="alert__title">Success</div>
            <div class="alert__body">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>
        <div class="alert alert--danger member-profile__alert">
            <div class="alert__title">Error</div>
            <div class="alert__body">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        </div>
    <?php endif; ?>

    <section class="member-profile__summary">

        <article class="member-profile__card member-profile__card--membership">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">
                        Membership
                    </span>
                    <h2>Membership Details</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M7 4h10v16H7zM9.5 8h5M9.5 12h5M9.5 16h3" />
                    </svg>
                </span>
            </div>

            <div class="member-profile__data-grid member-profile__data-grid--3">

                <div class="member-profile__data">
                    <span class="member-profile__label">Member Number</span>
                    <strong>
                        <?= $display($member['member_number'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Membership Type</span>
                    <strong>
                        <?= $formatLabel($member['membership_type'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Membership Date</span>
                    <strong>
                        <?= $formatDate($member['membership_date'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Current Status</span>
                    <strong>
                        <?= $statusLabel ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Lifecycle</span>
                    <strong>
                        <?= $status === 'active'
                            ? 'Active member'
                            : ($status === 'inactive'
                                ? 'Inactive member'
                                : ($status === 'pending'
                                    ? 'Awaiting approval'
                                    : 'Status managed')) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Member ID</span>
                    <strong>
                        #<?= $memberId ?>
                    </strong>
                </div>

            </div>

        </article>

        <aside class="member-profile__card member-profile__card--status">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">
                        Account Control
                    </span>
                    <h2>Member Status</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3v4M5.6 5.6l2.8 2.8M3 12h4M5.6 18.4l2.8-2.8M12 17v4M18.4 18.4l-2.8-2.8M17 12h4M18.4 5.6l-2.8 2.8" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>

            <p class="member-profile__status-copy">
                Manage this member's lifecycle status without leaving
                the profile.
            </p>

            <div class="member-profile__status-actions">

                <?php if (($member['status'] ?? '') === 'Pending'): ?>

                    <form
                        method="POST"
                        action="/members/<?= $memberId ?>/status"
                        onsubmit="return confirm('Are you sure you want to approve <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?>? The member will become Active.');">

                        <input
                            type="hidden"
                            name="status"
                            value="Active">

                        <button
                            type="submit"
                            class="btn btn--primary">
                            Approve
                        </button>

                    </form>

                    <form
                        method="POST"
                        action="/members/<?= $memberId ?>/status"
                        onsubmit="return confirm('Are you sure you want to mark <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?> as Inactive?');">

                        <input
                            type="hidden"
                            name="status"
                            value="Inactive">

                        <button
                            type="submit"
                            class="btn btn--outline">
                            Mark Inactive
                        </button>

                    </form>

                <?php elseif (($member['status'] ?? '') === 'Active'): ?>

                    <form
                        method="POST"
                        action="/members/<?= $memberId ?>/status"
                        onsubmit="return confirm('Are you sure you want to deactivate <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?>?');">

                        <input
                            type="hidden"
                            name="status"
                            value="Inactive">

                        <button
                            type="submit"
                            class="btn btn--outline">
                            Deactivate
                        </button>

                    </form>

                <?php elseif (($member['status'] ?? '') === 'Inactive'): ?>

                    <form
                        method="POST"
                        action="/members/<?= $memberId ?>/status"
                        onsubmit="return confirm('Are you sure you want to reactivate <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?>? The member will become Active.');">

                        <input
                            type="hidden"
                            name="status"
                            value="Active">

                        <button
                            type="submit"
                            class="btn btn--primary">
                            Reactivate
                        </button>

                    </form>

                <?php else: ?>

                    <span class="member-profile__status-note">
                        No status transition is currently available.
                    </span>

                <?php endif; ?>

            </div>

        </aside>

    </section>

    <div class="member-profile__grid">

        <section class="member-profile__card member-profile__card--personal">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">Identity</span>
                    <h2>Personal Information</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="3" />
                        <path d="M5 20a7 7 0 0 1 14 0" />
                    </svg>
                </span>
            </div>

            <div class="member-profile__data-grid member-profile__data-grid--3">

                <div class="member-profile__data">
                    <span class="member-profile__label">First Name</span>
                    <strong><?= $display($member['first_name'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Middle Name</span>
                    <strong><?= $display($member['middle_name'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Last Name</span>
                    <strong><?= $display($member['last_name'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Suffix</span>
                    <strong><?= $display($member['suffix'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Birth Date</span>
                    <strong><?= $formatDate($member['birth_date'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Birth Place</span>
                    <strong><?= $display($member['birth_place'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Sex</span>
                    <strong><?= $formatLabel($member['sex'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Civil Status</span>
                    <strong><?= $formatLabel($member['civil_status'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Nationality</span>
                    <strong><?= $display($member['nationality'] ?? null) ?></strong>
                </div>

            </div>

        </section>

        <section class="member-profile__card member-profile__card--contact">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">Reach</span>
                    <h2>Contact Information</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 4h3l2 5-2 1.5a14 14 0 0 0 4.5 4.5L15 13l5 2v3c0 1-1 2-2 2C10.3 20 4 13.7 4 6c0-1 1-2 2-2Z" />
                    </svg>
                </span>
            </div>

            <div class="member-profile__data-stack">

                <div class="member-profile__data">
                    <span class="member-profile__label">Mobile Number</span>
                    <strong><?= $display($member['mobile_number'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Telephone Number</span>
                    <strong><?= $display($member['telephone_number'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Email Address</span>
                    <strong><?= $display($member['email_address'] ?? null) ?></strong>
                </div>

            </div>

        </section>

        <section class="member-profile__card member-profile__card--address">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">Location</span>
                    <h2>Address</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 21s7-6.3 7-11a7 7 0 1 0-14 0c0 4.7 7 11 7 11Z" />
                        <circle cx="12" cy="10" r="2.5" />
                    </svg>
                </span>
            </div>

            <div class="member-profile__data-grid member-profile__data-grid--3">

                <div class="member-profile__data">
                    <span class="member-profile__label">House Number</span>
                    <strong><?= $display($member['house_number'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Street</span>
                    <strong><?= $display($member['street'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Barangay</span>
                    <strong><?= $display($member['barangay'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">City</span>
                    <strong><?= $display($member['city'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Province</span>
                    <strong><?= $display($member['province'] ?? null) ?></strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">ZIP Code</span>
                    <strong><?= $display($member['zip_code'] ?? null) ?></strong>
                </div>

            </div>

        </section>

        <section class="member-profile__card member-profile__card--livelihood">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">Work</span>
                    <h2>Livelihood</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 7h16v12H4zM8 7V5h8v2M8 12h8" />
                    </svg>
                </span>
            </div>

            <div class="member-profile__data-stack">

                <div class="member-profile__data">
                    <span class="member-profile__label">Employment Status</span>
                    <strong>
                        <?= $formatLabel($member['employment_status'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Occupation</span>
                    <strong>
                        <?= $display($member['occupation'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Employer / Business</span>
                    <strong>
                        <?= $display($member['employer'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">Monthly Income</span>
                    <strong>
                        <?= $formatMoney($member['monthly_income'] ?? null) ?>
                    </strong>
                </div>

            </div>

        </section>

        <section class="member-profile__card member-profile__card--education">

            <div class="member-profile__card-head">
                <div>
                    <span class="member-profile__eyebrow">Background</span>
                    <h2>Education</h2>
                </div>

                <span class="member-profile__card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m4 10 8-4 8 4-8 4-8-4Zm4 3v4c2.4 1.5 5.6 1.5 8 0v-4" />
                    </svg>
                </span>
            </div>

            <div class="member-profile__data-stack">

                <div class="member-profile__data">
                    <span class="member-profile__label">
                        Highest Educational Attainment
                    </span>

                    <strong>
                        <?= $formatLabel(
                            $member['highest_educational_attainment']
                                ?? null,
                        ) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">
                        School Attended
                    </span>

                    <strong>
                        <?= $display($member['school_name'] ?? null) ?>
                    </strong>
                </div>

                <div class="member-profile__data">
                    <span class="member-profile__label">
                        Graduation Year
                    </span>

                    <strong>
                        <?= $display($member['graduation_year'] ?? null) ?>
                    </strong>
                </div>

            </div>

        </section>

        <section class="member-profile__card member-profile__card--beneficiaries">

            <div class="member-profile__card-head">

                <div>
                    <span class="member-profile__eyebrow">
                        Family
                    </span>

                    <h2>Beneficiaries</h2>
                </div>

                <?php if ($beneficiaries !== []): ?>

                    <span class="member-profile__count">
                        <?= count($beneficiaries) ?>
                        <?= count($beneficiaries) === 1
                            ? 'Beneficiary'
                            : 'Beneficiaries' ?>
                    </span>

                <?php endif; ?>

            </div>

            <?php if ($beneficiaries === []): ?>

                <p class="member-profile__empty">
                    No beneficiaries registered.
                </p>

            <?php else: ?>

                <div class="member-profile__beneficiary-table">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Birth Date</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach (
                                $beneficiaries
                                as $beneficiary
                            ): ?>

                                <?php

                                $beneficiaryName = trim(
                                    implode(
                                        ' ',
                                        array_filter([
                                            $beneficiary['first_name'] ?? '',
                                            $beneficiary['middle_name'] ?? '',
                                            $beneficiary['last_name'] ?? '',
                                            $beneficiary['suffix'] ?? '',
                                        ]),
                                    ),
                                );

                                ?>

                                <tr>

                                    <td>
                                        <?= $display(
                                            $beneficiaryName !== ''
                                                ? $beneficiaryName
                                                : null,
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $formatLabel(
                                            $beneficiary['relationship']
                                                ?? null,
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $formatDate(
                                            $beneficiary['birth_date']
                                                ?? null,
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </div>

    <section class="member-profile__card member-profile__card--loans">

        <div class="member-profile__card-head">

            <div>
                <span class="member-profile__eyebrow">
                    Cooperative Activity
                </span>

                <h2>Loans</h2>
            </div>

            <span class="member-profile__count">
                <?= count($loans) ?>
                <?= count($loans) === 1 ? 'Loan' : 'Loans' ?>
            </span>

        </div>

        <?php if ($loans === []): ?>

            <p class="member-profile__empty">
                No loans recorded for this member.
            </p>

        <?php else: ?>

            <div class="member-profile__beneficiary-table">

                <table class="table">

                    <thead>
                        <tr>
                            <th>Loan</th>
                            <th>Type</th>
                            <th>Principal</th>
                            <th>Application</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($loans as $loan): ?>

                            <tr>

                                <td>
                                    #<?= (int) ($loan['id'] ?? 0) ?>
                                </td>

                                <td>
                                    <?= $formatLabel(
                                        $loan['loan_type'] ?? null,
                                    ) ?>
                                </td>

                                <td>
                                    <?= $formatMoney(
                                        $loan['principal_amount'] ?? null,
                                    ) ?>
                                </td>

                                <td>
                                    <?= $formatLabel(
                                        $loan['application_status']
                                            ?? null,
                                    ) ?>
                                </td>

                                <td>
                                    <?= $formatLabel(
                                        $loan['loan_status'] ?? null,
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="/loans/<?= (int) ($loan['id'] ?? 0) ?>/show"
                                        class="btn btn--outline btn--sm">
                                        View Loan
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</div>
