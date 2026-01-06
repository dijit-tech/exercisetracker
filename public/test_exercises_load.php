<?php
/**
 * Test Exercises Page Loading
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Testing Exercises Page Components</h1>";

// Test 1: Session
echo "<h2>1. Testing session...</h2>";
try {
    require_once __DIR__ . '/includes/session.php';
    requireLogin();
    echo "✓ Session and login check passed<br>";
    echo "User ID: " . getCurrentUserId() . "<br>";
    echo "Username: " . getCurrentUsername() . "<br>";
} catch (Exception $e) {
    echo "✗ Session failed: " . $e->getMessage() . "<br>";
    echo "You need to login first at: <a href='/index.php'>Login</a><br>";
    die();
}

// Test 2: Load exercises functions
echo "<h2>2. Testing exercises.php functions...</h2>";
try {
    require_once __DIR__ . '/includes/exercises.php';
    echo "✓ Exercises functions loaded<br>";
} catch (Exception $e) {
    echo "✗ Failed to load exercises: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Get user exercises
echo "<h2>3. Testing getUserExercises...</h2>";
try {
    $userId = getCurrentUserId();
    $exercises = getUserExercises($userId);
    echo "✓ Got " . count($exercises) . " exercises<br>";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "<br>";
}

// Test 4: Get exercise types
echo "<h2>4. Testing getExerciseTypes...</h2>";
try {
    $types = getExerciseTypes();
    echo "✓ Got " . count($types) . " exercise types<br>";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "<br>";
}

// Test 5: Get stats
echo "<h2>5. Testing getExerciseStats...</h2>";
try {
    $stats = getExerciseStats($userId, 7);
    echo "✓ Stats loaded: " . $stats['total_exercises'] . " exercises<br>";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "<br>";
}

// Test 6: Get all users activity (the new function)
echo "<h2>6. Testing getAllUsersExerciseActivity...</h2>";
try {
    $allUsers = getAllUsersExerciseActivity('2026-01-01', '2026-12-31');
    echo "✓ Got " . count($allUsers) . " users<br>";
    foreach ($allUsers as $user) {
        echo "  - " . $user['username'] . ": " . count($user['exercise_dates']) . " exercise days<br>";
    }
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "<br>";
    echo "Error details: " . $e->getTraceAsString() . "<br>";
}

echo "<h2>All component tests complete!</h2>";
echo "<p><a href='/exercises.php'>Try loading exercises.php</a></p>";
