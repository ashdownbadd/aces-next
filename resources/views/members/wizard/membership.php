<?php

declare(strict_types=1);

?>

<form
    method="POST"
    action="/members/create?step=membership">

    <div class="form-section">

        <?php

        $sectionTitle = 'Membership Information';

        $sectionDescription = 'Enter the member\'s membership details.';

        require __DIR__ . '/../partials/section-header.php';

        ?>

        <div class="form-grid">

            <div class="form-group">

                <label
                    class="form-label"
                    for="member_number">

                    Member Number Preview

                </label>

                <input
                    id="member_number"
                    class="input form-input--readonly"
                    type="text"
                    value="<?= htmlspecialchars(
                        $memberNumberPreview
                            ?? ($membership['member_number'] ?? '—')
                    ) ?>"
                    readonly
                    aria-describedby="member-number-preview-note">

                <small
                    id="member-number-preview-note"
                    class="form-help">

                    Preview only; the final number is assigned when registration is completed.

                </small>

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="membership_date">

                    Membership Date

                </label>

                <input
                    id="membership_date"
                    class="input"
                    type="date"
                    name="membership_date"
                    data-type="birthdate"
                    required
                    value="<?= htmlspecialchars($membership['membership_date'] ?? date('Y-m-d')) ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="member_status">

                    Status

                </label>

                <input
                    id="member_status"
                    class="input form-input--readonly"
                    type="text"
                    value="<?= htmlspecialchars(
                        $membership['status'] ?? 'Pending',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    readonly>

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="membership_type">

                    Membership Type

                </label>

                <select
                    id="membership_type"
                    class="input"
                    name="membership_type"
                    required>

                    <option
                        value="regular"
                        <?= ($membership['membership_type'] ?? 'regular') === 'regular'
                            ? 'selected'
                            : '' ?>>

                        Regular

                    </option>

                    <option
                        value="associate"
                        <?= ($membership['membership_type'] ?? '') === 'associate'
                            ? 'selected'
                            : '' ?>>

                        Associate

                    </option>

                </select>

            </div>

        </div>

    </div>

    <?php

    $previousStep = null;
    $submitLabel = 'Next →';

    require __DIR__ . '/../partials/wizard-navigation.php';

    ?>

</form>