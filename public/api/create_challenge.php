<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once '../includes/session.php';
require_once '../includes/challenges.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        // Fallback to $_POST if not JSON
        $input = $_POST;
    }

    $userId = $_SESSION['user_id'];

    // Check challenge limit
    if (getUserChallengeCount($userId) >= 10) {
        echo json_encode(['success' => false, 'error' => 'Maximum 10 challenges per user']);
        exit;
    }

    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $category = $input['category'] ?? 'Other';
    $privacy = $input['privacy'] ?? 'private';
    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $goalIds = $input['goal_ids'] ?? []; // Array of goal IDs to track

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Challenge name is required']);
        exit;
    }

    $challengeId = createChallenge($userId, $name, $description, $category, $privacy, $startDate, $endDate);

    if ($challengeId) {
        // Add selected goals to challenge
        if (is_array($goalIds)) {
            foreach ($goalIds as $goalId) {
                addGoalToChallenge($challengeId, $goalId, $userId);
            }
        }

        echo json_encode(['success' => true, 'challenge_id' => $challengeId]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create challenge']);
    }
} catch (Exception $e) {
    // Log the error
    error_log("Create challenge error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
