<?php
session_start();
require_once __DIR__ . '/../helper/auth.helper.php';

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitization: trim surrounding whitespace.
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation: length + allowlist regex.
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username must be 3-20 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username may contain only letters, digits and underscore.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 4 || strlen($password) > 72) {
        $errors[] = 'Password must be 4-72 characters.';
    }

    if ($errors === []) {
        if (login($username, $password)) {
            header('Location: ../protected.php');
            exit;
        }
        $errors[] = 'Invalid username or password.';
    }
}
?>

<?php foreach ($errors as $e): ?>
    <p><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<form method="POST" action="login.php">
    <input name="username" type="text" placeholder="Username"
        required minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]+"
        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" />
    <input name="password" type="password" placeholder="Password"
        required minlength="4" maxlength="72" />
    <input type="submit" value="Login" />
</form>
