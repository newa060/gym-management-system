<?php
/**
 * Shared HTML page header and top navigation bar.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce authentication on all pages using the shared header
require_login();

$current_user = current_user();
$current_page_title = isset($page_title) ? $page_title : 'Gym Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($current_page_title); ?> - Gym System</title>
    <!-- Styled with custom stylesheet -->
    <link rel="stylesheet" href="<?php echo escape(BASE_URL); ?>/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-container">
            <div class="brand-logo">
                <a href="<?php echo escape(BASE_URL); ?>/pages/dashboard.php">Gym System</a>
            </div>
            <nav class="main-navigation">
                <ul class="nav-list">
                    <li><a href="<?php echo escape(BASE_URL); ?>/pages/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo escape(BASE_URL); ?>/pages/members.php">Members</a></li>
                    <li><a href="<?php echo escape(BASE_URL); ?>/pages/plans.php">Membership Plans</a></li>
                    <li><a href="<?php echo escape(BASE_URL); ?>/pages/schedule.php">Class Schedule</a></li>
                    <li><a href="<?php echo escape(BASE_URL); ?>/pages/registration_form.php">Class Registration</a></li>
                </ul>
            </nav>
            <div class="header-user-actions">
                <span class="user-greeting">Hi, <strong><?php echo escape($current_user['name'] ?? 'Admin'); ?></strong></span>
                <a href="<?php echo escape(BASE_URL); ?>/pages/logout.php" class="btn btn-sm btn-logout">Logout</a>
            </div>
        </div>
    </header>
    <main class="main-content container">
