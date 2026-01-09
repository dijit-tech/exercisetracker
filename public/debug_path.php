<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Current Dir: " . __DIR__ . "<br>";
echo "Parent Dir: " . dirname(__DIR__) . "<br>";
echo "Grandparent Dir: " . dirname(dirname(__DIR__)) . "<br>";

$configPath = __DIR__ . '/../../config/database.php';
echo "Checking path: $configPath<br>";

if (file_exists($configPath)) {
    echo "EXISTS!";
} else {
    echo "MISSING!<br>";
    // debug what we can see
    $grandparent = dirname(dirname(__DIR__));
    if (is_dir($grandparent)) {
        echo "Listing $grandparent:<br>";
        $files = scandir($grandparent);
        print_r($files);
        
        $configDir = $grandparent . '/config';
        if (is_dir($configDir)) {
             echo "<br>Listing config dir:<br>";
             print_r(scandir($configDir));
        } else {
             echo "<br>Config dir not found!";
        }
    }
}
?>
