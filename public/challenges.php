<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once 'includes/session.php';
require_once 'includes/challenges.php';
require_once 'includes/goals.php';

requireLogin();

// Maintenance: Update status of expired challenges
updateExpiredChallenges();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

$categories = getGoalCategories();

// Get user's challenges
$activeChallenges = getUserChallenges($userId, 'active');
$pausedChallenges = getUserChallenges($userId, 'paused');
$archivedChallenges = getUserChallenges($userId, 'archived');

// Get pending invites
$pendingInvites = getUserPendingInvites($userId);

// Get user's goals (for creating challenges)
$userGoals = getUserGoals($userId);

$pageTitle = "My Challenges";
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
        .challenge-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .challenge-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .challenge-status-badge {
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-trophy"></i> My Challenges</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createChallengeModal">
                <i class="bi bi-plus-circle"></i> Create Challenge
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
                            <h6 class="card-title"><?php echo htmlspecialchars($invite['challenge_name']); ?></h6>
                            <p class="card-text small mb-2">
                                From: <strong><?php echo htmlspecialchars($invite['inviter_username']); ?></strong><br>
                                <?php echo htmlspecialchars(substr($invite['description'], 0, 100)); ?>
                            </p>
                            <button class="btn btn-success btn-sm" onclick="showAcceptInviteModal(<?php echo $invite['id']; ?>, '<?php echo addslashes($invite['challenge_name']); ?>', '<?php echo addslashes($invite['challenge_category']); ?>')">
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

        <!-- Active Challenges -->
        <h3 class="mb-3">Active Challenges (<?php echo count($activeChallenges); ?>)</h3>
        <?php if (count($activeChallenges) === 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You haven't joined any challenges yet. Create one to get started!
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($activeChallenges as $challenge): ?>
            <div class="col-md-4 mb-3">
                <div class="card challenge-card" onclick="window.location.href='challenge.php?id=<?php echo $challenge['id']; ?>'">
                    <span class="badge bg-success challenge-status-badge">Active</span>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($challenge['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($challenge['description'], 0, 100)); ?>
                            <?php if (strlen($challenge['description']) > 100) echo '...'; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-primary">
                                <i class="bi bi-people"></i> <?php echo $challenge['member_count']; ?> members
                            </span>
                            <span class="badge bg-info">
                                <i class="bi bi-bullseye"></i> <?php echo $challenge['my_goals_count']; ?> goals
                            </span>
                        </div>
                        <div class="mt-2 d-flex justify-content-between">
                            <?php if ($challenge['creator_user_id'] == $userId): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Creator</span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>

                            <?php if ($isAdmin || $challenge['creator_user_id'] == $userId): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editChallenge(<?php echo $challenge['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteChallenge(<?php echo $challenge['id']; ?>)">
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

        <!-- Paused Challenges -->
        <?php if (count($pausedChallenges) > 0): ?>
        <h3 class="mb-3 mt-4">Paused Challenges (<?php echo count($pausedChallenges); ?>)</h3>
        <div class="row">
            <?php foreach ($pausedChallenges as $challenge): ?>
            <div class="col-md-4 mb-3">
                <div class="card challenge-card opacity-75" onclick="window.location.href='challenge.php?id=<?php echo $challenge['id']; ?>'">
                    <span class="badge bg-warning challenge-status-badge">Paused</span>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($challenge['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($challenge['description'], 0, 100)); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-primary">
                                <i class="bi bi-people"></i> <?php echo $challenge['member_count']; ?> members
                            </span>
                        </div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <?php if ($challenge['creator_user_id'] == $userId): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Creator</span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>

                            <?php if ($isAdmin || $challenge['creator_user_id'] == $userId): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editChallenge(<?php echo $challenge['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteChallenge(<?php echo $challenge['id']; ?>)">
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

        <!-- Archived Challenges -->
        <?php if (count($archivedChallenges) > 0): ?>
        <h3 class="mb-3 mt-4">Archived Challenges (<?php echo count($archivedChallenges); ?>)</h3>
        <div class="row">
            <?php foreach ($archivedChallenges as $challenge): ?>
            <div class="col-md-4 mb-3">
                <div class="card challenge-card opacity-50" onclick="window.location.href='challenge.php?id=<?php echo $challenge['id']; ?>'">
                    <span class="badge bg-secondary challenge-status-badge">Archived</span>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($challenge['name']); ?></h5>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($challenge['description'], 0, 100)); ?>
                        </p>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <?php if ($challenge['creator_user_id'] == $userId): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Creator</span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>

                            <?php if ($isAdmin || $challenge['creator_user_id'] == $userId): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editChallenge(<?php echo $challenge['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteChallenge(<?php echo $challenge['id']; ?>)">
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

    <!-- Accept Invite Modal (New) -->
    <div class="modal fade" id="acceptInviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Join Challenge: <span id="inviteChallengeName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>To join this challenge, you need to track a goal. Choose an existing goal or create a new one.</p>
                    
                    <ul class="nav nav-tabs mb-3" id="inviteTabs" role="tablist">
                         <li class="nav-item">
                            <button class="nav-link active" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing-panel" type="button">Select Existing Goal</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-panel" type="button">Create New Goal</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Select Existing -->
                        <div class="tab-pane fade show active" id="existing-panel">
                             <?php if (count($userGoals) > 0): ?>
                                <select class="form-select mb-3" id="existingGoalSelect">
                                    <option value="">-- Select a Goal --</option>
                                    <?php foreach ($userGoals as $goal): ?>
                                        <option value="<?php echo $goal['id']; ?>" data-category="<?php echo htmlspecialchars($goal['goal_category']); ?>">
                                            <?php echo htmlspecialchars($goal['goal_title']); ?> (<?php echo htmlspecialchars($goal['goal_category']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary w-100" onclick="acceptInviteWithGoal('existing')">Join with Selected Goal</button>
                             <?php else: ?>
                                <p class="text-muted">You don't have any active goals.</p>
                             <?php endif; ?>
                        </div>
                        
                        <!-- Create New -->
                        <div class="tab-pane fade" id="new-panel">
                            <form id="newGoalForm">
                                <div class="mb-2">
                                    <label class="form-label">Goal Title</label>
                                    <input type="text" class="form-control" id="newGoalTitle" required placeholder="e.g., Run 5km daily">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" id="newGoalCategory">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">End Date (Optional)</label>
                                    <input type="date" class="form-control" id="newGoalEndDate">
                                </div>
                                <button type="submit" class="btn btn-success w-100">Create & Join</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Challenge Modal -->
    <div class="modal fade" id="createChallengeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Create New Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createChallengeForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="challengeName" class="form-label">Challenge Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="challengeName" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="challengeCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="challengeCategory" name="category" required onchange="filterChallengeGoals(this.value)">
                                <option value="">Choose a category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">All goals in this challenge must belong to this category.</div>
                        </div>

                        <div class="mb-3">
                            <label for="challengeDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="challengeDescription" name="description" rows="3"></textarea>
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
                            <label class="form-label">Select Goals to Track in This Challenge</label>
                            <?php if (count($userGoals) === 0): ?>
                            <div class="alert alert-warning">
                                You don't have any active goals yet. <a href="goals.php">Create a goal first</a>.
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($userGoals as $goal): ?>
                                <label class="list-group-item goal-option" data-category="<?php echo htmlspecialchars($goal['goal_category']); ?>">
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
                            <i class="bi bi-check-circle"></i> Create Challenge
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Challenge Modal -->
    <div class="modal fade" id="editChallengeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editChallengeForm">
                    <input type="hidden" id="editChallengeId" name="challenge_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editChallengeName" class="form-label">Challenge Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editChallengeName" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="editChallengeDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editChallengeDescription" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editChallengeEndDate" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="editChallengeEndDate" name="end_date">
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
        function filterChallengeGoals(category) {
            const items = document.querySelectorAll('.goal-option');
            items.forEach(item => {
                const itemCategory = item.dataset.category;
                if (category === '' || itemCategory === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    // Uncheck if hidden
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
            });
        }

        // Create challenge form submission
        document.getElementById('createChallengeForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('api/create_challenge.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Challenge created successfully!');
                    window.location.href = 'challenge.php?id=' + result.challenge_id;
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error creating challenge: ' + error.message);
            }
        });

        // Edit Challenge Modal & Logic
        const editChallengeModal = new bootstrap.Modal(document.getElementById('editChallengeModal'));

        async function editChallenge(challengeId) {
            try {
                // Fetch challenge details
                const response = await fetch(`api/get_challenge.php?challenge_id=${challengeId}`);
                const data = await response.json();

                if (!data.success) {
                    alert('Error loading challenge details: ' + (data.error || 'Unknown error'));
                    return;
                }

                const challenge = data.challenge;

                // Populate form
                document.getElementById('editChallengeId').value = challenge.id;
                document.getElementById('editChallengeName').value = challenge.name;
                document.getElementById('editChallengeDescription').value = challenge.description || '';
                document.getElementById('editChallengeEndDate').value = challenge.end_date ? challenge.end_date.substring(0, 10) : '';

                // Show modal
                editChallengeModal.show();

            } catch (error) {
                alert('Error fetching challenge details: ' + error.message);
            }
        }

        document.getElementById('editChallengeForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('api/update_challenge.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
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

        // Delete Challenge Logic
        async function deleteChallenge(challengeId) {
            if (!confirm('Are you sure you want to delete this challenge? This cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('challenge_id', challengeId);

            try {
                const response = await fetch('api/delete_challenge.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Challenge deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error deleting challenge: ' + error.message);
            }
        }


        // Invite Logic
        let currentInviteId = null;
        const acceptInviteModal = new bootstrap.Modal(document.getElementById('acceptInviteModal'));

        function showAcceptInviteModal(inviteId, challengeName, category) {
            currentInviteId = inviteId;
            document.getElementById('inviteChallengeName').textContent = challengeName;
            
            // Filter Existing Goals
            const goalSelect = document.getElementById('existingGoalSelect');
            if (goalSelect) {
                const options = goalSelect.options;
                for (let i = 0; i < options.length; i++) {
                    const opt = options[i];
                    if (opt.value === "") continue;
                    
                    // If challenge category is 'Other' or matches goal category, show it
                    if (!category || category === 'Other' || opt.dataset.category === category) {
                        opt.hidden = false;
                        opt.disabled = false;
                    } else {
                        opt.hidden = true;
                        opt.disabled = true;
                    }
                }
                goalSelect.value = "";
            }

            // Lock New Goal Category
            const catSelect = document.getElementById('newGoalCategory');
            if (category && category !== 'Other') {
                catSelect.value = category;
                catSelect.disabled = true;
            } else {
                catSelect.disabled = false;
            }

            acceptInviteModal.show();
        }

        async function acceptInviteWithGoal(type) {
            if (!currentInviteId) return;
            
            let goalId = null;
            
            if (type === 'existing') {
                goalId = document.getElementById('existingGoalSelect').value;
                if (!goalId) {
                    alert('Please select a goal');
                    return;
                }
                
                // Proceed to accept invite
                await submitInviteResponse(currentInviteId, 'accepted', goalId);
                
            } else if (type === 'new') {
                // Handled by form submit
            }
        }
        
        document.getElementById('newGoalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!currentInviteId) return;

            const title = document.getElementById('newGoalTitle').value;
            const category = document.getElementById('newGoalCategory').value;
            const endDate = document.getElementById('newGoalEndDate').value;

            try {
                // 1. Create Goal
                const goalRes = await fetch('api/create_goal.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        goal_title: title,
                        goal_category: category,
                        end_date: endDate,
                        start_date: new Date().toISOString().split('T')[0]
                    })
                });
                
                const goalData = await goalRes.json();
                
                if (goalData.goal_id) {
                    // 2. Accept Invite with New Goal
                    await submitInviteResponse(currentInviteId, 'accepted', goalData.goal_id);
                } else {
                    alert('Failed to create goal: ' + (goalData.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error processing request: ' + error.message);
            }
        });

        async function submitInviteResponse(inviteId, response, goalId = null) {
             const formData = new FormData();
             formData.append('invite_id', inviteId);
             formData.append('response', response);
             if (goalId) formData.append('goal_id', goalId);

             try {
                const res = await fetch('api/respond_invite.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                if (data.success) {
                    alert('Invite ' + response + '!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
             } catch (error) {
                 alert('Network error: ' + error.message);
             }
        }

        // Respond to invitation (Legacy/Decline)
        async function respondToInvite(inviteId, response) {
            if (response === 'accepted') {
                 return;
            }
            if (!confirm('Are you sure you want to ' + response + ' this invitation?')) {
                return;
            }
            await submitInviteResponse(inviteId, response);
        }
    </script>
</body>
</html>
