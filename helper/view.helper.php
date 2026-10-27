<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.helper.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function renderHeader(string $title): void
{
    $loggedIn = isAuthenticated();
    $username = $loggedIn ? (string) ($_SESSION['username'] ?? '') : '';
    $role = $loggedIn ? roleLabel((string) ($_SESSION['role'] ?? '')) : '';
    $flash = pullFlash();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — SAWM Lab 5</title>
    <style>
        :root {
            --bg: #f4f6fb;
            --card: #ffffff;
            --ink: #1d2433;
            --muted: #64708a;
            --line: #e2e7f0;
            --brand: #4356d6;
            --danger: #c0392b;
            --ok: #1f8a54;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
        }
        a { color: var(--brand); }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 14px 28px;
            background: #1d2433;
            color: #fff;
        }
        .topbar .brand { color: #fff; font-weight: 700; text-decoration: none; letter-spacing: .4px; }
        .topbar nav { display: flex; align-items: center; gap: 18px; font-size: 14px; }
        .topbar nav a { color: #cdd5ea; text-decoration: none; }
        .topbar nav a:hover { color: #fff; }
        .topbar .who { color: #9aa6c4; }
        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #e8ebfb;
            color: #3b4bbf;
        }
        .badge.role-administrator { background: #fdeaea; color: #a8261a; }
        .badge.role-manager { background: #e6f5ec; color: #17663f; }
        .page { max-width: 960px; margin: 32px auto 64px; padding: 0 20px; }
        h1 { font-size: 26px; margin: 0 0 6px; }
        h2 { font-size: 19px; margin: 0 0 10px; }
        .lead { color: var(--muted); margin: 0 0 24px; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(20, 30, 60, .04);
        }
        .grid { display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .grid .card { margin-bottom: 0; }
        .stat { font-size: 32px; font-weight: 700; margin: 2px 0 0; }
        .muted { color: var(--muted); }
        .small { font-size: 13px; }
        .alert {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            font-size: 14px;
            border: 1px solid transparent;
        }
        .alert-error { background: #fdeceb; border-color: #f5c2bd; color: #93251a; }
        .alert-success { background: #e9f7ef; border-color: #bde5cd; color: #17663f; }
        .alert-info { background: #eaf0fd; border-color: #c7d6f8; color: #2c3d8f; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        input[type=text], input[type=password], select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #c9d2e3;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            color: var(--ink);
        }
        .field { margin-bottom: 14px; }
        .btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: var(--brand);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover { background: #3445ba; }
        .btn-secondary { background: #fff; color: var(--ink); border-color: #c9d2e3; }
        .btn-secondary:hover { background: #f0f3fa; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #a02d20; }
        .btn-small { padding: 5px 12px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); }
        tr:last-child td { border-bottom: none; }
        .actions { display: flex; gap: 8px; align-items: center; }
        .actions form { margin: 0; }
        .locked { opacity: .75; border-style: dashed; }
        .locked .btn { background: #8d96aa; }
        .locked .btn:hover { background: #8d96aa; }
        footer { text-align: center; color: var(--muted); font-size: 12px; padding: 0 0 32px; }
        code { background: #eef1f8; padding: 1px 6px; border-radius: 5px; font-size: 13px; }
    </style>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(url('index.php')) ?>">SAWM · Lab 5</a>
    <nav>
        <?php if ($loggedIn): ?>
            <span class="who"><?= e($username) ?> <span class="badge role-<?= e((string) ($_SESSION['role'] ?? '')) ?>"><?= e($role) ?></span></span>
            <a href="<?= e(dashboardUrl()) ?>">Dashboard</a>
            <a href="<?= e(url('logout.php')) ?>">Log out</a>
        <?php else: ?>
            <a href="<?= e(url('auth/login.php')) ?>">Sign in</a>
            <a href="<?= e(url('auth/signup.php')) ?>">Sign up</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page">
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e((string) ($flash['type'] ?? 'info')) ?>"><?= e((string) ($flash['message'] ?? '')) ?></div>
<?php endif; ?>
<?php
}

function renderFooter(): void
{
    ?>
</main>
<footer>Лабораторная работа 5. Контроль доступа к веб приложению</footer>
</body>
</html>
<?php
}
