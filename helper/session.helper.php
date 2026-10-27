<?php
declare(strict_types=1);

require_once __DIR__ . '/../repository/session.repo.php';

const SESSION_NAME = 'sawm_session';

/** Seconds of inactivity before the session expires. */
const SESSION_IDLE_TTL = 1800; // 30 minutes

/** Hard limit for a session regardless of activity. */
const SESSION_MAX_LIFETIME = 28800; // 8 hours

function startSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Bind the PHP session to the `sessions` table and expire it on idle / absolute timeout.
 * Called on every request from helper/bootstrap.php.
 */
function validateSession(): void
{
    if (!isset($_SESSION['userId'])) {
        return;
    }

    $record = findSessionRecord(session_id());
    $userId = (int) $_SESSION['userId'];

    if ($record === null || (int) $record['user_id'] !== $userId) {
        destroySession();

        return;
    }

    $now = time();
    $expiresAt = strtotime((string) $record['expires_at'] . ' UTC');
    $createdAt = strtotime((string) $record['created_at'] . ' UTC');

    $idleExpired = $expiresAt === false || $expiresAt <= $now;
    $lifetimeExpired = $createdAt === false || ($createdAt + SESSION_MAX_LIFETIME) <= $now;

    if ($idleExpired || $lifetimeExpired) {
        deleteSessionRecord((string) $record['id']);
        destroySession();

        return;
    }

    touchSessionRecord(session_id(), SESSION_IDLE_TTL);
}

function destroySession(): void
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
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
}

function currentSessionId(): string
{
    return session_status() === PHP_SESSION_ACTIVE ? session_id() : '';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

/**
 * Path prefix the application is served from ('' at the document root, '/lab5' in a subfolder).
 */
function appBaseUrl(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(dirname(__DIR__));

    if ($documentRoot === false || $appRoot === false) {
        return $base = '';
    }

    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $appRoot = rtrim(str_replace('\\', '/', $appRoot), '/');

    if ($appRoot === $documentRoot) {
        return $base = '';
    }

    if (str_starts_with($appRoot, $documentRoot . '/')) {
        return $base = substr($appRoot, strlen($documentRoot));
    }

    return $base = '';
}

function url(string $path): string
{
    return appBaseUrl() . '/' . ltrim($path, '/');
}

function redirect(string $location): void
{
    if (!headers_sent()) {
        header('Location: ' . $location);
    }

    exit;
}
