<?php

declare(strict_types=1);

?>

<div class="form-actions">

    <?php if (! empty($previousStep)): ?>

        <a
            href="/members/create?step=<?= urlencode($previousStep) ?>"
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