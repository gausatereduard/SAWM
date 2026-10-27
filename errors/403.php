<?php
declare(strict_types=1);

require_once __DIR__ . '/../helper/bootstrap.php';

if (!headers_sent()) {
    http_response_code(403);
}

$message = $forbiddenMessage ?? 'You are not allowed to access this resource.';

renderHeader('Access denied');
?>
<h1>403 — Access denied</h1>
<p class="lead"><?= e($message) ?></p>

<div class="card">
    <p>This resource exists, but the server refused the request: your session does not carry the role or the
        permission it requires. Changing the URL does not change your permissions.</p>

    <?php if (isAuthenticated()): ?>
        <p class="small muted">
            Signed in as <strong><?= e((string) ($_SESSION['username'] ?? '')) ?></strong>
            (<?= e(roleLabel((string) (currentRole() ?? ''))) ?>).
            Permissions: <?= e(permissions() === [] ? 'none' : implode(', ', permissions())) ?>.
        </p>
        <p><a class="btn" href="<?= e(dashboardUrl()) ?>">Back to my dashboard</a></p>
    <?php else: ?>
        <p><a class="btn" href="<?= e(url('auth/login.php')) ?>">Sign in</a></p>
    <?php endif; ?>
</div>
<?php renderFooter();
