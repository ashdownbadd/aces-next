<?php

declare(strict_types=1);

?>

<div class="form-section">

    <?php

    $sectionTitle = 'Contact Information';

    $sectionDescription = 'Enter the member\'s contact details.';

    require __DIR__ . '/../partials/section-header.php';

    ?>

    <div class="form-grid">

        <div class="form-group">

            <label
                class="form-label"
                for="mobile_number">

                Mobile Number

            </label>

            <input
                id="mobile_number"
                class="input"
                type="tel"
                name="mobile_number"
                placeholder="09XXXXXXXXX"
                autocomplete="tel"
                value="<?= htmlspecialchars($contact['mobile_number'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label
                class="form-label"
                for="telephone_number">

                Telephone Number

            </label>

            <input
                id="telephone_number"
                class="input"
                type="tel"
                name="telephone_number"
                placeholder="(02) 8123-4567"
                autocomplete="tel-national"
                value="<?= htmlspecialchars($contact['telephone_number'] ?? '') ?>">

        </div>

        <div class="form-group form-group--full">

            <label
                class="form-label"
                for="email_address">

                Email Address

            </label>

            <input
                id="email_address"
                class="input"
                type="email"
                name="email_address"
                placeholder="name@example.com"
                autocomplete="email"
                value="<?= htmlspecialchars($contact['email_address'] ?? '') ?>">

        </div>

    </div>

</div>