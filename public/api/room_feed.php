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

$userId = $_SESSION['user_id'];
$roomId = $_GET['room_id'] ?? 0;
$limit = $_GET['limit'] ?? 50;

if (!$roomId) {
    echo json_encode(['success' => false, 'error' => 'Room ID required']);
    exit;
}

if (!isRoomMember($roomId, $userId) && !isRoomCreator($roomId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this room']);
    exit;
}

$posts = getRoomPosts($roomId, $limit);

echo json_encode(['success' => true, 'posts' => $posts]);
