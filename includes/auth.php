<?php
/**
 * Authentication and Session Management Helper.
 * Provides session lifecycle, authorization guards, and CSRF protection.
 */

require_once __DIR__ . '/../config/config.php';

// Initialize session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks whether the current request is from an authenticated user.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Retrieves the currently authenticated user's session data.
 *
 * @return array|null User session data array, or null if not authenticated.
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'admin',
    ];
}

/**
 * Enforces authentication on protected pages.
 * Redirects unauthenticated users directly to the login page.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

/**
 * Logs in a user by populating session variables and regenerating the session ID.
 *
 * @param array $user User database record.
 */
function login_user($user) {
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'admin';
}

/**
 * Logs out the current user, clears all session variables, and destroys the session.
 */
function logout_user() {
    $_SESSION = [];

    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Generates or retrieves an existing CSRF token for the session.
 *
 * @return string CSRF token string.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted CSRF token against the session token.
 *
 * @param string|null $token Submitted CSRF token.
 * @return bool True if valid, false otherwise.
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}
