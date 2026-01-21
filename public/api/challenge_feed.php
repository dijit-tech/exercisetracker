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

$userId = $_SESSION['user_id'];
$challengeId = $_GET['challenge_id'] ?? $_GET['room_id'] ?? 0;
$limit = $_GET['limit'] ?? 50;

if (!$challengeId) {
    echo json_encode(['success' => false, 'error' => 'Challenge ID required']);
    exit;
}

if (!isChallengeMember($challengeId, $userId) && !isChallengeCreator($challengeId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this challenge']);
    exit;
}

$posts = getChallengePosts($challengeId, $limit);

echo json_encode(['success' => true, 'posts' => $posts]);
