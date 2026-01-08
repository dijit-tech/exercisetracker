<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once '../includes/session.php';
require_once '../includes/rooms.php';

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

if (!$inviteId || !in_array($response, ['accepted', 'declined'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request parameters']);
    exit;
}

if (respondToRoomInvite($inviteId, $userId, $response)) {
    echo json_encode(['success' => true, 'response' => $response]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to respond to invite']);
}
