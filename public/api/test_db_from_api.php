<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing DB config loading from api/login.php context...\n\n";

echo "Current directory: " . __DIR__ . "\n";
echo "Config path: " . __DIR__ . '/../includes/db.php' . "\n\n";

require_once __DIR__ . '/../includes/db.php';

echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n\n";

echo "Testing connection...\n";
try {
    $pdo = getDbConnection();
    echo "✓ Connection successful!\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Query successful! Found " . $result['count'] . " users\n";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
}
