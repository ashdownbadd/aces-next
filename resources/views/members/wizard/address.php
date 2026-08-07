<?php

declare(strict_types=1);

?>

<form
    method="POST"
    action="/members/create?step=address">

    <div class="form-section">

        <?php

        $sectionTitle = 'Address Information';

        $sectionDescription = 'Enter the member\'s residential address.';

        require __DIR__ . '/../partials/section-header.php';

        ?>

        <div class="form-grid">

            <div class="form-group">

                <label
                    class="form-label"
                    for="house_number">

                    House No.

                </label>

                <input
                    id="house_number"
                    class="input"
                    type="text"
                    name="house_number"
                    autocomplete="address-line1"
                    maxlength="100"
                    value="<?= htmlspecialchars($address['house_number'] ?? '') ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="street">

                    Street

                </label>

                <input
                    id="street"
                    class="input"
                    type="text"
                    name="street"
                    data-type="title"
                    maxlength="150"
                    autocomplete="address-line2"
                    required
                    value="<?= htmlspecialchars($address['street'] ?? '') ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="barangay">

                    Barangay

                </label>

                <input
                    id="barangay"
                    class="input"
                    type="text"
                    name="barangay"
                    data-type="title"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($address['barangay'] ?? '') ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="city">

                    City / Municipality

                </label>

                <input
                    id="city"
                    class="input"
                    type="text"
                    name="city"
                    data-type="title"
                    maxlength="100"
                    autocomplete="address-level2"
                    required
                    value="<?= htmlspecialchars($address['city'] ?? '') ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="province">

                    Province

                </label>

                <input
                    id="province"
                    class="input"
                    type="text"
                    name="province"
                    data-type="title"
                    maxlength="100"
                    autocomplete="address-level1"
                    required
                    value="<?= htmlspecialchars($address['province'] ?? '') ?>">

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="zip_code">

                    ZIP Code

                </label>

                <input
                    id="zip_code"
                    class="input"
                    type="text"
                    name="zip_code"
                    data-type="zip"
                    data-max-length="4"
                    inputmode="numeric"
                    maxlength="4"
                    autocomplete="postal-code"
                    required
                    value="<?= htmlspecialchars($address['zip_code'] ?? '') ?>">

            </div>

        </div>

    </div>

    <?php

    $previousStep = 'contact';
    $submitLabel = 'Next →';

    require __DIR__ . '/../partials/wizard-navigation.php';

    ?>

</form>