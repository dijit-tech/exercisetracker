<?php
/**
 * Goal Management Functions
 * Handles CRUD operations for goals and goal logging
 */

require_once __DIR__ . '/db.php';

// ==================== GOAL CATEGORIES ====================

function getGoalCategories() {
    return [
        'Reading',
        'Learning',
        'Health & Fitness',
        'Meditation',
        'Writing',
        'Creative Work',
        'Professional Development',
        'Financial',
        'Relationships',
        'Personal Projects',
        'Other'
    ];
}

// ==================== GOAL CRUD OPERATIONS ====================

/**
 * Create a new goal
 */
function createGoal($userId, $title, $category, $startDate, $endDate = null) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO goals (user_id, goal_title, goal_category, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([$userId, $title, $category, $startDate, $endDate]);
    return $db->lastInsertId();
}

/**
 * Update an existing goal
 */
function updateGoal($goalId, $userId, $title, $category, $endDate = null) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE goals 
        SET goal_title = ?, goal_category = ?, end_date = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$title, $category, $endDate, $goalId, $userId]);
}

/**
 * Pause a goal
 */
function pauseGoal($goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE goals 
        SET status = 'paused', updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$goalId, $userId]);
}

/**
 * Resume a paused goal
 */
function resumeGoal($goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE goals 
        SET status = 'active', updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$goalId, $userId]);
}

/**
 * Archive a goal (mark as completed/finished)
 */
function archiveGoal($goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE goals 
        SET status = 'archived', updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$goalId, $userId]);
}

/**
 * Delete a goal (soft delete by marking status)
 */
function deleteGoal($goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE goals 
        SET status = 'deleted', updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$goalId, $userId]);
}

/**
 * Permanently delete a goal and all its logs
 */
function permanentlyDeleteGoal($goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        DELETE FROM goals 
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$goalId, $userId]);
}

// ==================== GOAL RETRIEVAL ====================

/**
 * Get a specific goal by ID
 */
function getGoalById($goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM goals 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$goalId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all goals for a user (optionally filter by status)
 */
function getUserGoals($userId, $status = null) {
    $db = getDbConnection();
    
    if ($status) {
        $stmt = $db->prepare("
            SELECT * FROM goals 
            WHERE user_id = ? AND status = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $status]);
    } else {
        $stmt = $db->prepare("
            SELECT * FROM goals 
            WHERE user_id = ? AND status != 'deleted'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get active goals only
 */
function getActiveGoals($userId) {
    return getUserGoals($userId, 'active');
}

/**
 * Get paused goals
 */
function getPausedGoals($userId) {
    return getUserGoals($userId, 'paused');
}

/**
 * Get archived goals
 */
function getArchivedGoals($userId) {
    return getUserGoals($userId, 'archived');
}

// ==================== GOAL LOGGING ====================

/**
 * Log goal completion for a specific date
 */
function logGoalCompletion($goalId, $userId, $logDate, $completed = true, $notes = null) {
    $db = getDbConnection();
    
    // Check driver to handle SQL syntax differences
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if ($driver === 'sqlite') {
        // SQLite syntax
        $stmt = $db->prepare("
            INSERT INTO goal_logs (goal_id, user_id, log_date, completed, notes)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(goal_id, log_date) DO UPDATE SET 
                completed = excluded.completed,
                notes = excluded.notes,
                updated_at = CURRENT_TIMESTAMP
        ");
    } else {
        // MySQL syntax
        $stmt = $db->prepare("
            INSERT INTO goal_logs (goal_id, user_id, log_date, completed, notes)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                completed = VALUES(completed),
                notes = VALUES(notes),
                updated_at = CURRENT_TIMESTAMP
        ");
    }
    
    return $stmt->execute([$goalId, $userId, $logDate, $completed, $notes]);
}

/**
 * Update an existing goal log
 */
function updateGoalLog($goalId, $userId, $logDate, $completed, $notes = null) {
    return logGoalCompletion($goalId, $userId, $logDate, $completed, $notes);
}

/**
 * Delete a goal log
 */
function deleteGoalLog($goalId, $userId, $logDate) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        DELETE FROM goal_logs 
        WHERE goal_id = ? AND user_id = ? AND log_date = ?
    ");
    
    return $stmt->execute([$goalId, $userId, $logDate]);
}

/**
 * Get goal log for a specific date
 */
function getGoalLogForDate($goalId, $logDate) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM goal_logs 
        WHERE goal_id = ? AND log_date = ?
    ");
    
    $stmt->execute([$goalId, $logDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all logs for a goal within a date range
 */
function getGoalLogs($goalId, $startDate = null, $endDate = null) {
    $db = getDbConnection();
    
    if ($startDate && $endDate) {
        $stmt = $db->prepare("
            SELECT * FROM goal_logs 
            WHERE goal_id = ? AND log_date BETWEEN ? AND ?
            ORDER BY log_date DESC
        ");
        $stmt->execute([$goalId, $startDate, $endDate]);
    } else {
        $stmt = $db->prepare("
            SELECT * FROM goal_logs 
            WHERE goal_id = ?
            ORDER BY log_date DESC
        ");
        $stmt->execute([$goalId]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get today's logs for a user (all goals)
 */
function getTodaysGoalLogs($userId) {
    $db = getDbConnection();
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT gl.*, g.goal_title, g.goal_category
        FROM goal_logs gl
        JOIN goals g ON gl.goal_id = g.id
        WHERE gl.user_id = ? AND gl.log_date = ? AND g.status = 'active'
        ORDER BY g.goal_title
    ");
    
    $stmt->execute([$userId, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== STREAK CALCULATIONS ====================

/**
 * Calculate current streak for a goal (consecutive days from today backwards)
 */
function calculateCurrentStreak($goalId) {
    $db = getDbConnection();
    
    // Get all completed logs for this goal, ordered by date descending
    $stmt = $db->prepare("
        SELECT log_date, completed 
        FROM goal_logs 
        WHERE goal_id = ? AND completed = TRUE
        ORDER BY log_date DESC
    ");
    
    $stmt->execute([$goalId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        return 0;
    }
    
    $streak = 0;
    $expectedDate = new DateTime();
    
    foreach ($logs as $log) {
        $logDate = new DateTime($log['log_date']);
        
        // Check if this log is for the expected date
        if ($logDate->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
            $streak++;
            $expectedDate->modify('-1 day');
        } else {
            // Streak broken
            break;
        }
    }
    
    return $streak;
}

/**
 * Calculate longest streak ever for a goal
 */
function calculateLongestStreak($goalId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT log_date 
        FROM goal_logs 
        WHERE goal_id = ? AND completed = TRUE
        ORDER BY log_date ASC
    ");
    
    $stmt->execute([$goalId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        return 0;
    }
    
    $maxStreak = 0;
    $currentStreak = 1;
    $prevDate = new DateTime($logs[0]['log_date']);
    
    for ($i = 1; $i < count($logs); $i++) {
        $currentDate = new DateTime($logs[$i]['log_date']);
        $diff = $prevDate->diff($currentDate)->days;
        
        if ($diff === 1) {
            // Consecutive day
            $currentStreak++;
        } else {
            // Streak broken, check if it was the longest
            $maxStreak = max($maxStreak, $currentStreak);
            $currentStreak = 1;
        }
        
        $prevDate = $currentDate;
    }
    
    // Check the final streak
    $maxStreak = max($maxStreak, $currentStreak);
    
    return $maxStreak;
}

/**
 * Get total completed days for a goal
 */
function getTotalCompletedDays($goalId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM goal_logs 
        WHERE goal_id = ? AND completed = TRUE
    ");
    
    $stmt->execute([$goalId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int)$result['total'];
}

/**
 * Get success rate for a goal (last N days, default 7)
 */
function getSuccessRate($goalId, $days = 7) {
    $db = getDbConnection();
    
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $endDate = date('Y-m-d');
    
    // Get goal start date
    $stmt = $db->prepare("SELECT start_date FROM goals WHERE id = ?");
    $stmt->execute([$goalId]);
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$goal) {
        return 0;
    }
    
    // Don't count days before goal started
    if ($goal['start_date'] > $startDate) {
        $startDate = $goal['start_date'];
    }
    
    // Calculate expected days
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $expectedDays = $start->diff($end)->days + 1;
    
    if ($expectedDays <= 0) {
        return 0;
    }
    
    // Get completed days
    $stmt = $db->prepare("
        SELECT COUNT(*) as completed 
        FROM goal_logs 
        WHERE goal_id = ? AND log_date BETWEEN ? AND ? AND completed = TRUE
    ");
    
    $stmt->execute([$goalId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completedDays = (int)$result['completed'];
    
    return round(($completedDays / $expectedDays) * 100);
}

// ==================== DASHBOARD STATS ====================

/**
 * Get comprehensive goal statistics for a user
 */
function getGoalStats($userId) {
    $db = getDbConnection();
    $today = date('Y-m-d');
    
    // Total active goals
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM goals WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $totalActive = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Completed today
    $stmt = $db->prepare("SELECT COUNT(*) as completed FROM goal_logs gl JOIN goals g ON gl.goal_id = g.id WHERE gl.user_id = ? AND gl.log_date = ? AND gl.completed = TRUE AND g.status = 'active'");
    $stmt->execute([$userId, $today]);
    $completedToday = (int)$stmt->fetch(PDO::FETCH_ASSOC)['completed'];
    
    // Success rate (last 7 days)
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
    $stmt = $db->prepare("SELECT COUNT(DISTINCT DATE(log_date)) as days_with_activity, SUM(CASE WHEN completed = TRUE THEN 1 ELSE 0 END) as total_completions FROM goal_logs gl JOIN goals g ON gl.goal_id = g.id WHERE gl.user_id = ? AND gl.log_date BETWEEN ? AND ? AND g.status = 'active'");
    $stmt->execute([$userId, $sevenDaysAgo, $today]);
    $weekStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $expectedCompletions = $totalActive * 7;
    $successRate = $expectedCompletions > 0 
        ? round(($weekStats['total_completions'] / $expectedCompletions) * 100) 
        : 0;
    
    return [
        'total_active' => $totalActive,
        'completed_today' => $completedToday,
        'remaining_today' => $totalActive - $completedToday,
        'success_rate_7days' => $successRate,
        'total_paused' => count(getPausedGoals($userId)),
        'total_archived' => count(getArchivedGoals($userId))
    ];
}

/**
 * Get goal completion percentage for all users for heatmap calendar
 * Returns array keyed by date, with user data showing percentage of goals completed
 */
function getAllUsersGoalCompletionPercentage($startDate, $endDate) {
    $db = getDbConnection();
    
    // Get all users except admin
    $users = $db->query("SELECT id, username FROM users WHERE username != 'admin' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    foreach ($users as $user) {
        $userId = $user['id'];
        
        // Get all dates in range
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        while ($start <= $end) {
            $date = $start->format('Y-m-d');
            
            // Get count of active goals on this date
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM goals
                WHERE user_id = ? 
                    AND status = 'active'
                    AND start_date <= ?
                    AND (end_date IS NULL OR end_date >= ?)
            ");
            $stmt->execute([$userId, $date, $date]);
            $totalGoals = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get completed goals details on this date
            $stmt = $db->prepare("
                SELECT gl.notes, g.goal_title, g.goal_category
                FROM goal_logs gl
                JOIN goals g ON gl.goal_id = g.id
                WHERE gl.user_id = ? AND gl.log_date = ? AND gl.completed = TRUE
            ");
            $stmt->execute([$userId, $date]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $completedGoals = count($logs);
            
            // Calculate percentage
            $percentage = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100) : 0;
            
            if (!isset($result[$date])) {
                $result[$date] = [];
            }
            
            $result[$date][$userId] = [
                'username' => $user['username'],
                'total_goals' => $totalGoals,
                'completed_goals' => $completedGoals,
                'percentage' => $percentage,
                'logs' => $logs
            ];
            
            $start->modify('+1 day');
        }
    }
    
    return $result;
}

/**
 * Get recent activity feed for a user
 */
function getRecentActivity($userId, $limit = 10) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT gl.log_date, gl.completed, gl.notes, g.goal_title, g.goal_category
        FROM goal_logs gl
        JOIN goals g ON gl.goal_id = g.id
        WHERE gl.user_id = ?
        ORDER BY gl.log_date DESC, gl.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get goals with their current streak and stats (for dashboard cards)
 */
function getGoalsWithStats($userId) {
    $goals = getActiveGoals($userId);
    
    foreach ($goals as &$goal) {
        $goal['current_streak'] = calculateCurrentStreak($goal['id']);
        $goal['longest_streak'] = calculateLongestStreak($goal['id']);
        $goal['total_completed'] = getTotalCompletedDays($goal['id']);
        $goal['success_rate'] = getSuccessRate($goal['id'], 7);
        
        // Check if completed today
        $today = date('Y-m-d');
        $todayLog = getGoalLogForDate($goal['id'], $today);
        $goal['completed_today'] = $todayLog && $todayLog['completed'];
        
        // Calculate days remaining (if end_date is set)
        if ($goal['end_date']) {
            $now = new DateTime();
            $endDate = new DateTime($goal['end_date']);
            $diff = $now->diff($endDate);
            $goal['days_remaining'] = $diff->invert ? 0 : $diff->days;
        } else {
            $goal['days_remaining'] = null;
        }
    }
    
    return $goals;
}

/**
 * Get goals with stats, grouped by room/challenge
 */
function getGoalsWithStatsGroupedByRoom($userId) {
    $db = getDbConnection();
    
    // Fetch active goals joined with their rooms/challenges
    $stmt = $db->prepare("
        SELECT 
            g.*,
            c.id as room_id,
            c.name as room_name,
            c.category as room_category,
            c.start_date as room_start_date,
            c.end_date as room_end_date
        FROM goals g
        LEFT JOIN challenge_goals cg ON g.id = cg.goal_id
        LEFT JOIN challenges c ON cg.challenge_id = c.id
        WHERE g.user_id = ? AND g.status = 'active'
        ORDER BY CASE WHEN c.id IS NULL THEN 1 ELSE 0 END, c.name ASC, g.goal_title ASC
    ");
    $stmt->execute([$userId]);
    $rawGoals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $grouped = [];
    
    foreach ($rawGoals as $goal) {
        $roomId = $goal['room_id'] ?: 0;
        
        // Calculate stats - Context Aware
        if ($roomId > 0 && ($goal['room_start_date'] || $goal['room_end_date'])) {
            // Challenge specific stats - scoped to dates
            $startDate = $goal['room_start_date'] ?: '1970-01-01';
            $endDate = $goal['room_end_date'] ?: '2099-12-31';
            
            // Total completed within range
            $countStmt = $db->prepare("SELECT COUNT(*) FROM goal_logs WHERE goal_id = ? AND completed = 1 AND log_date >= ? AND log_date <= ?");
            $countStmt->execute([$goal['id'], $startDate, $endDate]);
            $goal['total_completed'] = $countStmt->fetchColumn();
            
            // Percentage Completion (based on total days in challenge)
            // If end date is future, this is "current progress" vs "total potential"
            // The user requested: "Jan-first-half... based on 100% completion" (implies relative to total duration)
            if($goal['room_start_date'] && $goal['room_end_date']) {
                $start = new DateTime($goal['room_start_date']);
                $end = new DateTime($goal['room_end_date']);
                $diff = $start->diff($end);
                $totalDays = $diff->days + 1; // Inclusive (1st to 15th = 15 days)
                
                $goal['completion_percentage'] = min(100, round(($goal['total_completed'] / max(1, $totalDays)) * 100));
                $goal['total_possible_days'] = $totalDays;
            } else {
                 $goal['completion_percentage'] = 0;
            }
            
            // Current Streak within range (simplified: consecutive days ending today, bounded by start)
            // Note: If today is outside range (e.g. challenge ended), streak calculation might be moot or frozen. 
            // For now, we stick to standard calculation but respect start date.
            $goal['current_streak'] = calculateCurrentStreak($goal['id']); // Standard logic for now
            
        } else {
            // Standard Global Stats
            $goal['current_streak'] = calculateCurrentStreak($goal['id']);
            $goal['longest_streak'] = calculateLongestStreak($goal['id']);
            $goal['total_completed'] = getTotalCompletedDays($goal['id']);
            
            // Global Percentage (if goal has end date)
             if($goal['start_date'] && $goal['end_date']) {
                $start = new DateTime($goal['start_date']);
                $end = new DateTime($goal['end_date']);
                $diff = $start->diff($end);
                $totalDays = $diff->days + 1;
                
                $goal['completion_percentage'] = min(100, round(($goal['total_completed'] / max(1, $totalDays)) * 100));
                $goal['total_possible_days'] = $totalDays;
            } else {
                 // If no end date, maybe base it on "Yearly"? User said "whole year".
                 // For now, let's leave as null or handle in UI.
                 $goal['completion_percentage'] = null;
            }
        }
        
        $goal['success_rate'] = getSuccessRate($goal['id'], 7);
        
        // Check if completed today
        $today = date('Y-m-d');
        $todayLog = getGoalLogForDate($goal['id'], $today);
        $goal['completed_today'] = $todayLog && $todayLog['completed'];
        
        // Calculate days remaining
        if ($goal['end_date']) {
            $now = new DateTime();
            $endDate = new DateTime($goal['end_date']);
            $diff = $now->diff($endDate);
            $goal['days_remaining'] = $diff->invert ? 0 : $diff->days;
        } else {
            $goal['days_remaining'] = null;
        }
        
        // Grouping
        $roomName = $goal['room_name'] ?: 'Personal Goals';
        
        if (!isset($grouped[$roomId])) {
            $grouped[$roomId] = [
                'room_id' => $roomId,
                'room_name' => $roomName,
                'start_date' => $goal['room_start_date'],
                'end_date' => $goal['room_end_date'],
                'goals' => []
            ];
        }
        
        $grouped[$roomId]['goals'][] = $goal;
    }
    
    // Ensure "Personal Goals" (0) is at the bottom if it exists
    if (isset($grouped[0])) {
        $personal = $grouped[0];
        unset($grouped[0]);
        $grouped[0] = $personal;
    }
    
    return array_values($grouped);
}
