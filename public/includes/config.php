<?php
/**
 * Database Configuration - BETA
 * For goaltrackerbeta.dijit.tech on ipage.com
 */

// Database connection
define('DB_HOST', 'localhost');
define('DB_NAME', 'goaltracker');
define('DB_USER', 'root');
define('DB_PASS', ''); 
// Note: legacy config used DB_PASSWORD, new uses DB_PASS. db.php checks for both usually or we check db.php
define('DB_PASSWORD', ''); // Keep for compatibility
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_URL', 'https://goaltrackerbeta.dijit.tech');
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

// Microsoft Graph API Mail Settings
define('MAIL_TENANT_ID', 'your-tenant-id');
define('MAIL_CLIENT_ID', 'your-client-id');
define('MAIL_CLIENT_SECRET', getenv('MAIL_CLIENT_SECRET'));
define('MAIL_FROM_ADDRESS', 'goaltracker@example.com'); // Please verify: is this dijit.tech?
define('MAIL_FROM_NAME', 'Goal Tracker');

ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
