<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

define('DB_PATH', __DIR__ . '/../database.sqlite');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('PRAGMA foreign_keys = ON');

    initDb($pdo);

    return $pdo;
}

function initDb(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS roles (
            name TEXT PRIMARY KEY,
            label TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS permissions (
            code TEXT PRIMARY KEY,
            label TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS role_permissions (
            role_name TEXT NOT NULL REFERENCES roles(name) ON DELETE CASCADE,
            permission_code TEXT NOT NULL REFERENCES permissions(code) ON DELETE CASCADE,
            PRIMARY KEY (role_name, permission_code)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_meta (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )'
    );

    // Roles/permissions must exist before users are migrated (users.role references roles.name).
    seedAuthorization($pdo);
    migrateUsersTable($pdo);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS sessions (
            id TEXT PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            created_at DATETIME NOT NULL,
            last_seen_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at)');

    seedDemoAccounts($pdo);
}

function usersTableSql(string $table): string
{
    return "CREATE TABLE {$table} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'manager' REFERENCES roles(name) ON UPDATE CASCADE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK (role IN ('administrator', 'manager'))
    )";
}

/**
 * Rebuild the `users` table when it still has the Lab 2 shape:
 * `role` used to default to 'user' with no CHECK constraint.
 *
 * Plaintext password rows created by the old `vulnerable/` demo are dropped here:
 * they are not bcrypt/argon2 hashes and can never authenticate through `login()`.
 */
function migrateUsersTable(PDO $pdo): void
{
    $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'users'")->fetchColumn();

    if ($sql === false) {
        $pdo->exec(usersTableSql('users'));

        return;
    }

    $isCurrentSchema = false;
    foreach ($pdo->query('PRAGMA table_info(users)')->fetchAll() as $column) {
        if ($column['name'] === 'role' && $column['dflt_value'] === "'manager'") {
            $isCurrentSchema = true;
        }
    }

    if ($isCurrentSchema) {
        return;
    }

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();

    try {
        $pdo->exec('DROP TABLE IF EXISTS users_new');
        $pdo->exec(usersTableSql('users_new'));

        $rows = $pdo->query('SELECT id, username, password_hash, role, created_at FROM users')->fetchAll();
        $insert = $pdo->prepare(
            'INSERT INTO users_new (id, username, password_hash, role, created_at)
             VALUES (:id, :username, :password_hash, :role, :created_at)'
        );

        foreach ($rows as $row) {
            $hash = (string) $row['password_hash'];
            if (preg_match('/^\$(2[aby]|argon2)/', $hash) !== 1) {
                continue; // legacy plaintext row
            }

            $insert->execute([
                ':id' => $row['id'],
                ':username' => $row['username'],
                ':password_hash' => $hash,
                ':role' => $row['role'] === 'administrator' ? ROLE_ADMINISTRATOR : ROLE_MANAGER,
                ':created_at' => $row['created_at'],
            ]);
        }

        $pdo->exec('DROP TABLE users');
        $pdo->exec('ALTER TABLE users_new RENAME TO users');
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    } finally {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
}

function seedAuthorization(PDO $pdo): void
{
    $roles = [
        ROLE_ADMINISTRATOR => 'Administrator',
        ROLE_MANAGER => 'Manager',
    ];

    $permissions = [
        PERM_USERS_VIEW => 'View user accounts',
        PERM_USERS_UPDATE => 'Edit user accounts',
        PERM_USERS_DELETE => 'Delete user accounts',
        PERM_REPORTS_VIEW => 'View reports',
        PERM_PROFILE_UPDATE => 'Update own profile',
    ];

    $rolePermissions = [
        ROLE_ADMINISTRATOR => [PERM_USERS_VIEW, PERM_USERS_UPDATE, PERM_USERS_DELETE],
        ROLE_MANAGER => [PERM_REPORTS_VIEW, PERM_PROFILE_UPDATE],
    ];

    $roleStmt = $pdo->prepare('INSERT OR IGNORE INTO roles (name, label) VALUES (:name, :label)');
    foreach ($roles as $name => $label) {
        $roleStmt->execute([':name' => $name, ':label' => $label]);
    }

    $permissionStmt = $pdo->prepare('INSERT OR IGNORE INTO permissions (code, label) VALUES (:code, :label)');
    foreach ($permissions as $code => $label) {
        $permissionStmt->execute([':code' => $code, ':label' => $label]);
    }

    $linkStmt = $pdo->prepare(
        'INSERT OR IGNORE INTO role_permissions (role_name, permission_code) VALUES (:role, :permission)'
    );
    foreach ($rolePermissions as $role => $codes) {
        foreach ($codes as $code) {
            $linkStmt->execute([':role' => $role, ':permission' => $code]);
        }
    }
}

/**
 * Ensure the two demo accounts exist. Runs once, tracked in `app_meta`, so deleting an
 * account through the UI does not bring it back.
 */
function seedDemoAccounts(PDO $pdo): void
{
    $seeded = $pdo->query("SELECT value FROM app_meta WHERE key = 'demo_seeded'")->fetchColumn();
    if ($seeded !== false) {
        return;
    }

    $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = :username');

    $demoAccounts = [
        ['admin', 'Admin123!', ROLE_ADMINISTRATOR],
        ['manager', 'Manager123!', ROLE_MANAGER],
    ];

    $insert = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)'
    );

    foreach ($demoAccounts as [$username, $password, $role]) {
        $exists->execute([':username' => $username]);
        if ($exists->fetchColumn() !== false) {
            continue;
        }

        $insert->execute([
            ':username' => $username,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => $role,
        ]);
    }

    $pdo->prepare("INSERT INTO app_meta (key, value) VALUES ('demo_seeded', '1')")->execute();
}
