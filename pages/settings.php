<?php
/**
 * Settings Page (Removed).
 * Redirects incoming requests directly to the dashboard.
 */

require_once __DIR__ . '/../config/config.php';

header('Location: ' . BASE_URL . '/pages/dashboard.php');
exit;
