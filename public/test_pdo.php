<?php
header('Content-Type: text/plain');
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers());
?>