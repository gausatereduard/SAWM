<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

if (isAuthenticated()) {
    redirect(dashboardUrl());
}

$errors = [];
$username = '';
$registered = isset($_GET['registered']);
$loggedOut = isset($_GET['logged_out']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrfValid()) {
        $errors[] = 'The form expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $errors[] = 'Both username and password are required.';
    } elseif (login($username, $password)) {
        // Role-based landing page: administrator and manager land on different dashboards.
        redirect(dashboardUrl());
    } else {
        $errors[] = 'Invalid username or password.';
    }
}

renderHeader('Sign in');
?>
<h1>Sign in</h1>
<p class="lead">The administrator and manager areas are restricted and require a successful authentication.</p>

<?php if ($registered): ?>
    <div class="alert alert-success">Account created. You can sign in now.</div>
<?php endif; ?>
<?php if ($loggedOut): ?>
    <div class="alert alert-info">You have been signed out.</div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card" style="max-width: 420px;">
    <form method="POST" action="<?= e(url('auth/login.php')) ?>">
        <?= csrfField() ?>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required maxlength="20"
                   value="<?= e($username) ?>" autocomplete="username" autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn" type="submit">Sign in</button>
        <a class="btn btn-secondary" href="<?= e(url('auth/signup.php')) ?>">Create an account</a>
    </form>
</div>

<p class="small muted">Demo accounts: <code>admin</code> / <code>Admin123!</code> (administrator) and
<code>manager</code> / <code>Manager123!</code> (manager).</p>
<?php renderFooter();
