<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/db.helper.php';

function createUser(string $username, string $passwordHash, string $role = ROLE_MANAGER): int
{
    $stmt = getDB()->prepare(
        'INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)'
    );
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $passwordHash,
        ':role' => $role,
    ]);

    return (int) getDB()->lastInsertId();
}

function getUserById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT id, username, password_hash, role, created_at FROM users WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

function getUserByUsername(string $username): ?array
{
    $stmt = getDB()->prepare(
        'SELECT id, username, password_hash, role, created_at FROM users WHERE username = :username'
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

function getAllUsers(): array
{
    return getDB()
        ->query('SELECT id, username, password_hash, role, created_at FROM users ORDER BY id')
        ->fetchAll();
}

function countUsers(): int
{
    return (int) getDB()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

/**
 * @return array<string, int> role name => number of accounts
 */
function countUsersByRole(): array
{
    $counts = [];
    foreach (getDB()->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role') as $row) {
        $counts[(string) $row['role']] = (int) $row['total'];
    }

    return $counts;
}

function countUsersCreatedSince(int $days): int
{
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM users WHERE created_at >= :since');
    $stmt->execute([':since' => gmdate('Y-m-d H:i:s', time() - ($days * 86400))]);

    return (int) $stmt->fetchColumn();
}

function updateUser(int $id, ?string $username = null, ?string $passwordHash = null, ?string $role = null): bool
{
    $fields = [];
    $params = [':id' => $id];

    if ($username !== null) {
        $fields[] = 'username = :username';
        $params[':username'] = $username;
    }
    if ($passwordHash !== null) {
        $fields[] = 'password_hash = :password_hash';
        $params[':password_hash'] = $passwordHash;
    }
    if ($role !== null) {
        $fields[] = 'role = :role';
        $params[':role'] = $role;
    }

    if ($fields === []) {
        return false;
    }

    $stmt = getDB()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');

    return $stmt->execute($params);
}

function deleteUser(int $id): bool
{
    $stmt = getDB()->prepare('DELETE FROM users WHERE id = :id');

    return $stmt->execute([':id' => $id]);
}
