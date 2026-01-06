<?php
/**
 * Database Configuration - LOCAL DEVELOPMENT
 * For Docker environment
 */

// Database connection
define('DB_HOST', 'db');
define('DB_NAME', 'exercisetracker');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_URL', 'http://localhost:8000');
define('APP_ENV', 'development');

// Session settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('SESSION_NAME', 'exercise_tracker_session');

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
