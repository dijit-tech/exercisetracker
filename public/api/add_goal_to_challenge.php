<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once '../includes/session.php';
require_once '../includes/challenges.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    $input = $_POST;
}

$userId = $_SESSION['user_id'];
$challengeId = $input['challenge_id'] ?? $input['room_id'] ?? 0;
$goalId = $input['goal_id'] ?? 0;

if (!$challengeId || !$goalId) {
    echo json_encode(['success' => false, 'error' => 'Challenge ID and Goal ID required']);
    exit;
}

if (!isChallengeMember($challengeId, $userId)) {
    // Check if challenge is public, if so auto-join
    $challenge = getChallengeById($challengeId);
    if ($challenge && $challenge['privacy'] === 'public') {
        if (!addChallengeMember($challengeId, $userId)) {
             echo json_encode(['success' => false, 'error' => 'Failed to join public challenge']);
             exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Not a member of this challenge']);
        exit;
    }
} else {
    $challenge = getChallengeById($challengeId);
}

// Validation: Goal Category must match Challenge Category
// Fetch goal details
require_once '../includes/goals.php';
$goal = getGoalById($goalId, $userId);

if (!$goal) {
    echo json_encode(['success' => false, 'error' => 'Goal not found']);
    exit;
}

// Special case: "Other" challenge category might accept any? Or strict matching?
// Prompt says "All goals in that challenge should be in that category." => strict matching
if ($challenge['category'] !== 'Other' && $goal['goal_category'] !== $challenge['category']) {
    echo json_encode(['success' => false, 'error' => "Goal category '{$goal['goal_category']}' does not match challenge category '{$challenge['category']}'"]);
    exit;
}

if (addGoalToChallenge($challengeId, $goalId, $userId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add goal to challenge']);
}
