<?php

declare(strict_types=1);

$member = $member ?? [];

$beneficiaries = $member['beneficiaries'] ?? [];

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

/*
|--------------------------------------------------------------------------
| Display Helpers
|--------------------------------------------------------------------------
*/

$display = static function (
    mixed $value,
): string {
    if (
        $value === null ||
        $value === ''
    ) {
        return '—';
    }

    return htmlspecialchars(
        (string) $value,
    );
};

$formatDate = static function (
    mixed $value,
): string {
    if (
        $value === null ||
        $value === ''
    ) {
        return '—';
    }

    $timestamp = strtotime(
        (string) $value,
    );

    if ($timestamp === false) {
        return '—';
    }

    return htmlspecialchars(
        date(
            'F j, Y',
            $timestamp,
        ),
    );
};

$formatMoney = static function (
    mixed $value,
): string {
    if (
        $value === null ||
        $value === ''
    ) {
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
    if (
        $value === null ||
        $value === ''
    ) {
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
    (string) (
        $member['status'] ?? ''
    ),
);

$statusLabel = $formatLabel(
    $member['status'] ?? null,
);

?>

<div class="member-profile">

    <!--
    |--------------------------------------------------------------------------
    | Profile Header
    |--------------------------------------------------------------------------
    -->

    <div class="member-profile__header">

        <div class="member-profile__identity">

            <a
                href="/members"
                class="member-profile__back">

                ← Back to Members

            </a>

            <div class="member-profile__heading">

                <div>

                    <h1 class="member-profile__title">

                        <?= $display(
                            $fullName !== ''
                                ? $fullName
                                : 'Member Profile',
                        ) ?>

                    </h1>

                    <p class="member-profile__number">

                        Member No.

                        <strong>
                            <?= $display(
                                $member['member_number']
                                    ?? null,
                            ) ?>
                        </strong>

                    </p>

                </div>

                <span
                    class="member-profile__status member-profile__status--<?= htmlspecialchars(
                                                                                $status !== ''
                                                                                    ? $status
                                                                                    : 'unknown',
                                                                            ) ?>">

                    <?= $statusLabel ?>

                </span>

            </div>

        </div>

        <div class="member-profile__actions">

            <a
                href="/members/<?= (int) ($member['id'] ?? 0) ?>/edit"
                class="btn btn--primary">

                Edit Member

            </a>

        </div>

    </div>


    <?php if ($successMessage !== null): ?>

        <div class="alert alert--success">

            <div class="alert__title">
                Success
            </div>

            <div class="alert__body">
                <?= htmlspecialchars($successMessage) ?>
            </div>

        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>

        <div class="alert alert--danger">

            <div class="alert__title">
                Error
            </div>

            <div class="alert__body">
                <?= htmlspecialchars($errorMessage) ?>
            </div>

        </div>

    <?php endif; ?>

    <!--
    |--------------------------------------------------------------------------
    | Status Management
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <div>
                <h2 class="card__title">
                    Member Status
                </h2>

                <p class="card__subtitle">
                    Manage the member's current lifecycle status.
                </p>
            </div>

        </div>

        <div class="form-grid form-grid--2">

            <div class="form-group">

                <span class="form-label">
                    Current Status
                </span>

                <div class="form-value">
                    <?= $statusLabel ?>
                </div>

            </div>

            <?php if (($member['status'] ?? '') === 'Pending'): ?>

                <div class="form-group">

                    <span class="form-label">
                        Approval
                    </span>

                    <div class="form-value">
                        This member is awaiting approval.
                    </div>

                </div>

                <div class="form-group form-group--full">

                    <div class="form-actions">

                        <form
                            method="POST"
                            action="/members/<?= (int) ($member['id'] ?? 0) ?>/status"
                            onsubmit="return confirm('Are you sure you want to approve <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?>? The member will become Active.');">

                            <input
                                type="hidden"
                                name="status"
                                value="Active">

                            <button
                                type="submit"
                                class="btn btn--primary">

                                Approve Member

                            </button>

                        </form>

                        <form
                            method="POST"
                            action="/members/<?= (int) ($member['id'] ?? 0) ?>/status"
                            onsubmit="return confirm('Are you sure you want to mark <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?> as Inactive?');">

                            <input
                                type="hidden"
                                name="status"
                                value="Inactive">

                            <button
                                type="submit"
                                class="btn btn--secondary">

                                Mark Inactive

                            </button>

                        </form>

                    </div>

                </div>

            <?php elseif (($member['status'] ?? '') === 'Active'): ?>

                <div class="form-group">

                    <span class="form-label">
                        Member Status
                    </span>

                    <div class="form-value">
                        This member is currently active.
                    </div>

                </div>

                <div class="form-group form-group--full">

                    <div class="form-actions">

                        <form
                            method="POST"
                            action="/members/<?= (int) ($member['id'] ?? 0) ?>/status"
                            onsubmit="return confirm('Are you sure you want to deactivate <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?>?');">

                            <input
                                type="hidden"
                                name="status"
                                value="Inactive">

                            <button
                                type="submit"
                                class="btn btn--secondary">

                                Deactivate Member

                            </button>

                        </form>

                    </div>

                </div>

            <?php elseif (($member['status'] ?? '') === 'Inactive'): ?>

                <div class="form-group">

                    <span class="form-label">
                        Member Status
                    </span>

                    <div class="form-value">
                        This member is currently inactive.
                    </div>

                </div>

                <div class="form-group form-group--full">

                    <div class="form-actions">

                        <form
                            method="POST"
                            action="/members/<?= (int) ($member['id'] ?? 0) ?>/status"
                            onsubmit="return confirm('Are you sure you want to reactivate <?= htmlspecialchars($fullName !== '' ? $fullName : 'this member', ENT_QUOTES) ?>? The member will become Active.');">

                            <input
                                type="hidden"
                                name="status"
                                value="Active">

                            <button
                                type="submit"
                                class="btn btn--primary">

                                Reactivate Member

                            </button>

                        </form>

                    </div>

                </div>

            <?php else: ?>

                <div class="form-group">

                    <span class="form-label">
                        Status Management
                    </span>

                    <div class="form-value">
                        No status transition is currently available.
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!--
    |--------------------------------------------------------------------------
    | Membership Information
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Membership Information
            </h2>

        </div>

        <div class="form-grid">

            <div class="form-group">

                <span class="form-label">
                    Member Number
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['member_number']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Membership Date
                </span>

                <div class="form-value">
                    <?= $formatDate(
                        $member['membership_date']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Membership Type
                </span>

                <div class="form-value">
                    <?= $formatLabel(
                        $member['membership_type']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Status
                </span>

                <div class="form-value">
                    <?= $statusLabel ?>
                </div>

            </div>

        </div>

    </section>


    <!--
    |--------------------------------------------------------------------------
    | Personal Information
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Personal Information
            </h2>

        </div>

        <div class="form-grid">

            <div class="form-group">

                <span class="form-label">
                    First Name
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['first_name']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Middle Name
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['middle_name']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Last Name
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['last_name']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Suffix
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['suffix']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Birth Date
                </span>

                <div class="form-value">
                    <?= $formatDate(
                        $member['birth_date']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Birth Place
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['birth_place']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Sex
                </span>

                <div class="form-value">
                    <?= $formatLabel(
                        $member['sex']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Civil Status
                </span>

                <div class="form-value">
                    <?= $formatLabel(
                        $member['civil_status']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Nationality
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['nationality']
                            ?? null,
                    ) ?>
                </div>

            </div>

        </div>

    </section>


    <!--
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Contact Information
            </h2>

        </div>

        <div class="form-grid">

            <div class="form-group">

                <span class="form-label">
                    Mobile Number
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['mobile_number']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Telephone Number
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['telephone_number']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group form-group--full">

                <span class="form-label">
                    Email Address
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['email_address']
                            ?? null,
                    ) ?>
                </div>

            </div>

        </div>

    </section>


    <!--
    |--------------------------------------------------------------------------
    | Address
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Address
            </h2>

        </div>

        <div class="form-grid">

            <div class="form-group">

                <span class="form-label">
                    House Number
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['house_number']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Street
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['street']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Barangay
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['barangay']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    City
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['city']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Province
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['province']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    ZIP Code
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['zip_code']
                            ?? null,
                    ) ?>
                </div>

            </div>

        </div>

    </section>


    <!--
    |--------------------------------------------------------------------------
    | Livelihood Information
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Livelihood Information
            </h2>

        </div>

        <div class="form-grid">

            <div class="form-group">

                <span class="form-label">
                    Employment Status
                </span>

                <div class="form-value">
                    <?= $formatLabel(
                        $member['employment_status']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Occupation
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['occupation']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Employer / Business
                </span>

                <div class="form-value">
                    <?= $display(
                        $member['employer']
                            ?? null,
                    ) ?>
                </div>

            </div>

            <div class="form-group">

                <span class="form-label">
                    Monthly Income
                </span>

                <div class="form-value">

                    <?= $formatMoney(
                        $member['monthly_income']
                            ?? null,
                    ) ?>

                </div>

            </div>

        </div>

    </section>


    <!--
    |--------------------------------------------------------------------------
    | Education
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Education
            </h2>

        </div>

        <div class="form-grid">

            <div class="form-group form-group--full">

                <span class="form-label">
                    Highest Educational Attainment
                </span>

                <div class="form-value">
                    <?= $formatLabel(
                        $member['highest_educational_attainment'] ?? null,
                    ) ?>
                </div>

            </div>

        </div>

    </section>


    <!--
    |--------------------------------------------------------------------------
    | Beneficiaries
    |--------------------------------------------------------------------------
    -->

    <section class="card">

        <div class="card__header">

            <h2 class="card__title">
                Beneficiaries
            </h2>

            <?php if ($beneficiaries !== []): ?>

                <span class="card__meta">

                    <?= count($beneficiaries) ?>

                    <?= count($beneficiaries) === 1
                        ? 'beneficiary'
                        : 'beneficiaries'
                    ?>

                </span>

            <?php endif; ?>

        </div>

        <?php if ($beneficiaries === []): ?>

            <p class="members__empty-text">
                No beneficiaries registered.
            </p>

        <?php else: ?>

            <div class="table-container">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Name</th>

                            <th>Relationship</th>

                            <th>Birth Date</th>

                            <th>Remarks</th>

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
                                        $beneficiary['relationship'] ?? null,
                                    ) ?>

                                </td>

                                <td>

                                    <?= $formatDate(
                                        $beneficiary['birth_date'] ?? null,
                                    ) ?>

                                </td>

                                <td>

                                    <?= $display(
                                        $beneficiary['remarks'] ?? null,
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