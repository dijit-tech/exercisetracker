<?php
/**
 * Login API Endpoint - Debug Version
 */

// Start output buffering FIRST
ob_start();

// Capture any output
$debug_output = [];

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session
startSession();

// Check if headers already sent
if (headers_sent($file, $line)) {
    $debug_output[] = "Headers already sent at $file:$line";
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $debug_output[] = "Invalid request method: " . $_SERVER['REQUEST_METHOD'];
    ob_end_clean();
    header('Location: /index.php?error=Invalid request method');
    exit;
}

// Get credentials
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$debug_output[] = "Username: $username";

// Validate input
if (empty($username) || empty($password)) {
    $debug_output[] = "Empty credentials";
    ob_end_clean();
    header('Location: /index.php?error=Username and password are required');
    exit;
}

// Authenticate user
$user = authenticateUser($username, $password);

if (!$user) {
    $debug_output[] = "Authentication failed";
    ob_end_clean();
    header('Location: /index.php?error=Invalid username or password');
    exit;
}

$debug_output[] = "User authenticated: ID=" . $user['id'];

// Set session data
setUserSession(
    $user['id'],
    $user['username'],
    $user['email'],
    (bool)$user['is_admin']
);

$debug_output[] = "Session set. Session ID: " . session_id();
$debug_output[] = "Session data: " . json_encode($_SESSION);

// Check if headers can be sent
if (headers_sent($file, $line)) {
    echo "<h1>ERROR: Headers already sent!</h1>";
    echo "<p>Sent at: $file line $line</p>";
    echo "<h2>Debug Output:</h2><pre>";
    print_r($debug_output);
    echo "</pre>";
    echo "<h2>Output Buffer:</h2><pre>";
    echo htmlspecialchars(ob_get_contents());
    echo "</pre>";
    ob_end_flush();
    exit;
}

// Clear output buffer and redirect to dashboard
ob_end_clean();
header('Location: /dashboard.php');
exit;
