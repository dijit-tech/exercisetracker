-- Migration Script: Exercise Tracker to Goal Tracker
-- Date: January 7, 2026

-- Step 1: Create new goals table (goal definitions)
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

-- Step 2: Create goal_logs table (daily tracking)
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

-- Step 3: Migrate existing exercise data to goals (optional - for backward compatibility)
-- Uncomment if you want to preserve old exercise data as goals
/*
INSERT INTO goals (user_id, goal_title, goal_category, start_date, status)
SELECT DISTINCT 
    user_id,
    CONCAT('Track ', exercise_type) as goal_title,
    'Health & Fitness' as goal_category,
    MIN(exercise_date) as start_date,
    'active' as status
FROM exercises
GROUP BY user_id, exercise_type;

INSERT INTO goal_logs (goal_id, user_id, log_date, completed, notes)
SELECT 
    g.id as goal_id,
    e.user_id,
    e.exercise_date as log_date,
    TRUE as completed,
    CONCAT(e.duration_minutes, ' minutes', IF(e.notes != '', CONCAT(' - ', e.notes), '')) as notes
FROM exercises e
JOIN goals g ON g.user_id = e.user_id 
    AND g.goal_title = CONCAT('Track ', e.exercise_type);
*/

-- Step 4: Rename old exercises table (backup - don't delete yet)
-- RENAME TABLE exercises TO exercises_backup;

-- Verification queries
SELECT 'Goals table created' as status, COUNT(*) as row_count FROM goals;
SELECT 'Goal logs table created' as status, COUNT(*) as row_count FROM goal_logs;
