<?php
declare(strict_types=1);

require_once __DIR__ . '/repository/guest.repo.php';

header('Content-Type: text/html; charset=UTF-8');

$errors = [];
$user = '';
$textMessage = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitization: trim surrounding whitespace. Values are escaped only on output.
    $user = trim(is_string($_POST['user'] ?? null) ? $_POST['user'] : '');
    $textMessage = trim(is_string($_POST['text_message'] ?? null) ? $_POST['text_message'] : '');
    $email = trim(is_string($_POST['e_mail'] ?? null) ? $_POST['e_mail'] : '');

    // Server-side validation.
    if ($user === '') {
        $errors[] = 'Name is required.';
    } elseif (strlen($user) > 50) {
        $errors[] = 'Name must be at most 50 characters.';
    }

    if ($textMessage === '') {
        $errors[] = 'Message is required.';
    } elseif (strlen($textMessage) > 1000) {
        $errors[] = 'Message must be at most 1000 characters.';
    }

    if ($email === '') {
        $errors[] = 'E-mail is required.';
    } elseif (strlen($email) > 100 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'E-mail is not valid.';
    }

    if ($errors === []) {
        createGuestMessage($user, $textMessage, $email);
        // Redirect after POST so a refresh does not insert the message twice.
        header('Location: guestbook.php');
        exit;
    }
}

$messages = getAllGuestMessages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guestbook</title>
</head>
<body>
    <h1>Guestbook</h1>

    <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="POST" action="guestbook.php">
        <input name="user" type="text" placeholder="Name"
            required maxlength="50"
            value="<?= htmlspecialchars($user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" />
        <input name="e_mail" type="email" placeholder="E-mail"
            required maxlength="100"
            value="<?= htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" />
        <textarea name="text_message" placeholder="Message"
            required maxlength="1000"><?= htmlspecialchars($textMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        <input type="submit" value="Post message" />
    </form>

    <h2>Messages</h2>

    <?php if ($messages === []): ?>
        <p>No messages yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Message</th>
                <th>E-mail</th>
                <th>Date</th>
            </tr>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td><?= (int) $message['id'] ?></td>
                    <td><?= htmlspecialchars($message['user'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <!--<td style="white-space: pre-wrap"><?= $message['text_message'] ?></td>-->
                    <td style="white-space: pre-wrap"><?= htmlspecialchars($message['text_message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($message['e_mail'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($message['data_time_message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p><a href="index.php">Back to public page</a></p>
</body>
</html>
