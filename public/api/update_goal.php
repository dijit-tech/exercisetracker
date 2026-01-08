<?php
/**
 * API Endpoint: Update an existing goal
 */

ob_start();
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/goals.php';

startSession();
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];
$goalId = (int)($_POST['goal_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

// Validation
if ($goalId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid goal ID']);
    exit;
}

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Goal title is required']);
    exit;
}

if (empty($category)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Goal category is required']);
    exit;
}

// Verify goal belongs to user
$goal = getGoalById($goalId, $userId);
if (!$goal) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Goal not found']);
    exit;
}

try {
    $success = updateGoal($goalId, $userId, $title, $category, $endDate);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Goal updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update goal']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update goal: ' . $e->getMessage()]);
}
