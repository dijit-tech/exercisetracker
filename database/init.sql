-- Exercise Tracker Database Schema
-- Created: 2026-01-05
-- NOTE: For production, import this into your existing database via phpMyAdmin
-- For local Docker, the database is created automatically

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exercises table
CREATE TABLE exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    exercise_date DATE NOT NULL,
    exercise_type VARCHAR(50) NOT NULL,
    duration_minutes INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, exercise_date),
    INDEX idx_date (exercise_date),
    INDEX idx_type (exercise_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert test users
-- Password for all users: password123
INSERT INTO users (username, email, password_hash, is_admin) VALUES
('admin', 'admin@exercisetracker.local', '$2y$12$UL52o8stv6aF/sKE4ChPxOO4ltfdvLOZLK/ArlNg8jzci7tUqp07u', TRUE),
('testuser', 'test@exercisetracker.local', '$2y$12$UL52o8stv6aF/sKE4ChPxOO4ltfdvLOZLK/ArlNg8jzci7tUqp07u', FALSE);

-- Insert sample exercises for testing
INSERT INTO exercises (user_id, exercise_date, exercise_type, duration_minutes, notes) VALUES
(2, CURDATE(), 'Running', 30, 'Morning run in the park'),
(2, CURDATE(), 'Strength Training', 45, 'Upper body workout'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Cycling', 60, 'Evening bike ride'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Yoga', 30, 'Relaxing yoga session');

-- Verify setup
SELECT 'Users created:' as info;
SELECT id, username, email, is_admin FROM users;

SELECT 'Sample exercises:' as info;
SELECT e.id, u.username, e.exercise_date, e.exercise_type, e.duration_minutes 
FROM exercises e 
JOIN users u ON e.user_id = u.id 
ORDER BY e.exercise_date DESC;
