<?php
/**
 * API Endpoint: Bulk log goals (from Track Today page)
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
$logDate = $_POST['log_date'] ?? date('Y-m-d');
$goals = $_POST['goals'] ?? []; // Array of goal_id => ['completed' => bool, 'notes' => string]

// Validation
if (!is_array($goals) || empty($goals)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No goals provided']);
    exit;
}

try {
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($goals as $goalId => $data) {
        $goalId = (int)$goalId;
        $completed = isset($data['completed']) ? (bool)$data['completed'] : true;
        $notes = trim($data['notes'] ?? '');
        
        // Verify goal belongs to user
        $goal = getGoalById($goalId, $userId);
        if (!$goal) {
            $errors[] = "Goal ID $goalId not found";
            $errorCount++;
            continue;
        }
        
        $success = logGoalCompletion($goalId, $userId, $logDate, $completed, $notes);
        
        if ($success) {
            $successCount++;
        } else {
            $errors[] = "Failed to log goal ID $goalId";
            $errorCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Logged $successCount goals successfully",
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to log goals: ' . $e->getMessage()]);
}
