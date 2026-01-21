<?php
/**
 * Admin API - Create Challenge
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/challenges.php';

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
    header('Location: /admin.php?error=Challenge name is required');
    exit;
}

// Create challenge
// Use current admin user as creator
$creatorUserId = $_SESSION['user_id'];
$challengeId = createChallenge($creatorUserId, $name, $description, 'public', null, $endDate);

ob_end_clean();

if ($challengeId) {
    header('Location: /admin.php?success=Challenge created successfully');
} else {
    header('Location: /admin.php?error=Failed to create challenge');
}
exit;
