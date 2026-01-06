<?php
/**
 * Admin API - Update User
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
$userId = intval($_POST['user_id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';

// Validate
if ($userId <= 0 || empty($username) || empty($email)) {
    ob_end_clean();
    header('Location: /admin.php?error=User ID, username and email are required');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    header('Location: /admin.php?error=Invalid email format');
    exit;
}

// Validate password length if provided
if (!empty($newPassword) && strlen($newPassword) < 6) {
    ob_end_clean();
    header('Location: /admin.php?error=Password must be at least 6 characters');
    exit;
}

// Update user
$result = updateUser($userId, $username, $email, $isAdmin, $newPassword ?: null);

ob_end_clean();

if ($result['success']) {
    header('Location: /admin.php?success=User updated successfully');
} else {
    header('Location: /admin.php?error=' . urlencode($result['error']));
}
exit;
