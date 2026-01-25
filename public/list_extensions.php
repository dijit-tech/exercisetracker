<?php
header('Content-Type: text/plain');
echo "Loaded Extensions (" . count(get_loaded_extensions()) . " total):\n";
echo "========================================\n";
$extensions = get_loaded_extensions();
natcasesort($extensions);
foreach ($extensions as $ext) {
    echo $ext . "\n";
}
?>