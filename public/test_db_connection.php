<?php
/**
 * Test Database Connection
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Database Connection Test</h1>";

// Test config loading
echo "<h2>1. Testing config load...</h2>";
try {
    require_once __DIR__ . '/includes/../config/database.php';
    echo "✓ Config loaded<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "DB_USER: " . DB_USER . "<br>";
} catch (Exception $e) {
    echo "✗ Config failed: " . $e->getMessage() . "<br>";
    die();
}

// Test db.php loading
echo "<h2>2. Testing db.php load...</h2>";
try {
    require_once __DIR__ . '/includes/db.php';
    echo "✓ db.php loaded<br>";
} catch (Exception $e) {
    echo "✗ db.php failed: " . $e->getMessage() . "<br>";
    die();
}

// Test connection
echo "<h2>3. Testing database connection...</h2>";
try {
    $pdo = getDbConnection();
    echo "✓ Database connected<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Users table accessible (count: " . $result['count'] . ")<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test session
echo "<h2>4. Testing session...</h2>";
try {
    require_once __DIR__ . '/includes/session.php';
    startSession();
    echo "✓ Session started<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "✗ Session failed: " . $e->getMessage() . "<br>";
}

echo "<h2>All tests complete!</h2>";
