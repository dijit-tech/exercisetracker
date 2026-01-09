<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Checking includes:<br>";
$files = [
    'includes/session.php',
    'includes/db.php',
    'includes/auth.php',
    '/includes/session.php',
    __DIR__ . '/includes/session.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        echo "FOUND: $f<br>";
    } else {
        echo "MISSING: $f<br>";
    }
}

echo "Trying to include session.php...<br>";
require_once __DIR__ . '/includes/session.php';
echo "Include successful!";
?>
