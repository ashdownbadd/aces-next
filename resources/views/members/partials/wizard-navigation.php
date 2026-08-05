<div class="form-actions">

    <?php if ($previousStep !== null): ?>

        <a
            href="/members/create?step=<?= urlencode($previousStep) ?>"
            class="btn btn--secondary">
            ← Back
        </a>

    <?php else: ?>

        <button
            class="btn btn--secondary"
            type="button"
            disabled>
            ← Back
        </button>

    <?php endif; ?>

    <?php if ($nextStep !== null): ?>

        <button
            class="btn btn--primary"
            type="submit">
            Next →
        </button>

    <?php else: ?>

        <button
            class="btn btn--primary"
            type="submit">
            Register Member
        </button>

    <?php endif; ?>

</div>