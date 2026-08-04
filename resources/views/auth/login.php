<?php

$title = 'Sign In';

?>

<div class="auth">

    <div class="card auth__card">

        <div class="auth__header">

            <h1 class="auth__title">
                ACES
            </h1>

            <p class="auth__subtitle">
                Administrative Cooperative Enterprise System
            </p>

        </div>

        <?php if (isset($error)) : ?>

            <div class="alert alert--danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            action="/login">

            <div class="input-group">

                <label class="input-label">
                    Username
                </label>

                <input
                    class="input"
                    type="text"
                    name="username"
                    required
                    autofocus>

            </div>

            <div class="input-group">

                <label class="input-label">
                    Password
                </label>

                <input
                    class="input"
                    type="password"
                    name="password"
                    required>

            </div>

            <button
                class="btn btn--primary btn--block"
                type="submit">
                Sign In
            </button>

        </form>

    </div>

</div>