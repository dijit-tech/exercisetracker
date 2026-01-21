<?php
/**
 * Migration: Create default challenges for all users and link orphan goals
 */
require_once __DIR__ . '/../public/includes/db.php';
require_once __DIR__ . '/../public/includes/challenges.php';

echo "Starting migration...\n";

$db = getDbConnection();

// 1. Get all users
$stmt = $db->query("SELECT id, username FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    if ($user['username'] === 'admin') continue; // Optional: skip admin or treat as normal

    // Check if user has a default challenge
    $stmt = $db->prepare("SELECT id FROM challenges WHERE creator_user_id = ? AND is_default = 1");
    $stmt->execute([$user['id']]);
    $defaultId = $stmt->fetchColumn();

    if (!$defaultId) {
        echo "Creating default challenge for {$user['username']}...\n";
        
        // Manual insert to set is_default
        $stmt = $db->prepare("
            INSERT INTO challenges (creator_user_id, name, description, privacy, status, is_default, created_at)
            VALUES (?, ?, ?, 'private', 'active', 1, NOW())
        ");
        $name = $user['username'] . "'s Personal Goals";
        $desc = "Default personal workspace";
        $stmt->execute([$user['id'], $name, $desc]);
        $defaultId = $db->lastInsertId();
        
        // Add member
        addChallengeMember($defaultId, $user['id']);
    } else {
        echo "Default challenge exists for {$user['username']}.\n";
    }

    // 2. Find orphan goals (Goals not in ANY challenge)
    // Actually, prompt says "All goals should be based on a challenge"
    // So we look for goals by this user that are not in challenge_goals
    
    $stmt = $db->prepare("
        SELECT id, goal_title as title FROM goals 
        WHERE user_id = ? 
        AND id NOT IN (SELECT goal_id FROM challenge_goals)
    ");
    $stmt->execute([$user['id']]);
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($orphans) > 0) {
        echo "Found " . count($orphans) . " orphan goals. Linking to default challenge...\n";
        foreach ($orphans as $goal) {
            addGoalToChallenge($defaultId, $goal['id'], $user['id']);
        }
    }
}

echo "Migration complete.\n";
