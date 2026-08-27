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

        <?php
        $editingIndex = isset($_GET['edit'])
            ? (int) $_GET['edit']
            : null;

        $editingBeneficiary = $editingIndex !== null
            ? ($beneficiaries[$editingIndex] ?? null)
            : null;

        $isEditing = $editingBeneficiary !== null;

        $formAction = $isEditing
            ? '/members/beneficiaries/update'
            : '/members/beneficiaries';

        $submitLabel = $isEditing
            ? 'Update Beneficiary'
            : 'Add Beneficiary';
        ?>

        <form
            method="POST"
            action="<?= htmlspecialchars($formAction) ?>">

            <?php if ($isEditing): ?>
                <input
                    type="hidden"
                    name="index"
                    value="<?= $editingIndex ?>">
            <?php endif; ?>

            <div class="beneficiary-manager__form">

                <h3 class="beneficiary-manager__title">

                    <?= htmlspecialchars($submitLabel) ?>

                </h3>

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
                            name="first_name" data-type="personName" value="<?= htmlspecialchars($editingBeneficiary["first_name"] ?? "") ?>">

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
                            name="middle_name" data-type="personName" value="<?= htmlspecialchars($editingBeneficiary["middle_name"] ?? "") ?>">

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
                            name="last_name" data-type="personName" value="<?= htmlspecialchars($editingBeneficiary["last_name"] ?? "") ?>">

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
                            name="suffix" data-type="suffix" value="<?= htmlspecialchars($editingBeneficiary["suffix"] ?? "") ?>">

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
                            name="relationship" value="<?= htmlspecialchars($editingBeneficiary["relationship"] ?? "") ?>">

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
                            name="birth_date" value="<?= htmlspecialchars($editingBeneficiary["birth_date"] ?? "") ?>">

                    </div>
                </div>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn--secondary">

                        <?= htmlspecialchars($submitLabel) ?>

                    </button>
                    <?php if ($isEditing): ?>
                        <a
                            href="/members/create?step=beneficiaries"
                            class="btn btn--ghost">
                            Cancel
                        </a>
                    <?php endif; ?>


                </div>

            </div>

        </form>

        <hr>

        <!-- ========================================================= -->
        <!-- Beneficiary List -->
        <!-- ========================================================= -->

        <div class="beneficiary-manager__list">

            <h3>

                Beneficiary List

            </h3>

            <?php if (empty($beneficiaries)): ?>

                <div class="beneficiary-manager__empty">

                    No beneficiaries added.

                </div>

            <?php else: ?>

                <?php foreach ($beneficiaries as $index => $beneficiary): ?>

                    <div class="beneficiary-card">

                        <div class="beneficiary-card__content">

                            <strong>

                                <?= htmlspecialchars(
                                    trim(
                                        implode(
                                            ' ',
                                            array_filter([
                                                $beneficiary['first_name'] ?? '',
                                                $beneficiary['middle_name'] ?? '',
                                                $beneficiary['last_name'] ?? '',
                                                $beneficiary['suffix'] ?? '',
                                            ])
                                        )
                                    )
                                ) ?>

                            </strong>

                            <div>

                                <?= htmlspecialchars(
                                    $beneficiary['relationship'] ?? ''
                                ) ?>

                            </div>

                        </div>

                        <div class="beneficiary-card__actions">

                            <a


                                href="/members/create?step=beneficiaries&edit=<?= $index ?>"


                                class="btn btn--secondary btn--sm">


                                Edit


                            </a>



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