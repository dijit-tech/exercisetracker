<?php
/**
 * User Settings
 * Manage privacy and account preferences.
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

startSession();
requireLogin();

$userId = getCurrentUserId();
$pdo = getDbConnection();

// Fetch current preferences
$stmt = $pdo->prepare("SELECT preferences FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$preferences = $user['preferences'] ? json_decode($user['preferences'], true) : [];

// Default settings
$analyticsEnabled = $preferences['analytics_enabled'] ?? true; 

// Handle success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .settings-card {
            max-width: 800px;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
    </style>
    <!-- Inject User Preferences for Tracker JS -->
    <script>
        window.currentUserPrefs = <?php echo json_encode($preferences); ?>;
    </script>
    <script src="/js/tracker.js" defer></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/dashboard.php">Goal Tracker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/settings.php">Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/api/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="card settings-card">
        <div class="card-header">
            <h3 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Account Settings</h3>
        </div>
        <div class="card-body p-4">
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form id="settingsForm">
                <h5 class="mb-3 border-bottom pb-2">Privacy & Data</h5>
                
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="analyticsEnabled" name="analytics_enabled" 
                            <?php echo $analyticsEnabled ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="analyticsEnabled">Enable Usage Analytics</label>
                        <p class="text-muted small mt-1">
                            Help us improve Goal Tracker by allowing us to collect anonymous usage data. 
                            We track which features you use to make them better. 
                            We do <strong>not</strong> sell your data.
                        </p>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="/dashboard.php" class="btn btn-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const saveBtn = document.getElementById('saveBtn');
    const spinner = saveBtn.querySelector('.spinner-border');
    const analyticsEnabled = document.getElementById('analyticsEnabled').checked;
    
    // UI Loading State
    saveBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    try {
        const response = await fetch('/api/update_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                analytics_enabled: analyticsEnabled
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            // Show success message (or toast)
            // Reload to reflect changes globally
            window.location.href = '/settings.php?success=Settings updated successfully';
        } else {
            alert('Error: ' + (result.error || 'Failed to update settings'));
            saveBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Network error occurred');
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
