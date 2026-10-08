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

$highestCompletedStepIndex =
    isset($highestCompletedStepIndex)
        ? (int) $highestCompletedStepIndex
        : $currentStepIndex;

$validationError = $validationError ?? null;

?>

<div class="wizard-layout">

    <div class="member-registration__breadcrumb">
        <a href="/members">Members</a>
        <span>/</span>
        <span><?= $isEditing ? 'Edit Member' : 'Register Member' ?></span>
    </div>

    <div class="member-registration__header">
<h1>
            <?= $isEditing ? 'Edit Member' : 'Register Member' ?>
        </h1>

        <p>
            <?= $isEditing
                ? 'Update the member information by section.'
                : 'Create a new cooperative member.' ?>
        </p>
    </div>

    <?php if ($validationError !== null): ?>
        <div class="alert alert--error" role="alert">
            <?= htmlspecialchars($validationError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="wizard">

        <nav
            class="wizard__steps"
            aria-label="Member registration steps">

            <?php
            $wizardIcons = [
                'membership' => '<path d="M4 7.5h16v10H4z"/><path d="M8 7.5V5h8v2.5"/>',
                'personal' => '<circle cx="12" cy="8" r="3"/><path d="M5.5 19a6.5 6.5 0 0 1 13 0"/>',
                'contact' => '<path d="M6.5 4.5h3l1.5 3.5-2 1.5a12 12 0 0 0 5 5l1.5-2 3.5 1.5v3c0 1-1 2-2 2C10.4 19 5 13.6 5 7c0-1.5.5-2.5 1.5-2.5z"/>',
                'address' => '<path d="M12 20s6-5.1 6-10a6 6 0 1 0-12 0c0 4.9 6 10 6 10z"/><circle cx="12" cy="10" r="2"/>',
                'livelihood' => '<rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16M10 12v2h4v-2"/>',
                'education' => '<path d="m3 9 9-4 9 4-9 4-9-4z"/><path d="M7 11v4c2.8 2.2 7.2 2.2 10 0v-4M21 9v5"/>',
                'beneficiaries' => '<circle cx="9" cy="8" r="2.5"/><circle cx="16.5" cy="9" r="2"/><path d="M4 18a5 5 0 0 1 10 0M14 18a4 4 0 0 1 7 0"/>',
                'review' => '<path d="M7 3.5h10v17H7z"/><path d="M9.5 7.5h5M9.5 11h5M9.5 14.5l1.5 1.5 3-3"/>',
            ];
            ?>

            <?php foreach ($steps as $index => $wizardStep): ?>

                <?php
                $stepKey = (string) $wizardStep['key'];

                $isActive = $stepKey === $step;
                $isComplete = ! $isActive
                    && $index <= $highestCompletedStepIndex;

                $isNext = ! $isEditing
                    && ! $isActive
                    && $index === ($highestCompletedStepIndex + 1);

                $isLocked = ! $isActive
                    && ! $isEditing
                    && $index > ($highestCompletedStepIndex + 1);

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

                        <span class="wizard__indicator" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6z" />
                            </svg>
                        </span>

                        <span class="wizard__step-content">
                            <span class="wizard__label">
                                <?= htmlspecialchars($wizardStep['label']) ?>
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

                        <span class="wizard__indicator" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <?php if ($isComplete): ?>
                                    <path d="m5 12 4 4L19 6" />
                                <?php else: ?>
                                    <?= $wizardIcons[$stepKey] ?? '<circle cx="12" cy="12" r="4" />' ?>
                                <?php endif; ?>
                            </svg>
                        </span>

                        <span class="wizard__step-content">
                            <span class="wizard__label">
                                <?= htmlspecialchars($wizardStep['label']) ?>
                            </span>
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
