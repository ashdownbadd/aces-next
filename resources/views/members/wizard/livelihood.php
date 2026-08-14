<?php

declare(strict_types=1);

?>

<form
    method="POST"
    action="/members/create?step=livelihood">

    <div class="form-section">

        <?php

        $sectionTitle = 'Livelihood Information';
        $sectionDescription = 'Provide the member\'s primary source of livelihood.';

        require __DIR__ . '/../partials/section-header.php';

        ?>

        <div class="form-grid">

            <div class="form-group">

                <label
                    class="form-label"
                    for="employment_status">

                    Employment Status

                </label>

                <select
                    id="employment_status"
                    class="input"
                    name="employment_status"
                    required>

                    <option value="">
                        Select Employment Status
                    </option>

                    <option
                        value="employed"
                        <?= ($livelihood['employment_status'] ?? '') === 'employed'
                            ? 'selected'
                            : '' ?>>

                        Employed

                    </option>

                    <option
                        value="self_employed"
                        <?= ($livelihood['employment_status'] ?? '') === 'self_employed'
                            ? 'selected'
                            : '' ?>>

                        Self-employed

                    </option>

                    <option
                        value="business_owner"
                        <?= ($livelihood['employment_status'] ?? '') === 'business_owner'
                            ? 'selected'
                            : '' ?>>

                        Business Owner

                    </option>

                    <option
                        value="ofw"
                        <?= ($livelihood['employment_status'] ?? '') === 'ofw'
                            ? 'selected'
                            : '' ?>>

                        OFW

                    </option>

                    <option
                        value="retired"
                        <?= ($livelihood['employment_status'] ?? '') === 'retired'
                            ? 'selected'
                            : '' ?>>

                        Retired

                    </option>

                    <option
                        value="student"
                        <?= ($livelihood['employment_status'] ?? '') === 'student'
                            ? 'selected'
                            : '' ?>>

                        Student

                    </option>

                    <option
                        value="unemployed"
                        <?= ($livelihood['employment_status'] ?? '') === 'unemployed'
                            ? 'selected'
                            : '' ?>>

                        Unemployed

                    </option>

                </select>

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="occupation">

                    Occupation

                </label>

                <input
                    id="occupation"
                    class="input"
                    type="text"
                    name="occupation"
                    data-type="title"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                                $livelihood['occupation'] ?? ''
                            ) ?>">

            </div>

            <div class="form-group form-group--full">

                <label
                    class="form-label"
                    for="employer">

                    Employer / Business Name

                </label>

                <input
                    id="employer"
                    class="input"
                    type="text"
                    name="employer"
                    data-type="title"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                                $livelihood['employer'] ?? ''
                            ) ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="monthly_income">

                    Monthly Income

                </label>

                <input
                    id="monthly_income"
                    class="input"
                    type="text"
                    name="monthly_income"
                    inputmode="decimal"
                    autocomplete="off"
                    data-type="money"
                    value="<?= htmlspecialchars(
                                ($livelihood['monthly_income'] ?? '') !== ''
                                    ? number_format(
                                        (float) ($livelihood['monthly_income'] ?? 0),
                                        2,
                                        '.',
                                        ','
                                    )
                                    : ''
                            ) ?>">

            </div>

        </div>

    </div>

    <?php

    $previousStep = 'address';
    $submitLabel = 'Next →';

    require __DIR__ . '/../partials/wizard-navigation.php';

    ?>

</form>