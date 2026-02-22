<?php
/**
 * Track Today Page - Quick logging for all active goals
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/goals.php';

startSession();
requireLogin();

$username = getCurrentUsername();
$isAdmin = isAdmin();
$userId = getCurrentUserId();

// Get today's date or selected date
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$activeGoals = getActiveGoals($userId);

// Get today's logs
$todaysLogs = [];
foreach ($activeGoals as $goal) {
    $log = getGoalLogForDate($goal['id'], $selectedDate);
    $todaysLogs[$goal['id']] = $log;
}

// Get messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Today - Goal Tracker</title>
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
        .goal-item {
            border-left: 4px solid #667eea;
            transition: all 0.2s;
        }
        .goal-item.completed {
            background-color: #e7f5e9;
            border-left-color: #28a745;
        }
        .goal-item:hover {
            transform: translateX(5px);
        }
        .checkbox-large {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }
        .category-badge {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
        }
        .date-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                        <a class="nav-link" href="/goals.php">My Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/track_today.php">Track Today</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/challenges.php">Challenges</a>
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
                        <a class="nav-link" href="/settings.php">Settings</a>
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
            <h2>Track Your Goals ✅</h2>
            <p class="mb-0">Mark your goals as complete for <?= date('F j, Y', strtotime($selectedDate)) ?></p>
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

        <!-- Date Selector -->
        <div class="date-selector">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Select Date:</label>
                    <input type="date" class="form-control" id="dateSelector" 
                           value="<?= htmlspecialchars($selectedDate) ?>" 
                           max="<?= date('Y-m-d') ?>"
                           onchange="window.location.href='?date=' + this.value">
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-outline-secondary me-2" 
                            onclick="changeDate(-1)">
                        <i class="bi bi-chevron-left"></i> Previous Day
                    </button>
                    <button type="button" class="btn btn-outline-secondary me-2" 
                            onclick="changeDate(1)" 
                            <?= $selectedDate >= date('Y-m-d') ? 'disabled' : '' ?>>
                        Next Day <i class="bi bi-chevron-right"></i>
                    </button>
                    <?php if ($selectedDate !== date('Y-m-d')): ?>
                        <button type="button" class="btn btn-primary" 
                                onclick="window.location.href='?'">
                            Today
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Goals Tracking Form -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-list-check"></i> Your Active Goals (<?= count($activeGoals) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($activeGoals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4em; color: #ccc;"></i>
                        <p class="text-muted mt-3">You don't have any active goals yet.</p>
                        <a href="/goals.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Your First Goal
                        </a>
                    </div>
                <?php else: ?>
                    <form id="trackGoalsForm">
                        <input type="hidden" name="log_date" value="<?= htmlspecialchars($selectedDate) ?>">
                        
                        <?php foreach ($activeGoals as $goal): 
                            $log = $todaysLogs[$goal['id']] ?? null;
                            $isCompleted = $log && $log['completed'];
                            $existingNotes = $log['notes'] ?? '';
                        ?>
                            <div class="card goal-item mb-3 <?= $isCompleted ? 'completed' : '' ?>" data-goal-id="<?= $goal['id'] ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <input type="checkbox" 
                                                   class="checkbox-large goal-checkbox" 
                                                   name="goals[<?= $goal['id'] ?>][completed]"
                                                   value="1"
                                                   <?= $isCompleted ? 'checked' : '' ?>
                                                   onchange="toggleGoalCompleted(this)">
                                        </div>
                                        <div class="col">
                                            <h6 class="mb-1">
                                                <?= htmlspecialchars($goal['goal_title']) ?>
                                                <span class="category-badge ms-2"><?= htmlspecialchars($goal['goal_category']) ?></span>
                                            </h6>
                                            <div class="notes-section mt-2">
                                                <input type="text" 
                                                       class="form-control form-control-sm" 
                                                       name="goals[<?= $goal['id'] ?>][notes]"
                                                       placeholder="Add notes (optional)..."
                                                       value="<?= htmlspecialchars($existingNotes) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Save All Changes
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Tip:</strong> Check off the goals you completed and add any notes you'd like. 
                            Your streak will update automatically when you save!
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle goal completed styling
        function toggleGoalCompleted(checkbox) {
            const goalItem = checkbox.closest('.goal-item');
            if (checkbox.checked) {
                goalItem.classList.add('completed');
            } else {
                goalItem.classList.remove('completed');
            }
        }

        // Change date navigation
        function changeDate(days) {
            const currentDate = new Date('<?= $selectedDate ?>');
            currentDate.setDate(currentDate.getDate() + days);
            const newDate = currentDate.toISOString().split('T')[0];
            
            // Don't allow future dates
            const today = new Date().toISOString().split('T')[0];
            if (newDate <= today) {
                window.location.href = '?date=' + newDate;
            }
        }

        // Submit form
        document.getElementById('trackGoalsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Convert to the format expected by API
            const goals = {};
            const logDate = formData.get('log_date');
            
            // Process each goal
            <?php foreach ($activeGoals as $goal): ?>
            goals[<?= $goal['id'] ?>] = {
                completed: formData.get('goals[<?= $goal['id'] ?>][completed]') === '1',
                notes: formData.get('goals[<?= $goal['id'] ?>][notes]') || ''
            };
            <?php endforeach; ?>
            
            // Create new FormData for API
            const apiFormData = new FormData();
            apiFormData.append('log_date', logDate);
            
            // Add goals as JSON
            for (const [goalId, data] of Object.entries(goals)) {
                apiFormData.append(`goals[${goalId}][completed]`, data.completed ? '1' : '0');
                apiFormData.append(`goals[${goalId}][notes]`, data.notes);
            }
            
            try {
                const response = await fetch('/api/bulk_log_goals.php', {
                    method: 'POST',
                    body: apiFormData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/track_today.php?date=<?= $selectedDate ?>&success=' + encodeURIComponent(data.message);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error(error);
                alert('Error saving goals');
            }
        });
    </script>
</body>
</html>
