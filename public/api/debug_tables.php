<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../includes/db.php";
$pdo = getDbConnection();

echo "<h1>Tables Check</h1>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "<h2>Exercises Table Columns</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM exercises");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . $row["Field"] . " (" . $row["Type"] . ")</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Error checking exercises table: " . $e->getMessage();
}

