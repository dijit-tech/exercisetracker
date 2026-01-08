<?php
/**
 * API Endpoint: Change goal status (pause, resume, archive, delete)
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
$action = $_POST['action'] ?? '';

// Validation
if ($goalId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid goal ID']);
    exit;
}

if (!in_array($action, ['pause', 'resume', 'archive', 'delete'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
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
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'pause':
            $success = pauseGoal($goalId, $userId);
            $message = 'Goal paused successfully';
            break;
        case 'resume':
            $success = resumeGoal($goalId, $userId);
            $message = 'Goal resumed successfully';
            break;
        case 'archive':
            $success = archiveGoal($goalId, $userId);
            $message = 'Goal archived successfully';
            break;
        case 'delete':
            $success = deleteGoal($goalId, $userId);
            $message = 'Goal deleted successfully';
            break;
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update goal status']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update goal: ' . $e->getMessage()]);
}
