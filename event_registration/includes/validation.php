<?php
// includes/validation.php
// ------------------------------------------------------------
// Server-side checks for data typed in forms. The JavaScript checks in the
// browser can be bypassed, so these PHP checks are the ones we rely on.

// Reads one text value from a submitted form and removes spaces from both ends.
// If the value is missing (or not plain text) an empty string is returned.
function post_str(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// Reads a value from a submitted form WITHOUT trimming. Used for passwords.
function post_raw(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

// Name: starts with a letter, then letters, spaces, dots, apostrophes or hyphens (2-100 characters)
function is_valid_name(string $name): bool
{
    return preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/", $name) === 1;
}

// Email: simple standard format, for example anitha@example.com
function is_valid_email(string $email): bool
{
    return strlen($email) <= 100
        && preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $email) === 1;
}

// Phone: exactly 10 digits, starting with 6, 7, 8 or 9 (Indian mobile number)
function is_valid_phone(string $phone): bool
{
    return preg_match('/^[6-9][0-9]{9}$/', $phone) === 1;
}

// Admin username: letters, digits and underscore, 3-50 characters
function is_valid_username(string $username): bool
{
    return preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) === 1;
}

// Password: 8 to 64 characters with at least one uppercase letter,
// one lowercase letter and one digit
function is_valid_password(string $password): bool
{
    $length = strlen($password);

    return $length >= 8
        && $length <= 64
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/[a-z]/', $password) === 1
        && preg_match('/[0-9]/', $password) === 1;
}

// The message shown when a password is rejected
function password_rules(): string
{
    return 'Password must be 8 to 64 characters and contain at least one uppercase letter, '
         . 'one lowercase letter and one digit.';
}