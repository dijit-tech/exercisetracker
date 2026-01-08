<?php
/**
 * Goals Page - View and manage goals
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/goals.php';

startSession();
requireLogin();

$username = getCurrentUsername();
$isAdmin = isAdmin();
$userId = getCurrentUserId();

// Get user goals grouped by status
$activeGoals = getGoalsWithStats($userId);
$pausedGoals = getPausedGoals($userId);
$archivedGoals = getArchivedGoals($userId);
$categories = getGoalCategories();

// Get messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Goals - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
        .goal-card {
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        .goal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .goal-card.paused {
            border-left-color: #ffc107;
            opacity: 0.7;
        }
        .goal-card.archived {
            border-left-color: #28a745;
        }
        .badge-streak {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .category-badge {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                        <a class="nav-link active" href="/goals.php">My Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/track_today.php">Track Today</a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin.php">Admin</a>
                    </li>
                    <?php endif; ?>
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>My Goals 🎯</h2>
                    <p class="mb-0">Create and manage your personal goals</p>
                </div>
                <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#createGoalModal">
                    <i class="bi bi-plus-circle"></i> New Goal
                </button>
            </div>
        </div>

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

        <!-- Active Goals -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Active Goals (<?= count($activeGoals) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($activeGoals)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3em;"></i><br>
                        No active goals yet. Create your first goal to get started!
                    </p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($activeGoals as $goal): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card goal-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0"><?= htmlspecialchars($goal['goal_title']) ?></h6>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editGoal(<?= $goal['id'] ?>, '<?= htmlspecialchars($goal['goal_title']) ?>', '<?= htmlspecialchars($goal['goal_category']) ?>', '<?= $goal['end_date'] ?? '' ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" onclick="changeStatus(<?= $goal['id'] ?>, 'pause')">
                                                    <i class="bi bi-pause"></i>
                                                </button>
                                                <button class="btn btn-outline-success" onclick="changeStatus(<?= $goal['id'] ?>, 'archive')">
                                                    <i class="bi bi-archive"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $goal['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <span class="category-badge"><?= htmlspecialchars($goal['goal_category']) ?></span>
                                        
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="badge badge-streak">
                                                    <i class="bi bi-fire"></i> <?= $goal['current_streak'] ?> day streak
                                                </span>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-trophy"></i> Best: <?= $goal['longest_streak'] ?> days
                                                </span>
                                            </div>
                                            
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= $goal['success_rate'] ?>%;" 
                                                     aria-valuenow="<?= $goal['success_rate'] ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?= $goal['success_rate'] ?>% (7 days)
                                                </div>
                                            </div>
                                            
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-check"></i> <?= $goal['total_completed'] ?> days completed
                                                <?php if ($goal['days_remaining'] !== null): ?>
                                                    | <i class="bi bi-hourglass-split"></i> <?= $goal['days_remaining'] ?> days left
                                                <?php endif; ?>
                                                <?php if ($goal['completed_today']): ?>
                                                    | <span class="text-success"><i class="bi bi-check-circle-fill"></i> Done today!</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Paused Goals -->
        <?php if (!empty($pausedGoals)): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-pause-circle"></i> Paused Goals (<?= count($pausedGoals) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($pausedGoals as $goal): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($goal['goal_title']) ?></strong>
                                <span class="category-badge ms-2"><?= htmlspecialchars($goal['goal_category']) ?></span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="changeStatus(<?= $goal['id'] ?>, 'resume')">
                                    <i class="bi bi-play-fill"></i> Resume
                                </button>
                                <button class="btn btn-outline-primary" onclick="editGoal(<?= $goal['id'] ?>, '<?= htmlspecialchars($goal['goal_title']) ?>', '<?= htmlspecialchars($goal['goal_category']) ?>', '<?= $goal['end_date'] ?? '' ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $goal['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Archived Goals -->
        <?php if (!empty($archivedGoals)): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-archive"></i> Archived Goals (<?= count($archivedGoals) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($archivedGoals as $goal): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($goal['goal_title']) ?></strong>
                                <span class="category-badge ms-2"><?= htmlspecialchars($goal['goal_category']) ?></span>
                                <small class="text-muted d-block">Archived on <?= date('M j, Y', strtotime($goal['updated_at'])) ?></small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="changeStatus(<?= $goal['id'] ?>, 'resume')">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reactivate
                                </button>
                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $goal['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create Goal Modal -->
    <div class="modal fade" id="createGoalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Goal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createGoalForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="goalTitle" class="form-label">Goal Title *</label>
                            <input type="text" class="form-control" id="goalTitle" name="title" required 
                                   placeholder="e.g., Read 30 minutes daily">
                        </div>
                        
                        <div class="mb-3">
                            <label for="goalCategory" class="form-label">Category *</label>
                            <select class="form-select" id="goalCategory" name="category" required>
                                <option value="">Choose a category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" 
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="endDate" class="form-label">Target End Date (Optional)</label>
                            <input type="date" class="form-control" id="endDate" name="end_date">
                            <small class="text-muted">Leave empty for ongoing goals</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Goal Modal -->
    <div class="modal fade" id="editGoalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Goal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editGoalForm">
                    <input type="hidden" id="editGoalId" name="goal_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editGoalTitle" class="form-label">Goal Title *</label>
                            <input type="text" class="form-control" id="editGoalTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editGoalCategory" class="form-label">Category *</label>
                            <select class="form-select" id="editGoalCategory" name="category" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEndDate" class="form-label">Target End Date (Optional)</label>
                            <input type="date" class="form-control" id="editEndDate" name="end_date">
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
        // Create Goal
        document.getElementById('createGoalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api/create_goal.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/goals.php?success=Goal created successfully!';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error creating goal');
            }
        });

        // Edit Goal
        function editGoal(id, title, category, endDate) {
            document.getElementById('editGoalId').value = id;
            document.getElementById('editGoalTitle').value = title;
            document.getElementById('editGoalCategory').value = category;
            document.getElementById('editEndDate').value = endDate || '';
            
            new bootstrap.Modal(document.getElementById('editGoalModal')).show();
        }

        document.getElementById('editGoalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api/update_goal.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/goals.php?success=Goal updated successfully!';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error updating goal');
            }
        });

        // Change Status
        async function changeStatus(goalId, action) {
            const actionText = {
                'pause': 'pause',
                'resume': 'resume',
                'archive': 'archive'
            }[action];
            
            if (!confirm(`Are you sure you want to ${actionText} this goal?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('goal_id', goalId);
            formData.append('action', action);
            
            try {
                const response = await fetch('/api/change_goal_status.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/goals.php?success=' + data.message;
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error changing goal status');
            }
        }

        // Delete Goal
        async function confirmDelete(goalId) {
            if (!confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('goal_id', goalId);
            formData.append('action', 'delete');
            
            try {
                const response = await fetch('/api/change_goal_status.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/goals.php?success=Goal deleted successfully!';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error deleting goal');
            }
        }
    </script>
</body>
</html>
