-- Complete Database Schema for Goal Tracker (v2 Challenges)
-- Includes: Users, Goals, Goal Logs, Challenges, and Challenge features
-- Generated: 2026-01-17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

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
    last_login TIMESTAMP NULL,
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
-- CHALLENGE TABLES (Competition & Social Features)
-- ============================================

-- Challenges table (formerly rooms)
CREATE TABLE IF NOT EXISTS challenges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    creator_user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Other',
    privacy ENUM('public', 'private', 'invite-only') DEFAULT 'private',
    status ENUM('active', 'paused', 'archived', 'deleted') DEFAULT 'active',
    is_default TINYINT(1) DEFAULT 0,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_creator (creator_user_id),
    INDEX idx_status (status),
    INDEX idx_privacy (privacy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Challenge members table
CREATE TABLE IF NOT EXISTS challenge_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'left') DEFAULT 'active',
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_challenge_user (challenge_id, user_id),
    INDEX idx_user_challenges (user_id, status),
    INDEX idx_challenge_members (challenge_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Challenge goals table (linking goals to challenges)
CREATE TABLE IF NOT EXISTS challenge_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,  -- Denormalized for faster queries
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_challenge_goal (challenge_id, goal_id),
    INDEX idx_challenge (challenge_id),
    INDEX idx_goal (goal_id),
    INDEX idx_user (user_id),
    INDEX idx_challenge_user (challenge_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Challenge invites table
CREATE TABLE IF NOT EXISTS challenge_invites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    inviter_user_id INT NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    invitee_user_id INT NULL,  -- Matched user if email is registered
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invitee_email (invitee_email),
    INDEX idx_invitee_user (invitee_user_id),
    INDEX idx_status (status),
    INDEX idx_challenge (challenge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Challenge posts table
CREATE TABLE IF NOT EXISTS challenge_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    post_type ENUM('message', 'achievement', 'milestone', 'system') DEFAULT 'message',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_challenge_posts (challenge_id, created_at DESC),
    INDEX idx_user_posts (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Challenge achievements table
CREATE TABLE IF NOT EXISTS challenge_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,
    achievement_name VARCHAR(255) NOT NULL,
    achievement_description TEXT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_challenge_achievements (challenge_id, user_id),
    INDEX idx_user_achievements (user_id, earned_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
