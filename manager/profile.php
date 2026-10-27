<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

requirePermission(PERM_PROFILE_UPDATE);

$user = currentUser();

if ($user === null) {
    logout();
    setFlash('error', 'Your account no longer exists.');
    redirect(url('auth/login.php'));
}

$errors = [];
$username = (string) $user['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');

    if (!csrfValid()) {
        $errors[] = 'The form expired. Please try again.';
    }

    $errors = array_merge($errors, usernameErrors($username));

    $changingPassword = $newPassword !== '';

    if ($changingPassword) {
        if ($currentPassword === '' || !password_verify($currentPassword, (string) $user['password_hash'])) {
            $errors[] = 'The current password is not correct.';
        }
        $errors = array_merge($errors, newPasswordErrors($newPassword));
    }

    if ($errors === []) {
        try {
            updateUser(
                (int) $user['id'],
                $username,
                $changingPassword ? hashPassword($newPassword) : null
            );

            $_SESSION['username'] = $username;

            if ($changingPassword) {
                // Keep this session, revoke the others.
                deleteSessionsForUser((int) $user['id'], currentSessionId());
            }

            setFlash('success', $changingPassword
                ? 'Profile updated and password changed.'
                : 'Profile updated.');
            redirect(url('manager/profile.php'));
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Username is already taken.'
                : 'Update failed.';
        }
    }
}

renderHeader('My profile');
?>
<p class="small muted"><a href="<?= e(url('manager/dashboard.php')) ?>">&larr; Manager dashboard</a></p>
<h1>My profile</h1>
<p class="lead">Manager feature 2 of 2 — requires the <code>profile.update</code> permission.</p>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Account data</h2>
    <table>
        <tr><th>ID</th><td><?= (int) $user['id'] ?></td></tr>
        <tr><th>Username</th><td><?= e((string) $user['username']) ?></td></tr>
        <tr><th>Role</th><td><span class="badge role-<?= e((string) $user['role']) ?>"><?= e(roleLabel((string) $user['role'])) ?></span></td></tr>
        <tr><th>Created</th><td><?= e((string) $user['created_at']) ?></td></tr>
        <tr><th>Permissions</th><td>
            <?php foreach (permissions() as $permission): ?>
                <span class="badge"><?= e($permission) ?></span>
            <?php endforeach; ?>
        </td></tr>
    </table>
</div>

<div class="card" style="max-width: 460px;">
    <h2>Update my data</h2>
    <form method="POST" action="<?= e(url('manager/profile.php')) ?>">
        <?= csrfField() ?>

        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required minlength="3" maxlength="20"
                   pattern="[a-zA-Z0-9_]+" value="<?= e($username) ?>">
        </div>

        <div class="field">
            <label for="current_password">Current password (only to change the password)</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password">
        </div>

        <div class="field">
            <label for="new_password">New password (leave empty to keep the current one)</label>
            <input id="new_password" name="new_password" type="password" minlength="8" maxlength="72"
                   autocomplete="new-password">
        </div>

        <button class="btn" type="submit">Save changes</button>
    </form>
</div>
<?php renderFooter();
