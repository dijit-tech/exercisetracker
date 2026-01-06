<?php
/**
 * Login API Endpoint
 */

// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session
startSession();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: /index.php?error=Invalid request method');
    exit;
}

// Get credentials
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    ob_end_clean();
    header('Location: /index.php?error=Username and password are required');
    exit;
}

// Authenticate user
$user = authenticateUser($username, $password);

if (!$user) {
    ob_end_clean();
    header('Location: /index.php?error=Invalid username or password');
    exit;
}

// Set session data - THIS IS CRITICAL
setUserSession(
    $user['id'],
    $user['username'],
    $user['email'],
    (bool)$user['is_admin']
);

// Clear output buffer and redirect to dashboard
ob_end_clean();
header('Location: /dashboard.php');
exit;
