<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helper/bootstrap.php';

requirePermission(PERM_USERS_DELETE);

// Deletion is a state-changing operation: POST only, with a CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Delete requests must be submitted from the account list.');
    redirect(url('admin/users/index.php'));
}

if (!csrfValid()) {
    setFlash('error', 'The form expired. Please try again.');
    redirect(url('admin/users/index.php'));
}

$id = (int) ($_POST['id'] ?? 0);
$target = $id > 0 ? getUserById($id) : null;

if ($target === null) {
    setFlash('error', 'Account not found.');
    redirect(url('admin/users/index.php'));
}

if ($id === currentUserId()) {
    setFlash('error', 'You cannot delete your own account.');
    redirect(url('admin/users/index.php'));
}

deleteSessionsForUser($id);
deleteUser($id);

setFlash('success', 'Account "' . $target['username'] . '" deleted.');
redirect(url('admin/users/index.php'));
