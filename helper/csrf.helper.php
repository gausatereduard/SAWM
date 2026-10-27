<?php
declare(strict_types=1);

require_once __DIR__ . '/session.helper.php';

function csrfToken(): string
{
    if (!isset($_SESSION['csrfToken']) || !is_string($_SESSION['csrfToken'])) {
        $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrfToken'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrfValid(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || $submitted === '') {
        return false;
    }

    $expected = $_SESSION['csrfToken'] ?? '';

    return is_string($expected) && $expected !== '' && hash_equals($expected, $submitted);
}

function csrfRotate(): void
{
    unset($_SESSION['csrfToken']);
}
