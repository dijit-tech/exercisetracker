<?php
/**
 * API Endpoint: Create a new goal
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
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$startDate = $_POST['start_date'] ?? date('Y-m-d');
$endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

// Validation
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

$validCategories = getGoalCategories();
if (!in_array($category, $validCategories)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category']);
    exit;
}

try {
    $goalId = createGoal($userId, $title, $category, $startDate, $endDate);
    
    echo json_encode([
        'success' => true,
        'message' => 'Goal created successfully',
        'goal_id' => $goalId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create goal: ' . $e->getMessage()]);
}
