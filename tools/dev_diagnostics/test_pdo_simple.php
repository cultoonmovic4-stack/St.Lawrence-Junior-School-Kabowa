<?php
// Simple PDO MySQL test
header('Content-Type: text/plain');

echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO Available: " . (extension_loaded('pdo') ? 'YES' : 'NO') . "\n";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
echo "MySQLi Available: " . (extension_loaded('mysqli') ? 'YES' : 'NO') . "\n\n";

echo "Available PDO Drivers:\n";
$drivers = PDO::getAvailableDrivers();
if (empty($drivers)) {
    echo "  NONE - This is the problem!\n\n";
} else {
    foreach ($drivers as $driver) {
        echo "  - $driver\n";
    }
}

echo "\nExtension Dir: " . ini_get('extension_dir') . "\n";

// Check if DLL exists
$ext_dir = ini_get('extension_dir');
if ($ext_dir === 'ext') {
    $ext_dir = 'C:\xampp\php\ext';
}
$dll_path = $ext_dir . '\php_pdo_mysql.dll';
echo "DLL Path: $dll_path\n";
echo "DLL Exists: " . (file_exists($dll_path) ? 'YES' : 'NO') . "\n\n";

// Try to test connection with mysqli instead
if (extension_loaded('mysqli')) {
    echo "Testing with MySQLi...\n";
    $conn = @new mysqli('localhost', 'root', '', 'st_lawrence_school');
    if ($conn->connect_error) {
        echo "MySQLi Connection Failed: " . $conn->connect_error . "\n";
    } else {
        echo "MySQLi Connection: SUCCESS!\n";
        $conn->close();
    }
}
?>
