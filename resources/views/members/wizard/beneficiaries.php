<?php

declare(strict_types=1);

?>

<div class="form-section">

    <?php

    $sectionTitle = 'Beneficiaries';

    $sectionDescription = 'Add one or more beneficiaries before completing registration.';

    require __DIR__ . '/../partials/section-header.php';

    ?>

    <div class="beneficiary-manager">

        <!-- ========================================================= -->
        <!-- Add Beneficiary -->
        <!-- ========================================================= -->

        <form
            method="POST"
            action="/members/beneficiaries">

            <div class="beneficiary-manager__form">

                <div class="form-grid">

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="beneficiary_first_name">

                            First Name

                        </label>

                        <input
                            id="beneficiary_first_name"
                            class="input"
                            type="text"
                            name="first_name"
                            data-type="personName"
                            maxlength="100"
                            autocomplete="given-name"
                            required>

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="beneficiary_middle_name">

                            Middle Name

                        </label>

                        <input
                            id="beneficiary_middle_name"
                            class="input"
                            type="text"
                            name="middle_name"
                            data-type="personName"
                            maxlength="100"
                            autocomplete="additional-name">

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="beneficiary_last_name">

                            Last Name

                        </label>

                        <input
                            id="beneficiary_last_name"
                            class="input"
                            type="text"
                            name="last_name"
                            data-type="personName"
                            maxlength="100"
                            autocomplete="family-name"
                            required>

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="beneficiary_suffix">

                            Suffix

                        </label>

                        <input
                            id="beneficiary_suffix"
                            class="input"
                            type="text"
                            name="suffix"
                            data-type="uppercase"
                            maxlength="10"
                            placeholder="Jr., Sr., III">

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="relationship">

                            Relationship

                        </label>

                        <input
                            id="relationship"
                            class="input"
                            type="text"
                            name="relationship"
                            data-type="title"
                            maxlength="100"
                            required>

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="beneficiary_birth_date">

                            Birth Date

                        </label>

                        <input
                            id="beneficiary_birth_date"
                            class="input"
                            type="date"
                            name="birth_date"
                            data-type="birthdate"
                            required>

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="share_percentage">

                            Share Percentage

                        </label>

                        <input
                            id="share_percentage"
                            class="input"
                            type="text"
                            name="share_percentage"
                            data-type="percentage"
                            inputmode="decimal"
                            placeholder="100"
                            required>

                    </div>

                    <div class="form-group form-group--full">

                        <label
                            class="form-label"
                            for="remarks">

                            Remarks

                        </label>

                        <textarea
                            id="remarks"
                            class="input"
                            name="remarks"
                            rows="3"
                            maxlength="500"></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn--secondary">

                        Add Beneficiary

                    </button>

                </div>

            </div>

        </form>

        <hr>

        <!-- ========================================================= -->
        <!-- Current Beneficiaries -->
        <!-- ========================================================= -->

        <div class="beneficiary-manager__list">

            <?php if (empty($beneficiaries)): ?>

                <div class="beneficiary-manager__empty">

                    No beneficiaries added.

                </div>

            <?php else: ?>

                <?php foreach ($beneficiaries as $index => $beneficiary): ?>

                    <div class="beneficiary-card">

                        <div class="beneficiary-card__content">

                            <strong>

                                <?= htmlspecialchars(trim(implode(' ', array_filter([
                                    $beneficiary['first_name'] ?? '',
                                    $beneficiary['middle_name'] ?? '',
                                    $beneficiary['last_name'] ?? '',
                                    $beneficiary['suffix'] ?? '',
                                ])))) ?>

                            </strong>

                            <div>

                                <?= htmlspecialchars($beneficiary['relationship'] ?? '') ?>

                            </div>

                        </div>
                        <form
                            method="POST"
                            action="/members/beneficiaries/delete">

                            <input
                                type="hidden"
                                name="index"
                                value="<?= $index ?>">

                            <button
                                type="submit"
                                class="btn btn--danger btn--sm">

                                Remove

                            </button>

                        </form>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <!-- ========================================================= -->
        <!-- Wizard Navigation -->
        <!-- ========================================================= -->

        <form
            method="POST"
            action="/members/create?step=beneficiaries">

            <?php

            $previousStep = 'education';
            $submitLabel = 'Next →';

            require __DIR__ . '/../partials/wizard-navigation.php';

            ?>

        </form>

    </div>

</div>

<?php

/*
|--------------------------------------------------------------------------
| End of Beneficiaries Wizard Page
|--------------------------------------------------------------------------
|
| Input Types Used
|
| personName
| uppercase
| title
| birthdate
| percentage
|
| All formatting and input behavior is handled by:
|
| public/js/wizard.js
|
*/

// END OF FILE