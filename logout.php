<?php
declare(strict_types=1);

require_once __DIR__ . '/helper/bootstrap.php';

logout();

redirect(url('auth/login.php') . '?logged_out=1');
