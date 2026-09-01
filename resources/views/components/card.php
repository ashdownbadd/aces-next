<?php

declare(strict_types=1);

/**
 * Reusable ACES dashboard/data card shell.
 *
 * The component only provides structure. Callers provide the business content.
 *
 * $title, $value, $subtitle, $description
 * $body, $meta, $status
 * $icon, $actionUrl, $actionLabel
 * $href, $class, $variant, $hidden
 */

$className = trim(
    'c-card'
    . (! empty($variant)
        ? ' c-card--' . preg_replace('/[^a-z0-9-]/i', '', (string) $variant)
        : '')
    . (! empty($class) ? ' ' . trim((string) $class) : '')
);

$tag = ! empty($href) ? 'a' : 'article';
$hrefAttribute = ! empty($href)
    ? ' href="' . htmlspecialchars((string) $href, ENT_QUOTES, 'UTF-8') . '"'
    : '';
$hiddenAttribute = ! empty($hidden) ? ' hidden' : '';

?>

<<?= $tag ?>
    class="<?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8') ?>"
    <?= $hrefAttribute ?>
    <?= $hiddenAttribute ?>>

    <?php if (! empty($icon) || ! empty($actionUrl)): ?>

        <div class="c-card__top">

            <?php if (! empty($icon)): ?>

                <span class="c-card__icon" aria-hidden="true">
                    <i class="<?= htmlspecialchars((string) $icon, ENT_QUOTES, 'UTF-8') ?>"></i>
                </span>

            <?php endif; ?>

            <?php if (! empty($actionUrl)): ?>

                <span class="c-card__action-wrap">

                    <span
                        class="c-card__action"
                        aria-hidden="true">
                        ↗
                    </span>

                </span>

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <?php if (! empty($body)): ?>

        <div class="c-card__content c-card__custom-body">
            <?= $body ?>
        </div>

    <?php else: ?>

        <div class="c-card__content">

            <?php if (! empty($title)): ?>
                <h3 class="c-card__title">
                    <?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?>
                </h3>
            <?php endif; ?>

            <?php if ($value !== null && $value !== ''): ?>
                <strong class="c-card__value">
                    <?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            <?php endif; ?>

            <?php if (! empty($subtitle)): ?>
                <span class="c-card__subtitle">
                    <?= htmlspecialchars((string) $subtitle, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>

            <?php if (! empty($description)): ?>
                <p class="c-card__description">
                    <?= htmlspecialchars((string) $description, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

        </div>

    <?php endif; ?>

    <?php if (! empty($meta)): ?>

        <div class="c-card__meta">
            <?= $meta ?>
        </div>

    <?php endif; ?>

    <?php if (! empty($status)): ?>

        <div class="c-card__status">
            <?= $status ?>
        </div>

    <?php endif; ?>

</<?= $tag ?>>
