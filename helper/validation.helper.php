<?php
declare(strict_types=1);

/**
 * Validation rules shared by signup, the administrator edit form and the manager profile.
 */

/**
 * @return string[] list of validation errors (empty when valid)
 */
function usernameErrors(string $username): array
{
    if ($username === '') {
        return ['Username is required.'];
    }
    if (strlen($username) < 3 || strlen($username) > 20) {
        return ['Username must be 3-20 characters.'];
    }
    if (preg_match('/^[a-zA-Z0-9_]+$/', $username) !== 1) {
        return ['Username may contain only letters, digits and underscore.'];
    }

    return [];
}

/**
 * @return string[] list of validation errors (empty when valid)
 */
function newPasswordErrors(string $password): array
{
    if ($password === '') {
        return ['Password is required.'];
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        return ['Password must be 8-72 characters.'];
    }

    return [];
}
