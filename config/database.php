<?php
/**
 * Database Configuration - PRODUCTION
 * For exercisetracker.dijit.tech on ipage.com
 */

// Database connection
define('DB_HOST', 'anandlonkar.ipagemysql.com');
define('DB_NAME', 'apps_exercisetracker');
define('DB_USER', 'apps_exercise');
define('DB_PASS', 'ExerciseTracker2026!');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_URL', 'https://exercisetracker.dijit.tech');
define('APP_ENV', 'production');

// Session settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('SESSION_NAME', 'exercise_tracker_session');

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
