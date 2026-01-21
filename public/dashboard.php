<?php
/**
 * Dashboard - Main page for goal tracking
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/goals.php';
require_once __DIR__ . '/includes/challenges.php'; // Included for notifications

startSession();
requireLogin();

$username = getCurrentUsername();
$isAdmin = isAdmin();
$userId = getCurrentUserId();

// Get Pending Invites count
$pendingInvites = getUserPendingInvites($userId);
$pendingInvitesCount = count($pendingInvites);

// Get goal stats
$stats = getGoalStats($userId);
$goalsGrouped = getGoalsWithStatsGroupedByRoom($userId);
$recentActivity = getRecentActivity($userId, 10);

// Get month from query parameter or default to current month
$currentMonth = $_GET['month'] ?? date('Y-m');
$monthDate = new DateTime($currentMonth . '-01');
$monthStart = $monthDate->format('Y-m-01');
$monthEnd = $monthDate->format('Y-m-t');
$monthName = $monthDate->format('F Y');

// Calculate previous and next month
$prevMonth = (clone $monthDate)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthDate)->modify('+1 month')->format('Y-m');
$currentYearMonth = date('Y-m');

// Get all users goal completion percentage for the month (heatmap) - REMOVED for Room-based view
// $allUsersCompletion = getAllUsersGoalCompletionPercentage($monthStart, $monthEnd);

// Generate date range for the month
$startDate = new DateTime($monthStart);
$endDate = new DateTime($monthEnd);
$dateRange = [];
for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
    $dateRange[] = $date->format('Y-m-d');
}

// Fetch Leaderboards for each Room
foreach ($goalsGrouped as &$group) {
    if ($group['room_id']) {
        $group['leaderboard'] = getChallengeLeaderboard($group['room_id'], $currentMonth);
    }
}
unset($group);

// Goals are now grouped by room, chunking happens in the view
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
        }
        .goal-card {
            border-left: 4px solid #667eea;
            height: 100%;
            transition: all 0.3s;
        }
        .goal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
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
        .carousel-control-prev, .carousel-control-next {
            width: 5%;
        }
        .leaderboard-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
        }
        .race-track {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .race-track:hover {
            background: #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .track-user-info {
            min-width: 150px;
            text-align: left;
            flex-shrink: 0;
        }
        .track-username {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .track-score {
            font-size: 0.9em;
            color: #666;
        }
        .track-rank {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
            margin-top: 5px;
        }
        .track-rank.rank-1 { background: #ffd700; color: #000; }
        .track-rank.rank-2 { background: #c0c0c0; color: #000; }
        .track-rank.rank-3 { background: #cd7f32; color: #fff; }
        .track-rank.rank-other { background: #e9ecef; color: #666; }
        .track-progress {
            display: flex;
            gap: 3px;
            flex: 1;
            overflow-x: auto;
            padding: 5px 0;
        }
        .track-block {
            min-width: 20px;
            width: 20px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .track-block:hover {
            transform: scale(1.2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 10;
        }
        .track-finish {
            font-size: 2em;
            flex-shrink: 0;
            animation: trophy-glow 2s infinite;
        }
        @keyframes trophy-glow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        .start-flag {
            font-size: 1.5em;
            margin-right: 10px;
        }
        .track-block.percent-0,
        .calendar-day.percent-0 {
            background-color: #f0f0f0;
        }
        .track-block.percent-1-33,
        .calendar-day.percent-1-33 {
            background-color: #ffcccc;
        }
        .track-block.percent-34-66,
        .calendar-day.percent-34-66 {
            background-color: #ffffcc;
        }
        .track-block.percent-67-99,
        .calendar-day.percent-67-99 {
            background-color: #ccffcc;
        }
        .track-block.percent-100,
        .calendar-day.percent-100 {
            background-color: #00aa00;
        }
        .calendar-day:hover {
            transform: scale(1.5);
            border: 1px solid #333;
        }
        .calendar-legend {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85em;
            margin-top: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        .activity-item {
            border-left: 3px solid #667eea;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .quick-action-btn {
            font-size: 0.9em;
            padding: 8px 16px;
        }

        /* Mobile Adjustments for Leaderboard */
        @media (max-width: 768px) {
            .track-block {
                min-width: 10px;
                width: 10px;
                height: 20px;
                border-radius: 2px;
                margin-right: 1px;
            }
            .track-user-info {
                min-width: 100px;
                font-size: 0.9em;
            }
            .track-username {
                font-size: 1.0em;
            }
            .race-track {
                gap: 5px;
                padding: 8px;
            }
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
                        <a class="nav-link active" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/goals.php">My Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/challenges.php">
                            Challenges 
                            <?php if ($pendingInvitesCount > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $pendingInvitesCount; ?></span>
                            <?php endif; ?>
                        </a>
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
        <?php if ($pendingInvitesCount > 0): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-envelope-exclamation-fill me-2"></i>
            You have <strong><?php echo $pendingInvitesCount; ?></strong> pending challenge invitation<?php echo $pendingInvitesCount > 1 ? 's' : ''; ?>.
            <a href="/challenges.php" class="alert-link">View Invitations</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Welcome back, <?= htmlspecialchars($username) ?>! 👋</h2>
                    <p class="mb-0">Let's make today count. Track your goals and build your streaks!</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="/track_today.php" class="btn btn-light btn-lg">
                        <i class="bi bi-check-circle"></i> Track Today
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <h1 class="display-4"><?= $stats['total_active'] ?></h1>
                    <p class="mb-0">Active Goals</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-success text-white">
                    <h1 class="display-4"><?= $stats['completed_today'] ?> / <?= $stats['total_active'] ?></h1>
                    <p class="mb-0">Completed Today</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-info text-white">
                    <h1 class="display-4"><?= $stats['success_rate_7days'] ?>%</h1>
                    <p class="mb-0">Success Rate (7 days)</p>
                </div>
            </div>
        </div>

        <!-- Goal Cards Carousel -->
        <?php if (!empty($goalsGrouped)): ?>
            <?php foreach ($goalsGrouped as $roomGroup): ?>
                <?php 
                    $roomName = htmlspecialchars($roomGroup['room_name']);
                    $roomGoals = $roomGroup['goals'];
                    $goalChunks = array_chunk($roomGoals, 2);
                    $carouselId = 'carousel-' . ($roomGroup['room_id'] ?: 'personal');
                ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php if($roomGroup['room_id']): ?>
                        <i class="bi bi-trophy"></i> <?= $roomName ?>
                    <?php else: ?>
                        <i class="bi bi-person"></i> <?= $roomName ?>
                    <?php endif; ?>
                </h5>
                <a href="/goals.php" class="btn btn-light btn-sm">
                    <i class="bi bi-gear"></i> Manage
                </a>
            </div>
            <div class="card-body">
                <div id="<?= $carouselId ?>" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner">
                        <?php foreach ($goalChunks as $index => $chunk): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="row">
                                    <?php foreach ($chunk as $goal): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card goal-card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0"><?= htmlspecialchars($goal['goal_title']) ?></h6>
                                                        <?php if (!$goal['completed_today']): ?>
                                                            <button class="btn btn-sm btn-success quick-action-btn" 
                                                                    onclick="quickLog(<?= $goal['id'] ?>)">
                                                                <i class="bi bi-check"></i> Done!
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle-fill"></i> Completed
                                                            </span>
                                                        <?php endif; ?>
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
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: <?= $goal['success_rate'] ?>%;" 
                                                                 aria-valuenow="<?= $goal['success_rate'] ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?= $goal['success_rate'] ?>%
                                                            </div>
                                                        </div>
                                                        
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar-check"></i> <?= $goal['total_completed'] ?> days completed
                                                            <?php if ($goal['days_remaining'] !== null): ?>
                                                                | <i class="bi bi-hourglass-split"></i> <?= $goal['days_remaining'] ?> days left
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($goalChunks) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                        
                        <div class="carousel-indicators position-static mt-3">
                            <?php foreach ($goalChunks as $index => $chunk): ?>
                                <button type="button" data-bs-target="#<?= $carouselId ?>" 
                                        data-bs-slide-to="<?= $index ?>" 
                                        class="<?= $index === 0 ? 'active' : '' ?>" 
                                        aria-current="<?= $index === 0 ? 'true' : 'false' ?>" 
                                        aria-label="Slide <?= $index + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Leaderboard View -->
                <?php if (!empty($roomGroup['leaderboard'])): ?>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold"><i class="bi bi-trophy"></i> Top Performers</h6>
                        <a href="/challenge.php?id=<?= $roomGroup['room_id'] ?>" class="text-decoration-none small">View Full Board <i class="bi bi-chevron-right"></i></a>
                    </div>
                    <div class="row g-2">
                        <?php 
                        $topLimit = 5;
                        $count = 0;
                        foreach ($roomGroup['leaderboard'] as $lbUser): 
                            if ($count++ >= $topLimit) break;
                            $isMe = ($lbUser['username'] === $username);
                        ?>
                        <div class="col-auto">
                            <span class="badge <?= $isMe ? 'bg-primary' : 'bg-light text-dark border' ?> rounded-pill">
                                <?php 
                                if($lbUser['rank'] == 1) echo '🥇';
                                elseif($lbUser['rank'] == 2) echo '🥈';
                                elseif($lbUser['rank'] == 3) echo '🥉';
                                else echo '#' . $lbUser['rank'];
                                ?>
                                <?= htmlspecialchars($lbUser['username']) ?> 
                                <span class="opacity-75 ms-1">(<?= $lbUser['total_points'] ?>)</span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4em; color: #ccc;"></i>
                <h4 class="mt-3">No Goals Yet</h4>
                <p class="text-muted">Start tracking your progress by creating your first goal!</p>
                <a href="/goals.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle"></i> Create Your First Goal
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity Feed -->
        <?php if (!empty($recentActivity)): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong><?= htmlspecialchars($activity['goal_title']) ?></strong>
                                <span class="category-badge ms-2"><?= htmlspecialchars($activity['goal_category']) ?></span>
                            </div>
                            <small class="text-muted"><?= date('M j, Y', strtotime($activity['log_date'])) ?></small>
                        </div>
                        <?php if ($activity['notes']): ?>
                            <small class="text-muted"><i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($activity['notes']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Day Details Modal -->
    <div class="modal fade" id="dayDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dayModalTitle">Activity Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dayModalBody">
                    <!-- Content will be populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show details for a specific day block
        function showDayDetails(element) {
            const date = element.dataset.date;
            const username = element.dataset.username;
            const logsData = element.dataset.logs;
            
            document.getElementById('dayModalTitle').textContent = `${username}'s Activity - ${date}`;
            
            const modalBody = document.getElementById('dayModalBody');
            
            if (!logsData || logsData === '[]' || logsData === 'null') {
                modalBody.innerHTML = '<p class="text-muted text-center my-3">No completed activities for this day.</p>';
            } else {
                try {
                    const logs = JSON.parse(logsData);
                    let html = '<div class="list-group">';
                    
                    logs.forEach(log => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${log.goal_title}</h6>
                                    <span class="badge bg-primary rounded-pill">${log.goal_category}</span>
                                </div>
                                ${log.notes ? `<p class="mb-1 text-muted small"><i class="bi bi-card-text"></i> ${log.notes}</p>` : ''}
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    modalBody.innerHTML = html;
                } catch (e) {
                    console.error('Error parsing logs:', e);
                    modalBody.innerHTML = '<p class="text-danger">Error loading activity data.</p>';
                }
            }
            
            const modal = new bootstrap.Modal(document.getElementById('dayDetailsModal'));
            modal.show();
        }

        // Quick log goal completion from dashboard
        async function quickLog(goalId) {
            const formData = new FormData();
            formData.append('goal_id', goalId);
            formData.append('log_date', '<?= date('Y-m-d') ?>');
            formData.append('completed', '1');
            
            try {
                const response = await fetch('/api/log_goal_completion.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload page to show updated stats
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error logging goal completion');
            }
        }
    </script>
</body>
</html>
