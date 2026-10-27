# Lab 5 — Walkthrough and evidence

Demo accounts: `admin` / `Admin123!` (administrator) and `manager` / `Manager123!` (manager).

Start the server from the project root:

```bash
php -S localhost:8000
```

## 1. Requirement → implementation

| # | Requirement | Where it is implemented |
|---|-------------|-------------------------|
| 1 | Administration panel reachable by URL without authentication | Reproduced in §2.1 by temporarily removing the guard; the guard itself is `requirePermission(PERM_USERS_VIEW)` in `admin/users/index.php` |
| 2 | Session management; panel not reachable without authentication | `helper/session.helper.php`, `helper/auth.helper.php`, `requireLogin()` in every restricted page |
| 3 | `role` field with `administrator` / `manager`; manager workspace with two features | `users.role` (`CHECK` constraint), `manager/dashboard.php`, `manager/reports.php`, `manager/profile.php` |
| 4 | Tables for authorization; redirect to the dashboard of the role | `roles`, `permissions`, `role_permissions`, `sessions`; `dashboardUrl()` + `auth/login.php` after a successful login |
| 5 | Role/permission control; manager CRUD functions exist but are refused | `users.view` / `users.update` / `users.delete` on `admin/users/*`; links visible on the manager dashboard, requests answered with 403 |
| 6 | Logout from both areas, redirected to the authentication page | `logout.php` → `auth/login.php?logged_out=1` |
| 7 | Walkthrough for both roles | §3 and §4 below |

## 2. Demonstrations

### 2.1 Before: knowing the URL was enough (requirement 1)

1. Open `admin/users/index.php` and comment out the guard line:
   ```php
   // requirePermission(PERM_USERS_VIEW);
   ```
2. In a private window (no session), open `http://localhost:8000/admin/users/index.php`.
3. The page renders the full account list, including usernames, roles and creation dates — no login was
   requested. Screenshot this state.
4. Restore the line. Repeating step 2 now redirects to `auth/login.php`.

The same holds for `admin/users/edit.php?id=1` and `admin/dashboard.php`: with the guard removed, the URL
alone grants access.

### 2.2 After: authentication is mandatory (requirement 2)

1. Logged out, request `admin/dashboard.php`, `admin/users/index.php`, `manager/dashboard.php` and
   `manager/reports.php`.
2. Every request answers `302` with `Location: /auth/login.php` and no page content.
3. A session that is expired or deleted from the `sessions` table is treated the same way: the cookie
   alone is not accepted.

### 2.3 Session storage (requirement 4)

After a login, the `sessions` table contains one row per active session:

```sql
SELECT id, user_id, created_at, last_seen_at, expires_at FROM sessions;
```

`expires_at` is pushed forward on every request (30 min idle timeout) and is capped by an 8 h absolute
lifetime. Logout deletes the row, so the old cookie cannot be reused even if it was copied.

## 3. Administrator workflow (requirement 7)

1. `http://localhost:8000/` → **Sign in** → `admin` / `Admin123!`.
2. Redirected to `admin/dashboard.php`: account counters, active sessions, own permissions and the
   **User management** entry.
3. **Manage users** → list of all accounts with role badges.
4. **Edit** on `manager`: change the username or role, optionally set a new password → *Save changes* →
   success message on the list.
5. **Delete** a test account → confirmation → account removed, its sessions deleted.
6. Try **Delete** on your own row: the button is not offered, and a forged POST is refused with
   "You cannot delete your own account."
7. Open `manager/reports.php` directly: **403 Access denied** (`reports.view` is not an administrator
   permission).
8. **Log out** → `auth/login.php?logged_out=1` with "You have been signed out."

## 4. Manager workflow (requirement 7)

1. `http://localhost:8000/auth/login.php` → `manager` / `Manager123!`.
2. Redirected to `manager/dashboard.php` (a different workspace than the administrator one).
3. **Reports** (`reports.view`): aggregated statistics — accounts per role, new accounts in the last
   7 days, active sessions. No individual account data.
4. **My profile** (`profile.update`): view own account data, rename the account, change the password
   (the current password is required to set a new one).
5. On the dashboard, the **User management** card is visible on purpose. Clicking **View account data**,
   **Modify account data** or **Delete account data** gives **403 Access denied**, naming the missing
   permission (`users.view` / `users.update` / `users.delete`). Screenshot one of them.
6. Open `admin/users/edit.php?id=1` manually: same 403. The URL does not matter; the decision is made on
   the server.
7. **Log out** → back to the authentication page. The manager session row disappears from `sessions`.

## 5. Access control matrix (verified)

| Resource                          | anonymous     | manager        | administrator |
|-----------------------------------|---------------|----------------|---------------|
| `index.php`                       | 200           | 200            | 200           |
| `auth/login.php`                  | 200           | redirect (session already active) | redirect |
| `admin/dashboard.php`             | 302 → login   | **403**        | 200           |
| `admin/users/index.php`           | 302 → login   | **403**        | 200           |
| `admin/users/edit.php?id=1`       | 302 → login   | **403**        | 200           |
| `admin/users/delete.php`          | 302 → login   | **403**        | 200 (POST)    |
| `manager/dashboard.php`           | 302 → login   | 200            | **403**       |
| `manager/reports.php`             | 302 → login   | 200            | **403**       |
| `manager/profile.php`             | 302 → login   | 200            | **403**       |

Additional checks that pass: missing CSRF token is rejected, duplicate usernames are rejected,
passwords shorter than 8 characters are rejected, a wrong current password is rejected on the profile
page, an administrator cannot demote their own account, and a revoked or expired session redirects to
the login page.

## 6. Screenshot checklist

- [ ] `admin/users/index.php` reachable by URL without a session (guard temporarily removed — §2.1)
- [ ] the same URL redirecting to `auth/login.php` after the guard is restored
- [ ] administrator dashboard + user list
- [ ] administrator editing and deleting an account
- [ ] manager dashboard (two features + restricted card)
- [ ] 403 page when the manager opens an administrator page
- [ ] both logout redirects
