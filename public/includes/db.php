<?php
/**
 * Database Connection
 */

// Load appropriate config based on environment
// Check for dev config first (for local development)
$devConfig = __DIR__ . '/../../config/database_dev.php';
$prodConfig = __DIR__ . '/../../config/database.php';

if (file_exists($devConfig)) {
    require_once $devConfig;
} elseif (file_exists($prodConfig)) {
    require_once $prodConfig;
} else {
    die("Database configuration file not found!");
}

function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $password = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : '');
            $pdo = new PDO($dsn, DB_USER, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}
