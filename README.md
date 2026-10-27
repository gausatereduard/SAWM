# Lab 4 — Protecting Confidential Data

## 1. Overview and current state

The project is the same PHP + SQLite application used in Labs 1–3:

- `index.php` — public page;
- `protected.php` — page that requires an authenticated session;
- `secure/signup.php`, `secure/login.php` — validated forms with prepared statements;
- `vulnerable/signup.php`, `vulnerable/login.php` — intentionally insecure forms (SQL injection + plaintext passwords);
- `guestbook.php` — Lab 3 guestbook with XSS prevention;
- `database.sqlite` — the main database, table `users(id, username, password_hash, role, created_at)`;
- `guest.sqlite` — the Lab 3 guestbook database.

The `users.password_hash` column is named “hash”, but the vulnerable forms write the **raw password** into it. In the original Lab 2/3 copy of `database.sqlite` the demo rows were:

| username | stored value in `password_hash` | role |
|---|---|---|
| `alice` | `secret123` | `user` |
| `mallory` | `x` | `admin` |

Both were plaintext. The database file is mutable and may already contain other users from your previous work (or rows you migrated). If the current table has no plaintext row, create one through `vulnerable/signup.php` during the walkthrough. The secure forms call `password_hash()` for new accounts, so a user created through `secure/signup.php` gets a salted Argon2id/bcrypt hash. Lab 4 makes this difference visible, explains it, and adds a modern hashing and migration path.

## 2. The vulnerability: plaintext password storage

### 2.1 What the vulnerability is

A password is stored “in plaintext” when the database contains the exact characters the user typed:

```text
alice | secret123
```

Anyone who can read the database can read every password immediately. No cracking, guessing or special tool is required.

### 2.2 Why it is dangerous

- **Database leak = account takeover.** A leaked `database.sqlite` file, a backup, a replica, a misconfigured web directory or an SQL-injection dump gives the attacker valid credentials for every user, including administrators.
- **Insiders and over-privileged accounts.** Support staff, developers, hosting providers and anyone with read access see the passwords. Users often reuse passwords, so the damage spreads to their other accounts.
- **Backups and logs live longer than the database.** Old copies, debug dumps, error logs and monitoring systems may retain plaintext values for years.
- **Credential stuffing.** Because people reuse passwords, a leaked password can be tried on e-mail, banking and social-media accounts (credential stuffing).
- **No integrity or privacy.** The application cannot tell whether the stored value was changed, and it has no way to protect users if the storage layer is compromised.
- **Compliance problems.** OWASP, NIST, PCI DSS and GDPR-style regulations require passwords to be protected; plaintext storage is a finding in any security audit.

### 2.3 How it appears in this application

`vulnerable/signup.php` builds the `INSERT` by string interpolation and stores the raw password:

```php
$sql = "INSERT INTO users (username, password_hash, role)
        VALUES ('$username', '$password', 'user')";
```

`vulnerable/login.php` compares the raw value in SQL:

```php
$sql = "SELECT ... FROM users
        WHERE username = '$username' AND password_hash = '$password'";
```

Both the SQL injection and the plaintext storage are intentional teaching examples. The `users.php` page added in Lab 4 displays the stored values so the difference is visible on screen.

### 2.4 “Hidden” vs. “hashed”

Renaming a column, masking it in the UI or moving it to another table does **not** fix the problem. Those are examples of security by obscurity: an attacker with database access sees the real value. The only correct fix is to store a **one-way password hash**, not the password and not a reversible encryption of it.

## 3. Recommended password generation and storage

### 3.1 Generating passwords

The goal is a password that is hard to guess and unique to one service:

1. **Prefer length over complex rules.** A long passphrase or a randomly generated password beats a short string with a few symbols. Modern guidance (NIST SP 800-63B) favours length, blocklists and rate limiting over forced character-class rules.
2. **Use randomness, not personal data.** Avoid names, dates, pet names, team names, keyboard patterns (`qwerty`, `123456`) and leetspeak substitutions (`P@ssw0rd`).
3. **Use a unique password per service.** Reuse turns one breach into many.
4. **Use a password manager.** It generates and remembers long random passwords, so the user only has to remember one strong master passphrase.
5. **Use multi-factor authentication (MFA).** A password alone is a single factor; MFA limits the damage of a leaked password.
6. **Check breached-password lists.** Reject passwords known to appear in public breaches.
7. **Never generate passwords from predictable patterns** such as `Company2026!` or `Summer2026!`; attackers know these patterns.

### 3.2 Weak vs. strong examples

| Weak | Why it is weak |
|---|---|
| `123456` | The most common password; cracked instantly. |
| `password` | A dictionary word and a top breached password. |
| `qwerty` | Keyboard pattern. |
| `alice1990` | Username + year; easy to guess from public information. |
| `Secret123!` | Predictable word + predictable suffix; dictionary and rule-based attacks cover it. |
| `P@ssw0rd` | Common leetspeak; in every cracking wordlist. |
| `iloveyou` | A common phrase and a top breached password. |

| Strong | Why it is strong |
|---|---|
| `violet-orbit-cactus-42!` | Several random words plus a number and symbol; long and not a common phrase. |
| `7Qm$vL2#pR9x` | 12 random characters from a large alphabet; no pattern. |
| `correct horse battery staple` | A long passphrase — but note that this exact phrase is now famous and appears in wordlists; generate your own random words. |
| A 5–6 word Diceware passphrase | Large search space, easy to remember, hard to brute-force. |

A good practical rule: use a password manager to generate **16+ random characters**, or a **5–6 word random passphrase**, and never reuse it.

### 3.3 Storing passwords

Passwords should be stored with a **slow, salted, one-way password-hashing function**:

- **Argon2id** — the current first choice (memory-hard, Password Hashing Competition winner, recommended by OWASP).
- **bcrypt** — mature and widely available; uses a cost factor; has a 72-byte input limit.
- **scrypt** — memory-hard alternative.
- **PBKDF2-HMAC** — acceptable where required by standards/FIPS, with a high iteration count.

Recommended properties:

1. **One-way hashing, not encryption.** Encryption is reversible; a password hash is not. Do not use AES/RSA to “encrypt” passwords.
2. **Unique random salt per password.** The salt is stored with the hash and prevents precomputed tables (rainbow tables) and identical hashes for identical passwords.
3. **A work factor that is expensive for attackers.** Tune the algorithm so one hash takes roughly 100–500 ms on the server, and increase the cost over time.
4. **Automatic rehashing.** When the algorithm or cost changes, rehash the password on the next successful login (`password_needs_rehash()`).
5. **Constant-time verification.** Use `password_verify()` rather than comparing strings manually.
6. **Never log or e-mail passwords.** Avoid putting them in URLs, analytics, error messages or debug output.
7. **Rate limiting and MFA.** Hashing protects a stolen database; rate limiting and MFA protect online guessing.
8. **Optional pepper.** An application-wide secret stored outside the database (for example in an environment variable) adds protection if only the database leaks. It complements, and does not replace, a per-password salt.

## 4. Why MD5 and SHA-1 are considered vulnerable

### 4.1 They were designed for speed and integrity, not for passwords

MD5 (1992) and SHA-1 (1995) are general-purpose cryptographic hash functions. They were designed to be fast: fast to compute a checksum, fast to verify a file. That is exactly the opposite of what a password hash needs. A password hash should be **deliberately slow and memory-hungry** so that each guess costs the attacker time and resources.

### 4.2 MD5 is broken

- MD5 produces a 128-bit digest.
- Practical **collision attacks** were demonstrated in 2004 (Wang et al.); chosen-prefix collisions followed. Two different inputs can produce the same MD5 value.
- MD5 is deprecated for security use. It must not be used for signatures, certificates or password storage.

Collisions do not directly reveal a password, but they show that the algorithm is cryptographically broken. For passwords, the bigger practical problem is that MD5 is extremely fast and was usually used **without a salt**.

### 4.3 SHA-1 is broken

- SHA-1 produces a 160-bit digest.
- The **SHAttered** attack (Google/CWI, 2017) produced the first practical collision; chosen-prefix collisions were demonstrated in 2020.
- NIST and browser vendors deprecated SHA-1 for signatures and certificates. It is not suitable for password storage.

### 4.4 Attacks on passwords “hashed” with MD5

If a database stores `md5($password)` without a salt, an attacker who obtains the hashes can use:

1. **Brute force.** Try every candidate password. Fast hashes allow billions of guesses per second on modern GPUs.
2. **Dictionary attacks.** Try common passwords, words, names and leaked password lists.
3. **Rule-based attacks.** Mutate dictionary words: capitalise, append years, substitute letters (`a` → `@`, `o` → `0`), add `!`, etc.
4. **Rainbow tables.** Precomputed tables map millions of hashes back to passwords. Unsalted MD5 is especially vulnerable because the same password always produces the same hash.
5. **Credential stuffing.** Use passwords leaked from one site to attack another site where the user reused them.
6. **Identical-hash recognition.** Without a salt, two users with the same password have the same hash. Cracking one cracks both, and the attacker learns that they share a password.
7. **Collision attacks.** These are mainly relevant to integrity/signatures, but they confirm that MD5 and SHA-1 are no longer trustworthy primitives.

The `hash-demo.php` page demonstrates the core problem with a tiny dictionary: `md5('password')` and `sha1('password')` are matched immediately, while two salted Argon2id/bcrypt hashes of the same password are different and are not found in the dictionary. A real attacker uses far larger wordlists, rules and GPU farms; the demo is only an illustration.

**Important nuance:** even a strong hash does not make a weak password uncrackable. If the password is in a dictionary, an attacker will eventually guess it. The secure hash makes each guess expensive, so long random passwords become impractical to crack.

## 5. Modern password hashing with salt

### 5.1 The recommended functions

| Function | Notes |
|---|---|
| **Argon2id** | Memory-hard, side-channel-resistant hybrid; winner of the Password Hashing Competition; OWASP’s first choice. Parameters: memory, iterations, parallelism. |
| **bcrypt** | Mature, widely supported; cost factor controls the work; 72-byte input limit; `PASSWORD_DEFAULT` in PHP is currently bcrypt. |
| **scrypt** | Memory-hard; good alternative where Argon2 is unavailable. |
| **PBKDF2-HMAC** | Standardised (NIST SP 800-132); use a high iteration count; acceptable when FIPS compliance is required. |

### 5.2 The role of the salt

A salt is a **random value generated for each password** and stored together with the hash. It is not secret; its purpose is uniqueness.

Without a salt:

```text
alice  -> md5("secret123") = 5d7845ac6ee7cfffafc5fe5f35cf666d
bob    -> md5("secret123") = 5d7845ac6ee7cfffafc5fe5f35cf666d
```

One rainbow-table lookup or one cracking effort recovers both passwords.

With a unique salt:

```text
alice  -> hash("salt_A" + "secret123") = 8f14e45f...
bob    -> hash("salt_B" + "secret123") = c9f0f895...
```

The attacker must attack each hash separately, and precomputed tables no longer work. The salt is stored with the hash, for example in an encoded string:

```text
$argon2id$v=19$m=65536,t=4,p=2$<salt>$<hash>
$2y$12$<salt><hash>
```

The salt does not need to be hidden; the password does.

### 5.3 Pepper (optional)

A pepper is an application-wide secret kept outside the database (environment variable, key vault). It is combined with the password before hashing, often with HMAC. If an attacker steals only the database, the pepper is missing and cracking is much harder. A pepper is not a substitute for a per-password salt, and it must be rotated carefully.

### 5.4 Why “hidden” storage helps

“Hidden” here means **irreversible**, not “secret”. Even if the attacker copies the whole `users` table:

- the plaintext password is not there;
- each stored value is a slow, salted hash;
- the attacker must spend time and hardware on every candidate for every user;
- identical passwords do not produce identical hashes;
- a breach of one service does not immediately hand over passwords that users reused elsewhere;
- the defender gains time to force password resets and notify users.

This is the difference between “the attacker can log in as everyone now” and “the attacker has a expensive cracking project with uncertain results”.

### 5.5 PHP’s password API

PHP provides the correct primitives out of the box:

```php
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536, // 64 MiB
    'time_cost'   => 4,
    'threads'     => 2,
]);

if (password_verify($password, $hash)) {
    // login ok
}

if (password_needs_rehash($hash, PASSWORD_ARGON2ID, $options)) {
    // rehash after a successful login
}
```

`password_hash()` generates a random salt automatically and encodes the algorithm, cost parameters, salt and hash into one string. `password_verify()` compares in constant time. `password_get_info()` exposes the algorithm and parameters for diagnostics.

## 6. Implementation

Signup flow:

```text
form -> validate -> hashPassword() -> password_hash() -> database
```

Login flow:

```text
form -> getUserByUsername() -> verifyPassword() -> password_needs_rehash()? -> updateUser(hashPassword()) -> session
```

The `users.php` page classifies each stored value as `plaintext`, `MD5 (unsalted)`, `SHA-1 (unsalted)`, `SHA-256 (unsalted)`, `bcrypt` or `argon2id`. That is how the instructor can see the difference between the vulnerable and secure forms on screen. `getAllUsers()` in `repository/user.repo.php` now also returns `created_at`, so the page can show the full table row.

## 7. Demonstration walkthrough

> Run `php -S localhost:8000`.

1. **Show the current state.** Open `http://localhost:8000/users.php`.
   - If the original demo rows are still present, `alice` / `secret123` and `mallory` / `x` are shown as `plaintext`.
   - If the database was already changed or migrated, note the formats currently shown and continue; step 2 creates a fresh plaintext row.

2. **Create a vulnerable account.** Open `http://localhost:8000/vulnerable/signup.php`, create `bob` / `password123`, then open `users.php`.
   - The new row shows `password123` in the stored-value column.
   - This is the “database leak = account takeover” scenario.

3. **Create secure accounts.** Open `http://localhost:8000/secure/signup.php` and create `carol` / `correct-horse-battery-staple` and `dave` / `correct-horse-battery-staple` (choose a password of at least 8 characters).
   - `users.php` shows `argon2id` or `bcrypt` hashes, not the passwords.
   - The two hashes are different even though the passwords are the same — that is the salt.

4. **Compare MD5/SHA-1 with secure hashes.** Open `http://localhost:8000/hash-demo.php`.
   - Enter `password`.
   - MD5 and SHA-1 are matched from the small dictionary almost instantly.
   - The two secure hashes are different, both verify, and both contain algorithm/cost/salt information.
   - Try `secret123` or the plaintext password you created in step 2.

5. **Upgrade a legacy account by logging in.** Open `http://localhost:8000/secure/login.php` and log in as `bob` / `password123` (the plaintext row from step 2).
   - The login succeeds, and `login()` rehashes the plaintext password.
   - Refresh `users.php`: `bob` now shows a secure hash.

6. **Bulk migration.** Create another plaintext account, `eve` / `letmein`, through `vulnerable/signup.php`. Then open `http://localhost:8000/migrate_passwords.php` and confirm the action.
   - Remaining plaintext rows (for example `eve`) are hashed.
   - MD5/SHA-1/SHA-256 rows are reported as “cannot migrate — password reset required”, because the original password cannot be recovered from a hash.

7. **Confirm the vulnerable side still works.** Create a fresh plaintext account if needed, then use `vulnerable/login.php`.
   - The SQL-injection demo from Lab 2 still works there.
   - The vulnerable form cannot log in a bcrypt/Argon2id account, because it compares plaintext against the hash; this is expected and documented.

8. **Regression check.** Open `guestbook.php` and confirm the Lab 3 guestbook still works and still escapes XSS payloads.

## 8. Expected results

| Scenario | Stored value in `users.password_hash` | `users.php` format |
|---|---|---|
| `vulnerable/signup.php` with `bob` / `password123` | `password123` | `plaintext` |
| `secure/signup.php` with a modern PHP build | `$argon2id$v=19$m=65536,t=4,p=2$...` | `argon2id` |
| `secure/signup.php` without Argon2 support | `$2y$12$...` | `bcrypt` |
| Two secure accounts with the same password | different hashes | `argon2id` / `bcrypt` |
| Legacy MD5 row (if seeded) | 32 hex characters | `MD5 (unsalted)` |
| Legacy SHA-1 row (if seeded) | 40 hex characters | `SHA-1 (unsalted)` |

Known hashes for the demonstration:

```text
MD5("password")    = 5f4dcc3b5aa765d61d8327deb882cf99
SHA-1("password")  = 5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8
MD5("secret123")   = 5d7845ac6ee7cfffafc5fe5f35cf666d
SHA-1("secret123") = f2b14f68eb995facb3a1c35287b778d5bd785511
```

These values are public test vectors, not secrets. They are also why `secret123` is a weak password.

## 9. Production checklist

- [ ] Never store passwords in plaintext or reversible encryption.
- [ ] Use Argon2id (preferred) or bcrypt with a unique salt and a tuned cost.
- [ ] Use `password_hash()`, `password_verify()` and `password_needs_rehash()`.
- [ ] Enforce a minimum length (12+ characters is a good default) and check breached-password lists.
- [ ] Rate-limit login attempts and add MFA.
- [ ] Use HTTPS and secure, HttpOnly, SameSite session cookies.
- [ ] Remove `users.php`, `hash-demo.php` and `migrate_passwords.php` from production; they are teaching pages.
- [ ] Set `ALLOW_LEGACY_PASSWORDS` to `false` after all legacy accounts are migrated.
- [ ] Restrict database file permissions and encrypt backups.
- [ ] Log security events without logging passwords or hashes.

## Appendix A — Lab 3 recap (Guestbook / XSS prevention)

Lab 3 added:

- `guestbook.php` — guestbook form + list of all stored messages;
- `helper/guest.db.helper.php` — SQLite connection + schema for the **guest** database;
- `repository/guest.repo.php` — `createGuestMessage()` / `getAllGuestMessages()` with prepared statements;
- `guest.sqlite` — the guest database (auto-created on the first request).

Schema (database `guest`, single table `guest`):

```sql
CREATE TABLE guest (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    "user"            TEXT NOT NULL,
    text_message      TEXT NOT NULL,
    e_mail            TEXT NOT NULL,
    data_time_message DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

XSS prevention in the guestbook:

1. **Output escaping (main defense):** every value from the database or from the previous request is printed through `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
2. **Store raw, escape on output:** messages are saved unchanged in SQLite; escaping happens in the HTML context only.
3. **Input validation (defense in depth):** required fields, max lengths (name 50, message 1000), and `filter_var(..., FILTER_VALIDATE_EMAIL)`.
4. **Prepared statements:** the `INSERT` uses bound parameters, so the guestbook is not SQL-injectable.
5. **Server-side timestamp:** `data_time_message` is generated by PHP, never taken from the request.
6. **POST → redirect → GET:** a successful post redirects to `guestbook.php`, preventing duplicate inserts on refresh.

To test the Lab 3 behavior, open `http://localhost:8000/guestbook.php` and post payloads such as:

```html
<script>alert('XSS')</script>
<img src=x onerror="alert(document.cookie)">
<svg onload="alert(1)">
" onmouseover="alert(1)
```

They must be displayed as text, not executed.
