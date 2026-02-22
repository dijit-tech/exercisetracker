<?php
/**
 * Update User Settings API
 * Handles saving user preferences (privacy, etc.)
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// Start Session
startSession();

// Ensure User is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$userId = getCurrentUserId();
$analyticsEnabled = isset($data['analytics_enabled']) ? (bool) $data['analytics_enabled'] : true;

// Build preferences object
$preferences = [
    'analytics_enabled' => $analyticsEnabled,
    'updated_at' => date('c')
];

try {
    $pdo = getDbConnection();
    
    // Update USER in DB
    $stmt = $pdo->prepare("UPDATE users SET preferences = :prefs WHERE id = :id");
    $result = $stmt->execute([
        ':prefs' => json_encode($preferences),
        ':id' => $userId
    ]);

    if ($result) {
        // Update SESSION immediately so other scripts see the change
        $_SESSION['preferences'] = $preferences;

        // Set Cookie for Client-Side tracking logic (optional but helpful for anonymous state)
        // If they opt-out, set cookie for 1 year. If opt-in, clear it.
        if (!$analyticsEnabled) {
            setcookie('analytics_optout', 'true', time() + (86400 * 365), "/", "", true, true);
        } else {
            setcookie('analytics_optout', '', time() - 3600, "/");
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Settings updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update database']);
    }

} catch (PDOException $e) {
    error_log("Settings Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
