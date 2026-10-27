<?php
declare(strict_types=1);

require_once __DIR__ . '/../repository/user.repo.php';
require_once __DIR__ . '/../repository/role.repo.php';
require_once __DIR__ . '/../repository/session.repo.php';
require_once __DIR__ . '/session.helper.php';

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify the credentials, start an authenticated session and store the role
 * plus the permissions granted to that role in session variables.
 */
function login(string $username, string $password): bool
{
    $user = getUserByUsername($username);
    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        updateUser((int) $user['id'], null, hashPassword($password));
    }

    startSession();

    // Mitigate session fixation: a new id is issued, the old one is discarded.
    session_regenerate_id(true);

    deleteExpiredSessions();

    $_SESSION = [];
    $_SESSION['userId'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['permissions'] = getPermissionsForRole((string) $user['role']);
    $_SESSION['loginAt'] = time();

    // Server-side record, so the session can be revoked and expired on the server.
    createSessionRecord(session_id(), (int) $user['id'], SESSION_IDLE_TTL, SESSION_MAX_LIFETIME);

    return true;
}

function logout(): void
{
    startSession();

    if (session_status() === PHP_SESSION_ACTIVE) {
        deleteSessionRecord(session_id());
    }

    destroySession();
}

function isAuthenticated(): bool
{
    return session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['userId']);
}

function currentUserId(): ?int
{
    return isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : null;
}

function currentRole(): ?string
{
    return isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;
}

function currentUser(): ?array
{
    $userId = currentUserId();

    return $userId === null ? null : getUserById($userId);
}

/**
 * @return string[] permission codes stored in the session at login time
 */
function permissions(): array
{
    $stored = $_SESSION['permissions'] ?? [];

    return is_array($stored) ? array_values(array_map('strval', $stored)) : [];
}

function can(string $permission): bool
{
    return in_array($permission, permissions(), true);
}

function dashboardUrl(?string $role = null): string
{
    $role ??= currentRole();

    return $role === ROLE_ADMINISTRATOR
        ? url('admin/dashboard.php')
        : url('manager/dashboard.php');
}

/**
 * Authentication is mandatory: anonymous visitors are sent to the login page.
 */
function requireLogin(): void
{
    if (!isAuthenticated()) {
        redirect(url('auth/login.php'));
    }
}

/**
 * Role-based access control: only the listed roles may continue.
 */
function requireRole(string ...$roles): void
{
    requireLogin();

    if (!in_array(currentRole(), $roles, true)) {
        forbidden('Your role does not allow access to this area.');
    }
}

/**
 * Permission-based access control: every listed permission must be granted.
 */
function requirePermission(string ...$permissions): void
{
    requireLogin();

    foreach ($permissions as $permission) {
        if (!can($permission)) {
            forbidden('Your role does not include the "' . $permission . '" permission.');
        }
    }
}

/**
 * Render the 403 page and stop. Access is denied on the server, not hidden in the UI.
 */
function forbidden(string $message = 'You are not allowed to access this resource.'): void
{
    http_response_code(403);
    $forbiddenMessage = $message;
    require __DIR__ . '/../errors/403.php';
    exit;
}
