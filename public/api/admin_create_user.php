<?php
/**
 * Admin API - Create User
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin access
requireAdmin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: /admin.php?error=Invalid request method');
    exit;
}

// Get data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';

// Validate
if (empty($username) || empty($email) || empty($password)) {
    ob_end_clean();
    header('Location: /admin.php?error=All fields are required');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    header('Location: /admin.php?error=Invalid email format');
    exit;
}

// Validate password length
if (strlen($password) < 6) {
    ob_end_clean();
    header('Location: /admin.php?error=Password must be at least 6 characters');
    exit;
}

// Create user
$result = createUser($username, $email, $password, $isAdmin);

ob_end_clean();

if ($result['success']) {
    header('Location: /admin.php?success=User created successfully');
} else {
    header('Location: /admin.php?error=' . urlencode($result['error']));
}
exit;
