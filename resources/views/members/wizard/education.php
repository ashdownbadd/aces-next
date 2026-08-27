<?php

declare(strict_types=1);

?>

<form
    method="POST"
    action="/members/create?step=education">

    <div class="form-section">

        <?php

        $sectionTitle = 'Education Information';

        $sectionDescription = 'Provide the member\'s highest educational attainment.';

        require __DIR__ . '/../partials/section-header.php';

        ?>

        <div class="form-grid">

            <div class="form-group form-group--full">

                <label
                    class="form-label"
                    for="highest_educational_attainment">

                    Highest Educational Attainment

                </label>

                <select
                    id="highest_educational_attainment"
                    class="input"
                    name="highest_educational_attainment"
                    required>

                    <option value="">
                        Select Educational Attainment
                    </option>

                    <option
                        value="no_formal_education"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'no_formal_education'
                            ? 'selected'
                            : '' ?>>

                        No Formal Education

                    </option>

                    <option
                        value="elementary"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'elementary'
                            ? 'selected'
                            : '' ?>>

                        Elementary

                    </option>

                    <option
                        value="high_school"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'high_school'
                            ? 'selected'
                            : '' ?>>

                        High School

                    </option>

                    <option
                        value="senior_high_school"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'senior_high_school'
                            ? 'selected'
                            : '' ?>>

                        Senior High School

                    </option>

                    <option
                        value="vocational"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'vocational'
                            ? 'selected'
                            : '' ?>>

                        Vocational / Technical

                    </option>

                    <option
                        value="college"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'college'
                            ? 'selected'
                            : '' ?>>

                        College

                    </option>

                    <option
                        value="postgraduate"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'postgraduate'
                            ? 'selected'
                            : '' ?>>

                        Postgraduate

                    </option>

                    <option
                        value="other"
                        <?= ($education['highest_educational_attainment'] ?? '') === 'other'
                            ? 'selected'
                            : '' ?>>

                        Other

                    </option>

                </select>

            </div>

            <div
                class="form-group"
                data-education-school>

                <label
                    class="form-label"
                    for="school_name">

                    School Attended

                </label>

                <input
                    id="school_name"
                    class="input"
                    type="text"
                    name="school_name"
                    data-type="title"
                    maxlength="150"
                    autocomplete="organization"
                    value="<?= htmlspecialchars(
                        $education['school_name'] ?? ''
                    ) ?>">

            </div>

            <div
                class="form-group"
                data-education-graduation-year>

                <label
                    class="form-label"
                    for="graduation_year">

                    Graduation Year

                </label>

                <input
                    id="graduation_year"
                    class="input"
                    type="number"
                    name="graduation_year"
                    min="1900"
                    max="<?= date('Y') ?>"
                    step="1"
                    inputmode="numeric"
                    value="<?= htmlspecialchars(
                        $education['graduation_year'] ?? ''
                    ) ?>">

            </div>

        </div>

    </div>

    <?php

    $previousStep = 'livelihood';
    $submitLabel = 'Next →';

    require __DIR__ . '/../partials/wizard-navigation.php';

    ?>

</form>