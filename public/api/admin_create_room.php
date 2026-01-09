<?php
/**
 * Admin API - Create Room
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/rooms.php';

// Require admin access
if (!isAdmin()) {
    ob_end_clean();
    header('Location: /admin.php?error=Unauthorized access');
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: /admin.php?error=Invalid request method');
    exit;
}

// Get data
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

// Validate
if (empty($name)) {
    ob_end_clean();
    header('Location: /admin.php?error=Room name is required');
    exit;
}

// Create room
// Use current admin user as creator
$creatorUserId = $_SESSION['user_id'];
$roomId = createRoom($creatorUserId, $name, $description, 'public', null, $endDate);

ob_end_clean();

if ($roomId) {
    header('Location: /admin.php?success=Room created successfully');
} else {
    header('Location: /admin.php?error=Failed to create room');
}
exit;
