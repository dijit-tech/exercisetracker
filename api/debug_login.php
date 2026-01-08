<?php
/**
 * Debug Login Endpoint
 */

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== DEBUG START ===\n";

// Check if any output has been sent
echo "Headers sent: " . (headers_sent($file, $line) ? "YES at $file:$line" : "NO") . "\n";

require_once __DIR__ . '/../includes/session.php';
echo "After session.php - Headers sent: " . (headers_sent($file, $line) ? "YES at $file:$line" : "NO") . "\n";

require_once __DIR__ . '/../includes/auth.php';
echo "After auth.php - Headers sent: " . (headers_sent($file, $line) ? "YES at $file:$line" : "NO") . "\n";

startSession();
echo "After startSession() - Headers sent: " . (headers_sent($file, $line) ? "YES at $file:$line" : "NO") . "\n";

echo "\n=== POST DATA ===\n";
print_r($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    echo "\n=== AUTHENTICATING ===\n";
    echo "Username: $username\n";
    
    $user = authenticateUser($username, $password);
    
    if ($user) {
        echo "\n=== AUTH SUCCESS ===\n";
        print_r($user);
        
        echo "\n=== SETTING SESSION ===\n";
        setUserSession(
            $user['id'],
            $user['username'],
            $user['email'],
            (bool)$user['is_admin']
        );
        
        echo "\n=== SESSION DATA ===\n";
        print_r($_SESSION);
        
        echo "\n=== ATTEMPTING REDIRECT ===\n";
        echo "Headers sent before redirect: " . (headers_sent($file, $line) ? "YES at $file:$line" : "NO") . "\n";
    } else {
        echo "\n=== AUTH FAILED ===\n";
    }
}
