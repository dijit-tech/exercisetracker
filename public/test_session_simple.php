<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Simple Session Test</h1>";

// Manually start session
session_name('exercise_tracker_session');
session_start();

echo "<h2>Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . " (1=disabled, 2=active)<br>";

echo "<h2>Session Data</h2>";
if (empty($_SESSION)) {
    echo "⚠️ Session is EMPTY<br>";
    echo "This means you're not logged in or session was destroyed<br>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    if (isset($_SESSION['user_id'])) {
        echo "✓ user_id found: " . $_SESSION['user_id'] . "<br>";
    }
    if (isset($_SESSION['username'])) {
        echo "✓ username found: " . $_SESSION['username'] . "<br>";
    }
    if (isset($_SESSION['last_activity'])) {
        $lastActive = $_SESSION['last_activity'];
        $timeAgo = time() - $lastActive;
        echo "✓ last_activity: " . date('Y-m-d H:i:s', $lastActive) . " ($timeAgo seconds ago)<br>";
        
        $timeout = 7200; // 2 hours
        if ($timeAgo > $timeout) {
            echo "⚠️ Session TIMED OUT (timeout: $timeout seconds)<br>";
        } else {
            echo "✓ Session still valid<br>";
        }
    }
}

echo "<h2>Cookie Information</h2>";
echo "Session cookie name: " . session_name() . "<br>";
if (isset($_COOKIE[session_name()])) {
    echo "✓ Session cookie exists<br>";
    echo "Cookie value: " . substr($_COOKIE[session_name()], 0, 20) . "...<br>";
} else {
    echo "⚠️ No session cookie found<br>";
}

echo "<h2>Actions</h2>";
echo "<a href='/index.php'>Go to Login</a> | ";
echo "<a href='/dashboard.php'>Go to Dashboard</a>";
