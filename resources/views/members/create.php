<?php

declare(strict_types=1);

$title = 'Register Member';

?>

<div class="wizard-layout">

    <div class="member-registration__breadcrumb">

        <a href="/members">
            Members
        </a>

        <span>/</span>

        <span>
            Register Member
        </span>

    </div>

    <div class="member-registration__header">

        <h1>
            Register Member
        </h1>

        <p>
            Create a new cooperative member.
        </p>

    </div>

    <div class="wizard">

        <div class="wizard__steps">

            <?php foreach ($steps as $wizardStep): ?>

                <div
                    class="wizard__step <?= $wizardStep['key'] === $step ? 'wizard__step--active' : '' ?>">

                    <span class="wizard__indicator"></span>

                    <span class="wizard__label">

                        <?= htmlspecialchars($wizardStep['label']) ?>

                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <div class="card">

        <form
            method="POST"
            action="/members/create?step=<?= urlencode($step) ?>"
            autocomplete="off">

            <div class="wizard__body">

                <?php require __DIR__ . '/wizard/' . $step . '.php'; ?>

            </div>

            <?php require __DIR__ . '/partials/wizard-navigation.php'; ?>

        </form>

    </div>

</div>