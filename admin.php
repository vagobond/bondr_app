<?php
// PrivateCircle Admin Panel - PHP 8.1 Compatible
// admin.php

session_start();

// Check if config file exists
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle admin actions
if (isset($_POST['action']) && $_POST['action']) {
    $conn = db_connect();
    
    switch ($_POST['action']) {
        case 'approve_user':
            $user_id = (int)$_POST['user_id'];
            mysqli_query($conn, "UPDATE users SET status = 'approved' WHERE id = $user_id");
            $message = 'User approved successfully!';
            break;
            
        case 'reject_user':
            $user_id = (int)$_POST['user_id'];
            mysqli_query($conn, "UPDATE users SET status = 'banned' WHERE id = $user_id");
            $message = 'User rejected/banned successfully!';
            break;
            
        case 'make_moderator':
            $user_id = (int)$_POST['user_id'];
            mysqli_query($conn, "UPDATE users SET role = 'moderator' WHERE id = $user_id");
            $message = 'User promoted to moderator!';
            break;
            
        case 'approve_file':
            approve_file((int)$_POST['file_id']);
            $message = 'File approved successfully!';
            break;
            
        case 'reject_file':
            reject_file((int)$_POST['file_id']);
            $message = 'File rejected and deleted!';
            break;
    }
}

// Get pending users
$conn = db_connect();
$pending_users_result = mysqli_query($conn, "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$pending_users = array();
while ($row = mysqli_fetch_assoc($pending_users_result)) {
    $pending_users[] = $row;
}

// Get all users
$all_users_result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$all_users = array();
while ($row = mysqli_fetch_assoc($all_users_result)) {
    $all_users[] = $row;
}

// Get pending files
$pending_files = get_pending_files();

// Get statistics
$stats_result = mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM users WHERE status = 'approved') as total_users,
    (SELECT COUNT(*) FROM users WHERE status = 'pending') as pending_users,
    (SELECT COUNT(*) FROM posts) as total_posts,
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM files WHERE status = 'pending') as pending_files");
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #36393f;
            color: #dcddde;
            line-height: 1.6;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: #2f3136;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            font-size: 24px;
            color: #ffffff;
        }
        
        .back-link {
            background: #7289da;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .back-link:hover {
            background: #677bc4;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #2f3136;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 600;
            color: #7289da;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #b9bbbe;
            font-size: 14px;
        }
        
        .admin-section {
            background: #2f3136;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .section-header {
            background: #7289da;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .user-list, .file-list {
            display: grid;
            gap: 15px;
        }
        
        .user-item, .file-item {
            background: #40444b;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .user-info, .file-info {
            flex: 1;
        }
        
        .user-name, .file-name {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 5px;
        }
        
        .user-email, .file-details {
            color: #b9bbbe;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .user-meta, .file-meta {
            font-size: 12px;
            color: #72767d;
        }
        
        .user-actions, .file-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-approve {
            background: #43b581;
            color: white;
        }
        
        .btn-approve:hover {
            background: #3ca374;
        }
        
        .btn-reject {
            background: #f04747;
            color: white;
        }
        
        .btn-reject:hover {
            background: #d73d3d;
        }
        
        .btn-moderate {
            background: #faa61a;
            color: white;
        }
        
        .btn-moderate:hover {
            background: #e8940f;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #faa61a;
            color: white;
        }
        
        .status-approved {
            background: #43b581;
            color: white;
        }
        
        .status-banned {
            background: #f04747;
            color: white;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .role-admin {
            background: #7289da;
            color: white;
        }
        
        .role-moderator {
            background: #faa61a;
            color: white;
        }
        
        .role-member {
            background: #72767d;
            color: white;
        }
        
        .message {
            background: #43b581;
            color: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            color: #72767d;
            padding: 40px;
            font-style: italic;
        }
        
        .tabs {
            display: flex;
            background: #40444b;
            border-radius: 6px 6px 0 0;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            background: transparent;
            border: none;
            color: #b9bbbe;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab.active {
            background: #7289da;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">🛡️ Admin Panel</h1>
            <a href="index.php" class="back-link">← Back to <?php echo SITE_NAME; ?></a>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_users']; ?></div>
                <div class="stat-label">Pending Approvals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_posts']; ?></div>
                <div class="stat-label">Total Posts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_files']; ?></div>
                <div class="stat-label">Files Pending</div>
            </div>
        </div>
        
        <!-- Pending User Approvals -->
        <?php if (!empty($pending_users)): ?>
        <div class="admin-section">
            <div class="section-header">⏳ Pending User Approvals</div>
            <div class="section-content">
                <div class="user-list">
                    <?php foreach ($pending_users as $user): ?>
                    <div class="user-item">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            <div class="user-meta">
                                Registered: <?php echo date('M d, Y g:i A', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                        <div class="user-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-approve">✅ Approve</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="reject_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-reject">❌ Reject</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Pending File Approvals -->
        <?php if (!empty($pending_files)): ?>
        <div class="admin-section">
            <div class="section-header">📁 Pending File Approvals</div>
            <div class="section-content">
                <div class="file-list">
                    <?php foreach ($pending_files as $file): ?>
                    <div class="file-item">
                        <div class="file-info">
                            <div class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></div>
                            <div class="file-details">
                                Uploaded by: <?php echo htmlspecialchars($file['username']); ?> |
                                Size: <?php echo format_file_size($file['file_size']); ?> |
                                Type: <?php echo htmlspecialchars($file['mime_type']); ?>
                            </div>
                            <div class="file-meta">
                                Uploaded: <?php echo date('M d, Y g:i A', strtotime($file['created_at'])); ?>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="<?php echo UPLOAD_DIR . $file['stored_name']; ?>" target="_blank" class="btn" style="background: #7289da; color: white;">👁️ View</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve_file">
                                <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                <button type="submit" class="btn btn-approve">✅ Approve</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="reject_file">
                                <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                <button type="submit" class="btn btn-reject">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- All Users Management -->
        <div class="admin-section">
            <div class="section-header">👥 User Management</div>
            <div class="section-content">
                <?php if (empty($all_users)): ?>
                    <div class="empty-state">No users found.</div>
                <?php else: ?>
                    <div class="user-list">
                        <?php foreach ($all_users as $user): ?>
                        <div class="user-item">
                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo $user['status']; ?>
                                    </span>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </div>
                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                <div class="user-meta">
                                    Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    <?php if ($user['last_seen']): ?>
                                        | Last seen: <?php echo date('M d, Y g:i A', strtotime($user['last_seen'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="user-actions">
                                <?php if ($user['status'] === 'approved' && $user['role'] === 'member'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="make_moderator">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-moderate">⭐ Make Moderator</button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($user['status'] === 'approved'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reject_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-reject">🚫 Ban</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Confirmation for destructive actions
        document.querySelectorAll('.btn-reject').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to perform this action? This cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-refresh for real-time updates
        setTimeout(function() {
            location.reload();
        }, 60000); // Refresh every minute
    </script>
</body>
</html>
