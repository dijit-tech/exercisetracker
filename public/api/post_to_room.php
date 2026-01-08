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
$roomId = $input['room_id'] ?? 0;
$content = $input['content'] ?? '';

if (!$roomId || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Room ID and content required']);
    exit;
}

if (!isRoomMember($roomId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this room']);
    exit;
}

$postId = createRoomPost($roomId, $userId, $content, 'message');

if ($postId) {
    echo json_encode(['success' => true, 'post_id' => $postId]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create post']);
}
