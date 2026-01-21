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
$inviteId = $input['invite_id'] ?? 0;
$response = $input['response'] ?? '';
$goalId = $input['goal_id'] ?? 0;

if (!$inviteId || !in_array($response, ['accepted', 'declined'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request parameters']);
    exit;
}

if (respondToChallengeInvite($inviteId, $userId, $response)) {
    // If accepted and goal_id provided, link the goal
    if ($response === 'accepted' && $goalId) {
        // We need to find the challenge ID from the invite
        // This is a bit inefficient without refactoring getInvite, but let's assume we can get it or addGoalToChallenge will verify.
        // Actually, respondToChallengeInvite adds to member list.
        // We need to get the challenge ID first.
        
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT challenge_id FROM challenge_invites WHERE id = ?");
        $stmt->execute([$inviteId]);
        $challengeId = $stmt->fetchColumn();
        
        if ($challengeId) {
            addGoalToChallenge($challengeId, $goalId, $userId);
        }
    }
    
    echo json_encode(['success' => true, 'response' => $response]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to respond to invite']);
}
