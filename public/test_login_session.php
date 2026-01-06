<?php
/**
 * Test Login and Session Saving
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Login Session Test</h1>";

// Test session configuration
echo "<h2>Session Configuration</h2>";
echo "Save Path: " . session_save_path() . "<br>";
echo "Session Name: " . session_name() . "<br>";

// Start session manually
session_name('exercise_tracker_session');
session_start();

echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

// Simulate login by setting session data
echo "<h2>Simulating Login</h2>";
$_SESSION['user_id'] = 999;
$_SESSION['username'] = 'testuser';
$_SESSION['email'] = 'test@example.com';
$_SESSION['is_admin'] = false;
$_SESSION['created'] = time();
$_SESSION['last_activity'] = time();

echo "✓ Session data set<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Try to verify session was written
echo "<h2>Verification</h2>";
echo "Session data in memory: " . (isset($_SESSION['user_id']) ? "✓ EXISTS" : "✗ MISSING") . "<br>";

echo "<h2>Test Result</h2>";
echo "<p>Now refresh this page or click below to see if session persists:</p>";
echo "<a href='/test_login_session.php'>Refresh Page</a><br>";
echo "<a href='/test_session_simple.php'>Check with simple test</a><br>";
echo "<a href='/dashboard.php'>Try Dashboard</a>";
