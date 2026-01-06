<?php
/**
 * Delete Exercise API
 */

ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/exercises.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: /exercises.php?error=Invalid request');
    exit;
}

$userId = getCurrentUserId();
$exerciseId = intval($_POST['exercise_id'] ?? 0);

if ($exerciseId <= 0) {
    ob_end_clean();
    header('Location: /exercises.php?error=Invalid exercise ID');
    exit;
}

$result = deleteExercise($exerciseId, $userId);

ob_end_clean();

if ($result['success']) {
    header('Location: /exercises.php?success=Exercise deleted');
} else {
    header('Location: /exercises.php?error=' . urlencode($result['error']));
}
exit;
