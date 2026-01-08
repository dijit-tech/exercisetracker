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
$status = $_GET['status'] ?? 'active';

$rooms = getUserRooms($userId, $status);

echo json_encode(['success' => true, 'rooms' => $rooms]);
