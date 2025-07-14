<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Error reporting enabled<br>";
echo "Now testing the installer...<br><br>";

// Include the installer with error reporting
include 'install.php';
?>
