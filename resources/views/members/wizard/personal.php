<?php

declare(strict_types=1);

?>

<div class="form-section">

    <?php

    $sectionTitle = 'Personal Information';

    $sectionDescription = 'Enter the member\'s personal information.';

    require __DIR__ . '/../partials/section-header.php';

    ?>

    <div class="form-grid">

        <div class="form-group">

            <label
                class="form-label"
                for="first_name">

                First Name

            </label>

            <input
                id="first_name"
                class="input"
                type="text"
                name="first_name"
                autocomplete="given-name"
                value="<?= htmlspecialchars($personal['first_name'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="middle_name">

                Middle Name

            </label>

            <input
                id="middle_name"
                class="input"
                type="text"
                name="middle_name"
                autocomplete="additional-name"
                value="<?= htmlspecialchars($personal['middle_name'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="last_name">

                Last Name

            </label>

            <input
                id="last_name"
                class="input"
                type="text"
                name="last_name"
                autocomplete="family-name"
                value="<?= htmlspecialchars($personal['last_name'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="suffix">

                Suffix

            </label>

            <input
                id="suffix"
                class="input"
                type="text"
                name="suffix"
                placeholder="Jr., Sr., III (optional)"
                value="<?= htmlspecialchars($personal['suffix'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="birth_date">

                Birth Date

            </label>

            <input
                id="birth_date"
                class="input"
                type="date"
                name="birth_date"
                value="<?= htmlspecialchars($personal['birth_date'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="birth_place">

                Birth Place

            </label>

            <input
                id="birth_place"
                class="input"
                type="text"
                name="birth_place"
                value="<?= htmlspecialchars($personal['birth_place'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="sex">

                Sex

            </label>

            <select
                id="sex"
                class="input"
                name="sex">

                <option value="">
                    Select Sex
                </option>

                <option
                    value="male"
                    <?= ($personal['sex'] ?? '') === 'male' ? 'selected' : '' ?>>

                    Male

                </option>

                <option
                    value="female"
                    <?= ($personal['sex'] ?? '') === 'female' ? 'selected' : '' ?>>

                    Female

                </option>

            </select>

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="civil_status">

                Civil Status

            </label>

            <select
                id="civil_status"
                class="input"
                name="civil_status">

                <option value="">
                    Select Civil Status
                </option>

                <option
                    value="single"
                    <?= ($personal['civil_status'] ?? '') === 'single' ? 'selected' : '' ?>>

                    Single

                </option>

                <option
                    value="married"
                    <?= ($personal['civil_status'] ?? '') === 'married' ? 'selected' : '' ?>>

                    Married

                </option>

                <option
                    value="widowed"
                    <?= ($personal['civil_status'] ?? '') === 'widowed' ? 'selected' : '' ?>>

                    Widowed

                </option>

                <option
                    value="separated"
                    <?= ($personal['civil_status'] ?? '') === 'separated' ? 'selected' : '' ?>>

                    Separated

                </option>

            </select>

        </div>

    </div>

</div>