<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/session.php';

echo "Testing authentication...\n";

$user = authenticateUser('admin', 'password123');
if ($user) {
    echo "✓ Authentication successful!\n";
    echo "User ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    
    // Test session
    startSession();
    setUserSession($user);
    
    echo "\nSession data after setUserSession:\n";
    var_dump($_SESSION);
    
} else {
    echo "✗ Authentication failed!\n";
}
