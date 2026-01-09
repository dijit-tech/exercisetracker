<?php
/**
 * Session Management
 * CRITICAL: This must be rock-solid as previous version had session persistence bugs
 */

// Load config using same logic as db.php
$localConfig = __DIR__ . '/config.php';
$devConfig = __DIR__ . '/../../config/database_dev.php';
$prodConfig = __DIR__ . '/../../config/database.php';

if (!defined('DB_HOST')) {
    if (file_exists($localConfig)) {
        require_once $localConfig;
    } elseif (file_exists($devConfig)) {
        require_once $devConfig;
    } elseif (file_exists($prodConfig)) {
        require_once $prodConfig;
    }
}

/**
 * Start session with secure settings
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set custom session save path (writable directory)
        $sessionPath = __DIR__ . '/../../sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0700, true);
        }
        if (is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }
        
        // Set session name first
        session_name(SESSION_NAME);
        
        // Configure session settings
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
        
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    
    // Check if user_id exists in session
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            destroySession();
            return false;
        }
    }
    
    return true;
}

/**
 * Require user to be logged in (redirect to login if not)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /index.php?error=Please log in to continue');
        exit;
    }
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    requireLogin();
    
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: /dashboard.php?error=Access denied');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

/**
 * Destroy session (logout)
 */
function destroySession() {
    startSession();
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Set user session data after successful login
 */
function setUserSession($userId, $username, $email, $isAdmin = false) {
    startSession();
    
    // Set session data
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['is_admin'] = $isAdmin;
    $_SESSION['created'] = time();
    $_SESSION['last_activity'] = time();
}
