-- Complete Database Schema for Goal Tracker with Rooms
-- Date: January 7, 2026
-- Includes: Users, Goals, Goal Logs, Rooms, and Room features

-- ============================================
-- CORE TABLES (User & Goal Management)
-- ============================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Goals table (personal goals owned by users)
CREATE TABLE IF NOT EXISTS goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_title VARCHAR(200) NOT NULL,
    goal_category VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'paused', 'archived', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_category (goal_category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Goal logs table (daily tracking)
CREATE TABLE IF NOT EXISTS goal_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,
    log_date DATE NOT NULL,
    completed BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_goal_date (goal_id, log_date),
    INDEX idx_user_date (user_id, log_date),
    INDEX idx_goal_date (goal_id, log_date),
    INDEX idx_date (log_date),
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ROOMS TABLES (Competition & Social Features)
-- ============================================

-- Rooms table (competition spaces)
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    creator_user_id INT NOT NULL,
    privacy ENUM('private', 'invite-only') DEFAULT 'private',
    status ENUM('active', 'paused', 'archived', 'deleted') DEFAULT 'active',
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_creator (creator_user_id),
    INDEX idx_status (status),
    INDEX idx_privacy (privacy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room members table (who's in which room)
CREATE TABLE IF NOT EXISTS room_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'left') DEFAULT 'active',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user (room_id, user_id),
    INDEX idx_user_rooms (user_id, status),
    INDEX idx_room_members (room_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room goals table (which goals are tracked in which room)
-- CRITICAL: Enables users to track different goals in different rooms
CREATE TABLE IF NOT EXISTS room_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,  -- Denormalized for faster queries
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_goal (room_id, goal_id),
    INDEX idx_room (room_id),
    INDEX idx_goal (goal_id),
    INDEX idx_user (user_id),
    INDEX idx_room_user (room_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room invites table (email-based invitation system)
CREATE TABLE IF NOT EXISTS room_invites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    inviter_user_id INT NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    invitee_user_id INT NULL,  -- Matched user if email is registered
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invitee_email (invitee_email),
    INDEX idx_invitee_user (invitee_user_id),
    INDEX idx_status (status),
    INDEX idx_room (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room posts table (activity feed / forum)
CREATE TABLE IF NOT EXISTS room_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    post_type ENUM('message', 'achievement', 'milestone', 'system') DEFAULT 'message',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_posts (room_id, created_at DESC),
    INDEX idx_user_posts (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room achievements table (digital badges)
CREATE TABLE IF NOT EXISTS room_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,  -- e.g., 'first_100_points', 'perfect_week'
    achievement_name VARCHAR(255) NOT NULL,
    achievement_description TEXT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_achievements (room_id, user_id),
    INDEX idx_user_achievements (user_id, earned_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- Insert default admin and test user
INSERT INTO users (username, email, password_hash, is_admin) VALUES
('admin', 'admin@goaltracker.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
('testuser', 'test@goaltracker.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', FALSE);
-- Password for both: password123

-- ============================================
-- USEFUL QUERIES
-- ============================================

-- Get user's rooms with stats
/*
SELECT 
    r.id,
    r.name,
    r.status,
    COUNT(DISTINCT rm.user_id) as member_count,
    COUNT(DISTINCT rg.goal_id) as total_goals_tracked,
    r.start_date,
    r.end_date
FROM rooms r
LEFT JOIN room_members rm ON rm.room_id = r.id AND rm.status = 'active'
LEFT JOIN room_goals rg ON rg.room_id = r.id
WHERE r.id IN (
    SELECT room_id FROM room_members WHERE user_id = ? AND status = 'active'
)
GROUP BY r.id;
*/

-- Get room leaderboard for specific month
/*
SELECT 
    u.id,
    u.username,
    COUNT(DISTINCT gl.log_date) as days_active,
    SUM(
        CASE 
            WHEN daily_pct = 1.0 THEN 10
            WHEN daily_pct >= 0.67 THEN 7
            WHEN daily_pct >= 0.34 THEN 5
            WHEN daily_pct > 0 THEN 2
            ELSE 0
        END
    ) as total_points
FROM room_members rm
JOIN users u ON u.id = rm.user_id
LEFT JOIN (
    SELECT 
        gl.user_id,
        gl.log_date,
        COUNT(*) as total_goals,
        SUM(CASE WHEN gl.completed THEN 1 ELSE 0 END) as completed_goals,
        SUM(CASE WHEN gl.completed THEN 1 ELSE 0 END) * 1.0 / COUNT(*) as daily_pct
    FROM goal_logs gl
    WHERE gl.goal_id IN (
        SELECT goal_id FROM room_goals WHERE room_id = ?
    )
    AND gl.log_date BETWEEN ? AND ?
    GROUP BY gl.user_id, gl.log_date
) gl ON gl.user_id = rm.user_id
WHERE rm.room_id = ?
  AND rm.status = 'active'
GROUP BY u.id, u.username
ORDER BY total_points DESC;
*/

-- Get goals user is tracking in a room
/*
SELECT 
    g.id,
    g.goal_title,
    g.goal_category,
    rg.added_at
FROM room_goals rg
JOIN goals g ON g.id = rg.goal_id
WHERE rg.room_id = ?
  AND rg.user_id = ?
  AND g.status = 'active'
ORDER BY rg.added_at ASC;
*/

-- Verification
SELECT 'Schema created successfully!' as status;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
