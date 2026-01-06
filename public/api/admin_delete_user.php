<?php
/**
 * Admin API - Delete User
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

// Get user ID
$userId = intval($_POST['user_id'] ?? 0);

// Validate
if ($userId <= 0) {
    ob_end_clean();
    header('Location: /admin.php?error=Invalid user ID');
    exit;
}

// Prevent self-deletion
if ($userId === getCurrentUserId()) {
    ob_end_clean();
    header('Location: /admin.php?error=Cannot delete your own account');
    exit;
}

// Delete user
$result = deleteUser($userId);

ob_end_clean();

if ($result['success']) {
    header('Location: /admin.php?success=User deleted successfully');
} else {
    header('Location: /admin.php?error=' . urlencode($result['error']));
}
exit;
