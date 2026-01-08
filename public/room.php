<?php
// Completely suppress all error display
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once 'includes/session.php';
require_once 'includes/rooms.php';
require_once 'includes/goals.php';

requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$roomId = $_GET['id'] ?? 0;

if (!$roomId) {
    header('Location: rooms.php');
    exit;
}

// Get room details
$room = getRoomById($roomId);

if (!$room) {
    die('Room not found');
}

// Check if user is member
if (!isRoomMember($roomId, $userId) && !isRoomCreator($roomId, $userId)) {
    die('You are not a member of this room');
}

$isCreator = isRoomCreator($roomId, $userId);
$members = getRoomMembers($roomId);
$myGoals = getRoomGoalsByUser($roomId, $userId);
$availableGoals = getAvailableGoalsForRoom($roomId, $userId);
$allRoomGoals = getAllRoomGoals($roomId);
$posts = getRoomPosts($roomId, 50);

// Get current month leaderboard
$currentMonth = date('Y-m');
$leaderboard = getRoomLeaderboard($roomId, $currentMonth);

$pageTitle = htmlspecialchars($room['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .post-item {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .goal-badge {
            font-size: 0.85rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">🎯 Goal Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/goals.php">My Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/track_today.php">Track Today</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/rooms.php">Rooms</a>
                    </li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin.php">Admin</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/api/logout.php">Logout (<?php echo htmlspecialchars($username); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Room Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="mb-2">
                            <i class="bi bi-door-open"></i> <?php echo htmlspecialchars($room['name']); ?>
                            <span class="badge bg-<?php echo $room['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($room['status']); ?>
                            </span>
                        </h1>
                        <p class="text-muted"><?php echo htmlspecialchars($room['description']); ?></p>
                        <div class="d-flex gap-3">
                            <span><i class="bi bi-person-fill"></i> Created by: <strong><?php echo htmlspecialchars($room['creator_username']); ?></strong></span>
                            <span><i class="bi bi-people-fill"></i> <?php echo $room['member_count']; ?> members</span>
                            <?php if ($room['start_date']): ?>
                            <span><i class="bi bi-calendar-event"></i> Started: <?php echo date('M d, Y', strtotime($room['start_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($isCreator): ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
                            <i class="bi bi-envelope"></i> Invite
                        </button>
                        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editRoomModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <?php else: ?>
                        <button class="btn btn-danger btn-sm" onclick="leaveRoom()">
                            <i class="bi bi-box-arrow-right"></i> Leave Room
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Leaderboard -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> Leaderboard - <?php echo date('F Y', strtotime($currentMonth . '-01')); ?></h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="changeMonth(-1)">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="changeMonth(1)">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="leaderboardContent">
                        <?php if (count($leaderboard) === 0): ?>
                        <div class="alert alert-info">No activity this month yet. Start logging goals!</div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($leaderboard as $entry): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center <?php echo $entry['user_id'] == $userId ? 'bg-light' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-3" style="width: 30px;">
                                        <?php echo $entry['rank']; ?>
                                    </span>
                                    <div class="member-avatar me-3">
                                        <?php echo strtoupper(substr($entry['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($entry['username']); ?></strong>
                                        <?php if ($entry['user_id'] == $userId): ?>
                                        <span class="badge bg-info ms-2">You</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted"><?php echo $entry['days_active']; ?> days active</small>
                                    </div>
                                </div>
                                <h4 class="mb-0">
                                    <span class="badge bg-success"><?php echo $entry['total_points']; ?> pts</span>
                                </h4>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Activity Feed</h5>
                    </div>
                    <div class="card-body">
                        <form id="postForm" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="postContent" name="content" placeholder="Share an update..." required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-send"></i> Post
                                </button>
                            </div>
                        </form>

                        <div id="feedContent">
                            <?php if (count($posts) === 0): ?>
                            <div class="alert alert-info">No posts yet. Be the first to share!</div>
                            <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                            <div class="post-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                                        <?php if ($post['post_type'] === 'achievement'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-trophy"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo date('M d, g:ia', strtotime($post['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($post['content']); ?></p>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Members & Goals -->
            <div class="col-md-4">
                <!-- My Goals in This Room -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-bullseye"></i> My Goals (<?php echo count($myGoals); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($myGoals) === 0): ?>
                        <p class="text-muted small">No goals selected for this room.</p>
                        <?php else: ?>
                        <?php foreach ($myGoals as $goal): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary goal-badge flex-grow-1">
                                <?php echo htmlspecialchars($goal['goal_title']); ?>
                            </span>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeGoal(<?php echo $goal['id']; ?>)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (count($availableGoals) > 0): ?>
                        <button class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                            <i class="bi bi-plus"></i> Add Goal
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Members -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-people"></i> Members (<?php echo count($members); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($members as $member): ?>
                            <div class="list-group-item d-flex align-items-center p-2">
                                <div class="member-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($member['username'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <small><?php echo htmlspecialchars($member['username']); ?></small>
                                    <?php if ($member['user_id'] == $room['creator_user_id']): ?>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Creator</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Goal Modal -->
    <div class="modal fade" id="addGoalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Goal to Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <?php foreach ($availableGoals as $goal): ?>
                        <button type="button" class="list-group-item list-group-item-action" onclick="addGoal(<?php echo $goal['id']; ?>)">
                            <?php echo htmlspecialchars($goal['goal_title']); ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($goal['goal_category']); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite Modal -->
    <?php if ($isCreator): ?>
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invite to Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="inviteForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="inviteeEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="inviteeEmail" name="invitee_email" required>
                            <small class="form-text text-muted">Enter the email address of the person you want to invite.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Invitation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const roomId = <?php echo $roomId; ?>;
        let currentMonth = '<?php echo $currentMonth; ?>';

        // Post to feed
        document.getElementById('postForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('content', document.getElementById('postContent').value);
            
            try {
                const response = await fetch('api/post_to_room.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('postContent').value = '';
                    location.reload(); // Reload to show new post
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error posting: ' + error.message);
            }
        });

        // Send invitation
        <?php if ($isCreator): ?>
        document.getElementById('inviteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('invitee_email', document.getElementById('inviteeEmail').value);
            
            try {
                const response = await fetch('api/invite_to_room.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Invitation sent!');
                    bootstrap.Modal.getInstance(document.getElementById('inviteModal')).hide();
                    document.getElementById('inviteeEmail').value = '';
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error sending invitation: ' + error.message);
            }
        });
        <?php endif; ?>

        // Add goal to room
        async function addGoal(goalId) {
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('goal_id', goalId);
            
            try {
                const response = await fetch('api/add_goal_to_room.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error adding goal: ' + error.message);
            }
        }

        // Remove goal from room
        async function removeGoal(goalId) {
            if (!confirm('Remove this goal from the room?')) return;
            
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('goal_id', goalId);
            
            try {
                const response = await fetch('api/remove_goal_from_room.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error removing goal: ' + error.message);
            }
        }

        // Change month for leaderboard
        function changeMonth(direction) {
            const date = new Date(currentMonth + '-01');
            date.setMonth(date.getMonth() + direction);
            const newMonth = date.toISOString().substr(0, 7);
            window.location.href = '?id=' + roomId + '&month=' + newMonth;
        }
    </script>
</body>
</html>
