<?php

$title = 'Sign In';

?>

<div class="c-login">

    <div class="c-login__panel">

        <div class="c-login__eyebrow">
            <span class="c-login__line"></span>
            Cooperative Management
            <span class="c-login__line c-login__line--right"></span>
        </div>

        <div class="c-login__logo">ACES</div>
        <div class="c-login__sub">Member Portal</div>

        <?php if (!empty($error)): ?>

            <div class="c-login__error">
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="/login">

            <div class="c-login__field">
                <label class="c-login__label" for="username">
                    Username
                </label>

                <input
                    id="username"
                    class="c-login__input"
                    type="text"
                    name="username"
                    required
                    autofocus>
            </div>

            <div class="c-login__field">
                <label class="c-login__label" for="password">
                    Password
                </label>

                <input
                    id="password"
                    class="c-login__input"
                    type="password"
                    name="password"
                    required>
            </div>

            <button
                type="submit"
                class="c-login__button">
                Sign In
            </button>

        </form>

    </div>

</div>
