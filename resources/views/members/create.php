<?php

declare(strict_types=1);

$title = $isEditing
    ? 'Edit Member'
    : 'Register Member';

$currentStepIndex = 0;

foreach ($steps as $index => $wizardStep) {
    if ($wizardStep['key'] === $step) {
        $currentStepIndex = $index;
        break;
    }
}

?>

<div class="wizard-layout">

    <div class="member-registration__breadcrumb">
        <a href="/members">Members</a>
        <span>/</span>
        <span><?= $isEditing ? 'Edit Member' : 'Register Member' ?></span>
    </div>

    <div class="member-registration__header">
        <span class="member-registration__eyebrow">Member Administration</span>

        <h1>
            <?= $isEditing ? 'Edit Member' : 'Register Member' ?>
        </h1>

        <p>
            <?= $isEditing
                ? 'Update the member information by section.'
                : 'Create a new cooperative member.' ?>
        </p>
    </div>

    <div class="wizard">

        <nav
            class="wizard__steps"
            aria-label="Member registration steps">

            <?php foreach ($steps as $index => $wizardStep): ?>

                <?php
                $stepKey = (string) $wizardStep['key'];

                $isActive = $stepKey === $step;
                $isComplete = $index < $currentStepIndex;
                $isNext = ! $isEditing
                    && $index === ($currentStepIndex + 1);
                $isLocked = ! $isEditing
                    && $index > ($currentStepIndex + 1);

                $query = ['step' => $stepKey];

                if ($isEditing && ! empty($editMemberId)) {
                    $query['edit'] = (int) $editMemberId;
                }

                $stepUrl =
                    '/members/create?' . http_build_query($query);
                ?>

                <?php if ($isLocked): ?>

                    <span
                        class="wizard__step wizard__step--locked"
                        aria-disabled="true">

                        <span class="wizard__indicator">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6z" />
                            </svg>
                        </span>

                        <span class="wizard__step-content">
                            <span class="wizard__label">
                                <?= htmlspecialchars($wizardStep['label']) ?>
                            </span>

                            <span class="wizard__locked">
                                Complete previous step
                            </span>
                        </span>

                    </span>

                <?php else: ?>

                    <a
                        href="<?= htmlspecialchars($stepUrl) ?>"
                        class="wizard__step<?= $isActive ? ' wizard__step--active' : '' ?><?= $isComplete ? ' wizard__step--complete' : '' ?><?= $isNext ? ' wizard__step--next' : '' ?>"
                        aria-label="Go to <?= htmlspecialchars($wizardStep['label']) ?>"
                        <?= $isActive ? 'aria-current="step"' : '' ?>
                        <?= $isNext ? 'data-wizard-next' : '' ?>>

                        <span class="wizard__step-content">

                            <span class="wizard__label">
                                <?= htmlspecialchars($wizardStep['label']) ?>
                            </span>

                            <?php if ($isActive): ?>
                                <span class="wizard__current">Current step</span>
                            <?php elseif ($isNext): ?>
                                <span class="wizard__current">Next step</span>
                            <?php endif; ?>

                        </span>

                    </a>

                <?php endif; ?>

            <?php endforeach; ?>

        </nav>


    </div>

    <div class="card">

        <div class="wizard__body">
            <?php require __DIR__ . '/wizard/' . $step . '.php'; ?>
        </div>

    </div>

</div>

<script>
(() => {
    const nextStep = document.querySelector('[data-wizard-next]');
    if (!nextStep) {
        return;
    }

    nextStep.addEventListener('click', (event) => {
        const form =
            document.querySelector('.wizard__body form');

        if (!form) {
            return;
        }

        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
    });
})();
</script>
