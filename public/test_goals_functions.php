<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing goals.php include...\n\n";

require_once __DIR__ . '/includes/db.php';
echo "✓ DB included\n";

require_once __DIR__ . '/includes/goals.php';
echo "✓ Goals included\n";

// Test getGoalCategories
$categories = getGoalCategories();
echo "\nCategories (" . count($categories) . "):\n";
print_r($categories);

// Test getUserGoals for admin (user_id = 1)
echo "\n\nTesting getUserGoals(1)...\n";
try {
    $goals = getUserGoals(1);
    echo "✓ Found " . count($goals) . " goals for user 1\n";
    if (!empty($goals)) {
        echo "First goal:\n";
        print_r($goals[0]);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test getGoalStats
echo "\n\nTesting getGoalStats(1)...\n";
try {
    $stats = getGoalStats(1);
    echo "✓ Stats:\n";
    print_r($stats);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
