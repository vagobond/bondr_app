<?php
// diagnostic.php - Create this file to debug the issues
// Run this at bondr.app/diagnostic.php to see what's wrong

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Bondr.app Diagnostic</h1>";

// Test 1: Config file
echo "<h2>1. Config File Test</h2>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    require_once 'config.php';
    echo "✅ config.php loaded successfully<br>";
    echo "- DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
    echo "- DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
    echo "- SITE_NAME: " . (defined('SITE_NAME') ? SITE_NAME : 'NOT DEFINED') . "<br>";
} else {
    echo "❌ config.php does not exist<br>";
}

// Test 2: Functions file
echo "<h2>2. Functions File Test</h2>";
if (file_exists('functions.php')) {
    echo "✅ functions.php exists<br>";
    require_once 'functions.php';
    echo "✅ functions.php loaded successfully<br>";
    
    // Test if key functions exist
    $functions = ['db_connect', 'get_user', 'get_categories', 'get_posts_with_markdown'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✅ Function $func exists<br>";
        } else {
            echo "❌ Function $func missing<br>";
        }
    }
} else {
    echo "❌ functions.php does not exist<br>";
}

// Test 3: Database connection
echo "<h2>3. Database Connection Test</h2>";
if (function_exists('db_connect')) {
    try {
        $conn = db_connect();
        echo "✅ Database connection successful<br>";
        
        // Test if tables exist
        $tables = ['users', 'categories', 'posts', 'chat_messages'];
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (mysqli_num_rows($result) > 0) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        }
        
        // Check posts table structure
        echo "<h3>Posts Table Structure:</h3>";
        $result = mysqli_query($conn, "DESCRIBE posts");
        if ($result) {
            echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db_connect function not available<br>";
}

// Test 4: Session test
echo "<h2>4. Session Test</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in (ID: {$_SESSION['user_id']})<br>";
    
    if (function_exists('get_user')) {
        $user = get_user($_SESSION['user_id']);
        if ($user) {
            echo "✅ User data found<br>";
            echo "- Username: " . htmlspecialchars($user['username']) . "<br>";
            echo "- Status: " . htmlspecialchars($user['status']) . "<br>";
            echo "- Role: " . (isset($user['role']) ? htmlspecialchars($user['role']) : 'Not set') . "<br>";
        } else {
            echo "❌ User data not found in database<br>";
        }
    }
} else {
    echo "❌ User not logged in<br>";
    echo "This is why you're getting redirected to login.php<br>";
}

// Test 5: Categories test
echo "<h2>5. Categories Test</h2>";
if (function_exists('get_categories')) {
    try {
        $categories = get_categories();
        echo "✅ Categories loaded successfully<br>";
        echo "- Found " . count($categories) . " categories<br>";
        foreach ($categories as $cat) {
            echo "  - ID: {$cat['id']}, Name: " . htmlspecialchars($cat['name']) . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error loading categories: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ get_categories function not available<br>";
}

// Test 6: Posts test
echo "<h2>6. Posts Test</h2>";
if (function_exists('get_posts_with_markdown')) {
    try {
        $posts = get_posts_with_markdown(1);
        echo "✅ Posts loaded successfully<br>";
        echo "- Found " . count($posts) . " posts<br>";
        if (count($posts) > 0) {
            $post = $posts[0];
            echo "- Sample post keys: " . implode(', ', array_keys($post)) . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error loading posts: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ get_posts_with_markdown function not available<br>";
}

// Test 7: PHP Error log
echo "<h2>7. Recent PHP Errors</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Error log location: $error_log<br>";
    $errors = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $errors), -20);
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_errors)) . "</pre>";
} else {
    echo "Error log not found or not configured<br>";
}

echo "<h2>Diagnosis Complete</h2>";
echo "If everything shows ✅ but you still get a blank page, check your web server error logs.";
?>
