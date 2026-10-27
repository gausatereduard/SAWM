<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/db.helper.php';

/**
 * @return array<string, string> role name => display label
 */
function getRolesMap(): array
{
    $rows = getDB()->query('SELECT name, label FROM roles ORDER BY name')->fetchAll();

    $roles = [];
    foreach ($rows as $row) {
        $roles[(string) $row['name']] = (string) $row['label'];
    }

    return $roles;
}

function roleLabel(string $role): string
{
    static $labels = null;

    if ($labels === null) {
        $labels = getRolesMap();
    }

    return $labels[$role] ?? ucfirst($role);
}

function roleExists(string $role): bool
{
    $stmt = getDB()->prepare('SELECT 1 FROM roles WHERE name = :name');
    $stmt->execute([':name' => $role]);

    return $stmt->fetchColumn() !== false;
}

/**
 * @return string[] permission codes granted to the role
 */
function getPermissionsForRole(string $role): array
{
    $stmt = getDB()->prepare(
        'SELECT permission_code FROM role_permissions WHERE role_name = :role ORDER BY permission_code'
    );
    $stmt->execute([':role' => $role]);

    return array_map(static fn (array $row): string => (string) $row['permission_code'], $stmt->fetchAll());
}
