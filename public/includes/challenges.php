<?php
/**
 * Challenges Management Functions
 * Handles all challenge-related operations including CRUD, membership, invites, goals, and leaderboards
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php'; // Email functionality

// ============================================
// CHALLENGE CRUD OPERATIONS
// ============================================

/**
 * Create a new challenge
 * @param int $creatorUserId
 * @param string $name
 * @param string $description
 * @param string $category
 * @param string $privacy ('private' or 'invite-only')
 * @param string|null $startDate (YYYY-MM-DD)
 * @param string|null $endDate (YYYY-MM-DD)
 * @return int|false Challenge ID if successful, false otherwise
 */
function createChallenge($creatorUserId, $name, $description, $category, $privacy = 'private', $startDate = null, $endDate = null) {
    if (empty($category)) $category = 'Other';
    
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO challenges (creator_user_id, name, description, category, privacy, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([$creatorUserId, $name, $description, $category, $privacy, $startDate, $endDate]);
    
    if ($stmt->rowCount() > 0) {
        $challengeId = $db->lastInsertId();
        
        // Auto-add creator as first member
        addChallengeMember($challengeId, $creatorUserId);
        
        return $challengeId;
    }
    
    return false;
}

/**
 * Get challenge by ID
 * @param int $challengeId
 * @return array|false Challenge data or false if not found
 */
function getChallengeById($challengeId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT r.*,
               u.username as creator_username,
               (SELECT COUNT(*) FROM challenge_members WHERE challenge_id = r.id AND status = 'active') as member_count
        FROM challenges r
        JOIN users u ON u.id = r.creator_user_id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$challengeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all challenges (for admin)
 * @return array List of all challenges
 */
function getAllChallenges() {
    $db = getDbConnection();
    
    $stmt = $db->query("
        SELECT r.*,
               u.username as creator_username,
               (SELECT COUNT(*) FROM challenge_members WHERE challenge_id = r.id AND status = 'active') as member_count
        FROM challenges r
        JOIN users u ON u.id = r.creator_user_id
        ORDER BY r.created_at DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update challenge details
 * @param int $challengeId
 * @param string $name
 * @param string $description
 * @param string|null $endDate
 * @return bool Success
 */
function updateChallenge($challengeId, $name, $description, $endDate = null) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE challenges 
        SET name = ?, description = ?, end_date = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $description, $endDate, $challengeId]);
    return $stmt->rowCount() > 0;
}

/**
 * Change challenge status
 * @param int $challengeId
 * @param string $status ('active', 'paused', 'archived', 'deleted')
 * @return bool Success
 */
function changeChallengeStatus($challengeId, $status) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("UPDATE challenges SET status = ? WHERE id = ?");
    $stmt->execute([$status, $challengeId]);
    return $stmt->rowCount() > 0;
}

/**
 * Update expired challenges to archived status
 */
function updateExpiredChallenges() {
    $db = getDbConnection();
    $today = date('Y-m-d');
    
    // Archive active challenges that have ended
    $stmt = $db->prepare("
        UPDATE challenges 
        SET status = 'archived' 
        WHERE status = 'active' 
          AND end_date IS NOT NULL 
          AND end_date < ?
    ");
    $stmt->execute([$today]);
}

/**
 * Delete challenge permanently
 * @param int $challengeId
 * @return bool Success
 */
function deleteChallenge($challengeId) {
    return changeChallengeStatus($challengeId, 'deleted');
}

// ============================================
// CHALLENGE MEMBERSHIP
// ============================================

/**
 * Add member to challenge
 * @param int $challengeId
 * @param int $userId
 * @return bool Success
 */
function addChallengeMember($challengeId, $userId) {
    $db = getDbConnection();
    
    // Check if using our Custom Wrapper (getAttribute might not be implemented fully or return null)
    $driver = 'sqlite'; // Default assumption for wrapper
    try {
        if ($db instanceof PDO) {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        }
    } catch (Exception $e) {
        // Ignore, stick to default
    }
    
    if ($driver === 'sqlite') {
        $stmt = $db->prepare("
            INSERT INTO challenge_members (challenge_id, user_id, status)
            VALUES (?, ?, 'active')
            ON CONFLICT(challenge_id, user_id) DO UPDATE SET status = 'active', joined_at = CURRENT_TIMESTAMP
        ");
    } else {
        $stmt = $db->prepare("
            INSERT INTO challenge_members (challenge_id, user_id, status)
            VALUES (?, ?, 'active')
            ON DUPLICATE KEY UPDATE status = 'active', joined_at = CURRENT_TIMESTAMP
        ");
    }
    
    $stmt->execute([$challengeId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Remove member from challenge (mark as left)
 * @param int $challengeId
 * @param int $userId
 * @return bool Success
 */
function removeChallengeMember($challengeId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE challenge_members 
        SET status = 'left'
        WHERE challenge_id = ? AND user_id = ?
    ");
    
    $stmt->execute([$challengeId, $userId]);
    
    // Also remove their goals from the challenge
    if ($stmt->rowCount() > 0) {
        $stmt = $db->prepare("DELETE FROM challenge_goals WHERE challenge_id = ? AND user_id = ?");
        $stmt->execute([$challengeId, $userId]);
    }
    
    return true;
}

/**
 * Get all members of a challenge
 * @param int $challengeId
 * @param string $status ('active' or 'left')
 * @return array Array of member data
 */
function getChallengeMembers($challengeId, $status = 'active') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT rm.*, u.username, u.email
        FROM challenge_members rm
        JOIN users u ON u.id = rm.user_id
        WHERE rm.challenge_id = ? AND rm.status = ?
        ORDER BY rm.joined_at ASC
    ");
    
    $stmt->execute([$challengeId, $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if user is member of challenge
 * @param int $challengeId
 * @param int $userId
 * @return bool True if active member
 */
function isChallengeMember($challengeId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM challenge_members 
        WHERE challenge_id = ? AND user_id = ? AND status = 'active'
    ");
    
    $stmt->execute([$challengeId, $userId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get user's challenges
 * @param int $userId
 * @param string $status Challenge status filter ('active', 'paused', 'archived')
 * @return array Array of challenges
 */
function getUserChallenges($userId, $status = 'active') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT r.*,
               u.username as creator_username,
               (SELECT COUNT(*) FROM challenge_members WHERE challenge_id = r.id AND status = 'active') as member_count,
               (SELECT COUNT(*) FROM challenge_goals WHERE challenge_id = r.id AND user_id = ?) as my_goals_count
        FROM challenges r
        JOIN users u ON u.id = r.creator_user_id
        JOIN challenge_members rm ON rm.challenge_id = r.id
        WHERE rm.user_id = ? 
          AND rm.status = 'active'
          AND r.status = ?
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute([$userId, $userId, $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get count of challenges user is in
 * @param int $userId
 * @return int Count
 */
function getUserChallengeCount($userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM challenge_members rm
        JOIN challenges r ON r.id = rm.challenge_id
        WHERE rm.user_id = ? AND rm.status = 'active' AND r.status = 'active'
    ");
    
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ============================================
// CHALLENGE GOALS (TRACKING SELECTION)
// ============================================

/**
 * Add goal to challenge tracking
 * @param int $challengeId
 * @param int $goalId
 * @param int $userId
 * @return bool Success
 */
function addGoalToChallenge($challengeId, $goalId, $userId) {
    $db = getDbConnection();
    
    // Verify user owns the goal
    $stmt = $db->prepare("SELECT COUNT(*) FROM goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$goalId, $userId]);
    
    if ($stmt->fetchColumn() == 0) {
        return false;
    }
    
    // Check if using our Custom Wrapper (getAttribute might not be implemented fully or return null)
    $driver = 'sqlite'; // Default assumption for wrapper
    try {
        if ($db instanceof PDO) {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        }
    } catch (Exception $e) {
        // Ignore, stick to default
    }

    if ($driver === 'sqlite') {
        $stmt = $db->prepare("
            INSERT INTO challenge_goals (challenge_id, goal_id, user_id)
            VALUES (?, ?, ?)
            ON CONFLICT(challenge_id, goal_id) DO UPDATE SET added_at = CURRENT_TIMESTAMP
        ");
    } else {
        $stmt = $db->prepare("
            INSERT INTO challenge_goals (challenge_id, goal_id, user_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE added_at = CURRENT_TIMESTAMP
        ");
    }
    
    $stmt->execute([$challengeId, $goalId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Remove goal from challenge tracking
 * @param int $challengeId
 * @param int $goalId
 * @param int $userId
 * @return bool Success
 */
function removeGoalFromChallenge($challengeId, $goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        DELETE FROM challenge_goals 
        WHERE challenge_id = ? AND goal_id = ? AND user_id = ?
    ");
    
    $stmt->execute([$challengeId, $goalId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Get goals user is tracking in a challenge
 * @param int $challengeId
 * @param int $userId
 * @return array Array of goals
 */
function getChallengeGoalsByUser($challengeId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT g.*, rg.added_at
        FROM challenge_goals rg
        JOIN goals g ON g.id = rg.goal_id
        WHERE rg.challenge_id = ? AND rg.user_id = ? AND g.status = 'active'
        ORDER BY rg.added_at ASC
    ");
    
    $stmt->execute([$challengeId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get user's goals NOT yet in this challenge (available to add)
 * @param int $challengeId
 * @param int $userId
 * @return array Array of goals
 */
function getAvailableGoalsForChallenge($challengeId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT g.*
        FROM goals g
        WHERE g.user_id = ? 
          AND g.status = 'active'
          AND g.id NOT IN (
              SELECT goal_id FROM challenge_goals WHERE challenge_id = ? AND user_id = ?
          )
        ORDER BY g.created_at DESC
    ");
    
    $stmt->execute([$userId, $challengeId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all goals being tracked in a challenge (all users)
 * @param int $challengeId
 * @return array Array with user_id => goals
 */
function getAllChallengeGoals($challengeId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT rg.user_id, u.username, g.id as goal_id, g.goal_title, g.goal_category
        FROM challenge_goals rg
        JOIN goals g ON g.id = rg.goal_id
        JOIN users u ON u.id = rg.user_id
        WHERE rg.challenge_id = ? AND g.status = 'active'
        ORDER BY rg.user_id, rg.added_at
    ");
    
    $stmt->execute([$challengeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// CHALLENGE INVITATIONS
// ============================================

/**
 * Create challenge invitation
 * @param int $challengeId
 * @param int $inviterUserId
 * @param string $inviteeEmail
 * @return int|false Invite ID or false
 */
function createChallengeInvite($challengeId, $inviterUserId, $inviteeEmail) {
    $db = getDbConnection();
    
    // Check if email belongs to a registered user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$inviteeEmail]);
    $inviteeUserId = $stmt->fetchColumn();
    
    // Check if already invited or member
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM challenge_invites 
        WHERE challenge_id = ? AND invitee_email = ? AND status = 'pending'
    ");
    $stmt->execute([$challengeId, $inviteeEmail]);
    
    if ($stmt->fetchColumn() > 0) {
        return false; // Already has pending invite
    }
    
    $stmt = $db->prepare("
        INSERT INTO challenge_invites (challenge_id, inviter_user_id, invitee_email, invitee_user_id, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([$challengeId, $inviterUserId, $inviteeEmail, $inviteeUserId]);
    
    if ($stmt->rowCount() > 0) {
        $inviteId = $db->lastInsertId();
        
        // Send Email Notification
        $stmtDetails = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmtDetails->execute([$inviterUserId]);
        $inviterName = $stmtDetails->fetchColumn() ?: 'A user';

        $stmtDetails = $db->prepare("SELECT name FROM challenges WHERE id = ?");
        $stmtDetails->execute([$challengeId]);
        $challengeName = $stmtDetails->fetchColumn() ?: 'a challenge';
        
        $appUrl = defined('APP_URL') ? APP_URL : 'https://goaltrackerbeta.dijit.tech';
        $link = $appUrl . '/challenges.php';
        
        $subject = "$inviterName invited you to '$challengeName' on Goal Tracker";
        
        $body = "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
            <h2 style='color: #667eea;'>You've been invited!</h2>
            <p><strong>$inviterName</strong> has invited you to join the challenge <strong>$challengeName</strong>.</p>
            <p>Track your goals, compete on the leaderboard, and stay motivated!</p>
            <div style='margin: 30px 0;'>
                <a href='$link' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Accept Invitation</a>
            </div>
            <p style='font-size: 0.9em; color: #666;'>If you don't have an account yet, you can create one using this email address to see your invite.</p>
        </div>
        ";
        
        sendEmail($inviteeEmail, $subject, $body);
        
        return $inviteId;
    }
    
    return false;
}

/**
 * Respond to challenge invitation
 * @param int $inviteId
 * @param int $userId
 * @param string $response ('accepted' or 'declined')
 * @return bool Success
 */
function respondToChallengeInvite($inviteId, $userId, $response) {
    $db = getDbConnection();
    
    // Get invite details
    $stmt = $db->prepare("
        SELECT * FROM challenge_invites 
        WHERE id = ? AND invitee_user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$inviteId, $userId]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invite) {
        return false;
    }
    
    // Update invite status
    $stmt = $db->prepare("
        UPDATE challenge_invites 
        SET status = ?, responded_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$response, $inviteId]);
    
    // If accepted, add as member
    if ($response === 'accepted') {
        addChallengeMember($invite['challenge_id'], $userId);
    }
    
    return true;
}

/**
 * Get pending invitations for user
 * @param int $userId
 * @return array Array of invites
 */
function getUserPendingInvites($userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT ri.*, r.name as challenge_name, r.description, r.category as challenge_category, u.username as inviter_username
        FROM challenge_invites ri
        JOIN challenges r ON r.id = ri.challenge_id
        JOIN users u ON u.id = ri.inviter_user_id
        WHERE ri.invitee_user_id = ? AND ri.status = 'pending'
        ORDER BY ri.invited_at DESC
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get invitations sent for a challenge
 * @param int $challengeId
 * @return array Array of invites
 */
function getChallengeInvites($challengeId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT ri.*, u.username as invitee_username
        FROM challenge_invites ri
        LEFT JOIN users u ON u.id = ri.invitee_user_id
        WHERE ri.challenge_id = ?
        ORDER BY ri.invited_at DESC
    ");
    
    $stmt->execute([$challengeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// CHALLENGE LEADERBOARD & STATS
// ============================================

/**
 * Get challenge leaderboard for specific month
 * @param int $challengeId
 * @param string $month (YYYY-MM format)
 * @return array Array of user scores
 */
function getChallengeLeaderboard($challengeId, $month) {
    $db = getDbConnection();
    
    // Get challenge dates to constrain the query
    $challenge = getChallengeById($challengeId);
    
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    $queryStart = $monthStart;
    $queryEnd = $monthEnd;
    
    if ($challenge) {
        if (!empty($challenge['start_date']) && $challenge['start_date'] > $queryStart) {
            $queryStart = $challenge['start_date'];
        }
        if (!empty($challenge['end_date']) && $challenge['end_date'] < $queryEnd) {
            $queryEnd = $challenge['end_date'];
        }
    }
    
    $stmt = $db->prepare("
        SELECT 
            u.id as user_id,
            u.username,
            COUNT(DISTINCT gl.log_date) as days_active,
            SUM(daily_points) as total_points
        FROM challenge_members rm
        JOIN users u ON u.id = rm.user_id
        LEFT JOIN (
            SELECT 
                gl.user_id,
                gl.log_date,
                CASE 
                    WHEN AVG(CASE WHEN gl.completed THEN 1 ELSE 0 END) = 1.0 THEN 10
                    WHEN AVG(CASE WHEN gl.completed THEN 1 ELSE 0 END) >= 0.67 THEN 7
                    WHEN AVG(CASE WHEN gl.completed THEN 1 ELSE 0 END) >= 0.34 THEN 5
                    WHEN AVG(CASE WHEN gl.completed THEN 1 ELSE 0 END) > 0 THEN 2
                    ELSE 0
                END as daily_points
            FROM goal_logs gl
            WHERE gl.goal_id IN (
                SELECT goal_id FROM challenge_goals WHERE challenge_id = ?
            )
            AND gl.log_date BETWEEN ? AND ?
            GROUP BY gl.user_id, gl.log_date
        ) gl ON gl.user_id = rm.user_id
        WHERE rm.challenge_id = ? AND rm.status = 'active'
        GROUP BY u.id, u.username
        ORDER BY total_points DESC, days_active DESC
    ");
    
    $stmt->execute([$challengeId, $queryStart, $queryEnd, $challengeId]);
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add rank
    $rank = 1;
    foreach ($leaderboard as &$entry) {
        $entry['rank'] = $rank++;
        $entry['total_points'] = $entry['total_points'] ?? 0;
    }
    
    return $leaderboard;
}

/**
 * Get challenge completion data for heatmap (similar to global but challenge-filtered)
 * @param int $challengeId
 * @param string $startDate (YYYY-MM-DD)
 * @param string $endDate (YYYY-MM-DD)
 * @return array Date => User ID => completion data
 */
function getChallengeCompletionPercentage($challengeId, $startDate, $endDate) {
    $db = getDbConnection();
    
    // Get challenge dates to constrain the query
    $challenge = getChallengeById($challengeId);
    
    $queryStart = $startDate;
    $queryEnd = $endDate;
    
    if ($challenge) {
        if (!empty($challenge['start_date']) && $challenge['start_date'] > $queryStart) {
            $queryStart = $challenge['start_date'];
        }
        if (!empty($challenge['end_date']) && $challenge['end_date'] < $queryEnd) {
            $queryEnd = $challenge['end_date'];
        }
    }
    
    // Check if using our Custom Wrapper (getAttribute might not be implemented fully or return null)
    $driver = 'sqlite'; // Default assumption for wrapper
    try {
        if ($db instanceof PDO) {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        }
    } catch (Exception $e) {
        // Ignore, stick to default
    }
    
    if ($driver === 'sqlite') {
        $stmt = $db->prepare("
            SELECT 
                gl.log_date,
                gl.user_id,
                u.username,
                COUNT(gl.id) as completed_goals,
                GROUP_CONCAT(g.goal_title || '::' || g.goal_category, '||') as completed_titles,
                total.total_goals,
                ROUND(COUNT(gl.id) * 100.0 / total.total_goals, 0) as percentage
            FROM goal_logs gl
            JOIN users u ON u.id = gl.user_id
            JOIN goals g ON gl.goal_id = g.id
            JOIN (
                SELECT user_id, COUNT(DISTINCT goal_id) as total_goals
                FROM challenge_goals
                WHERE challenge_id = ?
                GROUP BY user_id
            ) total ON total.user_id = gl.user_id
            WHERE gl.goal_id IN (SELECT goal_id FROM challenge_goals WHERE challenge_id = ?)
              AND gl.log_date BETWEEN ? AND ?
              AND gl.completed = 1
            GROUP BY gl.log_date, gl.user_id, u.username, total.total_goals
            ORDER BY gl.log_date, gl.user_id
        ");
    } else {
        $stmt = $db->prepare("
            SELECT 
                gl.log_date,
                gl.user_id,
                u.username,
                COUNT(gl.id) as completed_goals,
                GROUP_CONCAT(CONCAT(g.goal_title, '::', g.goal_category) SEPARATOR '||') as completed_titles,
                total.total_goals,
                ROUND(COUNT(gl.id) * 100.0 / total.total_goals, 0) as percentage
            FROM goal_logs gl
            JOIN users u ON u.id = gl.user_id
            JOIN goals g ON gl.goal_id = g.id
            JOIN (
                SELECT user_id, COUNT(DISTINCT goal_id) as total_goals
                FROM challenge_goals
                WHERE challenge_id = ?
                GROUP BY user_id
            ) total ON total.user_id = gl.user_id
            WHERE gl.goal_id IN (SELECT goal_id FROM challenge_goals WHERE challenge_id = ?)
              AND gl.log_date BETWEEN ? AND ?
              AND gl.completed = TRUE
            GROUP BY gl.log_date, gl.user_id, u.username, total.total_goals
            ORDER BY gl.log_date, gl.user_id
        ");
    }
    
    $stmt->execute([$challengeId, $challengeId, $queryStart, $queryEnd]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restructure as [date][user_id] = data
    $structured = [];
    foreach ($results as $row) {
        if (!isset($structured[$row['log_date']])) {
            $structured[$row['log_date']] = [];
        }
        $structured[$row['log_date']][$row['user_id']] = [
            'username' => $row['username'],
            'total_goals' => $row['total_goals'],
            'completed_goals' => $row['completed_goals'],
            'percentage' => $row['percentage'],
            'completed_titles' => $row['completed_titles'] ?? ''
        ];
    }
    
    return $structured;
}

/**
 * Get challenge statistics
 * @param int $challengeId
 * @return array Stats array
 */
function getChallengeStats($challengeId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM challenge_members WHERE challenge_id = ? AND status = 'active') as total_members,
            (SELECT COUNT(DISTINCT goal_id) FROM challenge_goals WHERE challenge_id = ?) as total_goals_tracked,
            (SELECT COUNT(*) FROM challenge_posts WHERE challenge_id = ?) as total_posts,
            (SELECT COUNT(*) FROM challenge_achievements WHERE challenge_id = ?) as total_achievements
    ");
    
    $stmt->execute([$challengeId, $challengeId, $challengeId, $challengeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================
// CHALLENGE POSTS (ACTIVITY FEED)
// ============================================

/**
 * Create post in challenge
 * @param int $challengeId
 * @param int $userId
 * @param string $content
 * @param string $postType ('message', 'achievement', 'milestone', 'system')
 * @return int|false Post ID or false
 */
function createChallengePost($challengeId, $userId, $content, $postType = 'message') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO challenge_posts (challenge_id, user_id, post_type, content)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$challengeId, $userId, $postType, $content]);
    return $stmt->rowCount() > 0 ? $db->lastInsertId() : false;
}

/**
 * Get challenge activity feed
 * @param int $challengeId
 * @param int $limit
 * @return array Array of posts
 */
function getChallengePosts($challengeId, $limit = 50) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT rp.*, u.username
        FROM challenge_posts rp
        JOIN users u ON u.id = rp.user_id
        WHERE rp.challenge_id = ?
        ORDER BY rp.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$challengeId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Delete challenge post
 * @param int $postId
 * @param int $userId (must be post owner or challenge creator)
 * @return bool Success
 */
function deleteChallengePost($postId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        DELETE FROM challenge_posts 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$postId, $userId]);
    return $stmt->rowCount() > 0;
}

// ============================================
// CHALLENGE ACHIEVEMENTS
// ============================================

/**
 * Award achievement to user in challenge
 * @param int $challengeId
 * @param int $userId
 * @param string $type
 * @param string $name
 * @param string $description
 * @return int|false Achievement ID or false
 */
function awardChallengeAchievement($challengeId, $userId, $type, $name, $description = '') {
    $db = getDbConnection();
    
    // Check if already earned
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM challenge_achievements 
        WHERE challenge_id = ? AND user_id = ? AND achievement_type = ?
    ");
    $stmt->execute([$challengeId, $userId, $type]);
    
    if ($stmt->fetchColumn() > 0) {
        return false; // Already has this achievement
    }
    
    $stmt = $db->prepare("
        INSERT INTO challenge_achievements (challenge_id, user_id, achievement_type, achievement_name, achievement_description)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$challengeId, $userId, $type, $name, $description]);
    
    if ($stmt->rowCount() > 0) {
        $achievementId = $db->lastInsertId();
        
        // Create system post announcing achievement
        createChallengePost($challengeId, $userId, "earned achievement: {$name}", 'achievement');
        
        return $achievementId;
    }
    
    return false;
}

/**
 * Get challenge achievements for user
 * @param int $challengeId
 * @param int $userId
 * @return array Array of achievements
 */
function getUserChallengeAchievements($challengeId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM challenge_achievements 
        WHERE challenge_id = ? AND user_id = ?
        ORDER BY earned_at DESC
    ");
    
    $stmt->execute([$challengeId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all achievements in challenge
 * @param int $challengeId
 * @return array Array of achievements with usernames
 */
function getChallengeAchievements($challengeId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT ra.*, u.username
        FROM challenge_achievements ra
        JOIN users u ON u.id = ra.user_id
        WHERE ra.challenge_id = ?
        ORDER BY ra.earned_at DESC
    ");
    
    $stmt->execute([$challengeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Check if user is challenge creator
 * @param int $challengeId
 * @param int $userId
 * @return bool
 */
function isChallengeCreator($challengeId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM challenges WHERE id = ? AND creator_user_id = ?");
    $stmt->execute([$challengeId, $userId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Can user perform action on challenge (is creator or admin)
 * @param int $challengeId
 * @param int $userId
 * @return bool
 */
function canManageChallenge($challengeId, $userId) {
    require_once __DIR__ . '/session.php';
    return isChallengeCreator($challengeId, $userId) || isAdmin();
}
