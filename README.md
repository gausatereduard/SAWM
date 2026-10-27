# Lab 5 — Controlling Access to a Web Application

Minimal PHP 8 + SQLite application (no framework, no Composer) for the web-security course.
The lab requirements are in [`todo.md`](todo.md); the walkthrough with expected results is in
[`docs/REPORT.md`](docs/REPORT.md).

## 1. Layout

```
index.php                     public page (no session required)
auth/login.php                sign in, redirects by role
auth/signup.php               sign up (always creates a manager)
logout.php                    destroys the session and redirects to the login page

admin/dashboard.php           administrator workspace          role: administrator
admin/users/index.php         list accounts                    permission: users.view
admin/users/edit.php          edit an account                  permission: users.update
admin/users/delete.php        delete an account (POST)         permission: users.delete

manager/dashboard.php         manager workspace                role: manager
manager/reports.php           feature 1: reports               permission: reports.view
manager/profile.php           feature 2: own profile           permission: profile.update

errors/403.php                403 Access denied page

helper/constants.php          role and permission names
helper/bootstrap.php          loads every layer, starts the hardened session
helper/session.helper.php     session lifecycle, DB-backed sessions, flash, url(), redirect()
helper/auth.helper.php        login/logout, requireLogin/requireRole/requirePermission, can()
helper/csrf.helper.php        CSRF token generation and verification
helper/validation.helper.php  shared username/password rules
helper/view.helper.php        HTML layout (header/footer, escaping helper)
helper/db.helper.php          PDO connection, schema, migrations, seed data
repository/user.repo.php      user CRUD and aggregate queries
repository/role.repo.php      roles and role → permissions
repository/session.repo.php   server-side session records

database.sqlite               SQLite database
```

The Lab 2 `vulnerable/` folder and the `secure/` folder name are gone; authentication pages now live
in `auth/`, and `protected.php` (which dumped the password hash) was replaced by the role dashboards.

## 2. Requirements

- PHP 8.1 or newer with the `pdo_sqlite` extension
- No other dependency

```bash
php -S localhost:8000
# http://localhost:8000/
```

If the project is served from a subfolder (XAMPP `htdocs/lab5`), every internal link still works:
`url()` derives the base path from `DOCUMENT_ROOT`.

## 3. Demo accounts

| Username  | Password       | Role            |
|-----------|----------------|-----------------|
| `admin`   | `Admin123!`    | `administrator` |
| `manager` | `Manager123!`  | `manager`       |

Both are created automatically on an empty database (`seedDemoAccounts()`), with bcrypt hashes.
Signing up through `auth/signup.php` always creates a `manager`; administrators are only created by an
administrator.

## 4. Roles and permissions

| Permission       | administrator | manager |
|------------------|:-------------:|:-------:|
| `users.view`     | ✔             |         |
| `users.update`   | ✔             |         |
| `users.delete`   | ✔             |         |
| `reports.view`   |               | ✔       |
| `profile.update` |               | ✔       |

Permissions are granted per role in `role_permissions` and copied into `$_SESSION['permissions']` at
login. They are checked server-side with `requirePermission()`; the UI only reflects the decision.

## 5. Database

| Table              | Purpose |
|--------------------|---------|
| `users`            | accounts; `role` is `administrator` or `manager` (`CHECK` constraint, FK to `roles`) |
| `roles`            | the two roles and their display labels |
| `permissions`      | permission catalogue |
| `role_permissions` | role → permission mapping |
| `sessions`         | server-side session records (id, user_id, created_at, last_seen_at, expires_at) |
| `app_meta`         | bootstrap bookkeeping (`demo_seeded`) |

`initDb()` creates everything on first use. On a database created by Lab 2 it also rebuilds `users`
(adding the `CHECK` constraint and the `manager` default), maps `user` → `manager` / `admin` →
`administrator`, and drops legacy plaintext password rows, which can never authenticate.

## 6. Security controls

- Authentication required on every restricted page (`requireLogin()`), enforced before any output.
- Role checks (`requireRole()`) and permission checks (`requirePermission()`) return **403**, not a redirect.
- Server-side sessions: `sessions` row per login, id regenerated on login, 30 min idle / 8 h absolute
  expiry, deleted on logout and on password change, revocable from the database.
- Cookie flags: `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS is used, `session.use_strict_mode`.
- CSRF token on every POST form (login, signup, edit, delete, profile).
- Prepared statements everywhere; output escaped with `htmlspecialchars()`.
- Deletion is POST-only, and an administrator cannot delete or demote their own account.
