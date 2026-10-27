<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

// Role-based access control: managers only. This is a different workspace from the administrator area.
requireRole(ROLE_MANAGER);

renderHeader('Manager dashboard');
?>
<h1>Manager dashboard</h1>
<p class="lead">Signed in as <strong><?= e((string) ($_SESSION['username'] ?? '')) ?></strong> — this workspace requires the
    <span class="badge role-manager">manager</span> role.</p>

<div class="grid">
    <div class="card">
        <h2>Reports</h2>
        <p class="muted small">Aggregated statistics: accounts per role, new accounts and active sessions.
            Requires the <code>reports.view</code> permission.</p>
        <p><a class="btn" href="<?= e(url('manager/reports.php')) ?>">Open reports</a></p>
    </div>
    <div class="card">
        <h2>My profile</h2>
        <p class="muted small">View the data of your own account and update your username or password.
            Requires the <code>profile.update</code> permission.</p>
        <p><a class="btn" href="<?= e(url('manager/profile.php')) ?>">Open profile</a></p>
    </div>
</div>

<div class="card locked" style="margin-top: 20px;">
    <h2>User management <span class="badge">restricted</span></h2>
    <p class="actions">
        <a class="btn" href="<?= e(url('admin/users/index.php')) ?>">View account data</a>
        <a class="btn" href="<?= e(url('admin/users/edit.php?id=1')) ?>">Modify account data</a>
        <a class="btn" href="<?= e(url('admin/users/delete.php')) ?>">Delete account data</a>
    </p>
    <p class="small muted">This is only a demonstration link: the control is enforced on the server, not by hiding the
        buttons.</p>
</div>
<?php renderFooter();
