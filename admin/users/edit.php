<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helper/bootstrap.php';

requirePermission(PERM_USERS_UPDATE);

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$user = $id > 0 ? getUserById($id) : null;

if ($user === null) {
    http_response_code(404);
    renderHeader('User not found');
    ?>
    <h1>User not found</h1>
    <p class="lead">No account matches id <code><?= $id ?></code>.</p>
    <p><a class="btn" href="<?= e(url('admin/users/index.php')) ?>">Back to accounts</a></p>
    <?php
    renderFooter();
    exit;
}

$errors = [];
$username = (string) $user['username'];
$role = (string) $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $role = (string) ($_POST['role'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');

    if (!csrfValid()) {
        $errors[] = 'The form expired. Please try again.';
    }

    $errors = array_merge($errors, usernameErrors($username));

    if (!roleExists($role)) {
        $errors[] = 'Unknown role.';
    } elseif ($id === currentUserId() && $role !== ROLE_ADMINISTRATOR) {
        $errors[] = 'You cannot remove your own administrator role.';
    }

    if ($newPassword !== '') {
        $errors = array_merge($errors, newPasswordErrors($newPassword));
    }

    if ($errors === []) {
        try {
            updateUser(
                $id,
                $username,
                $newPassword !== '' ? hashPassword($newPassword) : null,
                $role
            );

            // A password change invalidates the target user's other sessions.
            if ($newPassword !== '') {
                deleteSessionsForUser($id, $id === currentUserId() ? currentSessionId() : null);
            }

            if ($id === currentUserId()) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['permissions'] = getPermissionsForRole($role);
            }

            setFlash('success', 'Account "' . $username . '" updated.');
            redirect(url('admin/users/index.php'));
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Username is already taken.'
                : 'Update failed.';
        }
    }
}

renderHeader('Edit account');
?>
<p class="small muted"><a href="<?= e(url('admin/users/index.php')) ?>">&larr; User accounts</a></p>
<h1>Edit account #<?= (int) $user['id'] ?></h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card" style="max-width: 460px;">
    <form method="POST" action="<?= e(url('admin/users/edit.php')) ?>">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">

        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required minlength="3" maxlength="20"
                   pattern="[a-zA-Z0-9_]+" value="<?= e($username) ?>">
        </div>

        <div class="field">
            <label for="role">Role</label>
            <select id="role" name="role">
                <?php foreach (getRolesMap() as $roleName => $label): ?>
                    <option value="<?= e($roleName) ?>" <?= $roleName === $role ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="new_password">New password (leave empty to keep the current one)</label>
            <input id="new_password" name="new_password" type="password" minlength="8" maxlength="72"
                   autocomplete="new-password">
        </div>

        <button class="btn" type="submit">Save changes</button>
        <a class="btn btn-secondary" href="<?= e(url('admin/users/index.php')) ?>">Cancel</a>
    </form>
</div>
<?php renderFooter();
