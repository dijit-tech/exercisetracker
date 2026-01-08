<?php
/**
 * Database Configuration - DEVELOPMENT
 * For local Docker environment
 */

// Database connection
define('DB_HOST', 'db'); // Docker service name
define('DB_NAME', 'exercisetracker');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_URL', 'http://localhost:8000');
define('APP_ENV', 'development');

// Session settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('SESSION_NAME', 'goal_tracker_session');

// Debug mode
define('DEBUG_MODE', true);

// Display errors in development
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
