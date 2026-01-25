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

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    $input = $_POST;
}

$userId = $_SESSION['user_id'];
$title = trim($input['title'] ?? $input['goal_title'] ?? '');
$category = trim($input['category'] ?? $input['goal_category'] ?? '');
$startDate = $input['start_date'] ?? date('Y-m-d');
$endDate = !empty($input['end_date']) ? $input['end_date'] : null;

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
    
    // Structural Change: All goals must belong to a challenge.
    // Check if challenge_id is provided in input. If not, use default Personal Challenge.
    
    $challengeId = $input['challenge_id'] ?? null;
    
    if (!$challengeId) {
        // Find or create default challenge for user
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT id FROM challenges WHERE creator_user_id = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $challengeId = $stmt->fetchColumn();
        
        if (!$challengeId) {
            // Create default on the fly
             require_once __DIR__ . '/../includes/challenges.php';
             // Manual insert to set is_default=1
             $timestamp = date('Y-m-d H:i:s');
             $category = 'Personal';
             $stmt = $db->prepare("
                INSERT INTO challenges (creator_user_id, name, description, category, privacy, status, is_default, created_at)
                VALUES (?, ?, ?, ?, 'private', 'active', 1, ?)
            ");
            $name = $_SESSION['username'] . "'s Personal Goals";
            $desc = "Default personal workspace";
            $stmt->execute([$userId, $name, $desc, $category, $timestamp]);
            $challengeId = $db->lastInsertId();
            addChallengeMember($challengeId, $userId);
        }
    }
    
    // Link goal to challenge
    require_once __DIR__ . '/../includes/challenges.php';
    if ($challengeId) {
        addGoalToChallenge($challengeId, $goalId, $userId);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Goal created successfully',
        'goal_id' => $goalId,
        'challenge_id' => $challengeId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create goal: ' . $e->getMessage()]);
}
