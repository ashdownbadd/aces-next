<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($title ?? 'ACES Next') ?></title>

</head>

<body>

    <?= $view->partial('partials.header') ?>

    <?= $view->partial('partials.navbar') ?>

    <main>

        <?= $content ?>

    </main>

    <?= $view->partial('partials.footer') ?>

</body>

</html>