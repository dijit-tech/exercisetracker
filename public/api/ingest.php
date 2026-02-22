<?php
/**
 * Clickstream Ingestion Endpoint (v3)
 * Based on architecture in docs/CLICKSTREAM_ARCHITECTURE.md
 * 
 * - Write-only endpoint.
 * - Respects user privacy preferences.
 * - Uses session hashing for anonymous tracking.
 */

// Start session to access user_id and preferences
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header for API response
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 1. Get raw input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// 2. Validate essential fields
if (!isset($data['event_name']) || !isset($data['client_timestamp'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing event_name or client_timestamp']);
    exit;
}

// 3. Connect to Database (using existing helper)
require_once __DIR__ . '/../includes/db.php';
try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    // Log error but don't expose details to client
    error_log("Database connection failed for ingestion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}

// 4. Determine Context & Privacy Settings
$userId = $_SESSION['user_id'] ?? null;
$analyticsEnabled = true; // Default to true unless opted out

// Check user preferences if logged in
if ($userId && isset($_SESSION['preferences'])) {
    $prefs = is_array($_SESSION['preferences']) ? $_SESSION['preferences'] : json_decode($_SESSION['preferences'], true);
    if (isset($prefs['analytics_enabled']) && $prefs['analytics_enabled'] === false) {
        $analyticsEnabled = false;
    }
}

// Check if user explicitly opted out via cookie (for guests/logged out)
if (isset($_COOKIE['analytics_optout']) && $_COOKIE['analytics_optout'] === 'true') {
    $analyticsEnabled = false;
}


// 5. Prepare Data based on Privacy
$sessionHash = hash('sha256', session_id()); // Anonymize actual session ID
$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

if (!$analyticsEnabled) {
    // Privacy Mode: Anonymize critical identifiers
    $ipAddress = hash('sha256', $ipAddress); // Hashed IP
    $userAgent = null; // No User Agent stored
    $userId = null; // Store as anonymous even if logged in
    
    // We still record the event name and timestamp, but strip identifying context
    // This allows us to track *system usage* without tracking *the user*
}

// 6. Construct Properties and Context JSON
$properties = isset($data['properties']) ? json_encode($data['properties']) : null;
$contextData = [
    'ip_address' => $ipAddress,
    'user_agent' => $userAgent,
    'referer' => $_SERVER['HTTP_REFERER'] ?? null,
    'screen_width' => $data['screen_width'] ?? null,
    'screen_height' => $data['screen_height'] ?? null
];

// If privacy is on, remove sensitive context
if (!$analyticsEnabled) {
    unset($contextData['referer']);
    unset($contextData['screen_width']);
    unset($contextData['screen_height']);
}

$context = json_encode($contextData);

// 7. Insert into Database
try {
    $stmt = $pdo->prepare("
        INSERT INTO clickstream_events 
        (event_name, user_id, session_hash, occurred_at_utc, client_timezone, client_timestamp, properties, context)
        VALUES 
        (:event_name, :user_id, :session_hash, UTC_TIMESTAMP(), :client_timezone, :client_timestamp, :properties, :context)
    ");

    $stmt->execute([
        ':event_name' => substr($data['event_name'], 0, 100), // Truncate to fit
        ':user_id' => $userId,
        ':session_hash' => $sessionHash,
        ':client_timezone' => $data['client_timezone'] ?? 'UTC',
        ':client_timestamp' => date('Y-m-d H:i:s', strtotime($data['client_timestamp'])), // Format for MySQL
        ':properties' => $properties,
        ':context' => $context
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'recorded', 'privacy_mode' => !$analyticsEnabled]);

} catch (PDOException $e) {
    error_log("Clickstream Insert Error: " . $e->getMessage());
    http_response_code(500); // Or 200 to not break client
    echo json_encode(['error' => 'Storage Failed']);
}
