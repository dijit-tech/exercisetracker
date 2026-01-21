<?php
/**
 * Database Connection
 */

// Load appropriate config based on environment
$localConfig = __DIR__ . '/config.php';
$devConfig = __DIR__ . '/../../config/database_dev.php';
$prodConfig = __DIR__ . '/../../config/database.php';

// Check for Docker environment variables first
if (getenv('DB_HOST')) {
    require_once __DIR__ . '/config_docker.php';
}
// Check for local config first (deployed in same directory)
elseif (file_exists($localConfig)) {
    require_once $localConfig;
} elseif (file_exists($devConfig)) {
    require_once $devConfig;
} elseif (file_exists($prodConfig)) {
    require_once $prodConfig;
} else {
    // Fallback: try to guess based on standard paths if relative paths fail (hosting issues)
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php',
        dirname(__DIR__, 2) . '/config/database.php'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
    
    die("Database configuration file not found! Searched: " . $prodConfig);
}

function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            // Debug logging
            error_log("Connecting to DB: Host=" . DB_HOST . ", DB=" . DB_NAME . ", User=" . DB_USER);
            
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
