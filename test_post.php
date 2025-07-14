<?php
// Message Test - Create this as test_post.php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_POST['test_message']) {
    echo "<h3>Testing Message Post:</h3>";
    
    if (!isset($_SESSION['user_id'])) {
        echo "❌ No user logged in<br>";
        exit;
    }
    
    $user = get_user($_SESSION['user_id']);
    echo "User: " . $user['username'] . "<br>";
    
    $content = $_POST['test_message'];
    echo "Message: " . htmlspecialchars($content) . "<br>";
    
    try {
        $post_id = create_post($_SESSION['user_id'], $content, 1, '');
        if ($post_id) {
            echo "✅ Message posted successfully! Post ID: $post_id<br>";
        } else {
            echo "❌ Failed to create post<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Message Test</title>
</head>
<body>
    <h2>Test Message Posting</h2>
    <form method="POST">
        <textarea name="test_message" placeholder="Type a test message..." required></textarea><br><br>
        <button type="submit">Test Post</button>
    </form>
    
    <br><a href="index.php">← Back to main site</a>
</body>
</html>
