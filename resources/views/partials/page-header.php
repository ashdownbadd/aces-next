<div class="page-header">

    <div>

        <h1 class="page-header__title">

            <?= htmlspecialchars($title) ?>

        </h1>

        <?php if (! empty($description)) : ?>

            <p class="page-header__description">

                <?= htmlspecialchars($description) ?>

            </p>

        <?php endif; ?>

    </div>

</div>