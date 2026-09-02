<?php

declare(strict_types=1);

?>

<div class="form-section">

    <div class="beneficiary-manager">

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

        $beneficiaryCount = count($beneficiaries ?? []);
        ?>

        <section
            class="beneficiary-manager__section beneficiary-manager__section--form"
            aria-labelledby="beneficiary-form-title">

            <div class="beneficiary-manager__section-header">

                <div>
                    <span class="beneficiary-manager__eyebrow">
                        <?= $isEditing ? 'Edit Entry' : 'Add Person' ?>
                    </span>

                    <h2
                        id="beneficiary-form-title"
                        class="beneficiary-manager__title">

                        <?= htmlspecialchars($submitLabel) ?>

                    </h2>

                    <p class="beneficiary-manager__description">
                        <?= $isEditing
                            ? 'Update the beneficiary details below.'
                            : 'Record a person who should be listed as a beneficiary.' ?>
                    </p>
                </div>

                <?php if ($beneficiaryCount > 0): ?>

                    <span class="beneficiary-manager__count">
                        <?= $beneficiaryCount ?>
                        <?= $beneficiaryCount === 1 ? 'Added' : 'Added' ?>
                    </span>

                <?php endif; ?>

            </div>

            <form
                method="POST"
                action="<?= htmlspecialchars($formAction) ?>"
                class="beneficiary-manager__form">

                <?php if ($isEditing): ?>

                    <input
                        type="hidden"
                        name="index"
                        value="<?= $editingIndex ?>">

                <?php endif; ?>

                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label" for="beneficiary_first_name">
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
                            value="<?= htmlspecialchars($editingBeneficiary['first_name'] ?? '') ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="beneficiary_middle_name">
                            Middle Name
                        </label>

                        <input
                            id="beneficiary_middle_name"
                            class="input"
                            type="text"
                            name="middle_name"
                            data-type="personName"
                            maxlength="100"
                            autocomplete="additional-name"
                            value="<?= htmlspecialchars($editingBeneficiary['middle_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="beneficiary_last_name">
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
                            value="<?= htmlspecialchars($editingBeneficiary['last_name'] ?? '') ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="beneficiary_suffix">
                            Suffix
                        </label>

                        <input
                            id="beneficiary_suffix"
                            class="input"
                            type="text"
                            name="suffix"
                            data-type="suffix"
                            maxlength="20"
                            value="<?= htmlspecialchars($editingBeneficiary['suffix'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="relationship">
                            Relationship
                        </label>

                        <input
                            id="relationship"
                            class="input"
                            type="text"
                            name="relationship"
                            data-type="title"
                            maxlength="100"
                            value="<?= htmlspecialchars($editingBeneficiary['relationship'] ?? '') ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="beneficiary_birth_date">
                            Birth Date
                        </label>

                        <input
                            id="beneficiary_birth_date"
                            class="input"
                            type="date"
                            name="birth_date"
                            value="<?= htmlspecialchars($editingBeneficiary['birth_date'] ?? '') ?>">
                    </div>

                </div>

                <div class="beneficiary-manager__form-actions">

                    <button type="submit" class="btn btn--secondary">
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

            </form>

        </section>

        <section
            class="beneficiary-manager__section beneficiary-manager__section--list"
            aria-labelledby="beneficiary-list-title">

            <div class="beneficiary-manager__section-header">

                <div>
                    <span class="beneficiary-manager__eyebrow">
                        Your Entries
                    </span>

                    <h2
                        id="beneficiary-list-title"
                        class="beneficiary-manager__title">

                        Added Beneficiaries

                    </h2>

                    <p class="beneficiary-manager__description">
                        Review or change the people you've added.
                    </p>
                </div>

                <?php if ($beneficiaryCount > 0): ?>

                    <span class="beneficiary-manager__count">
                        <?= $beneficiaryCount ?>
                        <?= $beneficiaryCount === 1 ? 'Person' : 'People' ?>
                    </span>

                <?php endif; ?>

            </div>

            <?php if ($beneficiaryCount === 0): ?>

                <div
                    class="beneficiary-manager__empty"
                    role="status">

                    <strong>No beneficiaries added yet.</strong>

                    <span>
                        Use the form above to add the first beneficiary.
                    </span>

                </div>

            <?php else: ?>

                <div class="beneficiary-manager__entries">

                    <?php foreach ($beneficiaries as $index => $beneficiary): ?>

                        <?php
                        $fullName = trim(
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

                        $birthDate = ! empty($beneficiary['birth_date'])
                            ? strtotime((string) $beneficiary['birth_date'])
                            : false;
                        ?>

                        <article class="beneficiary-card">

                            <div class="beneficiary-card__identity">

                                <div
                                    class="beneficiary-card__avatar"
                                    aria-hidden="true">

                                    <?= htmlspecialchars(
                                        strtoupper(
                                            substr(
                                                $fullName !== '' ? $fullName : 'B',
                                                0,
                                                1
                                            )
                                        )
                                    ) ?>

                                </div>

                                <div class="beneficiary-card__content">

                                    <strong class="beneficiary-card__name">
                                        <?= htmlspecialchars(
                                            $fullName !== ''
                                                ? $fullName
                                                : 'Unnamed beneficiary'
                                        ) ?>
                                    </strong>

                                    <span class="beneficiary-card__meta">

                                        <?= htmlspecialchars(
                                            $beneficiary['relationship']
                                                ?? 'Relationship not specified'
                                        ) ?>

                                        <?php if ($birthDate !== false): ?>

                                            <span aria-hidden="true">·</span>

                                            <?= htmlspecialchars(
                                                date('F j, Y', $birthDate)
                                            ) ?>

                                        <?php endif; ?>

                                    </span>

                                </div>

                            </div>

                            <div class="beneficiary-card__actions">

                                <a
                                    href="/members/create?step=beneficiaries&edit=<?= $index ?>"
                                    class="btn btn--ghost btn--sm">

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

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <form
            method="POST"
            action="/members/create?step=beneficiaries"
            class="beneficiary-manager__navigation">

            <?php

            $previousStep = 'education';
            $submitLabel = 'Next →';

            require __DIR__ . '/../partials/wizard-navigation.php';

            ?>

        </form>

    </div>

</div>
