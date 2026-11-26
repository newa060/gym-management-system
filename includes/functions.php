<?php
/**
 * Utility functions for input validation, sanitization, and output escaping.
 */

if (defined('TIMEZONE') && TIMEZONE !== '[TIMEZONE]') {
    date_default_timezone_set(TIMEZONE);
}

/**
 * Escapes a string for safe output in HTML to prevent cross-site scripting (XSS).
 *
 * @param mixed $value The string or value to escape.
 * @return string The sanitized string safe for HTML rendering.
 */
function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Trims and sanitizes standard text input.
 *
 * @param string $data Raw input text.
 * @return string Sanitized input string.
 */
function sanitize_input($data) {
    return trim((string)$data);
}

/**
 * Validates whether an email string adheres to RFC standards.
 *
 * @param string $email Email address to validate.
 * @return bool True if valid, false otherwise.
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

