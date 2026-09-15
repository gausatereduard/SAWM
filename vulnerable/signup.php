<?php
// WARNING: INTENTIONALLY VULNERABLE - SQL injection demo for a local web-security course only.
// Do NOT deploy this. No validation, no sanitization, no prepared statements, plaintext password.
session_start();
require_once __DIR__ . '/../helper/db.helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "INSERT INTO users (username, password_hash, role) VALUES ('$username', '$password', 'user')";
    getDB()->exec($sql);
    header('Location: login.php');
    exit;
}
?>

<form method="POST" action="signup.php">
    <input name="username" type="text" placeholder="Username" />
    <input name="password" type="password" placeholder="Password" />
    <input type="submit" value="Sign up" />
</form>
