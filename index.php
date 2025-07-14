<?php
// PrivateCircle Main Application - PHP 8.1 Compatible
// index.php - Main entry point
// Testing Git
session_start();

// Check if config file exists
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = get_user($_SESSION['user_id']);
if (!$user || $user['status'] !== 'approved') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Update last seen
update_last_seen($_SESSION['user_id']);

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_posts':
            echo json_encode(get_posts_with_markdown($_GET['category_id'] ?? 1));
            exit;
        case 'get_categories':
            echo json_encode(get_categories());
            exit;
        case 'get_chat':
            echo json_encode(get_chat_messages());
            exit;
        case 'send_chat':
            if (isset($_POST['message']) && $_POST['message']) {
                send_chat_message($_SESSION['user_id'], $_POST['message']);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No message provided']);
            }
            exit;
        case 'create_post':
            error_log('create_post action called');
            if (isset($_POST['content']) && $_POST['content']) {
                error_log('Content received: ' . $_POST['content']);
                error_log('Category: ' . ($_POST['category_id'] ?? 'null'));
                error_log('Tags: ' . ($_POST['tags'] ?? 'null'));
                
                try {
                    $post_id = create_post_with_markdown($_SESSION['user_id'], $_POST['content'], $_POST['category_id'] ?? null, $_POST['tags'] ?? '');
                    error_log('Post created with ID: ' . $post_id);
                    echo json_encode(['success' => true, 'post_id' => $post_id]);
                } catch (Exception $e) {
                    error_log('Error creating post: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                error_log('No content provided');
                echo json_encode(['success' => false, 'error' => 'No content provided']);
            }
            exit;
        case 'preview_markdown':
            if (isset($_POST['content'])) {
                echo json_encode(['html' => markdown_to_html($_POST['content'])]);
            } else {
                echo json_encode(['html' => '']);
            }
            exit;
        case 'search':
            echo json_encode(search_posts($_GET['query'] ?? ''));
            exit;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
}

$categories = get_categories();
$river_posts = get_posts_with_markdown(1); // "The River" category
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <style>
        /* PrivateCircle - Clean Indignified-inspired Theme */
        /* Discord layout with indignified.com colors and typography */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            height: 100vh;
            overflow: hidden;
            line-height: 1.6;
        }

        .app-container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: #f8f8f8;
            border-right: 1px solid #e1e1e1;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid #e1e1e1;
            background: #ffffff;
        }

        .site-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 16px;
        }

        .categories-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        .category-item {
            padding: 8px 16px;
            margin: 1px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            color: #666666;
            font-weight: 500;
        }

        .category-item:hover {
            background: #f0f0f0;
            color: #1a1a1a;
        }

        .category-item.active {
            background: #dc2626;
            color: #ffffff;
        }

        .category-icon {
            margin-right: 8px;
            font-size: 16px;
        }

        .user-area {
            padding: 15px;
            background: #f8f8f8;
            border-top: 1px solid #e1e1e1;
            display: flex;
            align-items: center;
            position: relative;
        }

        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            flex: 1;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .user-profile:hover {
            background: #f0f0f0;
        }

        .user-dropdown {
            position: absolute;
            bottom: 100%;
            left: 15px;
            right: 15px;
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
        }

        .user-dropdown.show {
            display: block;
        }

        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f8f8f8;
        }

        .dropdown-item.logout {
            color: #dc2626;
            font-weight: 600;
        }

        .dropdown-item.logout:hover {
            background: #fef2f2;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: 600;
            color: #ffffff;
            overflow: hidden;
        }

        .user-info {
            flex: 1;
        }

        .username {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
        }

        .user-status {
            font-size: 12px;
            color: #666666;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .content-header {
            background: #ffffff;
            padding: 20px;
            border-bottom: 1px solid #e1e1e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .channel-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 18px;
        }

        .search-box {
            background: #f8f8f8;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            padding: 10px 15px;
            color: #1a1a1a;
            width: 250px;
            font-family: inherit;
        }

        .search-box:focus {
            outline: none;
            border-color: #dc2626;
            background: #ffffff;
        }

        .search-box::placeholder {
            color: #999999;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #ffffff;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            background: #fafafa;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 600;
            color: #ffffff;
            flex-shrink: 0;
            overflow: hidden;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .message-author {
            font-weight: 600;
            color: #1a1a1a;
            margin-right: 15px;
        }

        .message-time {
            font-size: 12px;
            color: #999999;
        }

        .message-text {
            line-height: 1.6;
            color: #333333;
            word-wrap: break-word;
        }

        .message-tags {
            margin-top: 10px;
        }

        .tag {
            background: #dc2626;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 8px;
            display: inline-block;
            font-weight: 500;
        }

        .message-input-container {
            padding: 20px;
            background: #f8f8f8;
            border-top: 1px solid #e1e1e1;
        }

        .input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message-input {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 15px;
            color: #1a1a1a;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }

        .message-input:focus {
            outline: none;
            border-color: #dc2626;
        }

        .message-input::placeholder {
            color: #999999;
        }

        .input-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .post-button {
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            font-family: inherit;
            font-weight: 600;
        }

        .post-button:hover {
            background: #b91c1c;
        }

        .input-hint {
            font-size: 12px;
            color: #999999;
        }

        .chat-panel {
            width: 300px;
            background: #f8f8f8;
            border-left: 1px solid #e1e1e1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px 15px;
            border-bottom: 1px solid #e1e1e1;
            font-weight: 600;
            color: #1a1a1a;
            background: #ffffff;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .chat-message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #f0f0f0;
        }

        .chat-author {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .chat-text {
            font-size: 14px;
            color: #333333;
            line-height: 1.4;
        }

        .chat-input {
            padding: 15px;
            background: #ffffff;
            border-top: 1px solid #e1e1e1;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 10px;
        }

        .chat-input input {
            flex: 1;
            background: #f8f8f8;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            padding: 10px 12px;
            color: #1a1a1a;
            font-size: 14px;
            font-family: inherit;
        }

        .chat-input input:focus {
            outline: none;
            border-color: #dc2626;
            background: #ffffff;
        }

        .chat-input input::placeholder {
            color: #999999;
        }

        .chat-send-button {
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
            font-family: inherit;
            font-weight: 600;
        }

        .chat-send-button:hover {
            background: #b91c1c;
        }

        .online-users {
            padding: 15px;
            border-top: 1px solid #e1e1e1;
        }

        .online-user {
            display: flex;
            align-items: center;
            padding: 6px 0;
        }

        .online-avatar {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #dc2626;
            margin-right: 10px;
        }

        .online-name {
            font-size: 12px;
            color: #333333;
            font-weight: 500;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f8f8f8;
        }

        ::-webkit-scrollbar-thumb {
            background: #e1e1e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #cccccc;
        }
        
        /* Markdown content styles */
        .message-text h1, .message-text h2, .message-text h3 {
            margin: 16px 0 8px 0;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .message-text h1 { font-size: 24px; border-bottom: 2px solid #e1e1e1; padding-bottom: 8px; }
        .message-text h2 { font-size: 20px; }
        .message-text h3 { font-size: 18px; }
        
        .message-text strong { font-weight: 600; color: #1a1a1a; }
        .message-text em { font-style: italic; }
        .message-text del { text-decoration: line-through; opacity: 0.7; }
        
        .message-text code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            font-size: 13px;
            color: #dc2626;
        }
        
        .message-text pre {
            background: #f8f8f8;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            padding: 12px;
            margin: 12px 0;
            overflow-x: auto;
        }
        
        .message-text pre code {
            background: none;
            padding: 0;
            color: #333333;
            font-size: 14px;
        }
        
        .message-text blockquote {
            border-left: 4px solid #dc2626;
            padding-left: 16px;
            margin: 12px 0;
            color: #666666;
            font-style: italic;
        }
        
        .message-text ul, .message-text ol {
            margin: 12px 0;
            padding-left: 24px;
        }
        
        .message-text li {
            margin: 4px 0;
        }
        
        .message-text hr {
            border: none;
            border-top: 2px solid #e1e1e1;
            margin: 20px 0;
        }
        
        .message-text a {
            color: #dc2626;
            text-decoration: none;
            border-bottom: 1px dotted #dc2626;
        }
        
        .message-text a:hover {
            text-decoration: none;
            border-bottom: 1px solid #dc2626;
        }
        
        .message-text img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 8px 0;
        }
        
        /* Markdown input enhancements */
        .markdown-toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #e1e1e1;
            border-radius: 6px 6px 0 0;
            border-bottom: none;
        }
        
        .markdown-btn {
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
            font-family: inherit;
        }
        
        .markdown-btn:hover {
            background: #f0f0f0;
        }
        
        .markdown-input-with-toolbar {
            border-radius: 0 0 8px 8px;
            border-top: none;
        }
        
        .preview-toggle {
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
            font-family: inherit;
            font-weight: 600;
        }
        
        .preview-toggle:hover {
            background: #b91c1c;
        }
        
        .preview-toggle.active {
            background: #059669;
        }
        
        .preview-toggle.active:hover {
            background: #047857;
        }
        
        .preview-container {
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 15px;
            min-height: 80px;
            display: none;
            color: #333333;
        }
        
        .markdown-help {
            font-size: 11px;
            color: #999999;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="site-name">
                    <img src="bondr.png" alt="Bondr App Logo" style="height: 40px; vertical-align: middle;">
                </div>
            </div>
            
            <div class="categories-list">
                <?php foreach ($categories as $category): ?>
                <div class="category-item" data-category="<?php echo $category['id']; ?>">
                    <span class="category-icon">
                        <?php echo $category['name'] === 'The River' ? '🌊' : '#'; ?>
                    </span>
                    <?php echo htmlspecialchars($category['name']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="user-area">
                <div class="user-profile" id="user-profile-btn">
                    <div class="user-avatar">
                        <?php if (isset($user['avatar']) && $user['avatar'] && file_exists(UPLOAD_DIR . $user['avatar'])): ?>
                            <img src="<?php echo UPLOAD_DIR . $user['avatar']; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                        <div class="user-status"><?php echo isset($user['role']) ? ucfirst($user['role']) : 'User'; ?></div>
                    </div>
                </div>
                <div class="user-dropdown" id="user-dropdown">
                    <div class="dropdown-item" onclick="openProfile()">
                        👤 View Profile
                    </div>
                    <div class="dropdown-item" onclick="editProfile()">
                        ✏️ Edit Profile
                    </div>
                    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                    <div class="dropdown-item" onclick="window.location.href='admin.php'">
                        🛡️ Admin Panel
                    </div>
                    <?php endif; ?>
                    <div class="dropdown-item logout" onclick="logout()">
                        🚪 Logout
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <div class="channel-name">
                    <span id="current-channel">🌊 The River</span>
                </div>
                <input type="text" class="search-box" placeholder="Search posts..." id="search-input">
            </div>
            
            <div class="messages-container" id="messages-container">
                <?php if (empty($river_posts)): ?>
                    <div style="text-align: center; color: #666666; padding: 40px;">
                        NO POSTS YET<br><br>BE THE FIRST TO POST SOMETHING
                    </div>
                <?php else: ?>
                    <?php foreach ($river_posts as $post): ?>
                    <div class="message">
                        <div class="message-avatar">
                            <?php if (isset($post['avatar']) && $post['avatar'] && file_exists(UPLOAD_DIR . $post['avatar'])): ?>
                                <img src="<?php echo UPLOAD_DIR . $post['avatar']; ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-author"><?php echo htmlspecialchars($post['username']); ?></span>
                                <span class="message-time"><?php echo date('M d, Y g:i A', strtotime($post['created_at'])); ?></span>
                            </div>
                            <div class="message-text">
                                <?php
                                    if (isset($post['display_content'])) {
                                        echo $post['display_content'];
                                    } elseif (isset($post['content'])) {
                                        echo nl2br(htmlspecialchars($post['content']));
                                    } elseif (isset($post['title'])) {
                                        echo nl2br(htmlspecialchars($post['title']));
                                    } else {
                                        echo 'No content available';
                                    }
                                ?>
                            </div>
                            <?php if (isset($post['tags']) && $post['tags']): ?>
                            <div class="message-tags">
                                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="message-input-container">
                <div class="input-wrapper">
                    <!-- Markdown Toolbar -->
                    <div class="markdown-toolbar">
                        <button type="button" class="markdown-btn" onclick="insertMarkdown('**', '**')" title="Bold">𝐁</button>
                        <button type="button" class="markdown-btn" onclick="insertMarkdown('*', '*')" title="Italic">𝑰</button>
                        <button type="button" class="markdown-btn" onclick="insertMarkdown('`', '`')" title="Code">Code</button>
                        <button type="button" class="markdown-btn" onclick="insertMarkdown('[', '](url)')" title="Link">🔗</button>
                        <button type="button" class="markdown-btn" onclick="insertMarkdown('- ', '')" title="List">•</button>
                        <button type="button" class="markdown-btn" onclick="insertMarkdown('> ', '')" title="Quote">❝</button>
                        <button type="button" class="preview-toggle" id="preview-toggle" onclick="togglePreview()">👁 Preview</button>
                    </div>
                    
                    <textarea class="message-input markdown-input-with-toolbar" id="message-input" placeholder="Type your message using Markdown... 

**Bold text** or *italic text*
`code` or ```code blocks```
[links](https://example.com)
> quotes
- lists
# headers

Use #tags for organization" rows="4"></textarea>
                    
                    <div class="preview-container" id="preview-container">
                        <!-- Preview content will appear here -->
                    </div>
                    
                    <div class="input-actions">
                        <button type="button" id="post-button" class="post-button">📝 Post Message</button>
                        <div>
                            <span class="input-hint">Markdown supported • Press Enter to post, Shift+Enter for new line</span>
                            <div class="markdown-help">
                                **bold** *italic* `code` [link](url) > quote - list # header
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        
        <!-- Chat Panel -->
        <div class="chat-panel">
            <div class="chat-header">💬 Live Chat</div>
            <div class="chat-messages" id="chat-messages">
                <!-- Chat messages will be loaded here -->
            </div>
            <div class="chat-input">
                <div class="chat-input-wrapper">
                    <input type="text" id="chat-input" placeholder="Type a message...">
                    <button type="button" id="chat-send-button" class="chat-send-button">💬 Send</button>
                </div>
            </div>
            <div class="online-users">
                <div class="online-user">
                    <div class="online-avatar"></div>
                    <div class="online-name">Online Users</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentCategory = 1;
        let chatUpdateInterval;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('PrivateCircle loaded, initializing...');
            
            // Set first category as active
            const firstCategory = document.querySelector('.category-item');
            if (firstCategory) {
                firstCategory.classList.add('active');
                currentCategory = parseInt(firstCategory.dataset.category) || 1;
                console.log('Current category set to:', currentCategory);
            }
            
            loadChat();
            startChatPolling();
            
            // Category switching
            document.querySelectorAll('.category-item').forEach(item => {
                item.addEventListener('click', function() {
                    const categoryId = this.dataset.category;
                    console.log('Switching to category:', categoryId);
                    switchCategory(categoryId);
                });
            });
            
            // Message input - both Enter and button click
            document.getElementById('message-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    console.log('Enter pressed, sending message');
                    sendMessage();
                }
            });
            
            document.getElementById('post-button').addEventListener('click', function() {
                console.log('Post button clicked');
                sendMessage();
            });
            
            // Chat input - both Enter and button click
            document.getElementById('chat-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    console.log('Chat enter pressed');
                    sendChatMessage();
                }
            });
            
            document.getElementById('chat-send-button').addEventListener('click', function() {
                console.log('Chat send button clicked');
                sendChatMessage();
            });
            
            // Search
            document.getElementById('search-input').addEventListener('input', function() {
                if (this.value.length > 2) {
                    searchPosts(this.value);
                }
            });
            
            console.log('Initialization complete');
            
            // User profile dropdown
            document.getElementById('user-profile-btn').addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdown = document.getElementById('user-dropdown');
                dropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                document.getElementById('user-dropdown').classList.remove('show');
            });
            
            // Auto-preview on input
            const textarea = document.getElementById('message-input');
            let previewTimeout;
            
            textarea.addEventListener('input', function() {
                if (document.getElementById('preview-toggle').classList.contains('active')) {
                    clearTimeout(previewTimeout);
                    previewTimeout = setTimeout(updatePreview, 300); // Debounce
                }
            });
        });
        
        // Profile functions
        function openProfile() {
            window.location.href = 'profile.php';
        }
        
        function editProfile() {
            window.location.href = 'profile.php?edit=1';
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        function switchCategory(categoryId) {
            currentCategory = categoryId;
            
            // Update UI
            document.querySelectorAll('.category-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-category="${categoryId}"]`).classList.add('active');
            
            // Update channel name
            const categoryName = document.querySelector(`[data-category="${categoryId}"]`).textContent.trim();
            document.getElementById('current-channel').innerHTML = categoryName === 'The River' ? '🌊 The River' : '# ' + categoryName;
            
            // Load posts for category
            loadPosts(categoryId);
        }
        
        function loadPosts(categoryId) {
            console.log('Loading posts for category:', categoryId);
            
            fetch(`?action=get_posts&category_id=${categoryId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(posts => {
                    console.log('Loaded posts:', posts);
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '';
                    
                    if (posts.length === 0) {
                        container.innerHTML = '<div style="text-align: center; color: #666666; padding: 40px;">NO POSTS YET<br><br>BE THE FIRST TO POST SOMETHING</div>';
                        return;
                    }
                    
                    posts.forEach(post => {
                        const messageDiv = createMessageElement(post);
                        container.appendChild(messageDiv);
                    });
                    
                    container.scrollTop = container.scrollHeight;
                })
                .catch(error => {
                    console.error('Error loading posts:', error);
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '<div style="text-align: center; color: #ff0000; padding: 40px;">ERROR LOADING POSTS<br><br>CHECK CONSOLE FOR DETAILS</div>';
                });
        }
        
        function createMessageElement(post) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message';
            
            let avatarHtml = '';
            if (post.avatar) {
                avatarHtml = `<img src="uploads/${post.avatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            } else {
                avatarHtml = post.username.charAt(0).toUpperCase();
            }
            
            const time = new Date(post.created_at).toLocaleString();
            
            let tagsHtml = '';
            if (post.tags) {
                const tags = post.tags.split(',');
                tagsHtml = '<div class="message-tags">' +
                    tags.map(tag => `<span class="tag">${tag.trim()}</span>`).join('') +
                    '</div>';
            }
            
            // Handle different content fields based on your database structure
            let content = '';
            if (post.display_content) {
                content = post.display_content;
            } else if (post.content) {
                content = post.content.replace(/\n/g, '<br>');
            } else if (post.title) {
                content = post.title.replace(/\n/g, '<br>');
            } else {
                content = 'No content available';
            }
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatarHtml}</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-author">${post.username}</span>
                        <span class="message-time">${time}</span>
                    </div>
                    <div class="message-text">${content}</div>
                    ${tagsHtml}
                </div>
            `;
            
            return messageDiv;
        }
        
        function sendMessage() {
            console.log('sendMessage() called');
            const input = document.getElementById('message-input');
            const content = input.value.trim();
            
            console.log('Message content:', content);
            
            if (!content) {
                alert('Please enter a message');
                console.log('No content - aborting');
                return;
            }
            
            // Extract tags from content
            const tags = content.match(/#\w+/g) || [];
            const tagString = tags.join(',').replace(/#/g, '');
            console.log('Tags found:', tagString);
            
            const formData = new FormData();
            formData.append('content', content);
            formData.append('category_id', currentCategory);
            formData.append('tags', tagString);
            
            console.log('Sending POST to: ?action=create_post');
            console.log('Current category:', currentCategory);
            
            fetch('?action=create_post', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON:', data);
                    if (data.success) {
                        console.log('Success! Clearing input and reloading content');
                        input.value = '';
                        
                        // Reload both posts and categories
                        loadPosts(currentCategory);
                        loadCategories();
                        
                        // Show success message if category was created
                        if (text.includes('Created category')) {
                            setTimeout(() => {
                                alert('🎉 New category created from your hashtag! Check the sidebar.');
                            }, 1000);
                        }
                    } else {
                        console.error('Server returned error:', data.error);
                        alert('Error posting message: ' + (data.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Raw text was:', text);
                    alert('Server error: Invalid response format');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error posting message: ' + error.message);
            });
        }
        
        function loadCategories() {
            console.log('Reloading categories...');
            
            fetch('?action=get_categories')
                .then(response => response.json())
                .then(categories => {
                    console.log('Loaded categories:', categories);
                    const container = document.querySelector('.categories-list');
                    container.innerHTML = '';
                    
                    categories.forEach(category => {
                        const categoryDiv = document.createElement('div');
                        categoryDiv.className = 'category-item';
                        categoryDiv.dataset.category = category.id;
                        
                        if (category.id == currentCategory) {
                            categoryDiv.classList.add('active');
                        }
                        
                        const icon = category.name === 'The River' ? '🌊' : '#';
                        categoryDiv.innerHTML = `
                            <span class="category-icon">${icon}</span>
                            ${category.name}
                        `;
                        
                        // Add click event
                        categoryDiv.addEventListener('click', function() {
                            const categoryId = this.dataset.category;
                            console.log('Switching to category:', categoryId);
                            switchCategory(categoryId);
                        });
                        
                        container.appendChild(categoryDiv);
                    });
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                });
        }
        
        function loadChat() {
            fetch('?action=get_chat')
                .then(response => response.json())
                .then(messages => {
                    const container = document.getElementById('chat-messages');
                    container.innerHTML = '';
                    
                    messages.forEach(msg => {
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'chat-message';
                        msgDiv.innerHTML = `
                            <div class="chat-author">${msg.username}</div>
                            <div class="chat-text">${msg.message}</div>
                        `;
                        container.appendChild(msgDiv);
                    });
                    
                    container.scrollTop = container.scrollHeight;
                })
                .catch(error => {
                    console.error('Error loading chat:', error);
                });
        }
        
        function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            const formData = new FormData();
            formData.append('message', message);
            
            fetch('?action=send_chat', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadChat();
                }
            })
            .catch(error => {
                console.error('Error sending chat message:', error);
            });
        }
        
        function startChatPolling() {
            chatUpdateInterval = setInterval(loadChat, 3000);
        }
        
        function searchPosts(query) {
            fetch(`?action=search&query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(posts => {
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '';
                    
                    if (posts.length === 0) {
                        container.innerHTML = '<div style="text-align: center; color: #666666; padding: 40px;">No posts found</div>';
                        return;
                    }
                    
                    posts.forEach(post => {
                        const messageDiv = createMessageElement(post);
                        container.appendChild(messageDiv);
                    });
                })
                .catch(error => {
                    console.error('Error searching posts:', error);
                });
        }
        
        // Markdown helper functions
        function insertMarkdown(before, after) {
            const textarea = document.getElementById('message-input');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            const newText = before + selectedText + after;
            textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
            
            // Set cursor position
            const newCursorPos = start + before.length + selectedText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            textarea.focus();
            
            // Update preview if active
            if (document.getElementById('preview-toggle').classList.contains('active')) {
                updatePreview();
            }
        }
        
        function togglePreview() {
            const toggle = document.getElementById('preview-toggle');
            const textarea = document.getElementById('message-input');
            const preview = document.getElementById('preview-container');
            
            if (toggle.classList.contains('active')) {
                // Switch to edit mode
                toggle.classList.remove('active');
                toggle.textContent = '👁 Preview';
                textarea.style.display = 'block';
                preview.style.display = 'none';
            } else {
                // Switch to preview mode
                toggle.classList.add('active');
                toggle.textContent = '✏️ Edit';
                textarea.style.display = 'none';
                preview.style.display = 'block';
                updatePreview();
            }
        }
        
        function updatePreview() {
            const content = document.getElementById('message-input').value;
            const preview = document.getElementById('preview-container');
            
            if (!content.trim()) {
                preview.innerHTML = '<em style="color: #999;">Nothing to preview...</em>';
                return;
            }
            
            // Send to server for markdown processing
            fetch('?action=preview_markdown', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'content=' + encodeURIComponent(content)
            })
            .then(response => response.json())
            .then(data => {
                preview.innerHTML = data.html;
            })
            .catch(error => {
                console.error('Preview error:', error);
                preview.innerHTML = '<em style="color: #dc2626;">Preview unavailable</em>';
            });
        }
    </script>
    
    </body>
</html>
