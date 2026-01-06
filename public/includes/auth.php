<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Authenticate user with username and password
 * Returns user data array on success, false on failure
 */
function authenticateUser($username, $password) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, is_admin 
        FROM users 
        WHERE username = ? OR email = ?
    ");
    
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    
    // Update last login
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    return $user;
}

/**
 * Register a new user
 * Returns user ID on success, false on failure
 */
function registerUser($username, $email, $password) {
    $pdo = getDbConnection();
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username already exists'];
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already exists'];
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, is_admin) 
        VALUES (?, ?, ?, FALSE)
    ");
    
    try {
        $stmt->execute([$username, $email, $passwordHash]);
        $userId = $pdo->lastInsertId();
        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, is_admin, created_at, last_login 
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get all users (admin only)
 */
function getAllUsers() {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("
        SELECT id, username, email, is_admin, created_at, last_login 
        FROM users 
        ORDER BY created_at DESC
    ");
    
    return $stmt->fetchAll();
}

/**
 * Create a new user (admin only)
 */
function createUser($username, $email, $password, $isAdmin = false) {
    $pdo = getDbConnection();
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username already exists'];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email already exists'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, is_admin, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0]);
        
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log("Create user failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to create user'];
    }
}

/**
 * Update user (admin only)
 */
function updateUser($userId, $username, $email, $isAdmin = false, $newPassword = null) {
    $pdo = getDbConnection();
    
    try {
        // Check if username is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username already exists'];
        }
        
        // Check if email is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email already exists'];
        }
        
        // Update user
        if ($newPassword) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, password_hash = ?, is_admin = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0, $userId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, is_admin = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $isAdmin ? 1 : 0, $userId]);
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Update user failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update user'];
    }
}

/**
 * Delete user (admin only)
 */
function deleteUser($userId) {
    $pdo = getDbConnection();
    
    try {
        // Prevent deleting the last admin
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
        $result = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['is_admin'] && $result['count'] <= 1) {
            return ['success' => false, 'error' => 'Cannot delete the last admin user'];
        }
        
        // Delete user (exercises will be cascade deleted)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Delete user failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete user'];
    }
}
