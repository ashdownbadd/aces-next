<?php

declare(strict_types=1);

?>

<div class="form-actions">

    <?php if (! empty($previousStep)): ?>

        <?php
        $previousQuery = [
            'step' => $previousStep,
        ];

        if (! empty($isEditing) && ! empty($editMemberId)) {
            $previousQuery['edit'] = (int) $editMemberId;
        }
        ?>

        <a
            href="/members/create?<?= http_build_query($previousQuery) ?>"
            class="btn btn--secondary">
            ← Previous
        </a>

    <?php else: ?>

        <span></span>

    <?php endif; ?>

    <button
        type="submit"
        class="btn btn--primary">
        <?= htmlspecialchars($submitLabel ?? 'Next →') ?>
    </button>

</div>
