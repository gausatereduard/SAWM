<?php
declare(strict_types=1);

require_once __DIR__ . '/repository/user.repo.php';
require_once __DIR__ . '/helper/password.helper.php';

header('Content-Type: text/html; charset=UTF-8');

$users = getAllUsers();
$insecureCount = 0;

foreach ($users as $user) {
    if (!isSecurePasswordHash($user['password_hash'])) {
        $insecureCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users table — password storage</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f3f3; }
        .stored { font-family: ui-monospace, monospace; word-break: break-all; max-width: 520px; }
        .good { color: #0a7a2f; font-weight: bold; }
        .bad { color: #b00020; font-weight: bold; }
        .summary { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Users table — password storage</h1>

    <p class="summary">
        Total users: <strong><?= count($users) ?></strong>.
        Insecure stored passwords: <strong class="bad"><?= $insecureCount ?></strong>.
    </p>

    <?php if ($users === []): ?>
        <p>No users yet. Create one through <a href="vulnerable/signup.php">vulnerable signup</a>
            or <a href="secure/signup.php">secure signup</a>.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created at</th>
                    <th>Stored value in <code>password_hash</code></th>
                    <th>Detected format</th>
                    <th>Secure?</th>
                    <th>Length</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $stored = $user['password_hash'];
                    $secure = isSecurePasswordHash($stored);
                    ?>
                    <tr>
                        <td><?= (int) $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td class="stored"><?= htmlspecialchars($stored, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(passwordStorageFormat($stored), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                        <td class="<?= $secure ? 'good' : 'bad' ?>"><?= $secure ? 'yes' : 'no' ?></td>
                        <td><?= strlen($stored) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p>
        <a href="hash-demo.php">Compare MD5/SHA-1 with salted hashes</a> ·
        <a href="index.php">Back to public page</a>
    </p>
</body>
</html>
