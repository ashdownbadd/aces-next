<?php

declare(strict_types=1);

$title = 'Create Loan';

$members = $members ?? [];

$loanTypes = [
    'Bridge Financing',
    'Investment Loan',
    'Pension Loan',
    'Productivity Loan',
    'Personal Loan',
    'Salary Loan',
    'Micro-Finance Loan',
];

$collaterals = [
    'Post-Dated Check',
    'Real Property',
    'Chattels / Movable Assets',
];

$amortizationTypes = [
    'Straight-line',
    'Diminishing balance',
    'Manual',
];

$paymentFrequencies = [
    'Monthly',
    'Bi-Monthly',
    'Weekly',
];

?>

<div class="loan-create">

    <div class="loan-create__breadcrumb">
        <a href="/loans">Loans</a>
        <span>/</span>
        <span>Create Loan</span>
    </div>

    <header class="loan-create__header">
        <div>
            <h1 class="loan-create__title">Create Loan</h1>
            <p class="loan-create__description">
                Enter the loan application details and review the computed deductions before submission.
            </p>
        </div>
    </header>

    <form
        id="loan-create-form"
        class="form"
        method="POST"
        action="/loans/create"
        enctype="multipart/form-data"
        novalidate>

        <section class="card loan-create__section">

            <div class="form-section__header">
                <h2 class="form-section__title">Member Information</h2>
                <p class="form-section__description">
                    Select the member applying for the loan.
                </p>
            </div>

            <div class="form-grid">
                <div class="form-group form-group--span-2">

                    <label
                        class="form-label"
                        for="loan-member">

                        Member
                        <span class="form-required">*</span>

                    </label>

                    <select
                        id="loan-member"
                        name="member_id"
                        class="input"
                        required>

                        <option value="">Select member</option>

                        <?php foreach ($members as $member): ?>

                            <?php
                            $memberId = (int) ($member['id'] ?? 0);
                            $memberNumber = (string) ($member['member_number'] ?? '');
                            $memberName = (string) ($member['full_name'] ?? $member['name'] ?? $member['member_name'] ?? '');
                            ?>

                            <?php if ($memberId > 0): ?>

                                <option value="<?= $memberId ?>">
                                    <?= htmlspecialchars(
                                        trim($memberNumber . ' — ' . $memberName),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                    <span class="form-help">
                        The selected member becomes the borrower for this application.
                    </span>

                </div>
            </div>

        </section>

        <section class="card loan-create__section">

            <div class="form-section__header">
                <h2 class="form-section__title">Loan Information</h2>
                <p class="form-section__description">
                    Loan type controls the available amortization and payment options.
                </p>
            </div>

            <div class="form-grid">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="loan-type">

                        Loan Type
                        <span class="form-required">*</span>

                    </label>

                    <select
                        id="loan-type"
                        name="loan_type"
                        class="input"
                        required>

                        <option value="">Select loan type</option>

                        <?php foreach ($loanTypes as $loanType): ?>

                            <option value="<?= htmlspecialchars($loanType, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($loanType, ENT_QUOTES, 'UTF-8') ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="loan-collateral">

                        Collateral
                        <span class="form-required">*</span>

                    </label>

                    <select
                        id="loan-collateral"
                        name="collateral"
                        class="input"
                        required>

                        <option value="">Select collateral</option>

                        <?php foreach ($collaterals as $collateral): ?>

                            <option value="<?= htmlspecialchars($collateral, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($collateral, ENT_QUOTES, 'UTF-8') ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="principal-amount">

                        Principal Amount
                        <span class="form-required">*</span>

                    </label>

                    <input
                        id="principal-amount"
                        name="principal_amount"
                        class="input"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        data-loan-money
                        required>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="interest-rate">

                        Interest Rate
                        <span class="form-required">*</span>

                    </label>

                    <div class="loan-create__input-suffix">
                        <input
                            id="interest-rate"
                            name="interest_rate"
                            class="input"
                            type="text"
                            inputmode="decimal"
                            value="2"
                            autocomplete="off"
                            data-loan-rate
                            required>

                        <span class="loan-create__suffix">%</span>
                    </div>

                    <span class="form-help">
                        Defaults to 2% for standard loans and 5% for Micro-Finance.
                        Editing this field disables automatic rate changes until the loan type changes.
                    </span>

                </div>

                <div
                    class="form-group"
                    data-standard-amortization>

                    <label
                        class="form-label"
                        for="amortization-type">

                        Amortization Type
                        <span class="form-required">*</span>

                    </label>

                    <select
                        id="amortization-type"
                        name="amortization_type"
                        class="input"
                        required>

                        <option value="">Select amortization</option>

                        <?php foreach ($amortizationTypes as $type): ?>

                            <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div
                    class="form-group loan-create__conditional"
                    data-payment-frequency>

                    <label
                        class="form-label"
                        for="payment-frequency">

                        Payment Frequency
                        <span class="form-required">*</span>

                    </label>

                    <select
                        id="payment-frequency"
                        name="payment_frequency"
                        class="input">

                        <option value="">Select frequency</option>

                        <?php foreach ($paymentFrequencies as $frequency): ?>

                            <option value="<?= htmlspecialchars($frequency, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($frequency, ENT_QUOTES, 'UTF-8') ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div
                    class="form-group loan-create__conditional"
                    data-manual-payment>

                    <label
                        class="form-label"
                        for="manual-payment">

                        Manual Payment
                        <span class="form-required">*</span>

                    </label>

                    <input
                        id="manual-payment"
                        name="manual_payment"
                        class="input"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        data-loan-money>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="terms-months">

                        Terms
                        <span class="form-required">*</span>

                    </label>

                    <div class="loan-create__input-suffix">

                        <input
                            id="terms-months"
                            name="terms_months"
                            class="input"
                            type="number"
                            min="1"
                            step="1"
                            required>

                        <span class="loan-create__suffix">months</span>

                    </div>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="start-date">

                        Start Date
                        <span class="form-required">*</span>

                    </label>

                    <input
                        id="start-date"
                        name="start_date"
                        class="input"
                        type="date"
                        required>

                </div>

            </div>

        </section>

        <section class="card loan-create__section">

            <div class="form-section__header">
                <h2 class="form-section__title">Computed Deductions</h2>
                <p class="form-section__description">
                    These values update automatically from the principal and term.
                </p>
            </div>

            <div class="loan-create__deductions">

                <div class="loan-create__deduction">
                    <span>Processing Fee</span>
                    <strong data-processing-fee>₱0.00</strong>
                </div>

                <div class="loan-create__deduction">
                    <span>Insurance</span>
                    <strong data-insurance>₱0.00</strong>
                </div>

                <div class="loan-create__deduction">
                    <span>Notarial Fee</span>
                    <strong>₱400.00</strong>
                </div>

                <div class="loan-create__deduction loan-create__deduction--total">
                    <span>Net Proceeds</span>
                    <strong data-net-proceeds>₱0.00</strong>
                </div>

            </div>

            <p class="form-help loan-create__calculation-note" data-calculation-note>
                Enter both principal and terms to calculate deductions.
            </p>

        </section>

        <section
            class="card loan-create__section loan-create__conditional"
            data-real-property>

            <div class="form-section__header">
                <h2 class="form-section__title">Real Property Information</h2>
                <p class="form-section__description">
                    These fields are used only when Real Property is selected as collateral.
                </p>
            </div>

            <div class="form-grid">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="tct-no">

                        TCT No.

                    </label>

                    <input
                        id="tct-no"
                        name="tct_no"
                        class="input"
                        type="text"
                        maxlength="100">

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="tax-declaration-no">

                        Tax Declaration No.

                    </label>

                    <input
                        id="tax-declaration-no"
                        name="tax_declaration_no"
                        class="input"
                        type="text"
                        maxlength="100">

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="real-property-payment-status">

                        Real Property Payments Status

                    </label>

                    <select
                        id="real-property-payment-status"
                        name="real_property_payment_status"
                        class="input">

                        <option value="">Select status</option>
                        <option value="Updated">Updated</option>
                        <option value="Not Updated">Not Updated</option>
                        <option value="Pending">Pending</option>

                    </select>

                </div>

                <div class="form-group form-group--span-2">

                    <label
                        class="form-label"
                        for="undertaking">

                        Undertaking
                    </label>

                    <input
                        id="undertaking"
                        name="undertaking"
                        class="input"
                        type="file"
                        accept="application/pdf">

                    <span class="form-help">
                        PDF only. Document storage will be wired in the backend phase.
                    </span>

                </div>

                <div class="form-group form-group--span-2">

                    <label
                        class="form-label"
                        for="assignment-of-deed">

                        Assignment of Deed of Rights
                    </label>

                    <input
                        id="assignment-of-deed"
                        name="assignment_of_deed"
                        class="input"
                        type="file"
                        accept="application/pdf">

                    <span class="form-help">
                        PDF only. Document storage will be wired in the backend phase.
                    </span>

                </div>

            </div>

        </section>

        <section class="card loan-create__section">

            <div class="form-section__header">
                <h2 class="form-section__title">Notes</h2>
                <p class="form-section__description">
                    Optional notes for the loan application.
                </p>
            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="loan-notes">

                    Notes

                </label>

                <textarea
                    id="loan-notes"
                    name="notes"
                    class="input"
                    rows="5"
                    maxlength="5000"></textarea>

            </div>

        </section>

        <div
            class="loan-create__actions"
            data-loan-actions>

            <a
                href="/loans"
                class="btn btn--secondary">

                Cancel

            </a>

            <button
                type="button"
                class="btn btn--primary"
                data-review-loan>

                Review Application →

            </button>

        </div>

        <div
            class="alert alert--error loan-create__validation"
            data-loan-validation
            hidden
            role="alert"
            aria-live="polite"></div>

    </form>

</div>

<script src="/js/loan-create.js"></script>
