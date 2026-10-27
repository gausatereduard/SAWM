<?php
declare(strict_types=1);

require_once __DIR__ . '/helper/bootstrap.php';

$loggedIn = isAuthenticated();

renderHeader('Public page');
?>
<h1>Public page</h1>
<p class="lead">Everyone can see this page — no session required.</p>

<div class="card">
    <h2>Restricted areas</h2>
    <p class="muted">Both areas are protected on the server. Knowing the URL is not enough: without a valid
        session you are redirected to the sign-in page.</p>
    <?php if ($loggedIn): ?>
        <p>
            <a class="btn" href="<?= e(dashboardUrl()) ?>">Open my dashboard</a>
            <a class="btn btn-secondary" href="<?= e(url('logout.php')) ?>">Log out</a>
        </p>
        <p class="small muted">Signed in as <strong><?= e((string) ($_SESSION['username'] ?? '')) ?></strong>
            with role <strong><?= e(roleLabel((string) (currentRole() ?? ''))) ?></strong>.</p>
    <?php else: ?>
        <p>
            <a class="btn" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
            <a class="btn btn-secondary" href="<?= e(url('auth/signup.php')) ?>">Sign up</a>
        </p>
    <?php endif; ?>
</div>
<?php renderFooter();
