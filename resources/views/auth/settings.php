<?php

$e = static fn(mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8',
);
?>

<section class="settings-page">
    <header class="page-header">
        <div>
            <span class="settings-page__eyebrow">Account</span>
            <h1>Profile Settings</h1>
            <p>Manage your username and password.</p>
        </div>
    </header>

    <?php if (!empty($success)): ?>
        <div class="alert alert--success" role="status" aria-live="polite">
            <?= $e($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert--error" role="alert">
            <?= $e($error) ?>
        </div>
    <?php endif; ?>

    <section class="card settings-page__card">
        <form method="POST" action="/settings" class="settings-page__form">
            <?= $view->csrfField() ?>

            <div class="settings-page__section">
                <div>
                    <h2>Account</h2>
                    <p>Update the username used to sign in.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="settings-username">Username</label>
                    <input
                        id="settings-username"
                        name="username"
                        class="input"
                        type="text"
                        value="<?= $e($user->username()) ?>"
                        minlength="3"
                        maxlength="50"
                        autocomplete="username"
                        required>
                </div>
            </div>

            <div class="settings-page__section">
                <div>
                    <h2>Password</h2>
                    <p>Leave the new password fields blank to keep your current password.</p>
                </div>

                <div class="settings-page__fields">
                    <div class="form-group">
                        <label class="form-label" for="current-password">Current password</label>
                        <input
                            id="current-password"
                            name="current_password"
                            class="input"
                            type="password"
                            autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new-password">New password</label>
                        <input
                            id="new-password"
                            name="new_password"
                            class="input"
                            type="password"
                                autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new-password-confirmation">Confirm new password</label>
                        <input
                            id="new-password-confirmation"
                            name="new_password_confirmation"
                            class="input"
                            type="password"
                                autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="settings-page__actions">
                <button type="submit" class="btn btn--primary">Save Changes</button>
            </div>
        </form>
    </section>
</section>
