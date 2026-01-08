<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing auth.php loading...\n\n";

require_once __DIR__ . '/../includes/auth.php';
echo "✓ Auth.php loaded\n\n";

echo "Testing authenticateUser...\n";
$user = authenticateUser('admin', 'password123');

if ($user) {
    echo "✓ Authentication successful!\n";
    echo "User: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
} else {
    echo "✗ Authentication failed!\n";
}
