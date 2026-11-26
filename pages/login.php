<?php
/**
 * User Login Page.
 * Handles authentication for administrators and staff.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/user.php';

// If already logged in, redirect to the dashboard
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error_message = '';
$username_value = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submitted_token)) {
        $error_message = 'Invalid session token. Please try submitting again.';
    } else {
        $username_or_email = sanitize_input($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $username_value = $username_or_email;

        if (empty($username_or_email) || empty($password)) {
            $error_message = 'Please enter both your username and password.';
        } else {
            $pdo = getDbConnection();
            $user_model = new User($pdo);
            $user = $user_model->authenticate($username_or_email, $password);

            if ($user) {
                login_user($user);
                header('Location: ' . BASE_URL . '/pages/dashboard.php');
                exit;
            } else {
                $error_message = 'Invalid username or password.';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$logged_out = isset($_GET['logged_out']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gym System</title>
    <link rel="stylesheet" href="<?php echo escape(BASE_URL); ?>/assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h1>Apex Fitness</h1>
                <p class="subtitle">Gym Management System</p>
            </div>

            <?php if ($logged_out): ?>
                <div class="alert alert-success">
                    You have been logged out.
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo escape($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo escape(BASE_URL); ?>/pages/login.php" method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo escape($username_value); ?>" 
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required
                    >
                </div>

                <button type="submit" class="btn btn-login">Sign in</button>
            </form>
        </div>
    </div>
</body>
</html>
