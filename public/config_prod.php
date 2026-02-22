<?php
/**
 * Production Database Configuration
 * This file contains the production database credentials
 */

define('DB_HOST', 'anandlonkar.ipagemysql.com');
define('DB_NAME', 'goaltracker_prod');
define('DB_USER', 'goaltracker_user');
define('DB_PASS', 'Just4Goals!');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// Environment
define('APP_ENV', 'production');
define('DEBUG', false);

// Session configuration
define('SESSION_SAVE_PATH', __DIR__ . '/../sessions');

// Microsoft Graph API Mail Settings
define('MAIL_TENANT_ID', getenv('MAIL_TENANT_ID') ?: 'your-tenant-id');
define('MAIL_CLIENT_ID', getenv('MAIL_CLIENT_ID') ?: 'your-client-id');
define('MAIL_CLIENT_SECRET', getenv('MAIL_CLIENT_SECRET'));
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'goaltracker@example.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Goal Tracker');


// Ensure sessions directory exists
if (!file_exists(SESSION_SAVE_PATH)) {
    mkdir(SESSION_SAVE_PATH, 0755, true);
}
