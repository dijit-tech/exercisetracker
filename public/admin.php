<?php
/**
 * Admin Panel
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rooms.php';

// Require admin access
requireAdmin();

$username = getCurrentUsername();
$currentUserId = getCurrentUserId();

// Get all users
$users = getAllUsers();
// Get all rooms
$rooms = getAllRooms();

// Get messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">� Goal Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?= htmlspecialchars($username) ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/api/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="page-header">
            <h2>Admin Panel ⚙️</h2>
            <p class="mb-0">Manage users and system settings</p>
        </div>

        <!-- Admin Content -->
        
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Admin Actions -->
        <div class="row mb-4">
            <!-- Add User Form -->
            <div class="col-md-12 col-lg-6 mb-3 mb-lg-0">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Add New User</h5>
                    </div>
                    <div class="card-body">
                        <form action="/api/admin_create_user.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_admin" 
                                               name="is_admin" value="1">
                                        <label class="form-check-label" for="is_admin">
                                            Make Admin
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add User</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Room Form -->
            <div class="col-md-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Room</h5>
                    </div>
                    <div class="card-body">
                        <form action="/api/admin_create_room.php" method="POST">
                            <div class="mb-3">
                                <label for="room_name" class="form-label">Room Name</label>
                                <input type="text" class="form-control" id="room_name" name="name" required placeholder="e.g. 2026 Fitness Challenge">
                            </div>
                            <div class="mb-3">
                                <label for="room_description" class="form-label">Description</label>
                                <textarea class="form-control" id="room_description" name="description" rows="1" placeholder="Optional description"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="room_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="room_end_date" name="end_date">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Create Room</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room List -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Rooms (<?= isset($rooms) ? count($rooms) : '0' ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Creator</th>
                                <th>Members</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($rooms) && count($rooms) > 0): ?>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td><?= $room['id'] ?></td>
                                        <td><?= htmlspecialchars($room['name']) ?></td>
                                        <td><?= htmlspecialchars($room['creator_username'] ?? 'Unknown') ?></td>
                                        <td><?= $room['member_count'] ?? 0 ?></td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'active' => 'success',
                                                'paused' => 'warning',
                                                'archived' => 'secondary'
                                            ];
                                            $badgeClass = $badges[$room['status'] ?? 'active'] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($room['status'] ?? 'active') ?></span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($room['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editRoomModal"
                                                    data-room-id="<?= $room['id'] ?>"
                                                    data-name="<?= htmlspecialchars($room['name']) ?>"
                                                    data-description="<?= htmlspecialchars($room['description']) ?>"
                                                    data-end-date="<?= $room['end_date'] ?>">
                                                Edit
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="deleteRoom(<?= $room['id'] ?>, '<?= addslashes($room['name']) ?>')">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No rooms found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- User List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Users (<?= count($users) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if ($user['id'] === $currentUserId): ?>
                                            <span class="badge bg-info">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?= date('M j, Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] !== $currentUserId): ?>
                                            <button type="button" class="btn btn-sm btn-warning me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    data-user-id="<?= $user['id'] ?>"
                                                    data-username="<?= htmlspecialchars($user['username']) ?>"
                                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                                    data-is-admin="<?= $user['is_admin'] ? '1' : '0' ?>">
                                                Edit
                                            </button>
                                            <form action="/api/admin_delete_user.php" method="POST" 
                                                  style="display: inline;"
                                                  onsubmit="return confirm('Are you sure you want to delete user <?= htmlspecialchars($user['username']) ?>? This will also delete all their exercises.')">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-warning me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    data-user-id="<?= $user['id'] ?>"
                                                    data-username="<?= htmlspecialchars($user['username']) ?>"
                                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                                    data-is-admin="<?= $user['is_admin'] ? '1' : '0' ?>">
                                                Edit Profile
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/api/admin_update_user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="new_password" 
                                   placeholder="Leave blank to keep current password" minlength="6">
                            <div class="form-text">Leave blank to keep the current password</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_admin" 
                                       name="is_admin" value="1">
                                <label class="form-check-label" for="edit_is_admin">
                                    Administrator Rights
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRoomForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_room_id" name="room_id">
                        
                        <div class="mb-3">
                            <label for="edit_room_name" class="form-label">Room Name</label>
                            <input type="text" class="form-control" id="edit_room_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_room_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_room_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_room_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="edit_room_end_date" name="end_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate edit modal with user data
        const editModal = document.getElementById('editUserModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            const email = button.getAttribute('data-email');
            const isAdmin = button.getAttribute('data-is-admin') === '1';
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_is_admin').checked = isAdmin;
        });

        // Edit Room Modal
        const editRoomModal = document.getElementById('editRoomModal');
        editRoomModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const roomId = button.getAttribute('data-room-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');
            const endDate = button.getAttribute('data-end-date');
            
            document.getElementById('edit_room_id').value = roomId;
            document.getElementById('edit_room_name').value = name;
            document.getElementById('edit_room_description').value = description;
            document.getElementById('edit_room_end_date').value = endDate;
        });

        // Handle Room Edit
        document.getElementById('editRoomForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            fetch('/api/update_room.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });

        // Delete Room
        function deleteRoom(roomId, roomName) {
            if (confirm('Are you sure you want to delete the room "' + roomName + '"? This cannot be undone.')) {
                fetch('/api/delete_room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ room_id: roomId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
    </script>
</body>
</html>
