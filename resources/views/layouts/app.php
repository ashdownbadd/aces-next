<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'ACES'; ?></title>

    <link
        rel="stylesheet"
        href="/css/app.css">

</head>

<body>

    <div class="app">

        <?= $view->partial('partials.header') ?>

        <div class="app__body">

            <?= $view->partial('partials.sidebar') ?>

            <main class="app__content">

                <?= $content ?>

            </main>

        </div>

    </div>

    <script src="/js/wizard.js"></script>
    <script src="/js/live-search.js"></script>
    <script src="/js/app.js"></script>

</body>

</html>