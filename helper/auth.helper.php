<?php
declare(strict_types=1);

require_once __DIR__ . '/../repository/user.repo.php';

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function login(string $username, string $password): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $user = getUserByUsername($username);
    if ($user === null || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Mitigate session fixation.
    session_regenerate_id(true);

    $_SESSION['userId'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    return true;
}

function logout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function isAuthenticated(): bool
{
    return session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['userId']);
}

function currentUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    return getUserById((int) $_SESSION['userId']);
}
