<?php
// Tag Debug Page - Create as tag_debug.php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

echo "<h2>Tag System Debug</h2>";

// Show current tags and their counts
$conn = db_connect();
$result = mysqli_query($conn, "SELECT * FROM tags ORDER BY usage_count DESC");

echo "<h3>Current Tags:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Tag Name</th><th>Usage Count</th><th>Is Category?</th><th>Category ID</th></tr>";

while ($tag = mysqli_fetch_assoc($result)) {
    $is_category = $tag['is_category'] ? 'YES' : 'NO';
    echo "<tr>";
    echo "<td>" . htmlspecialchars($tag['name']) . "</td>";
    echo "<td>" . $tag['usage_count'] . "</td>";
    echo "<td>" . $is_category . "</td>";
    echo "<td>" . ($tag['category_id'] ?: 'None') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show post_tags relationships
echo "<h3>Post-Tag Relationships:</h3>";
$result = mysqli_query($conn, "SELECT pt.*, p.content, t.name as tag_name 
                              FROM post_tags pt 
                              JOIN posts p ON pt.post_id = p.id 
                              JOIN tags t ON pt.tag_id = t.id 
                              ORDER BY t.name");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Post ID</th><th>Tag Name</th><th>Post Content (first 50 chars)</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['post_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['tag_name']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['content'], 0, 50)) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// Test form to create posts with tags
if ($_POST['test_post']) {
    $content = $_POST['test_post'];
    echo "<h3>Creating test post: " . htmlspecialchars($content) . "</h3>";
    
    // Extract tags
    preg_match_all('/#(\w+)/', $content, $matches);
    $tags = $matches[1];
    
    echo "<p>Found tags: " . implode(', ', $tags) . "</p>";
    
    $post_id = create_post($_SESSION['user_id'], $content, 1, implode(',', $tags));
    
    echo "<p>Created post with ID: $post_id</p>";
    echo "<p><a href='tag_debug.php'>Refresh to see updated counts</a></p>";
}
?>

<h3>Test Tag Creation:</h3>
<form method="POST">
    <textarea name="test_post" placeholder="Enter a post with #hashtags" rows="3" cols="50"></textarea><br>
    <button type="submit">Create Test Post</button>
</form>

<h3>Quick Test Posts:</h3>
<p>Click these to quickly test a tag:</p>
<form method="POST">
    <input type="hidden" name="test_post" value="This is a test post with #tech">
    <button type="submit">Add #tech post</button>
</form>

<form method="POST">
    <input type="hidden" name="test_post" value="Another post about #tech and #programming">
    <button type="submit">Add #tech + #programming post</button>
</form>

<br><br>
<a href="index.php">← Back to main site</a>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f0f0f0; }
</style>
