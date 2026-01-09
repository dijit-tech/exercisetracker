<?php
/**
 * Deployment Extraction Script
 * This file extracts the uploaded goaltracker-app.zip file
 * 
 * IMPORTANT: Delete this file after extraction for security!
 */

error_reporting(0);
ini_set('display_errors', '0');

// Check if ZIP extension is loaded
if (!extension_loaded('zip')) {
    die('ZIP extension not available on server');
}

$zipFile = __DIR__ . '/goaltracker-app.zip';
$extractPath = dirname(__DIR__);

// Verify ZIP file exists
if (!file_exists($zipFile)) {
    die("Error: goaltracker-app.zip not found in /apps/goaltracker/");
}

try {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($extractPath);
        $zip->close();
        
        echo "<h1>✓ Extraction Complete!</h1>";
        echo "<p>Files have been extracted successfully.</p>";
        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>Visit <a href='/'>goaltracker.dijit.tech</a></li>";
        echo "<li>Login with: <strong>admin / password</strong> or <strong>testuser / password</strong></li>";
        echo "<li>Change the password immediately in Admin panel</li>";
        echo "<li><strong>Delete this file (extract.php) for security</strong></li>";
        echo "</ol>";
        echo "<p><a href='/'>Go to Goal Tracker</a></p>";
        
        // Log extraction
        file_put_contents(__DIR__ . '/extraction.log', "Extracted at: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
    } else {
        echo "Error: Could not open ZIP file";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
