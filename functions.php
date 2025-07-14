<?php
// PrivateCircle Functions - PHP 8.1 Compatible
// functions.php - Core functionality

// Database connection
function db_connect() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            die('Database connection failed: ' . mysqli_connect_error());
        }
    }
    return $conn;
}

// User functions
function get_user($user_id) {
    $conn = db_connect();
    $user_id = (int)$user_id;
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
    return mysqli_fetch_assoc($result);
}

function authenticate_user($email, $password) {
    $conn = db_connect();
    $email = mysqli_real_escape_string($conn, $email);
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND status = 'approved'");
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function create_user($username, $email, $password) {
    $conn = db_connect();
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $result = mysqli_query($conn, "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password_hash')");
    return $result ? mysqli_insert_id($conn) : false;
}

// Category functions
function get_categories() {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
    $categories = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

function create_category_from_tag($tag_name) {
    $conn = db_connect();
    $tag_name = mysqli_real_escape_string($conn, $tag_name);
    
    // Create new category
    $description = "Auto-generated category from #$tag_name tag";
    $result = mysqli_query($conn, "INSERT INTO categories (name, description, is_auto_generated) VALUES ('$tag_name', '$description', 1)");
    
    if (!$result) {
        error_log("Error creating category: " . mysqli_error($conn));
        return false;
    }
    
    $category_id = mysqli_insert_id($conn);
    error_log("Created category '$tag_name' with ID $category_id");
    
    return $category_id;
}

// MARKDOWN FUNCTIONS
/**
 * Convert Markdown to HTML with security measures
 */
function markdown_to_html($text) {
    // Escape HTML first to prevent XSS
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Headers (# ## ###)
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // Bold (**text** or __text__)
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    
    // Italic (*text* or _text_)
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
    
    // Strikethrough (~~text~~)
    $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
    
    // Inline code (`code`)
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    
    // Code blocks (```language\ncode\n```)
    $text = preg_replace_callback('/```(\w*)\n(.*?)\n```/s', function($matches) {
        $language = $matches[1] ? ' data-language="' . $matches[1] . '"' : '';
        return '<pre><code' . $language . '>' . $matches[2] . '</code></pre>';
    }, $text);
    
    // Links [text](url)
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
        $text = $matches[1];
        $url = filter_var($matches[2], FILTER_SANITIZE_URL);
        // Only allow http/https links for security
        if (preg_match('/^https?:\/\//', $url)) {
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
        }
        return $matches[0]; // Return original if invalid URL
    }, $text);
    
    // Images ![alt](url)
    $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function($matches) {
        $alt = $matches[1];
        $url = filter_var($matches[2], FILTER_SANITIZE_URL);
        // Only allow http/https images for security
        if (preg_match('/^https?:\/\//', $url)) {
            return '<img src="' . $url . '" alt="' . $alt . '" style="max-width: 100%; height: auto; border-radius: 4px; margin: 8px 0;">';
        }
        return $matches[0]; // Return original if invalid URL
    }, $text);
    
    // Unordered lists
    $text = preg_replace_callback('/^(\s*[-*+]\s+.+(?:\n\s*[-*+]\s+.+)*)/m', function($matches) {
        $list_items = preg_replace('/^\s*[-*+]\s+(.+)$/m', '<li>$1</li>', $matches[1]);
        return '<ul>' . $list_items . '</ul>';
    }, $text);
    
    // Ordered lists
    $text = preg_replace_callback('/^(\s*\d+\.\s+.+(?:\n\s*\d+\.\s+.+)*)/m', function($matches) {
        $list_items = preg_replace('/^\s*\d+\.\s+(.+)$/m', '<li>$1</li>', $matches[1]);
        return '<ol>' . $list_items . '</ol>';
    }, $text);
    
    // Blockquotes (> text)
    $text = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $text);
    
    // Horizontal rules (--- or ***)
    $text = preg_replace('/^(---|\*\*\*)$/m', '<hr>', $text);
    
    // Line breaks
    $text = nl2br($text);
    
    return $text;
}

/**
 * Strip markdown formatting for plain text preview
 */
function strip_markdown($text) {
    // Remove markdown syntax
    $text = preg_replace('/[#*_`~\[\]()>-]/', '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// Post functions
function get_posts($category_id = null, $limit = 50) {
    $conn = db_connect();
    $limit = (int)$limit;
    
    if ($category_id == 1) { // "The River" - show all posts
        $query = "SELECT p.*, u.username, u.avatar FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  ORDER BY p.created_at ASC LIMIT $limit";
    } else {
        $category_id = (int)$category_id;
        $query = "SELECT p.*, u.username, u.avatar FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.category_id = $category_id 
                  ORDER BY p.created_at ASC LIMIT $limit";
    }
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Database error in get_posts: " . mysqli_error($conn));
        return array();
    }
    
    $posts = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    return $posts;
}

/**
 * Get posts with HTML content for display
 */
function get_posts_with_markdown($category_id = null, $limit = 50) {
    $conn = db_connect();
    $limit = (int)$limit;
    
    if ($category_id == 1) { // "The River" - show all posts
        $query = "SELECT p.*, u.username, u.avatar FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  ORDER BY p.created_at ASC LIMIT $limit";
    } else {
        $category_id = (int)$category_id;
        $query = "SELECT p.*, u.username, u.avatar FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.category_id = $category_id 
                  ORDER BY p.created_at ASC LIMIT $limit";
    }
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Database error in get_posts_with_markdown: " . mysqli_error($conn));
        return array();
    }
    
    $posts = array();
    while ($row = mysqli_fetch_assoc($result)) {
        // Use HTML content if available, otherwise convert markdown on the fly
        if (isset($row['html_content']) && $row['html_content']) {
            $row['display_content'] = $row['html_content'];
        } else {
            $row['display_content'] = markdown_to_html($row['content']);
        }
        $posts[] = $row;
    }
    return $posts;
}

/**
 * Create post with markdown support
 */
function create_post_with_markdown($user_id, $content, $category_id = null, $tags = '') {
    $conn = db_connect();
    $user_id = (int)$user_id;
    $original_content = $content;
    $html_content = markdown_to_html($content);
    $content_escaped = mysqli_real_escape_string($conn, $original_content);
    $html_content_escaped = mysqli_real_escape_string($conn, $html_content);
    $category_id = $category_id ? (int)$category_id : null;
    $tags = mysqli_real_escape_string($conn, $tags);
    
    $category_value = $category_id ? $category_id : 'NULL';
    
    // Insert post with both markdown and HTML versions
  $query = "INSERT INTO posts (user_id, content, html_content, category_id, tags) VALUES ($user_id, '$content_escaped', '$html_content_escaped', $category_value, '$tags')";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Database error in create_post_with_markdown: " . mysqli_error($conn));
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    $post_id = mysqli_insert_id($conn);
    
    // Process tags
    if ($tags) {
        $tag_array = explode(',', $tags);
        foreach ($tag_array as $tag) {
            $tag = trim($tag);
            if ($tag) {
                process_tag($tag, $post_id);
            }
        }
    }
    
    // Cross-post to "The River" if not already there
    if ($category_id != 1) {
        mysqli_query($conn, "INSERT INTO posts (user_id, content, html_content, category_id, tags) VALUES ($user_id, '$content_escaped', '$html_content_escaped', 1, '$tags')");
    }
    
    return $post_id;
}

function create_post($user_id, $content, $category_id = null, $tags = '') {
    // Forward to the markdown version for consistency
    return create_post_with_markdown($user_id, $content, $category_id, $tags);
}

// Tag processing function
function process_tag($tag_name, $post_id) {
    $conn = db_connect();
    $tag_name = mysqli_real_escape_string($conn, $tag_name);
    $post_id = (int)$post_id;
    
    // Check if tags table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'tags'");
    if (mysqli_num_rows($result) == 0) {
        error_log("Tags table does not exist, skipping tag processing");
        return false;
    }
    
    // Check if tag exists
    $result = mysqli_query($conn, "SELECT * FROM tags WHERE name = '$tag_name'");
    $tag = mysqli_fetch_assoc($result);
    
    if (!$tag) {
        // Create new tag
        mysqli_query($conn, "INSERT INTO tags (name) VALUES ('$tag_name')");
        $tag_id = mysqli_insert_id($conn);
    } else {
        $tag_id = $tag['id'];
    }
    
    // Check if post_tags table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'post_tags'");
    if (mysqli_num_rows($result) > 0) {
        // Link post to tag (avoid duplicates)
        mysqli_query($conn, "INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES ($post_id, $tag_id)");
    }
    
    return $tag_id;
}

function search_posts($query) {
    $conn = db_connect();
    $query = mysqli_real_escape_string($conn, $query);
    
    $result = mysqli_query($conn, "SELECT p.*, u.username FROM posts p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE p.content LIKE '%$query%' OR p.tags LIKE '%$query%' 
                          ORDER BY p.created_at DESC LIMIT 20");
    
    $posts = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    return $posts;
}

// Chat functions
function get_chat_messages($limit = 20) {
    $conn = db_connect();
    $limit = (int)$limit;
    
    $result = mysqli_query($conn, "SELECT c.*, u.username FROM chat_messages c 
                          JOIN users u ON c.user_id = u.id 
                          ORDER BY c.created_at DESC LIMIT $limit");
    
    $messages = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    return array_reverse($messages); // Show oldest first
}

function send_chat_message($user_id, $message) {
    $conn = db_connect();
    $user_id = (int)$user_id;
    $message = mysqli_real_escape_string($conn, $message);
    
    mysqli_query($conn, "INSERT INTO chat_messages (user_id, message) VALUES ($user_id, '$message')");
    
    // Keep only last 100 messages to save space
    mysqli_query($conn, "DELETE FROM chat_messages WHERE id NOT IN (SELECT * FROM (SELECT id FROM chat_messages ORDER BY created_at DESC LIMIT 100) AS temp)");
}

// Utility functions
function is_admin($user_id) {
    $user = get_user($user_id);
    return $user && $user['role'] === 'admin';
}

function is_moderator($user_id) {
    $user = get_user($user_id);
    return $user && ($user['role'] === 'admin' || $user['role'] === 'moderator');
}

function update_last_seen($user_id) {
    $conn = db_connect();
    $user_id = (int)$user_id;
    mysqli_query($conn, "UPDATE users SET last_seen = NOW() WHERE id = $user_id");
}

function get_online_users($minutes = 5) {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT username FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL $minutes MINUTE) ORDER BY username");
    
    $users = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    return $users;
}

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function format_file_size($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    $unit_index = 0;
    
    while ($size >= 1024 && $unit_index < count($units) - 1) {
        $size /= 1024;
        $unit_index++;
    }
    
    return round($size, 2) . ' ' . $units[$unit_index];
}

// Generate random password for users
function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>
