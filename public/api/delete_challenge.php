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

if (!$challengeId) {
    echo json_encode(['success' => false, 'error' => 'Challenge ID required']);
    exit;
}

if (!canManageChallenge($challengeId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not authorized to delete this challenge']);
    exit;
}

if (deleteChallenge($challengeId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete challenge']);
}
