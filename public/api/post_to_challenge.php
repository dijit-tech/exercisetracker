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
$content = $input['content'] ?? '';

if (!$challengeId || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Challenge ID and content required']);
    exit;
}

if (!isChallengeMember($challengeId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this challenge']);
    exit;
}

$postId = createChallengePost($challengeId, $userId, $content, 'message');

if ($postId) {
    echo json_encode(['success' => true, 'post_id' => $postId]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create post']);
}
