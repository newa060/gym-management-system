<?php
/**
 * Utility functions for input validation, sanitization, settings, and output escaping.
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

/**
 * Retrieves a setting value by key from the database.
 *
 * @param PDO $pdo Active database connection.
 * @param string $setting_key Key name of the setting.
 * @param string $default_value Fallback value if key is not found.
 * @return string Stored setting value or fallback.
 */
function get_setting($pdo, $setting_key, $default_value = '') {
    $statement = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1");
    $statement->execute([':setting_key' => $setting_key]);
    $setting_row = $statement->fetch();
    return $setting_row ? (string)$setting_row['setting_value'] : $default_value;
}

/**
 * Updates or inserts a setting value into the database.
 *
 * @param PDO $pdo Active database connection.
 * @param string $setting_key Key name of the setting.
 * @param string $setting_value New value to store.
 * @return bool True on success, false on failure.
 */
function update_setting($pdo, $setting_key, $setting_value) {
    $statement = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES (:setting_key, :setting_value)
        ON DUPLICATE KEY UPDATE setting_value = :setting_value_update
    ");
    return $statement->execute([
        ':setting_key' => $setting_key,
        ':setting_value' => $setting_value,
        ':setting_value_update' => $setting_value,
    ]);
}
