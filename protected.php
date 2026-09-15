<?php
session_start();
require_once __DIR__ . '/helper/auth.helper.php';

if (!isset($_SESSION['userId'])) {
    header('Location: index.php');
    exit;
}

$user = currentUser();
?>

<h1>Authenticated</h1>

<?php if ($user): ?>
    <table border="1" cellpadding="8">
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>id</td><td><?= $user['id'] ?></td></tr>
        <tr><td>username</td><td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td>password_hash</td><td><?= htmlspecialchars($user['password_hash'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td>role</td><td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    </table>
    <p><a href="logout.php">Logout</a></p>
<?php else: ?>
    <p>User not found.</p>
<?php endif; ?>
