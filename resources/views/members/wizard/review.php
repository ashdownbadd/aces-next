<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Helper Variables
|--------------------------------------------------------------------------
*/

$fullName = trim(
    implode(
        ' ',
        array_filter([
            $personal['first_name'] ?? '',
            $personal['middle_name'] ?? '',
            $personal['last_name'] ?? '',
            $personal['suffix'] ?? '',
        ])
    )
);

$addressText = trim(
    implode(
        ', ',
        array_filter([
            $address['house_number'] ?? '',
            $address['street'] ?? '',
            $address['barangay'] ?? '',
            $address['city'] ?? '',
            $address['province'] ?? '',
            $address['zip_code'] ?? '',
        ])
    )
);

$formatDate = static function (?string $date): string {

    if (empty($date)) {
        return '—';
    }

    return date(
        'F j, Y',
        strtotime($date)
    );
};

$formatMoney = static function ($amount): string {

    if (
        $amount === null ||
        $amount === ''
    ) {
        return '—';
    }

    return '₱' . number_format(
        (float) $amount,
        2
    );
};

$humanize = static function (?string $value): string {

    if (empty($value)) {
        return '—';
    }

    return ucwords(
        str_replace(
            '_',
            ' ',
            $value
        )
    );
};

?>

<form
    method="POST"
    action="/members/register"
    data-registration-form>

    <div class="form-section">

        <?php

        $sectionTitle = 'Registration Summary';

        $sectionDescription = 'Review all information before registering this member.';

        require __DIR__ . '/../partials/section-header.php';

        ?>

        <div class="summary">

            <!-- ========================================================= -->
            <!-- Membership -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Membership

                    </h3>

                    <a
                        href="/members/create?step=membership"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <div class="summary__list">

                        <div class="summary__row">

                            <div class="summary__label">

                                Membership Type

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $membership['membership_type']
                                            ?? '—'
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Membership Date

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $formatDate(
                                        $membership['membership_date'] ?? null
                                    )
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- ========================================================= -->
            <!-- Personal -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Personal Information

                    </h3>

                    <a
                        href="/members/create?step=personal"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <div class="summary__list">

                        <div class="summary__row">

                            <div class="summary__label">

                                Full Name

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $fullName !== ''
                                        ? $fullName
                                        : '—'
                                ) ?>

                            </div>

                        </div>
                        <div class="summary__row">

                            <div class="summary__label">

                                Birth Date

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $formatDate(
                                        $personal['birth_date'] ?? null
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Birth Place

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $personal['birth_place']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Sex

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $personal['sex']
                                            ?? '—'
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Civil Status

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $personal['civil_status']
                                            ?? '—'
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Nationality

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $personal['nationality']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- ========================================================= -->
            <!-- Contact -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Contact Information

                    </h3>

                    <a
                        href="/members/create?step=contact"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <div class="summary__list">

                        <div class="summary__row">

                            <div class="summary__label">

                                Mobile Number

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $contact['mobile_number']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Telephone Number

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $contact['telephone_number']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Email Address

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $contact['email_address']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- ========================================================= -->
            <!-- Address -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Residential Address

                    </h3>

                    <a
                        href="/members/create?step=address"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <div class="summary__list">

                        <div class="summary__row">

                            <div class="summary__label">

                                Complete Address

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $addressText !== ''
                                        ? $addressText
                                        : '—'
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- ========================================================= -->
            <!-- Livelihood -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Livelihood Information

                    </h3>

                    <a
                        href="/members/create?step=livelihood"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <div class="summary__list">

                        <div class="summary__row">

                            <div class="summary__label">

                                Employment Status

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $humanize(
                                        $livelihood['employment_status'] ?? null
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Occupation

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $livelihood['occupation']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Employer

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $livelihood['employer']
                                        ?? '—'
                                ) ?>

                            </div>

                        </div>

                        <div class="summary__row">

                            <div class="summary__label">

                                Monthly Income

                            </div>

                            <div class="summary__value">

                                <?php
                                $monthlyIncome = $livelihood['monthly_income'] ?? null;
                                ?>

                                <?= $monthlyIncome !== null && $monthlyIncome !== ''
                                    ? '₱' . number_format(
                                        (float) $monthlyIncome,
                                        2,
                                    )
                                    : '—'
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- ========================================================= -->
            <!-- Education -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Education

                    </h3>

                    <a
                        href="/members/create?step=education"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <div class="summary__list">

                        <div class="summary__row">

                            <div class="summary__label">

                                Highest Educational Attainment

                            </div>

                            <div class="summary__value">

                                <?= htmlspecialchars(
                                    $humanize(
                                        $education['highest_educational_attainment'] ?? null
                                    )
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <!-- ========================================================= -->
            <!-- Beneficiaries -->
            <!-- ========================================================= -->

            <section class="summary__section">

                <div class="summary__header">

                    <h3 class="summary__title">

                        Beneficiaries

                    </h3>

                    <a
                        href="/members/create?step=beneficiaries"
                        class="summary__edit">

                        Edit

                    </a>

                </div>

                <div class="summary__body">

                    <?php if (empty($beneficiaries)): ?>

                        <div class="summary__list">

                            <div class="summary__row">

                                <div class="summary__label">

                                    Beneficiaries

                                </div>

                                <div class="summary__value">

                                    None added

                                </div>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="summary__list">

                            <?php foreach ($beneficiaries as $beneficiary): ?>

                                <?php

                                $beneficiaryName = trim(
                                    implode(
                                        ' ',
                                        array_filter([
                                            $beneficiary['first_name'] ?? '',
                                            $beneficiary['middle_name'] ?? '',
                                            $beneficiary['last_name'] ?? '',
                                            $beneficiary['suffix'] ?? '',
                                        ])
                                    )
                                );

                                ?>

                                <div class="summary__row">

                                    <div class="summary__label">

                                        <?= htmlspecialchars(
                                            $beneficiary['relationship']
                                                ?? 'Beneficiary'
                                        ) ?>

                                    </div>

                                    <div class="summary__value">

                                        <?= htmlspecialchars(
                                            $beneficiaryName !== ''
                                                ? $beneficiaryName
                                                : '—'
                                        ) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

        <?php

        $previousStep = 'beneficiaries';
        $submitLabel = $isEditing ? 'Update Member' : 'Register Member';

        require __DIR__ . '/../partials/wizard-navigation.php';

        ?>

    </div>

    <?php

    /*
    |--------------------------------------------------------------------------
    | Hidden Registration Data
    |--------------------------------------------------------------------------
    |
    | The Review page is the final submission point. Since the previous
    | wizard steps have already stored everything in the session, these
    | hidden fields simply identify that this is the final registration
    | request.
    |
    */

    ?>

    <input
        type="hidden"
        name="step"
        value="review">


<div
    class="registration-progress"
    data-registration-progress
    hidden
    aria-live="polite"
    aria-busy="true">

    <span
        class="registration-progress__spinner"
        aria-hidden="true"></span>

    <div>
        <strong>Registering member</strong>
        <span>Please wait while the member record is being saved.</span>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(
        "[data-registration-form]"
    );

    const progress = document.querySelector(
        "[data-registration-progress]"
    );

    if (!form || !progress) {
        return;
    }

    form.addEventListener("submit", () => {
        const button = form.querySelector(
            'button[type="submit"]'
        );

        button?.setAttribute(
            "disabled",
            "disabled"
        );

        if (button) {
            button.textContent =
                "Registering...";
        }

        progress.hidden = false;

        window.AcesWizard?.clearDraft();
    });
});
</script>

</form>

<?php

/*
|--------------------------------------------------------------------------
| Future Notes
|--------------------------------------------------------------------------
|
| This page intentionally contains NO database logic.
|
| Submission Flow:
|
| Review
|     ↓
| POST /members/register
|     ↓
| MembersController::register()
|     ↓
| MemberService::create()
|     ↓
| MemberRepository::create()
|
| The Review page only displays the collected session data.
|
*/