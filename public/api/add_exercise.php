<?php
/**
 * Add Exercise API
 */

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/exercises.php';

// Require login (session already started by session.php)
requireLogin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: /exercises.php?error=Invalid request method');
    exit;
}

// Get data
$userId = getCurrentUserId();
$exerciseDate = trim($_POST['exercise_date'] ?? '');
$exerciseType = trim($_POST['exercise_type'] ?? '');
$durationMinutes = intval($_POST['duration_minutes'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

// Validate
if (empty($exerciseDate) || empty($exerciseType) || $durationMinutes <= 0) {
    ob_end_clean();
    header('Location: /exercises.php?error=All fields are required');
    exit;
}

// Add exercise
$result = addExercise($userId, $exerciseDate, $exerciseType, $durationMinutes, $notes);

ob_end_clean();

if ($result['success']) {
    header('Location: /exercises.php?success=Exercise added successfully');
} else {
    header('Location: /exercises.php?error=' . urlencode($result['error']));
}
exit;
