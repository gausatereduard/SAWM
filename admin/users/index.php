<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helper/bootstrap.php';

// Authentication + permission based access control.
requirePermission(PERM_USERS_VIEW);

$users = getAllUsers();
$currentId = currentUserId();

renderHeader('User accounts');
?>
<p class="small muted"><a href="<?= e(url('admin/dashboard.php')) ?>">&larr; Administrator dashboard</a></p>
<h1>User accounts</h1>
<p class="lead">Full account data. Only accounts with the <code>users.view</code> permission can read this page.</p>

<div class="card">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= (int) $user['id'] ?></td>
                <td>
                    <?= e((string) $user['username']) ?>
                    <?php if ((int) $user['id'] === $currentId): ?>
                        <span class="badge">you</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge role-<?= e((string) $user['role']) ?>"><?= e(roleLabel((string) $user['role'])) ?></span></td>
                <td class="small muted"><?= e((string) $user['created_at']) ?></td>
                <td>
                    <div class="actions">
                        <a class="btn btn-secondary btn-small"
                           href="<?= e(url('admin/users/edit.php?id=' . (int) $user['id'])) ?>">Edit</a>
                        <?php if ((int) $user['id'] !== $currentId): ?>
                            <form method="POST" action="<?= e(url('admin/users/delete.php')) ?>"
                                  onsubmit="return confirm('Delete <?= e((string) $user['username']) ?> permanently?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button class="btn btn-danger btn-small" type="submit">Delete</button>
                            </form>
                        <?php else: ?>
                            <span class="small muted">—</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php renderFooter();
