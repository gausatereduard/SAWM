<?php
declare(strict_types=1);

// The SQLite "guest" database (one file = one database) with a single table: guest.
define('GUEST_DB_PATH', __DIR__ . '/../guest.sqlite');

function getGuestDB(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . GUEST_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    initGuestDb($pdo);

    return $pdo;
}

function initGuestDb(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS guest (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            "user" TEXT NOT NULL,
            text_message TEXT NOT NULL,
            e_mail TEXT NOT NULL,
            data_time_message DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
}
