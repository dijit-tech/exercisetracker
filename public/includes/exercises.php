<?php
/**
 * Exercise Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Get all exercises for a user
 */
function getUserExercises($userId, $limit = null, $offset = 0) {
    $pdo = getDbConnection();
    
    $sql = "SELECT * FROM exercises WHERE user_id = ? ORDER BY exercise_date DESC, created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll();
}

/**
 * Get exercises for a specific date range
 */
function getUserExercisesByDateRange($userId, $startDate, $endDate) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT * FROM exercises 
        WHERE user_id = ? AND exercise_date BETWEEN ? AND ?
        ORDER BY exercise_date DESC, created_at DESC
    ");
    
    $stmt->execute([$userId, $startDate, $endDate]);
    return $stmt->fetchAll();
}

/**
 * Get exercise by ID
 */
function getExerciseById($exerciseId, $userId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ? AND user_id = ?");
    $stmt->execute([$exerciseId, $userId]);
    
    return $stmt->fetch();
}

/**
 * Add new exercise
 */
function addExercise($userId, $exerciseDate, $exerciseType, $durationMinutes, $notes = '') {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO exercises (user_id, exercise_date, exercise_type, duration_minutes, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $exerciseDate, $exerciseType, $durationMinutes, $notes]);
        return ['success' => true, 'exercise_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log("Add exercise failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add exercise'];
    }
}

/**
 * Update exercise
 */
function updateExercise($exerciseId, $userId, $exerciseDate, $exerciseType, $durationMinutes, $notes = '') {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE exercises 
            SET exercise_date = ?, exercise_type = ?, duration_minutes = ?, notes = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([$exerciseDate, $exerciseType, $durationMinutes, $notes, $exerciseId, $userId]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Update exercise failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update exercise'];
    }
}

/**
 * Delete exercise
 */
function deleteExercise($exerciseId, $userId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ? AND user_id = ?");
        $stmt->execute([$exerciseId, $userId]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Delete exercise failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete exercise'];
    }
}

/**
 * Get exercise statistics for a user
 */
function getExerciseStats($userId, $days = 7) {
    $pdo = getDbConnection();
    
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_exercises,
            SUM(duration_minutes) as total_minutes,
            AVG(duration_minutes) as avg_minutes,
            COUNT(DISTINCT exercise_date) as active_days
        FROM exercises 
        WHERE user_id = ? AND exercise_date >= ?
    ");
    
    $stmt->execute([$userId, $startDate]);
    return $stmt->fetch();
}

/**
 * Get available exercise types
 */
function getExerciseTypes() {
    return [
        'Running',
        'Walking',
        'Cycling',
        'Swimming',
        'Strength Training',
        'Yoga',
        'Sports',
        'Flexibility',
        'Cardio',
        'Other'
    ];
}

/**
 * Get all users with their exercise activity for a date range
 * Returns array with users and dates they exercised
 */
function getAllUsersExerciseActivity($startDate, $endDate) {
    try {
        $pdo = getDbConnection();
        
        // Get all users
        $usersStmt = $pdo->prepare("SELECT id, username FROM users ORDER BY username");
        $usersStmt->execute();
        $users = $usersStmt->fetchAll();
        
        // Get all exercise dates for all users in the range
        $exercisesStmt = $pdo->prepare("
            SELECT user_id, exercise_date, COUNT(*) as exercise_count
            FROM exercises 
            WHERE exercise_date BETWEEN ? AND ?
            GROUP BY user_id, exercise_date
            ORDER BY user_id, exercise_date
        ");
        $exercisesStmt->execute([$startDate, $endDate]);
        $exercises = $exercisesStmt->fetchAll();
        
        // Build a map of user_id => [dates]
        $exerciseMap = [];
        foreach ($exercises as $exercise) {
            $userId = $exercise['user_id'];
            $date = $exercise['exercise_date'];
            
            if (!isset($exerciseMap[$userId])) {
                $exerciseMap[$userId] = [];
            }
            $exerciseMap[$userId][] = $date;
        }
        
        // Add exercise dates to user records
        foreach ($users as &$user) {
            $user['exercise_dates'] = $exerciseMap[$user['id']] ?? [];
        }
        
        return $users;
        
    } catch (PDOException $e) {
        error_log("Error getting all users exercise activity: " . $e->getMessage());
        return [];
    }
}
