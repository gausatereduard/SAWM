<?php
declare(strict_types=1);

/**
 * Application bootstrap: loads every layer and starts a hardened PHP session.
 * Must be the first include of each entry point (before any output).
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../repository/user.repo.php';
require_once __DIR__ . '/../repository/role.repo.php';
require_once __DIR__ . '/../repository/session.repo.php';
require_once __DIR__ . '/session.helper.php';
require_once __DIR__ . '/csrf.helper.php';
require_once __DIR__ . '/auth.helper.php';
require_once __DIR__ . '/validation.helper.php';
require_once __DIR__ . '/view.helper.php';

startSession();

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

validateSession();
