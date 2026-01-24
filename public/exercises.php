<?php
/**
 * Exercises Page - View and manage exercises
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/exercises.php';

// Require login
requireLogin();

$username = getCurrentUsername();
$isAdmin = isAdmin();
$userId = getCurrentUserId();

// Get user exercises
$exercises = getUserExercises($userId);
$exerciseTypes = getExerciseTypes();

// Get messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exercises - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">🎯 Goal Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/exercises.php">My Exercises</a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin.php">Admin</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?= htmlspecialchars($username) ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/api/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="page-header">
            <h2>My Exercises 💪</h2>
            <p class="mb-0">Track and manage your workout history</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Exercise Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New Exercise</h5>
            </div>
            <div class="card-body">
                <form action="/api/add_exercise.php" method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label for="exercise_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="exercise_date" name="exercise_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="exercise_type" class="form-label">Exercise Type</label>
                        <select class="form-select" id="exercise_type" name="exercise_type" required>
                            <option value="">Select type...</option>
                            <?php foreach ($exerciseTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="duration_minutes" class="form-label">Duration (min)</label>
                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" 
                               min="1" max="999" required>
                    </div>
                    <div class="col-md-3">
                        <label for="notes" class="form-label">Notes (optional)</label>
                        <input type="text" class="form-control" id="notes" name="notes" 
                               placeholder="e.g., Morning run">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Exercises List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Exercise History</h5>
                <span class="badge bg-primary"><?= count($exercises) ?> total</span>
            </div>
            <div class="card-body">
                <?php if (empty($exercises)): ?>
                    <p class="text-muted text-center py-5">No exercises yet. Add your first workout above!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Exercise</th>
                                    <th>Duration</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exercises as $exercise): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($exercise['exercise_date'])) ?></td>
                                        <td><strong><?= htmlspecialchars($exercise['exercise_type']) ?></strong></td>
                                        <td><?= $exercise['duration_minutes'] ?> min</td>
                                        <td><?= htmlspecialchars($exercise['notes'] ?: '-') ?></td>
                                        <td>
                                            <form method="POST" action="/api/delete_exercise.php" style="display:inline;" 
                                                  onsubmit="return confirm('Delete this exercise?');">
                                                <input type="hidden" name="exercise_id" value="<?= $exercise['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
