<?php
// Simple PHP test file
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
$host = "localhost";
$dbname = "baldoybp_bondr";  // Replace with your actual database name
$username = "baldoybp_bondr"; // Replace with your actual database username
$password = "vazdog-fyccEw-nowvu2"; // Replace with your actual database password

echo "Attempting database connection...<br>";

// Try mysqli (modern PHP)
if (function_exists('mysqli_connect')) {
    echo "mysqli functions available<br>";
    $conn = mysqli_connect($host, $username, $password, $dbname);
    if ($conn) {
        echo "✅ Database connection successful with mysqli!<br>";
        mysqli_close($conn);
    } else {
        echo "❌ Database connection failed with mysqli: " . mysqli_connect_error() . "<br>";
    }
} else {
    echo "mysqli functions NOT available<br>";
}

// Try old mysql (for older PHP)
if (function_exists('mysql_connect')) {
    echo "mysql functions available<br>";
    $conn = mysql_connect($host, $username, $password);
    if ($conn) {
        echo "✅ Database connection successful with mysql!<br>";
        mysql_close($conn);
    } else {
        echo "❌ Database connection failed with mysql: " . mysql_error() . "<br>";
    }
} else {
    echo "mysql functions NOT available<br>";
}

phpinfo();
?>
