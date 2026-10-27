<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/db.helper.php';

function createUser(string $username, string $passwordHash, string $role = 'user'): int
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
    $stmt = getDB()->prepare('SELECT id, username, password_hash, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

function getUserByUsername(string $username): ?array
{
    $stmt = getDB()->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

function getAllUsers(): array
{
    return getDB()->query('SELECT id, username, password_hash, role, created_at FROM users ORDER BY id')->fetchAll();
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
