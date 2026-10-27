<?php
declare(strict_types=1);

require_once __DIR__ . '/helper/password.helper.php';

header('Content-Type: text/html; charset=UTF-8');

$password = 'password';
$truncated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = $_POST['password'] ?? '';
    $password = is_string($raw) ? $raw : '';

    // Keep the demo page readable if someone pastes a very long value.
    if (strlen($password) > 200) {
        $password = substr($password, 0, 200);
        $truncated = true;
    }
}

$md5 = md5($password);
$sha1 = sha1($password);
$sha256 = hash('sha256', $password);

$secureStart = microtime(true);
$secure1 = hashPassword($password);
$secure2 = hashPassword($password);
$secureDuration = microtime(true) - $secureStart;

$dictionary = [
    '123456',
    'password',
    'qwerty',
    'secret123',
    'admin',
    'letmein',
    'welcome',
    'iloveyou',
    'abc123',
    '12345678',
];

$md5Start = microtime(true);
$md5Cracked = null;
foreach ($dictionary as $candidate) {
    if (hash_equals($md5, md5($candidate))) {
        $md5Cracked = $candidate;
        break;
    }
}
$md5Duration = microtime(true) - $md5Start;

$sha1Start = microtime(true);
$sha1Cracked = null;
foreach ($dictionary as $candidate) {
    if (hash_equals($sha1, sha1($candidate))) {
        $sha1Cracked = $candidate;
        break;
    }
}
$sha1Duration = microtime(true) - $sha1Start;

$info = password_get_info($secure1);
$secureFormat = passwordStorageFormat($secure1);
$verify1 = password_verify($password, $secure1);
$verify2 = password_verify($password, $secure2);
$sameHash = hash_equals($secure1, $secure2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MD5/SHA-1 vs salted password hashes</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; margin: 0.5rem 0 1.5rem; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f3f3; }
        code, .hash { font-family: ui-monospace, monospace; word-break: break-all; }
        .good { color: #0a7a2f; font-weight: bold; }
        .bad { color: #b00020; font-weight: bold; }
        input[type="text"] { width: 24rem; max-width: 100%; padding: 4px; }
        button { padding: 8px 16px; }
    </style>
</head>
<body>
    <h1>MD5 / SHA-1 vs salted password hashes</h1>

    <form method="POST" action="hash-demo.php">
        <label for="password">Password to test:</label>
        <input id="password" name="password" type="text"
            value="<?= htmlspecialchars($password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" />
        <button type="submit">Analyze</button>
    </form>

    <?php if ($truncated): ?>
        <p class="bad">The input was longer than 200 characters and was truncated for this demo.</p>
    <?php endif; ?>

    <h2>Fast, unsalted hashes</h2>
    <table>
        <thead>
            <tr>
                <th>Algorithm</th>
                <th>Result</th>
                <th>Property</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>MD5</td>
                <td class="hash"><?= htmlspecialchars($md5, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td>128-bit, fast, cryptographically broken</td>
            </tr>
            <tr>
                <td>SHA-1</td>
                <td class="hash"><?= htmlspecialchars($sha1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td>160-bit, fast, deprecated</td>
            </tr>
            <tr>
                <td>SHA-256 (also unsuitable alone)</td>
                <td class="hash"><?= htmlspecialchars($sha256, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td>Fast; good for integrity, not for passwords</td>
            </tr>
        </tbody>
    </table>

    <h2>Salted password hashes (same input)</h2>
    <table>
        <thead>
            <tr>
                <th>Hash</th>
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Secure hash #1</td>
                <td class="hash"><?= htmlspecialchars($secure1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td>Secure hash #2</td>
                <td class="hash"><?= htmlspecialchars($secure2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr>
                <th>Algorithm</th>
                <td><?= htmlspecialchars($secureFormat, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            </tr>
            <tr>
                <th>Parameters</th>
                <td class="hash"><?= htmlspecialchars((string) json_encode($info['options']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            </tr>
            <tr>
                <th>Hash #1 verifies</th>
                <td class="<?= $verify1 ? 'good' : 'bad' ?>"><?= $verify1 ? 'yes' : 'no' ?></td>
            </tr>
            <tr>
                <th>Hash #2 verifies</th>
                <td class="<?= $verify2 ? 'good' : 'bad' ?>"><?= $verify2 ? 'yes' : 'no' ?></td>
            </tr>
            <tr>
                <th>Hash #1 and #2 are identical</th>
                <td class="<?= $sameHash ? 'bad' : 'good' ?>"><?= $sameHash ? 'yes — salt is missing!' : 'no — a random salt was used' ?></td>
            </tr>
            <tr>
                <th>Time for two secure hashes</th>
                <td><?= number_format($secureDuration * 1000, 3) ?> ms</td>
            </tr>
        </tbody>
    </table>

    <h2>Dictionary attack against the fast hashes</h2>
    <table>
        <thead>
            <tr>
                <th>Algorithm</th>
                <th>Result</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>MD5</td>
                <td class="<?= $md5Cracked !== null ? 'bad' : 'good' ?>">
                    <?php if ($md5Cracked !== null): ?>
                        cracked: <code><?= htmlspecialchars($md5Cracked, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                    <?php else: ?>
                        not in this 10-word dictionary
                    <?php endif; ?>
                </td>
                <td><?= number_format($md5Duration * 1000, 3) ?> ms</td>
            </tr>
            <tr>
                <td>SHA-1</td>
                <td class="<?= $sha1Cracked !== null ? 'bad' : 'good' ?>">
                    <?php if ($sha1Cracked !== null): ?>
                        cracked: <code><?= htmlspecialchars($sha1Cracked, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                    <?php else: ?>
                        not in this 10-word dictionary
                    <?php endif; ?>
                </td>
                <td><?= number_format($sha1Duration * 1000, 3) ?> ms</td>
            </tr>
        </tbody>
    </table>

    <p>
        The secure hashes are different even though the password is the same. That is the salt:
        a precomputed rainbow table cannot be reused, and the attacker must attack every hash
        separately. The slow algorithm makes each guess expensive.
    </p>

    <p><a href="users.php">Open the users table</a> · <a href="index.php">Back to public page</a></p>
</body>
</html>
