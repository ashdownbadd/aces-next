<?php

declare(strict_types=1);

?>

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

                Member Number

            </label>

            <input
                id="member_number"
                class="input form-input--readonly"
                type="text"
                value="Generated upon registration"
                readonly>

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
                value="Active"
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
                name="membership_type">

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