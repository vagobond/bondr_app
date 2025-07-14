<?php
// PrivateCircle Profile Page
// profile.php

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

$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';
$message = '';
$error = '';

// Handle profile update
if ($_POST['action'] == 'update_profile') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $bio = sanitize_input($_POST['bio']);
    
    // Handle avatar upload
    $avatar_path = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowed_types) && $file['size'] <= 2097152) { // 2MB limit
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $avatar_filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
            $upload_path = UPLOAD_DIR . $avatar_filename;
            
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old avatar if it exists
                if ($user['avatar'] && file_exists(UPLOAD_DIR . $user['avatar'])) {
                    unlink(UPLOAD_DIR . $user['avatar']);
                }
                $avatar_path = $avatar_filename;
            } else {
                $error = 'Failed to upload avatar image.';
            }
        } else {
            $error = 'Invalid image file. Please use JPG, PNG, or GIF under 2MB.';
        }
    }
    
    if (!$error) {
        $conn = db_connect();
        $username = mysqli_real_escape_string($conn, $username);
        $email = mysqli_real_escape_string($conn, $email);
        $bio = mysqli_real_escape_string($conn, $bio);
        $avatar_path = mysqli_real_escape_string($conn, $avatar_path);
        
        $sql = "UPDATE users SET username = '$username', email = '$email', bio = '$bio', avatar = '$avatar_path' WHERE id = " . $user['id'];
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Profile updated successfully!';
            $user = get_user($_SESSION['user_id']); // Refresh user data
            $edit_mode = false; // Exit edit mode
        } else {
            $error = 'Error updating profile: ' . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
            min-height: 100vh;
        }

        .header {
            background: #f8f8f8;
            padding: 20px;
            border-bottom: 1px solid #e1e1e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .back-btn {
            background: #dc2626;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: #b91c1c;
        }

        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .profile-header {
            background: #f8f8f8;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #e1e1e1;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 600;
            color: #ffffff;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .edit-avatar {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .profile-name {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .profile-role {
            background: #dc2626;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .profile-body {
            padding: 30px;
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .profile-info {
            display: grid;
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #666666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #1a1a1a;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            font-size: 16px;
            font-family: inherit;
            background: #ffffff;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #dc2626;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            font-size: 16px;
            font-family: inherit;
            background: #ffffff;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.2s;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #dc2626;
        }

        .file-input {
            display: none;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            margin-right: 10px;
        }

        .btn-primary {
            background: #dc2626;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #f8f8f8;
            color: #1a1a1a;
            border: 1px solid #e1e1e1;
        }

        .btn-secondary:hover {
            background: #f0f0f0;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }

        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $edit_mode ? 'Edit Profile' : 'Profile'; ?></h1>
        <a href="index.php" class="back-btn">← Back to <?php echo SITE_NAME; ?></a>
    </div>

    <div class="profile-container">
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar-container">
                    <div class="avatar">
                        <?php if ($user['avatar'] && file_exists(UPLOAD_DIR . $user['avatar'])): ?>
                            <img src="<?php echo UPLOAD_DIR . $user['avatar']; ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($edit_mode): ?>
                        <button class="edit-avatar" onclick="document.getElementById('avatar-input').click()">
                            📷
                        </button>
                    <?php endif; ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['username']); ?></div>
                <span class="profile-role"><?php echo ucfirst($user['role']); ?></span>
            </div>

            <div class="profile-body">
                <?php if ($edit_mode): ?>
                    <!-- Edit Form -->
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="file" id="avatar-input" name="avatar" class="file-input" accept="image/*">
                        
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-textarea" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                            <a href="profile.php" class="btn btn-secondary">❌ Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- View Profile -->
                    <div class="profile-section">
                        <h3 class="section-title">Profile Information</h3>
                        <div class="profile-info">
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            
                            <?php if ($user['bio']): ?>
                            <div class="info-item">
                                <span class="info-label">Bio</span>
                                <span class="info-value"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <span class="info-label">Member Since</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                            </div>
                            
                            <?php if ($user['last_seen']): ?>
                            <div class="info-item">
                                <span class="info-label">Last Seen</span>
                                <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($user['last_seen'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3 class="section-title">Activity Stats</h3>
                        <div class="stats-grid">
                            <?php
                            $conn = db_connect();
                            $user_id = $user['id'];
                            
                            // Get post count
                            $posts_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM posts WHERE user_id = $user_id");
                            $posts_count = mysqli_fetch_assoc($posts_result)['count'];
                            
                            // Get chat message count
                            $chat_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM chat_messages WHERE user_id = $user_id");
                            $chat_count = mysqli_fetch_assoc($chat_result)['count'];
                            ?>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $posts_count; ?></div>
                                <div class="stat-label">Posts</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $chat_count; ?></div>
                                <div class="stat-label">Chat Messages</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="profile.php?edit=1" class="btn btn-primary">✏️ Edit Profile</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Preview avatar before upload
        document.getElementById('avatar-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.querySelector('.avatar');
                    avatar.innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
