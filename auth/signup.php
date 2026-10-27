<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

if (isAuthenticated()) {
    redirect(dashboardUrl());
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrfValid()) {
        $errors[] = 'The form expired. Please try again.';
    }

    $errors = array_merge($errors, usernameErrors($username), newPasswordErrors($password));

    if ($errors === []) {
        try {
            // Public signup always creates the least privileged role: the role is
            // taken from the code, never from the request.
            createUser($username, hashPassword($password), ROLE_MANAGER);
            setFlash('success', 'Account created. You can sign in now.');
            redirect(url('auth/login.php'));
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Username is already taken.'
                : 'Signup failed.';
        }
    }
}

renderHeader('Sign up');
?>
<h1>Create an account</h1>
<p class="lead">New accounts receive the <strong>manager</strong> role. Administrator accounts are created by an administrator.</p>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card" style="max-width: 420px;">
    <form method="POST" action="<?= e(url('auth/signup.php')) ?>">
        <?= csrfField() ?>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required minlength="3" maxlength="20"
                   pattern="[a-zA-Z0-9_]+" value="<?= e($username) ?>" autocomplete="username" autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required minlength="8" maxlength="72"
                   autocomplete="new-password">
            <p class="small muted">At least 8 characters.</p>
        </div>
        <button class="btn" type="submit">Sign up</button>
        <a class="btn btn-secondary" href="<?= e(url('auth/login.php')) ?>">I already have an account</a>
    </form>
</div>
<?php renderFooter();
