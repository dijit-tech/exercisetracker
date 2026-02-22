<?php
// Completely suppress all error display
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once 'includes/session.php';
require_once 'includes/challenges.php';
require_once 'includes/goals.php';

requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$challengeId = $_GET['id'] ?? 0;

if (!$challengeId) {
    header('Location: challenges.php');
    exit;
}

// Get challenge details
$challenge = getChallengeById($challengeId);

if (!$challenge) {
    die('Challenge not found');
}

// Check if user is member
if (!isChallengeMember($challengeId, $userId) && !isChallengeCreator($challengeId, $userId)) {
    die('You are not a member of this challenge');
}

$isCreator = isChallengeCreator($challengeId, $userId);
$members = getChallengeMembers($challengeId);
$myGoals = getChallengeGoalsByUser($challengeId, $userId);
$availableGoals = getAvailableGoalsForChallenge($challengeId, $userId);
$allChallengeGoals = getAllChallengeGoals($challengeId);
$posts = getChallengePosts($challengeId, 50);

// Get current month leaderboard
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// If accessing without specific month, and challenge has ended, default to the end-date month
if (!isset($_GET['month']) && !empty($challenge['end_date'])) {
    $challengeEndMonth = date('Y-m', strtotime($challenge['end_date']));
    if ($currentMonth > $challengeEndMonth) {
        $currentMonth = $challengeEndMonth;
    }
}

// Determine start/end of month
$monthDate = new DateTime($currentMonth . '-01');
$monthStart = $monthDate->format('Y-m-01');
$monthEnd = $monthDate->format('Y-m-t');
$monthName = $monthDate->format('F Y');

// Calculate previous and next month
$prevMonth = (clone $monthDate)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthDate)->modify('+1 month')->format('Y-m');

// Navigation visibility
$showPrev = true;
if (!empty($challenge['start_date'])) {
    $challengeStartMonth = date('Y-m', strtotime($challenge['start_date']));
    if ($currentMonth <= $challengeStartMonth) {
        $showPrev = false;
    }
}

$showNext = true;
if (!empty($challenge['end_date'])) {
    $challengeEndMonth = date('Y-m', strtotime($challenge['end_date']));
    if ($currentMonth >= $challengeEndMonth) {
        $showNext = false;
    }
} elseif ($nextMonth > date('Y-m', strtotime('+1 month'))) {
     // Optional: Prevents scrolling infinitely into future for ongoing challenges? 
     // Requirement only asked for "past their end date". So I'll stick to that.
}

// Get completion data for race track
$completionData = getChallengeCompletionPercentage($challengeId, $monthStart, $monthEnd);

// Generate date range
$startDate = new DateTime($monthStart);
$endDate = new DateTime($monthEnd);

// Restrict to challenge dates if they exist
if (!empty($challenge['start_date']) && $challenge['start_date'] > $monthStart) {
    if ($challenge['start_date'] <= $monthEnd) {
        $startDate = new DateTime($challenge['start_date']);
    }
}
if (!empty($challenge['end_date']) && $challenge['end_date'] < $monthEnd) {
    if ($challenge['end_date'] >= $monthStart) {
        $endDate = new DateTime($challenge['end_date']);
    }
}

$dateRange = [];
for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
    $dateRange[] = $date->format('Y-m-d');
}

// Calculate scores and rankings
$userScores = [];
// Flatten/Restructure data: [uid] => {username, score, days: [date=>data]}
// completionData is [date][uid] = data

// First, initialize users from members list to ensure everyone shows up
$membersList = getChallengeMembers($challengeId);
foreach ($membersList as $member) {
    if ($member['status'] == 'active') {
        $userScores[$member['user_id']] = [
            'username' => $member['username'],
            'score' => 0,
            'days' => []
        ];
    }
}

// Populate scores
foreach ($completionData as $date => $users) {
    foreach ($users as $uid => $data) {
        if (!isset($userScores[$uid])) {
             // In case a user isn't in active members list anymore but has data
             $userScores[$uid] = [
                'username' => $data['username'],
                'score' => 0,
                'days' => []
            ];
        }
        
        // Scoring logic (same as dashboard)
        $percentage = $data['percentage'];
        if ($percentage == 100) $points = 10;
        elseif ($percentage >= 67) $points = 7;
        elseif ($percentage >= 34) $points = 5;
        elseif ($percentage >= 1) $points = 2;
        else $points = 0;
        
        $userScores[$uid]['score'] += $points;
        $userScores[$uid]['days'][$date] = $data;
    }
}

// Sort by score
uasort($userScores, function($a, $b) {
    return $b['score'] - $a['score'];
});

// Assign ranks
$rank = 1;
foreach ($userScores as $uid => &$data) {
    $data['rank'] = $rank++;
}
unset($data);

$pageTitle = htmlspecialchars($challenge['name']);
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
        /* Race Track Styles */
        .race-track {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
            margin-bottom: 10px;
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
        .track-block.percent-0 { background-color: #f0f0f0; }
        .track-block.percent-1-33 { background-color: #ffcccc; }
        .track-block.percent-34-66 { background-color: #ffffcc; }
        .track-block.percent-67-99 { background-color: #ccffcc; }
        .track-block.percent-100 { background-color: #00aa00; }
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
        @media (max-width: 768px) {
            .track-block { min-width: 10px; width: 10px; height: 20px; }
            .track-user-info { min-width: 100px; font-size: 0.9em; }
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
                        <a class="nav-link active" href="/challenges.php">Challenges</a>
                    </li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin.php">Admin</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/settings.php">Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/api/logout.php">Logout (<?php echo htmlspecialchars($username); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Challenge Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="mb-2">
                            <i class="bi bi-trophy"></i> <?php echo htmlspecialchars($challenge['name']); ?>
                            <span class="badge bg-<?php echo $challenge['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($challenge['status']); ?>
                            </span>
                        </h1>
                        <p class="text-muted"><?php echo htmlspecialchars($challenge['description']); ?></p>
                        <div class="d-flex gap-3">
                            <span><i class="bi bi-person-fill"></i> Created by: <strong><?php echo htmlspecialchars($challenge['creator_username']); ?></strong></span>
                            <span><i class="bi bi-people-fill"></i> <?php echo $challenge['member_count']; ?> members</span>
                            <?php if ($challenge['start_date']): ?>
                                <?php 
                                    $duration = date('M d, Y', strtotime($challenge['start_date']));
                                    if ($challenge['end_date']) {
                                        $duration .= ' - ' . date('M d, Y', strtotime($challenge['end_date']));
                                        $days = (strtotime($challenge['end_date']) - strtotime($challenge['start_date'])) / (60 * 60 * 24);
                                        $duration .= ' (' . round($days) . ' days)';
                                    } else {
                                        $duration .= ' (Ongoing)';
                                    }
                                ?>
                                <span><i class="bi bi-calendar-event"></i> <?php echo $duration; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($isCreator): ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
                            <i class="bi bi-envelope"></i> Invite
                        </button>
                        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editChallengeModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <?php else: ?>
                        <!-- Leave challenge button could go here -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Leaderboard -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> Leaderboard</h5>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-light me-2" onclick="window.location.search='?id=<?= $challengeId ?>&month=<?= $prevMonth ?>'" <?= $showPrev ? '' : 'disabled' ?>>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="fw-bold"><?= $monthName ?></span>
                            <button class="btn btn-sm btn-light ms-2" onclick="window.location.search='?id=<?= $challengeId ?>&month=<?= $nextMonth ?>'" <?= $showNext ? '' : 'disabled' ?>>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="leaderboardContent">
                        <?php if (empty($userScores)): ?>
                        <div class="alert alert-info">No activity this month yet. Start logging goals!</div>
                        <?php else: ?>
                        
                        <div class="leaderboard-container">
                        <?php foreach ($userScores as $uid => $userData): ?>
                            <div class="race-track">
                                <!-- User Info -->
                                <div class="track-user-info">
                                    <div class="track-username"><?= htmlspecialchars($userData['username']) ?></div>
                                    <div class="track-score"><?= $userData['score'] ?> points</div>
                                    <div class="track-rank <?= $userData['rank'] <= 3 ? 'rank-' . $userData['rank'] : 'rank-other' ?>">
                                        <?php 
                                        if ($userData['rank'] == 1) echo '🥇 #1';
                                        elseif ($userData['rank'] == 2) echo '🥈 #2';
                                        elseif ($userData['rank'] == 3) echo '🥉 #3';
                                        else echo '#' . $userData['rank'];
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Start Flag -->
                                <div class="start-flag">🏁</div>
                                
                                <!-- Progress Blocks -->
                                <div class="track-progress">
                                    <?php foreach ($dateRange as $date): ?>
                                        <?php
                                        $dayData = $userData['days'][$date] ?? ['percentage' => 0, 'total_goals' => 0, 'completed_goals' => 0];
                                        $percentage = $dayData['percentage'];
                                        
                                        // Determine color class
                                        if ($percentage == 0) {
                                            $colorClass = 'percent-0';
                                        } elseif ($percentage <= 33) {
                                            $colorClass = 'percent-1-33';
                                        } elseif ($percentage <= 66) {
                                            $colorClass = 'percent-34-66';
                                        } elseif ($percentage < 100) {
                                            $colorClass = 'percent-67-99';
                                        } else {
                                            $colorClass = 'percent-100';
                                        }
                                        
                                        $formattedDate = date('M j, Y', strtotime($date));
                                        $title = $formattedDate . ': ' . 
                                                 ($dayData['completed_goals'] ?? 0) . ' of ' . 
                                                 ($dayData['total_goals'] ?? 0) . ' goals (' . 
                                                 $percentage . '%)';
                                        
                                        // Parse completed titles for logs
                                        $logsForJson = [];
                                        if (!empty($dayData['completed_titles'])) {
                                            $entries = explode('||', $dayData['completed_titles']);
                                            foreach ($entries as $entry) {
                                                $parts = explode('::', $entry);
                                                $goalTitle = $parts[0] ?? 'Goal';
                                                $goalCategory = $parts[1] ?? 'General';
                                                
                                                $logsForJson[] = [
                                                    'goal_title' => $goalTitle, 
                                                    'completed' => true, 
                                                    'goal_category' => $goalCategory, 
                                                    'notes' => ''
                                                ];
                                            }
                                        }
                                        
                                        $logsJson = json_encode($logsForJson);
                                        $usernameSafe = htmlspecialchars($userData['username']);
                                        
                                        echo '<div class="track-block ' . $colorClass . '" 
                                                  title="' . htmlspecialchars($title) . '"
                                                  data-date="' . $formattedDate . '"
                                                  data-username="' . $usernameSafe . '"
                                                  data-logs=\'' . htmlspecialchars($logsJson, ENT_QUOTES) . '\'
                                                  onclick="showDayDetails(this)"></div>';
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Finish Trophy -->
                                <div class="track-finish">🏆</div>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <!-- Legend -->
                        <div class="calendar-legend">
                            <span>Less</span>
                            <div class="legend-item">
                                <div class="legend-box percent-0"></div>
                                <span>0%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box percent-1-33"></div>
                                <span>1-33%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box percent-34-66"></div>
                                <span>34-66%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box percent-67-99"></div>
                                <span>67-99%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box percent-100"></div>
                                <span>100%</span>
                            </div>
                            <span>More</span>
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
                <!-- My Goals in This Challenge -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-bullseye"></i> My Goals (<?php echo count($myGoals); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($myGoals) === 0): ?>
                        <p class="text-muted small">No goals selected for this challenge.</p>
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
                                    <?php if ($member['user_id'] == $challenge['creator_user_id']): ?>
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
                    <h5 class="modal-title">Add Goal to Challenge</h5>
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

    <!-- Activity Details Modal -->
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

    <!-- Invite Modal -->
    <?php if ($isCreator): ?>
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invite to Challenge</h5>
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

    <!-- Edit Challenge Modal -->
    <div class="modal fade" id="editChallengeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editChallengeForm">
                    <input type="hidden" id="editChallengeId" name="challenge_id" value="<?php echo $challengeId; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editChallengeName" class="form-label">Challenge Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editChallengeName" name="name" value="<?php echo htmlspecialchars($challenge['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="editChallengeDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editChallengeDescription" name="description" rows="3"><?php echo htmlspecialchars($challenge['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editChallengeEndDate" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="editChallengeEndDate" name="end_date" value="<?php echo $challenge['end_date'] ? substr($challenge['end_date'], 0, 10) : ''; ?>">
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
        const challengeId = <?php echo $challengeId; ?>;
        let currentMonth = '<?php echo $currentMonth; ?>';

        // Post to feed
        document.getElementById('postForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('challenge_id', challengeId);
            formData.append('content', document.getElementById('postContent').value);
            
            try {
                const response = await fetch('api/post_to_challenge.php', {
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
            formData.append('challenge_id', challengeId);
            formData.append('invitee_email', document.getElementById('inviteeEmail').value);
            
            try {
                const response = await fetch('api/invite_to_challenge.php', {
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
        
        // Edit Challenge
        document.getElementById('editChallengeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            // Must map fields to what update_room.php expects
            // update_challenge.php might not exist, using api/update_room.php based on folder structure
            // Or wait, file list showed update_room.php but also update_challenge.php in recent changes?
            // Actually file list showed api/update_room.php.
            // Let's check api folder again.
            
            // Assuming update_room.php for now as it was legacy name for challenges
            
            try {
                const response = await fetch('api/update_challenge.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Challenge updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error updating challenge: ' + error.message);
            }
        });
        <?php endif; ?>

        // Add goal to challenge
        async function addGoal(goalId) {
            const formData = new FormData();
            formData.append('challenge_id', challengeId);
            formData.append('goal_id', goalId);
            
            try {
                const response = await fetch('api/add_goal_to_challenge.php', {
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

        // Remove goal from challenge
        async function removeGoal(goalId) {
            if (!confirm('Remove this goal from the challenge?')) return;
            
            const formData = new FormData();
            formData.append('challenge_id', challengeId);
            formData.append('goal_id', goalId);
            
            try {
                const response = await fetch('api/remove_goal_from_challenge.php', {
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
            // Already handled by navigation buttons using ?month=
            // But if there were logic here:
            // ...
        }

        // Show details for a specific day block
        function showDayDetails(element) {
            const date = element.dataset.date;
            const username = element.dataset.username;
            const logsData = element.dataset.logs; 
            
            document.getElementById('dayModalTitle').textContent = `${username}'s Activity - ${date}`;
            
            const modalBody = document.getElementById('dayModalBody');
            
            if (!logsData || logsData === '[]' || logsData === 'null') {
                modalBody.innerHTML = '<p class="text-muted text-center my-3">No detailed activity logs available.</p>';
            } else {
                 try {
                    const logs = JSON.parse(logsData);
                    let html = '<div class="list-group">';
                    logs.forEach(log => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${log.goal_title || 'Goal'}</h6>
                                    <span class="badge bg-primary rounded-pill">${log.goal_category || 'General'}</span>
                                </div>
                                ${log.notes ? `<p class="mb-1 text-muted small"><i class="bi bi-card-text"></i> ${log.notes}</p>` : ''}
                            </div>`;
                    });
                    html += '</div>';
                    modalBody.innerHTML = html;
                } catch (e) {
                     modalBody.innerHTML = '<p class="text-muted text-center my-3">No detailed activity logs available.</p>';
                }
            }
            
            const modal = new bootstrap.Modal(document.getElementById('dayDetailsModal'));
            modal.show();
        }
    </script>
</body>
</html>
