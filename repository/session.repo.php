<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/db.helper.php';

/**
 * Persist a server-side session so it can be revoked independently of the cookie.
 */
function createSessionRecord(string $sessionId, int $userId, int $idleTtl, int $maxLifetime): void
{
    $now = time();

    $stmt = getDB()->prepare(
        'INSERT INTO sessions (id, user_id, created_at, last_seen_at, expires_at)
         VALUES (:id, :user_id, :created_at, :last_seen_at, :expires_at)'
    );
    $stmt->execute([
        ':id' => $sessionId,
        ':user_id' => $userId,
        ':created_at' => gmdate('Y-m-d H:i:s', $now),
        ':last_seen_at' => gmdate('Y-m-d H:i:s', $now),
        ':expires_at' => gmdate('Y-m-d H:i:s', $now + min($idleTtl, $maxLifetime)),
    ]);
}

function findSessionRecord(string $sessionId): ?array
{
    $stmt = getDB()->prepare('SELECT id, user_id, created_at, last_seen_at, expires_at FROM sessions WHERE id = :id');
    $stmt->execute([':id' => $sessionId]);
    $record = $stmt->fetch();

    return $record === false ? null : $record;
}

function touchSessionRecord(string $sessionId, int $idleTtl): void
{
    $now = time();

    $stmt = getDB()->prepare(
        'UPDATE sessions SET last_seen_at = :last_seen_at, expires_at = :expires_at WHERE id = :id'
    );
    $stmt->execute([
        ':last_seen_at' => gmdate('Y-m-d H:i:s', $now),
        ':expires_at' => gmdate('Y-m-d H:i:s', $now + $idleTtl),
        ':id' => $sessionId,
    ]);
}

function deleteSessionRecord(string $sessionId): void
{
    $stmt = getDB()->prepare('DELETE FROM sessions WHERE id = :id');
    $stmt->execute([':id' => $sessionId]);
}

function deleteSessionsForUser(int $userId, ?string $exceptSessionId = null): void
{
    if ($exceptSessionId !== null) {
        $stmt = getDB()->prepare('DELETE FROM sessions WHERE user_id = :user_id AND id <> :id');
        $stmt->execute([':user_id' => $userId, ':id' => $exceptSessionId]);

        return;
    }

    $stmt = getDB()->prepare('DELETE FROM sessions WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
}

function deleteExpiredSessions(): void
{
    $stmt = getDB()->prepare('DELETE FROM sessions WHERE expires_at <= :now');
    $stmt->execute([':now' => gmdate('Y-m-d H:i:s')]);
}

function countActiveSessions(): int
{
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM sessions WHERE expires_at > :now');
    $stmt->execute([':now' => gmdate('Y-m-d H:i:s')]);

    return (int) $stmt->fetchColumn();
}
