<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Starting debug...<br>";

echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current File: " . __FILE__ . "<br>";
echo "Directory: " . __DIR__ . "<br><br>";

echo "Checking paths...<br>";
echo "includes/session.php exists: " . (file_exists(__DIR__ . '/includes/session.php') ? 'YES' : 'NO') . "<br>";
echo "config/database.php exists: " . (file_exists(__DIR__ . '/config/database.php') ? 'YES' : 'NO') . "<br>";

echo "<br>Trying to require session.php...<br>";
try {
    require_once __DIR__ . '/includes/session.php';
    echo "SUCCESS: session.php loaded<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "<br>All files in current directory:<br>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "  - $file<br>";
    }
}
