<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

// Role-based access control: administrators only.
requireRole(ROLE_ADMINISTRATOR);

$counts = countUsersByRole();
$totalUsers = countUsers();
$administrators = $counts[ROLE_ADMINISTRATOR] ?? 0;
$managers = $counts[ROLE_MANAGER] ?? 0;
$activeSessions = countActiveSessions();

renderHeader('Administrator dashboard');
?>
<h1>Administrator dashboard</h1>
<p class="lead">Signed in as <strong><?= e((string) ($_SESSION['username'] ?? '')) ?></strong> — this workspace requires the
    <span class="badge role-administrator">administrator</span> role.</p>

<div class="grid">
    <div class="card">
        <p class="small muted">Accounts</p>
        <p class="stat"><?= $totalUsers ?></p>
        <p class="small muted"><?= $administrators ?> administrator(s) · <?= $managers ?> manager(s)</p>
    </div>
    <div class="card">
        <p class="small muted">Active sessions</p>
        <p class="stat"><?= $activeSessions ?></p>
        <p class="small muted">Stored server-side in the <code>sessions</code> table</p>
    </div>
    <div class="card">
        <p class="small muted">My permissions</p>
        <p class="small">
            <?php foreach (permissions() as $permission): ?>
                <span class="badge"><?= e($permission) ?></span>
            <?php endforeach; ?>
        </p>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h2>User management</h2>
    <p class="muted small">Administrators can view, edit and delete accounts. These pages are also protected by
        permission checks (<code>users.view</code>, <code>users.update</code>, <code>users.delete</code>).</p>
    <p>
        <a class="btn" href="<?= e(url('admin/users/index.php')) ?>">Manage users</a>
    </p>
</div>
<?php renderFooter();
