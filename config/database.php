<?php
/**
 * Database Configuration - PRODUCTION
 * For goaltracker.dijit.tech on ipage.com
 */

// Database connection
define('DB_HOST', 'anandlonkar.ipagemysql.com');
define('DB_NAME', 'goaltracker_v2');
define('DB_USER', 'goaltrackeradmin');
define('DB_PASS', 'Just4Goals!');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_URL', 'https://goaltracker.dijit.tech');
define('APP_ENV', 'production');

// Session settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('SESSION_NAME', 'goaltracker_session');

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
