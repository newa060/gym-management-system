<?php
/**
 * User Logout Handler.
 * Clears session and redirects to the login page.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();

header('Location: ' . BASE_URL . '/pages/login.php?logged_out=1');
exit;
