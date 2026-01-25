<?php
/**
 * Guest Login API
 * Creates ephemeral SQLite database and logs user in
 */
ini_set('display_errors', '0');
error_reporting(0);

require_once '../includes/session.php';
require_once '../includes/sqlite_helper.php';

startSession();

// Generate unique session ID for Guest
$guestId = uniqid('guest_', true);

// Initialize DB and Seed Data
try {
    $userId = initGuestDb($guestId);
    $dbPath = getGuestDbPath($guestId);
    
    // Set Session Variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = 'Guest User';
    $_SESSION['is_admin'] = false;
    $_SESSION['is_guest'] = true;
    $_SESSION['guest_id'] = $guestId;
    $_SESSION['guest_db_path'] = $dbPath;
    $_SESSION['email'] = 'guest@example.com';
    $_SESSION['logged_in'] = true;
    
    // Redirect to Dashboard
    header('Location: /dashboard.php?guest_welcome=1');
    exit;
    
} catch (Exception $e) {
    die("Error initializing guest mode: " . $e->getMessage());
}
?>