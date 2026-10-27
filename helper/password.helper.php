<?php
declare(strict_types=1);

/**
 * Password hashing and verification helpers for Lab 4.
 *
 * New passwords are hashed with Argon2id when the PHP build supports it,
 * otherwise with PASSWORD_DEFAULT (currently bcrypt). Both algorithms
 * generate a random salt automatically.
 *
 * ALLOW_LEGACY_PASSWORDS exists only to demonstrate the migration of the
 * intentionally insecure Lab 2 rows (plaintext / MD5 / SHA-1 / SHA-256).
 * Set it to false after every legacy account has been upgraded.
 */
const ALLOW_LEGACY_PASSWORDS = true;

function passwordAlgorithm(): string
{
    if (
        defined('PASSWORD_ARGON2ID')
        && function_exists('password_algos')
        && in_array(PASSWORD_ARGON2ID, password_algos(), true)
    ) {
        return PASSWORD_ARGON2ID;
    }

    return PASSWORD_DEFAULT;
}

function passwordOptions(): array
{
    if (passwordAlgorithm() === 'argon2id') {
        return [
            'memory_cost' => 65536, // 64 MiB; lower this if memory_limit is too small
            'time_cost' => 4,
            'threads' => 2,
        ];
    }

    return ['cost' => 12];
}

function hashPassword(string $password): string
{
    $algorithm = passwordAlgorithm();

    try {
        $hash = password_hash($password, $algorithm, passwordOptions());
    } catch (Throwable $exception) {
        // Argon2id can fail on hosts with a low memory limit; fall back to bcrypt.
        $hash = false;
    }

    if ($hash === false && $algorithm !== PASSWORD_DEFAULT) {
        $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    if ($hash === false) {
        throw new RuntimeException('Unable to hash the password.');
    }

    return $hash;
}

/**
 * Returns a human-readable description of the stored value.
 */
function passwordStorageFormat(string $stored): string
{
    $info = password_get_info($stored);
    $algorithm = $info['algoName'];

    if (in_array($algorithm, ['argon2id', 'argon2i', 'bcrypt'], true)) {
        return $algorithm;
    }

    if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
        return 'MD5 (unsalted)';
    }

    if (preg_match('/^[a-f0-9]{40}$/i', $stored) === 1) {
        return 'SHA-1 (unsalted)';
    }

    if (preg_match('/^[a-f0-9]{64}$/i', $stored) === 1) {
        return 'SHA-256 (unsalted)';
    }

    return 'plaintext';
}

function isSecurePasswordHash(string $stored): bool
{
    return in_array(passwordStorageFormat($stored), ['bcrypt', 'argon2i', 'argon2id'], true);
}

function verifyPassword(string $password, string $stored): bool
{
    $format = passwordStorageFormat($stored);

    if (in_array($format, ['bcrypt', 'argon2i', 'argon2id'], true)) {
        return password_verify($password, $stored);
    }

    if (!ALLOW_LEGACY_PASSWORDS) {
        return false;
    }

    switch ($format) {
        case 'plaintext':
            return hash_equals($stored, $password);
        case 'MD5 (unsalted)':
            return hash_equals(strtolower($stored), md5($password));
        case 'SHA-1 (unsalted)':
            return hash_equals(strtolower($stored), sha1($password));
        case 'SHA-256 (unsalted)':
            return hash_equals(strtolower($stored), hash('sha256', $password));
        default:
            return false;
    }
}

function passwordNeedsRehash(string $stored): bool
{
    if (!isSecurePasswordHash($stored)) {
        return true;
    }

    return password_needs_rehash($stored, passwordAlgorithm(), passwordOptions());
}
