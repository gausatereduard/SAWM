# Lab 2 — PHP Auth + SQL Injection Demo

> **Local course demo only. Do NOT deploy `vulnerable/` to the internet.**

## 1. What this project is

A minimal PHP login/signup app (SQLite, no dependencies) built for a web-security
course to compare **secure** vs **SQL-injectable** authentication side by side.

- Public page: `index.php`
- Restricted page: `protected.php` (requires `$_SESSION['userId']`)
- Secure forms: `secure/login.php`, `secure/signup.php`
- Vulnerable forms: `vulnerable/login.php`, `vulnerable/signup.php`
- Shared layers: `helper/db.helper.php` (PDO + schema), `helper/auth.helper.php`
  (`hashPassword()`, `login()`, `logout()`), `repository/user.repo.php` (CRUD)
- DB: `database.sqlite`, table `users(id, username UNIQUE, password_hash, role, created_at)`
  (auto-created on first `getDB()` call)

Run locally:

```bash
php -S localhost:8000
# open http://localhost:8000/vulnerable/signup.php  (or /secure/...)
```

Note: `secure/` stores **bcrypt hashes**, `vulnerable/` stores **plaintext** in the
same column — accounts created in one variant will not log in via the other.
Create the demo account through the same variant you test.

## 2. Where the SQL injection lives

`vulnerable/login.php:11` builds SQL by string interpolation:

```php
$sql = "SELECT id, username, password_hash, role FROM users WHERE username = '$username' AND password_hash = '$password'";
$user = getDB()->query($sql)->fetch();
```

User input becomes part of the query's *code*. A quote in the input breaks out
of the string literal and changes the query logic. Same flaw in
`vulnerable/signup.php:11` (`INSERT ... VALUES ('$username', '$password', 'user')`).

The secure variant (`secure/login.php` → `getUserByUsername()` with
`prepare()` + bound params) never concatenates input, and its allowlist regex
`/^[a-zA-Z0-9_]+$/` rejects quotes entirely.

## 3. How to test it (step by step)

**Setup:**

1. Start the server and open `http://localhost:8000/vulnerable/signup.php`.
2. Register a normal user, e.g. `alice` / `secret123`.
3. Note you land on `vulnerable/login.php` afterwards.

**Test A — authentication bypass (login):**

1. Open `http://localhost:8000/vulnerable/login.php`.
2. Username: `' OR '1'='1' --` (password: anything, e.g. `x`).
3. Submit → you land on `protected.php` as the first user in the table,
   without knowing any password.

Why it works — the query becomes:

```sql
SELECT ... WHERE username = '' OR '1'='1' --' AND password_hash = 'x'
```

`'1'='1'` is always true, and `--` comments out the password check, so the
`WHERE` matches every row and `fetch()` returns the first one.

**Test B — privilege escalation (signup), optional:**

1. Open `http://localhost:8000/vulnerable/signup.php`.
2. Username: `mallory', 'x', 'admin') --` , password: anything.
3. The `INSERT` becomes `VALUES ('mallory', 'x', 'admin') --', ...)` —
   the row is created with `role='admin'` instead of `'user'`.

**Test C — confirm the secure forms resist:**

1. Repeat Test A against `http://localhost:8000/secure/login.php`.
2. The browser's `pattern` attribute blocks the quote client-side; disabling
   that, server-side `preg_match` rejects it with
   "Username may contain only letters, digits and underscore."
3. Even without validation, the prepared statement treats the payload as a
   literal string, so no row matches and you get "Invalid username or password."
