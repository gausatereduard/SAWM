<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

requirePermission(PERM_REPORTS_VIEW);

$counts = countUsersByRole();
$totalUsers = countUsers();
$newThisWeek = countUsersCreatedSince(7);
$activeSessions = countActiveSessions();

renderHeader('Reports');
?>
<p class="small muted"><a href="<?= e(url('manager/dashboard.php')) ?>">&larr; Manager dashboard</a></p>
<h1>Reports</h1>
<p class="lead">Manager feature 1 of 2 — requires the <code>reports.view</code> permission.</p>

<div class="grid">
    <div class="card">
        <p class="small muted">Accounts</p>
        <p class="stat"><?= $totalUsers ?></p>
    </div>
    <div class="card">
        <p class="small muted">New in the last 7 days</p>
        <p class="stat"><?= $newThisWeek ?></p>
    </div>
    <div class="card">
        <p class="small muted">Active sessions</p>
        <p class="stat"><?= $activeSessions ?></p>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h2>Accounts by role</h2>
    <table>
        <thead>
        <tr>
            <th>Role</th>
            <th>Accounts</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (getRolesMap() as $roleName => $label): ?>
            <tr>
                <td><span class="badge role-<?= e($roleName) ?>"><?= e($label) ?></span></td>
                <td><?= (int) ($counts[$roleName] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="small muted">Aggregated data only: the manager never sees individual account records.</p>
</div>
<?php renderFooter();
