<div class="form-section">

    <?php

    $sectionTitle = 'Registration Summary';

    $sectionDescription = 'Review all information before registering this member.';

    require __DIR__ . '/../partials/section-header.php';

    ?>

    <div class="summary">

        <!-- Membership -->

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

                            —

                        </div>

                    </div>

                    <div class="summary__row">

                        <div class="summary__label">

                            Membership Date

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Personal -->

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

                            —

                        </div>

                    </div>

                    <div class="summary__row">

                        <div class="summary__label">

                            Birth Date

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                    <div class="summary__row">

                        <div class="summary__label">

                            Civil Status

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Contact -->

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

                            —

                        </div>

                    </div>

                    <div class="summary__row">

                        <div class="summary__label">

                            Email Address

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Address -->

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

                            Address

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Livelihood -->

        <section class="summary__section">

            <div class="summary__header">

                <h3 class="summary__title">

                    Livelihood

                </h3>

                <a
                    href="/members/create?step=employment"
                    class="summary__edit">
                    Edit
                </a>

            </div>

            <div class="summary__body">

                <div class="summary__list">

                    <div class="summary__row">

                        <div class="summary__label">

                            Livelihood Type

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Education -->

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

                            Educational Attainment

                        </div>

                        <div class="summary__value">

                            —

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Beneficiaries -->

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

            </div>

        </section>

    </div>

    <div class="form-actions">

        <a
            href="/members/create?step=beneficiaries"
            class="btn btn--secondary">
            ← Previous
        </a>

        <button
            class="btn btn--primary"
            type="submit">
            Register Member
        </button>

    </div>

</div>