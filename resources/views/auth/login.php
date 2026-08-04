<?php

$title = 'Sign In';
?>

<h1>Welcome to ACES</h1>

<p>
    Administrative Cooperative Enterprise System
</p>

<?php if (isset($error)) : ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="/login">

    <label>
        Username
    </label>

    <br>

    <input
        type="text"
        name="username"
        required>

    <br><br>

    <label>
        Password
    </label>

    <br>

    <input
        type="password"
        name="password"
        required>

    <br><br>

    <button type="submit">
        Sign In
    </button>

</form>