<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once 'includes/session.php';
require_once 'includes/rooms.php';
require_once 'includes/goals.php';

requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user's rooms
$activeRooms = getUserRooms($userId, 'active');
$pausedRooms = getUserRooms($userId, 'paused');
$archivedRooms = getUserRooms($userId, 'archived');

// Get pending invites
$pendingInvites = getUserPendingInvites($userId);

// Get user's goals (for creating rooms)
$userGoals = getUserGoals($userId);

$pageTitle = "My Rooms";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .room-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .room-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .invite-card {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-door-open"></i> My Rooms</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                <i class="bi bi-plus-circle"></i> Create Room
            </button>
        </div>

        <!-- Pending Invites -->
        <?php if (count($pendingInvites) > 0): ?>
        <div class="alert alert-warning mb-4">
            <h5 class="alert-heading"><i class="bi bi-envelope"></i> Pending Invitations (<?php echo count($pendingInvites); ?>)</h5>
            <div class="row">
                <?php foreach ($pendingInvites as $invite): ?>
                <div class="col-md-6 mb-2">
                    <div class="card invite-card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($invite['room_name']); ?></h6>
                            <p class="card-text small mb-2">
                                From: <strong><?php echo htmlspecialchars($invite['inviter_username']); ?></strong><br>
                                <?php echo htmlspecialchars(substr($invite['description'], 0, 100)); ?>
                            </p>
                            <button class="btn btn-success btn-sm" onclick="respondToInvite(<?php echo $invite['id']; ?>, 'accepted')">
                                <i class="bi bi-check-circle"></i> Accept
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="respondToInvite(<?php echo $invite['id']; ?>, 'declined')">
                                <i class="bi bi-x-circle"></i> Decline
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Active Rooms -->
        <h3 class="mb-3">Active Rooms (<?php echo count($activeRooms); ?>)</h3>
        <?php if (count($activeRooms) === 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You haven't joined any rooms yet. Create one to get started!
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($activeRooms as $room): ?>
            <div class="col-md-4 mb-3">
                <div class="card room-card" onclick="window.location.href='room.php?id=<?php echo $room['id']; ?>'">
                    <span class="badge bg-success room-status-badge">Active</span>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>
                            <?php if (strlen($room['description']) > 100) echo '...'; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-primary">
                                <i class="bi bi-people"></i> <?php echo $room['member_count']; ?> members
                            </span>
                            <span class="badge bg-info">
                                <i class="bi bi-bullseye"></i> <?php echo $room['my_goals_count']; ?> goals
                            </span>
                        </div>
                        <div class="mt-2 d-flex justify-content-between">
                            <?php if ($room['creator_user_id'] == $userId): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Creator</span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            
                            <?php if ($isAdmin || $room['creator_user_id'] == $userId): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editRoom(<?php echo $room['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteRoom(<?php echo $room['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Paused Rooms -->
        <?php if (count($pausedRooms) > 0): ?>
        <h3 class="mb-3 mt-4">Paused Rooms (<?php echo count($pausedRooms); ?>)</h3>
        <div class="row">
            <?php foreach ($pausedRooms as $room): ?>
            <div class="col-md-4 mb-3">
                <div class="card room-card opacity-75" onclick="window.location.href='room.php?id=<?php echo $room['id']; ?>'">
                    <span class="badge bg-warning room-status-badge">Paused</span>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-primary">
                                <i class="bi bi-people"></i> <?php echo $room['member_count']; ?> members
                            </span>
                        </div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <?php if ($room['creator_user_id'] == $userId): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Creator</span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            
                            <?php if ($isAdmin || $room['creator_user_id'] == $userId): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editRoom(<?php echo $room['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteRoom(<?php echo $room['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Archived Rooms -->
        <?php if (count($archivedRooms) > 0): ?>
        <h3 class="mb-3 mt-4">Archived Rooms (<?php echo count($archivedRooms); ?>)</h3>
        <div class="row">
            <?php foreach ($archivedRooms as $room): ?>
            <div class="col-md-4 mb-3">
                <div class="card room-card opacity-50" onclick="window.location.href='room.php?id=<?php echo $room['id']; ?>'">
                    <span class="badge bg-secondary room-status-badge">Archived</span>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>
                        </p>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <?php if ($room['creator_user_id'] == $userId): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Creator</span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            
                            <?php if ($isAdmin || $room['creator_user_id'] == $userId): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editRoom(<?php echo $room['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteRoom(<?php echo $room['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create Room Modal -->
    <div class="modal fade" id="createRoomModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Create New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createRoomForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="roomName" class="form-label">Room Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roomName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="roomDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="roomDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="endDate" name="end_date">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Goals to Track in This Room</label>
                            <?php if (count($userGoals) === 0): ?>
                            <div class="alert alert-warning">
                                You don't have any active goals yet. <a href="goals.php">Create a goal first</a>.
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($userGoals as $goal): ?>
                                <label class="list-group-item">
                                    <input class="form-check-input me-2" type="checkbox" name="goal_ids[]" value="<?php echo $goal['id']; ?>">
                                    <?php echo htmlspecialchars($goal['goal_title']); ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($goal['goal_category']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editRoomForm">
                    <input type="hidden" id="editRoomId" name="room_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editRoomName" class="form-label">Room Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editRoomName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editRoomDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editRoomDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editRoomEndDate" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="editRoomEndDate" name="end_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create room form submission
        document.getElementById('createRoomForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/create_room.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Room created successfully!');
                    window.location.href = 'room.php?id=' + result.room_id;
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error creating room: ' + error.message);
            }
        });

        // Edit Room Modal & Logic
        const editRoomModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
        
        async function editRoom(roomId) {
            try {
                // Fetch room details
                const response = await fetch(`api/get_room.php?id=${roomId}`);
                const data = await response.json();
                
                if (!data.success) {
                    alert('Error loading room details: ' + (data.error || 'Unknown error'));
                    return;
                }
                
                const room = data.room;
                
                // Populate form
                document.getElementById('editRoomId').value = room.id;
                document.getElementById('editRoomName').value = room.name;
                document.getElementById('editRoomDescription').value = room.description || '';
                document.getElementById('editRoomEndDate').value = room.end_date ? room.end_date.substring(0, 10) : '';
                
                // Show modal
                editRoomModal.show();
                
            } catch (error) {
                alert('Error fetching room details: ' + error.message);
            }
        }

        document.getElementById('editRoomForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Use URLSearchParams for form data encoding to handle special characters better if needed, 
            // but JSON is cleaner if the endpoint supports it.
            // update_room.php supports JSON input: $input = json_decode(file_get_contents('php://input'), true);
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/update_room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Room updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error updating room: ' + error.message);
            }
        });

        // Delete Room Logic
        async function deleteRoom(roomId) {
            if (!confirm('Are you sure you want to delete this room? This cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('room_id', roomId);
            
            try {
                const response = await fetch('api/delete_room.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Room deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error deleting room: ' + error.message);
            }
        }
        
        // Respond to invitation
        async function respondToInvite(inviteId, response) {
            if (!confirm('Are you sure you want to ' + response + ' this invitation?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('invite_id', inviteId);
            formData.append('response', response);
            
            try {
                const res = await fetch('api/respond_invite.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await res.json();
                
                if (result.success) {
                    alert('Invitation ' + response + '!');
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error responding to invitation: ' + error.message);
            }
        }
    </script>
</body>
</html>
