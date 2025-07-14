<?php
// Debug Check - Create this as debug.php
echo "<h2>PrivateCircle Debug Check</h2>";

// Check if files exist
$files = ['config.php', 'functions.php', 'index.php', 'login.php', 'admin.php', 'profile.php', 'logout.php'];
echo "<h3>File Check:</h3>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Check config
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "<h3>Config Check:</h3>";
    echo "Site Name: " . SITE_NAME . "<br>";
    echo "Base URL: " . BASE_URL . "<br>";
}

// Check database connection
if (file_exists('functions.php')) {
    require_once 'functions.php';
    echo "<h3>Database Check:</h3>";
    try {
        $conn = db_connect();
        echo "✅ Database connection successful<br>";
        
        // Check if user is logged in
        session_start();
        if (isset($_SESSION['user_id'])) {
            $user = get_user($_SESSION['user_id']);
            echo "✅ User logged in: " . $user['username'] . "<br>";
            echo "User avatar: " . ($user['avatar'] ? $user['avatar'] : 'none') . "<br>";
        } else {
            echo "❌ No user logged in<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
}

// Check index.php content
echo "<h3>Index.php Check:</h3>";
if (file_exists('index.php')) {
    $content = file_get_contents('index.php');
    if (strpos($content, '#dc2626') !== false) {
        echo "✅ Red color scheme found in index.php<br>";
    } else {
        echo "❌ Red color scheme NOT found in index.php<br>";
    }
    
    if (strpos($content, 'user-dropdown') !== false) {
        echo "✅ Profile dropdown code found<br>";
    } else {
        echo "❌ Profile dropdown code NOT found<br>";
    }
}

echo "<h3>Browser Cache:</h3>";
echo "Try hard refresh: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)<br>";
echo "Or open incognito/private window<br>";
?>
