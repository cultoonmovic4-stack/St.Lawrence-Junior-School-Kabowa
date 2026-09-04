<?php
/**
 * Check PHP Extensions
 */

header('Content-Type: application/json');

$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mysqli' => extension_loaded('mysqli'),
    'mysql' => extension_loaded('mysql')
];

$loaded_extensions = get_loaded_extensions();

echo json_encode([
    'php_version' => PHP_VERSION,
    'extensions_check' => $extensions,
    'all_loaded_extensions' => $loaded_extensions,
    'pdo_drivers' => PDO::getAvailableDrivers()
], JSON_PRETTY_PRINT);
?>
