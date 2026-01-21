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

$challengeId = $_GET['challenge_id'] ?? $_GET['room_id'] ?? 0; // Support both for query param transition if needed, but preferably challenge_id

if (!$challengeId) {
    echo json_encode(['success' => false, 'error' => 'Challenge ID required']);
    exit;
}

$userId = $_SESSION['user_id'];

// Check if user is member
if (!isChallengeMember($challengeId, $userId) && !isChallengeCreator($challengeId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this challenge']);
    exit;
}

$challenge = getChallengeById($challengeId);

if ($challenge) {
    echo json_encode(['success' => true, 'challenge' => $challenge]);
} else {
    echo json_encode(['success' => false, 'error' => 'Challenge not found']);
}
