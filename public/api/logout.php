<?php
/**
 * Logout API Endpoint
 */

require_once __DIR__ . '/../includes/session.php';

// Destroy session
destroySession();

// Redirect to login page
header('Location: /index.php?success=Successfully logged out');
exit;
