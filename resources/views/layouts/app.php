<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="google" content="notranslate">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'ACES'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="/css/app.css">

</head>

<body>

    <div class="app">

        <?= $view->partial('partials.header') ?>

        <main class="app__content">
            <?= $content ?>
        </main>

    </div>

    <script src="/js/wizard.js"></script>
    <script src="/js/live-search.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/critical-action.js"></script>
    <script src="/js/unsaved-changes.js"></script>

</body>

</html>
