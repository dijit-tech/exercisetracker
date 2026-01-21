<?php
/**
 * Database Configuration - DOCKER
 */

// Database connection
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST'));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME'));
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER'));
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', getenv('DB_PASS'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Application settings
if (!defined('APP_URL')) define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
if (!defined('APP_ENV')) define('APP_ENV', getenv('APP_ENV') ?: 'local');

// Session settings
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 7200); // 2 hours
if (!defined('SESSION_NAME')) define('SESSION_NAME', 'goaltracker_session');

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (dev)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
