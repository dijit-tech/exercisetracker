<?php
/**
 * Dashboard - Main page after login
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/exercises.php';

// Require login
requireLogin();

$username = getCurrentUsername();
$isAdmin = isAdmin();
$userId = getCurrentUserId();

// Get stats
$stats = getExerciseStats($userId, 7);
$recentExercises = getUserExercises($userId, 5);

// Get all users exercise activity for the year
$yearStart = '2026-01-01';
$yearEnd = '2026-12-31';
$allUsersActivity = getAllUsersExerciseActivity($yearStart, $yearEnd);

// Generate date range for the year
$startDate = new DateTime($yearStart);
$endDate = new DateTime($yearEnd);
$dateRange = [];
for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
    $dateRange[] = $date->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Exercise Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .calendar-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .calendar-grid {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .calendar-table {
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .calendar-table th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
            padding: 8px 4px;
        }
        .calendar-table td {
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .calendar-table th.user-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 20;
            font-weight: 600;
            text-align: left;
            min-width: 120px;
            padding-left: 12px;
            box-shadow: 2px 0 4px rgba(0,0,0,0.05);
        }
        .calendar-table td.user-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 5;
            font-weight: 500;
            text-align: left;
            padding-left: 12px;
            box-shadow: 2px 0 4px rgba(0,0,0,0.05);
        }
        .exercise-yes {
            color: #28a745;
            font-weight: bold;
        }
        .exercise-no {
            color: #dc3545;
            opacity: 0.5;
        }
        .month-header {
            background: #e7f3ff;
            font-weight: 600;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">🏃 Exercise Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/exercises.php">My Exercises</a>
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
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2>Welcome back, <?= htmlspecialchars($username) ?>! 👋</h2>
            <p class="mb-0">Ready to track your fitness journey today?</p>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?= $stats['total_exercises'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Exercises This Week</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?= $stats['total_minutes'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Total Minutes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3 class="text-info"><?= $stats['active_days'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Active Days</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Exercises -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Exercises</h5>
                <a href="/exercises.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentExercises)): ?>
                    <p class="text-muted text-center py-5">No exercises yet. Start tracking today!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Exercise</th>
                                    <th>Duration</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentExercises as $exercise): ?>
                                    <tr>
                                        <td><?= date('M j', strtotime($exercise['exercise_date'])) ?></td>
                                        <td><strong><?= htmlspecialchars($exercise['exercise_type']) ?></strong></td>
                                        <td><?= $exercise['duration_minutes'] ?> min</td>
                                        <td class="text-muted"><?= htmlspecialchars($exercise['notes'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Yearly Exercise Calendar for All Users -->
        <div class="card calendar-card mt-4">
            <div class="card-header">
                <h5 class="mb-0">📅 2026 Exercise Activity - All Users</h5>
            </div>
            <div class="card-body">
                <div class="calendar-grid">
                    <table class="table table-sm calendar-table mb-0">
                        <thead>
                            <tr>
                                <th class="user-col">User</th>
                                <?php 
                                $currentMonth = '';
                                $monthDayCount = [];
                                
                                // Count days per month
                                foreach ($dateRange as $date) {
                                    $month = date('M', strtotime($date));
                                    if (!isset($monthDayCount[$month])) {
                                        $monthDayCount[$month] = 0;
                                    }
                                    $monthDayCount[$month]++;
                                }
                                
                                // Output month headers
                                foreach ($monthDayCount as $month => $count) {
                                    echo '<th colspan="' . $count . '" class="month-header">' . $month . '</th>';
                                }
                                ?>
                            </tr>
                            <tr>
                                <th class="user-col">Name</th>
                                <?php 
                                foreach ($dateRange as $date) {
                                    $day = date('j', strtotime($date));
                                    echo '<th title="' . date('M j, Y', strtotime($date)) . '">' . $day . '</th>';
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsersActivity as $user): ?>
                                <tr>
                                    <td class="user-col"><?= htmlspecialchars($user['username']) ?></td>
                                    <?php 
                                    $exerciseDates = $user['exercise_dates'];
                                    foreach ($dateRange as $date) {
                                        $hasExercise = in_array($date, $exerciseDates);
                                        if ($hasExercise) {
                                            echo '<td class="exercise-yes" title="' . htmlspecialchars($user['username']) . ' exercised on ' . date('M j', strtotime($date)) . '">✓</td>';
                                        } else {
                                            echo '<td class="exercise-no">✕</td>';
                                        }
                                    }
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-muted small">
                    <strong>Legend:</strong> 
                    <span class="exercise-yes">✓</span> Exercised | 
                    <span class="exercise-no">✕</span> No exercise
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
