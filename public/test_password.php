<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'password123';

echo "Testing password verification\n";
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\n";

if (password_verify($password, $hash)) {
    echo "✓ PASSWORD MATCHES!\n";
} else {
    echo "✗ PASSWORD DOES NOT MATCH\n";
    echo "\n";
    echo "Creating new hash...\n";
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    echo "New hash: $newHash\n";
    echo "Verifying new hash: " . (password_verify($password, $newHash) ? "✓ MATCH" : "✗ NO MATCH") . "\n";
}
