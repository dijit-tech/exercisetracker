<?php
/**
 * API Endpoint: Log goal completion (quick action from dashboard)
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
$logDate = $_POST['log_date'] ?? date('Y-m-d');
$completed = isset($_POST['completed']) ? (bool)$_POST['completed'] : true;
$notes = trim($_POST['notes'] ?? '');

// Validation
if ($goalId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid goal ID']);
    exit;
}

// Verify goal belongs to user and is active
$goal = getGoalById($goalId, $userId);
if (!$goal) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Goal not found']);
    exit;
}

try {
    $success = logGoalCompletion($goalId, $userId, $logDate, $completed, $notes);
    
    if ($success) {
        // Get updated stats
        $currentStreak = calculateCurrentStreak($goalId);
        
        echo json_encode([
            'success' => true,
            'message' => $completed ? 'Goal completed!' : 'Goal marked as incomplete',
            'current_streak' => $currentStreak
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to log goal completion']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to log completion: ' . $e->getMessage()]);
}
