<?php
/**
 * Rooms Management Functions
 * Handles all room-related operations including CRUD, membership, invites, goals, and leaderboards
 */

require_once __DIR__ . '/db.php';

// ============================================
// ROOM CRUD OPERATIONS
// ============================================

/**
 * Create a new room
 * @param int $creatorUserId
 * @param string $name
 * @param string $description
 * @param string $privacy ('private' or 'invite-only')
 * @param string|null $startDate (YYYY-MM-DD)
 * @param string|null $endDate (YYYY-MM-DD)
 * @return int|false Room ID if successful, false otherwise
 */
function createRoom($creatorUserId, $name, $description, $privacy = 'private', $startDate = null, $endDate = null) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO rooms (creator_user_id, name, description, privacy, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([$creatorUserId, $name, $description, $privacy, $startDate, $endDate]);
    
    if ($stmt->rowCount() > 0) {
        $roomId = $db->lastInsertId();
        
        // Auto-add creator as first member
        addRoomMember($roomId, $creatorUserId);
        
        return $roomId;
    }
    
    return false;
}

/**
 * Get room by ID
 * @param int $roomId
 * @return array|false Room data or false if not found
 */
function getRoomById($roomId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT r.*,
               u.username as creator_username,
               (SELECT COUNT(*) FROM room_members WHERE room_id = r.id AND status = 'active') as member_count
        FROM rooms r
        JOIN users u ON u.id = r.creator_user_id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$roomId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all rooms (for admin)
 * @return array List of all rooms
 */
function getAllRooms() {
    $db = getDbConnection();
    
    $stmt = $db->query("
        SELECT r.*,
               u.username as creator_username,
               (SELECT COUNT(*) FROM room_members WHERE room_id = r.id AND status = 'active') as member_count
        FROM rooms r
        JOIN users u ON u.id = r.creator_user_id
        ORDER BY r.created_at DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update room details
 * @param int $roomId
 * @param string $name
 * @param string $description
 * @param string|null $endDate
 * @return bool Success
 */
function updateRoom($roomId, $name, $description, $endDate = null) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE rooms 
        SET name = ?, description = ?, end_date = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $description, $endDate, $roomId]);
    return $stmt->rowCount() > 0;
}

/**
 * Change room status
 * @param int $roomId
 * @param string $status ('active', 'paused', 'archived', 'deleted')
 * @return bool Success
 */
function changeRoomStatus($roomId, $status) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("UPDATE rooms SET status = ? WHERE id = ?");
    $stmt->execute([$status, $roomId]);
    return $stmt->rowCount() > 0;
}

/**
 * Delete room permanently
 * @param int $roomId
 * @return bool Success
 */
function deleteRoom($roomId) {
    return changeRoomStatus($roomId, 'deleted');
}

// ============================================
// ROOM MEMBERSHIP
// ============================================

/**
 * Add member to room
 * @param int $roomId
 * @param int $userId
 * @return bool Success
 */
function addRoomMember($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO room_members (room_id, user_id, status)
        VALUES (?, ?, 'active')
        ON DUPLICATE KEY UPDATE status = 'active', joined_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$roomId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Remove member from room (mark as left)
 * @param int $roomId
 * @param int $userId
 * @return bool Success
 */
function removeRoomMember($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE room_members 
        SET status = 'left'
        WHERE room_id = ? AND user_id = ?
    ");
    
    $stmt->execute([$roomId, $userId]);
    
    // Also remove their goals from the room
    if ($stmt->rowCount() > 0) {
        $stmt = $db->prepare("DELETE FROM room_goals WHERE room_id = ? AND user_id = ?");
        $stmt->execute([$roomId, $userId]);
    }
    
    return true;
}

/**
 * Get all members of a room
 * @param int $roomId
 * @param string $status ('active' or 'left')
 * @return array Array of member data
 */
function getRoomMembers($roomId, $status = 'active') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT rm.*, u.username, u.email
        FROM room_members rm
        JOIN users u ON u.id = rm.user_id
        WHERE rm.room_id = ? AND rm.status = ?
        ORDER BY rm.joined_at ASC
    ");
    
    $stmt->execute([$roomId, $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if user is member of room
 * @param int $roomId
 * @param int $userId
 * @return bool True if active member
 */
function isRoomMember($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM room_members 
        WHERE room_id = ? AND user_id = ? AND status = 'active'
    ");
    
    $stmt->execute([$roomId, $userId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get user's rooms
 * @param int $userId
 * @param string $status Room status filter ('active', 'paused', 'archived')
 * @return array Array of rooms
 */
function getUserRooms($userId, $status = 'active') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT r.*,
               u.username as creator_username,
               (SELECT COUNT(*) FROM room_members WHERE room_id = r.id AND status = 'active') as member_count,
               (SELECT COUNT(*) FROM room_goals WHERE room_id = r.id AND user_id = ?) as my_goals_count
        FROM rooms r
        JOIN users u ON u.id = r.creator_user_id
        JOIN room_members rm ON rm.room_id = r.id
        WHERE rm.user_id = ? 
          AND rm.status = 'active'
          AND r.status = ?
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute([$userId, $userId, $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get count of rooms user is in
 * @param int $userId
 * @return int Count
 */
function getUserRoomCount($userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM room_members rm
        JOIN rooms r ON r.id = rm.room_id
        WHERE rm.user_id = ? AND rm.status = 'active' AND r.status = 'active'
    ");
    
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ============================================
// ROOM GOALS (TRACKING SELECTION)
// ============================================

/**
 * Add goal to room tracking
 * @param int $roomId
 * @param int $goalId
 * @param int $userId
 * @return bool Success
 */
function addGoalToRoom($roomId, $goalId, $userId) {
    $db = getDbConnection();
    
    // Verify user owns the goal
    $stmt = $db->prepare("SELECT COUNT(*) FROM goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$goalId, $userId]);
    
    if ($stmt->fetchColumn() == 0) {
        return false;
    }
    
    $stmt = $db->prepare("
        INSERT INTO room_goals (room_id, goal_id, user_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE added_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$roomId, $goalId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Remove goal from room tracking
 * @param int $roomId
 * @param int $goalId
 * @param int $userId
 * @return bool Success
 */
function removeGoalFromRoom($roomId, $goalId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        DELETE FROM room_goals 
        WHERE room_id = ? AND goal_id = ? AND user_id = ?
    ");
    
    $stmt->execute([$roomId, $goalId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Get goals user is tracking in a room
 * @param int $roomId
 * @param int $userId
 * @return array Array of goals
 */
function getRoomGoalsByUser($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT g.*, rg.added_at
        FROM room_goals rg
        JOIN goals g ON g.id = rg.goal_id
        WHERE rg.room_id = ? AND rg.user_id = ? AND g.status = 'active'
        ORDER BY rg.added_at ASC
    ");
    
    $stmt->execute([$roomId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get user's goals NOT yet in this room (available to add)
 * @param int $roomId
 * @param int $userId
 * @return array Array of goals
 */
function getAvailableGoalsForRoom($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT g.*
        FROM goals g
        WHERE g.user_id = ? 
          AND g.status = 'active'
          AND g.id NOT IN (
              SELECT goal_id FROM room_goals WHERE room_id = ? AND user_id = ?
          )
        ORDER BY g.created_at DESC
    ");
    
    $stmt->execute([$userId, $roomId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all goals being tracked in a room (all users)
 * @param int $roomId
 * @return array Array with user_id => goals
 */
function getAllRoomGoals($roomId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT rg.user_id, u.username, g.id as goal_id, g.goal_title, g.goal_category
        FROM room_goals rg
        JOIN goals g ON g.id = rg.goal_id
        JOIN users u ON u.id = rg.user_id
        WHERE rg.room_id = ? AND g.status = 'active'
        ORDER BY rg.user_id, rg.added_at
    ");
    
    $stmt->execute([$roomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// ROOM INVITATIONS
// ============================================

/**
 * Create room invitation
 * @param int $roomId
 * @param int $inviterUserId
 * @param string $inviteeEmail
 * @return int|false Invite ID or false
 */
function createRoomInvite($roomId, $inviterUserId, $inviteeEmail) {
    $db = getDbConnection();
    
    // Check if email belongs to a registered user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$inviteeEmail]);
    $inviteeUserId = $stmt->fetchColumn();
    
    // Check if already invited or member
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM room_invites 
        WHERE room_id = ? AND invitee_email = ? AND status = 'pending'
    ");
    $stmt->execute([$roomId, $inviteeEmail]);
    
    if ($stmt->fetchColumn() > 0) {
        return false; // Already has pending invite
    }
    
    $stmt = $db->prepare("
        INSERT INTO room_invites (room_id, inviter_user_id, invitee_email, invitee_user_id, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([$roomId, $inviterUserId, $inviteeEmail, $inviteeUserId]);
    
    return $stmt->rowCount() > 0 ? $db->lastInsertId() : false;
}

/**
 * Respond to room invitation
 * @param int $inviteId
 * @param int $userId
 * @param string $response ('accepted' or 'declined')
 * @return bool Success
 */
function respondToRoomInvite($inviteId, $userId, $response) {
    $db = getDbConnection();
    
    // Get invite details
    $stmt = $db->prepare("
        SELECT * FROM room_invites 
        WHERE id = ? AND invitee_user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$inviteId, $userId]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invite) {
        return false;
    }
    
    // Update invite status
    $stmt = $db->prepare("
        UPDATE room_invites 
        SET status = ?, responded_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$response, $inviteId]);
    
    // If accepted, add as member
    if ($response === 'accepted') {
        addRoomMember($invite['room_id'], $userId);
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
        SELECT ri.*, r.name as room_name, r.description, u.username as inviter_username
        FROM room_invites ri
        JOIN rooms r ON r.id = ri.room_id
        JOIN users u ON u.id = ri.inviter_user_id
        WHERE ri.invitee_user_id = ? AND ri.status = 'pending'
        ORDER BY ri.invited_at DESC
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get invitations sent for a room
 * @param int $roomId
 * @return array Array of invites
 */
function getRoomInvites($roomId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT ri.*, u.username as invitee_username
        FROM room_invites ri
        LEFT JOIN users u ON u.id = ri.invitee_user_id
        WHERE ri.room_id = ?
        ORDER BY ri.invited_at DESC
    ");
    
    $stmt->execute([$roomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// ROOM LEADERBOARD & STATS
// ============================================

/**
 * Get room leaderboard for specific month
 * @param int $roomId
 * @param string $month (YYYY-MM format)
 * @return array Array of user scores
 */
function getRoomLeaderboard($roomId, $month) {
    $db = getDbConnection();
    
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    $stmt = $db->prepare("
        SELECT 
            u.id as user_id,
            u.username,
            COUNT(DISTINCT gl.log_date) as days_active,
            SUM(daily_points) as total_points
        FROM room_members rm
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
                SELECT goal_id FROM room_goals WHERE room_id = ?
            )
            AND gl.log_date BETWEEN ? AND ?
            GROUP BY gl.user_id, gl.log_date
        ) gl ON gl.user_id = rm.user_id
        WHERE rm.room_id = ? AND rm.status = 'active'
        GROUP BY u.id, u.username
        ORDER BY total_points DESC, days_active DESC
    ");
    
    $stmt->execute([$roomId, $monthStart, $monthEnd, $roomId]);
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
 * Get room completion data for heatmap (similar to global but room-filtered)
 * @param int $roomId
 * @param string $startDate (YYYY-MM-DD)
 * @param string $endDate (YYYY-MM-DD)
 * @return array Date => User ID => completion data
 */
function getRoomCompletionPercentage($roomId, $startDate, $endDate) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            gl.log_date,
            gl.user_id,
            u.username,
            COUNT(gl.id) as completed_goals,
            total.total_goals,
            ROUND(COUNT(gl.id) * 100.0 / total.total_goals, 0) as percentage
        FROM goal_logs gl
        JOIN users u ON u.id = gl.user_id
        JOIN (
            SELECT user_id, COUNT(DISTINCT goal_id) as total_goals
            FROM room_goals
            WHERE room_id = ?
            GROUP BY user_id
        ) total ON total.user_id = gl.user_id
        WHERE gl.goal_id IN (SELECT goal_id FROM room_goals WHERE room_id = ?)
          AND gl.log_date BETWEEN ? AND ?
          AND gl.completed = TRUE
        GROUP BY gl.log_date, gl.user_id, u.username, total.total_goals
        ORDER BY gl.log_date, gl.user_id
    ");
    
    $stmt->execute([$roomId, $roomId, $startDate, $endDate]);
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
            'percentage' => $row['percentage']
        ];
    }
    
    return $structured;
}

/**
 * Get room statistics
 * @param int $roomId
 * @return array Stats array
 */
function getRoomStats($roomId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM room_members WHERE room_id = ? AND status = 'active') as total_members,
            (SELECT COUNT(DISTINCT goal_id) FROM room_goals WHERE room_id = ?) as total_goals_tracked,
            (SELECT COUNT(*) FROM room_posts WHERE room_id = ?) as total_posts,
            (SELECT COUNT(*) FROM room_achievements WHERE room_id = ?) as total_achievements
    ");
    
    $stmt->execute([$roomId, $roomId, $roomId, $roomId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================
// ROOM POSTS (ACTIVITY FEED)
// ============================================

/**
 * Create post in room
 * @param int $roomId
 * @param int $userId
 * @param string $content
 * @param string $postType ('message', 'achievement', 'milestone', 'system')
 * @return int|false Post ID or false
 */
function createRoomPost($roomId, $userId, $content, $postType = 'message') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO room_posts (room_id, user_id, post_type, content)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$roomId, $userId, $postType, $content]);
    return $stmt->rowCount() > 0 ? $db->lastInsertId() : false;
}

/**
 * Get room activity feed
 * @param int $roomId
 * @param int $limit
 * @return array Array of posts
 */
function getRoomPosts($roomId, $limit = 50) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT rp.*, u.username
        FROM room_posts rp
        JOIN users u ON u.id = rp.user_id
        WHERE rp.room_id = ?
        ORDER BY rp.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$roomId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Delete room post
 * @param int $postId
 * @param int $userId (must be post owner or room creator)
 * @return bool Success
 */
function deleteRoomPost($postId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        DELETE FROM room_posts 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$postId, $userId]);
    return $stmt->rowCount() > 0;
}

// ============================================
// ROOM ACHIEVEMENTS
// ============================================

/**
 * Award achievement to user in room
 * @param int $roomId
 * @param int $userId
 * @param string $type
 * @param string $name
 * @param string $description
 * @return int|false Achievement ID or false
 */
function awardRoomAchievement($roomId, $userId, $type, $name, $description = '') {
    $db = getDbConnection();
    
    // Check if already earned
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM room_achievements 
        WHERE room_id = ? AND user_id = ? AND achievement_type = ?
    ");
    $stmt->execute([$roomId, $userId, $type]);
    
    if ($stmt->fetchColumn() > 0) {
        return false; // Already has this achievement
    }
    
    $stmt = $db->prepare("
        INSERT INTO room_achievements (room_id, user_id, achievement_type, achievement_name, achievement_description)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$roomId, $userId, $type, $name, $description]);
    
    if ($stmt->rowCount() > 0) {
        $achievementId = $db->lastInsertId();
        
        // Create system post announcing achievement
        createRoomPost($roomId, $userId, "earned achievement: {$name}", 'achievement');
        
        return $achievementId;
    }
    
    return false;
}

/**
 * Get room achievements for user
 * @param int $roomId
 * @param int $userId
 * @return array Array of achievements
 */
function getUserRoomAchievements($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM room_achievements 
        WHERE room_id = ? AND user_id = ?
        ORDER BY earned_at DESC
    ");
    
    $stmt->execute([$roomId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all achievements in room
 * @param int $roomId
 * @return array Array of achievements with usernames
 */
function getRoomAchievements($roomId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT ra.*, u.username
        FROM room_achievements ra
        JOIN users u ON u.id = ra.user_id
        WHERE ra.room_id = ?
        ORDER BY ra.earned_at DESC
    ");
    
    $stmt->execute([$roomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Check if user is room creator
 * @param int $roomId
 * @param int $userId
 * @return bool
 */
function isRoomCreator($roomId, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE id = ? AND creator_user_id = ?");
    $stmt->execute([$roomId, $userId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Can user perform action on room (is creator or admin)
 * @param int $roomId
 * @param int $userId
 * @return bool
 */
function canManageRoom($roomId, $userId) {
    require_once __DIR__ . '/session.php';
    return isRoomCreator($roomId, $userId) || isAdmin();
}