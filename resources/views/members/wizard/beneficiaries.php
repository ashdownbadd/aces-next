<form
    method="POST"
    action="/members/create?step=<?= urlencode($step) ?>">

    <div class="form-section">

        <?php

        $sectionTitle = 'Beneficiaries';

        $sectionDescription = 'Add the people who will become the member\'s beneficiaries.';

        require __DIR__ . '/../partials/section-header.php';

        ?>

        <div class="beneficiary-manager">

            <div class="beneficiary-manager__toolbar">

                <button
                    type="button"
                    class="btn btn--primary">
                    + Add Beneficiary
                </button>

            </div>

            <div class="beneficiary-manager__empty">

                <h3>

                    No beneficiaries added yet.

                </h3>

                <p>

                    Click "Add Beneficiary" to begin.

                </p>

            </div>

        </div>

        <?php require __DIR__ . '/../partials/wizard-navigation.php'; ?>

    </div>

</form>