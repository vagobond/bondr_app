<?php
// PrivateCircle Installation Script - PHP 8.1 Compatible
// A self-hosted private group communication platform

if ($_POST['action'] == 'install') {
    $dbhost = $_POST['dbhost'];
    $dbname = $_POST['dbname'];
    $dbuser = $_POST['dbuser'];
    $dbpass = $_POST['dbpass'];
    $admin_email = $_POST['admin_email'];
    $admin_pass = $_POST['admin_pass'];
    $site_name = $_POST['site_name'];
    
    // Database connection using mysqli
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    if (!$conn) {
        die('Database connection failed: ' . mysqli_connect_error());
    }
    
    // Create tables
    $sql_statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'moderator', 'member') DEFAULT 'member',
            status ENUM('pending', 'approved', 'banned') DEFAULT 'pending',
            avatar VARCHAR(255),
            bio TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_seen TIMESTAMP NULL,
            gnupg_key TEXT
        )",
        
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT,
            is_auto_generated BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(parent_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT,
            title VARCHAR(255),
            content TEXT NOT NULL,
            is_reply BOOLEAN DEFAULT FALSE,
            parent_id INT,
            tags TEXT,
            file_attachment VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(category_id),
            INDEX(parent_id),
            INDEX(created_at)
        )",
        
        "CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL,
            usage_count INT DEFAULT 0,
            is_category BOOLEAN DEFAULT FALSE,
            category_id INT,
            INDEX(usage_count),
            INDEX(name)
        )",
        
        "CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT,
            tag_id INT,
            PRIMARY KEY(post_id, tag_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS private_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            recipient_id INT NOT NULL,
            encrypted_content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read BOOLEAN DEFAULT FALSE,
            INDEX(sender_id),
            INDEX(recipient_id),
            INDEX(created_at)
        )",
        
        "CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_size INT,
            mime_type VARCHAR(100),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(status)
        )",
        
        "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(created_at)
        )",
        
        "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(64) PRIMARY KEY,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP,
            INDEX(user_id)
        )"
    ];
    
    // Execute each CREATE TABLE statement
    foreach ($sql_statements as $sql) {
        if (!mysqli_query($conn, $sql)) {
            die('Error creating tables: ' . mysqli_error($conn));
        }
    }
    
    // Insert default categories
    $categories_sql = "INSERT INTO categories (name, description) VALUES 
        ('The River', 'All posts that you are approved to see'),
        ('General', 'General discussions'),
        ('Announcements', 'Important announcements')";
    
    if (!mysqli_query($conn, $categories_sql)) {
        die('Error creating categories: ' . mysqli_error($conn));
    }
    
    // Create admin user
    $admin_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
    $admin_email_escaped = mysqli_real_escape_string($conn, $admin_email);
    
    $admin_sql = "INSERT INTO users (username, email, password, role, status) VALUES 
        ('admin', '$admin_email_escaped', '$admin_hash', 'admin', 'approved')";
    
    if (!mysqli_query($conn, $admin_sql)) {
        die('Error creating admin user: ' . mysqli_error($conn));
    }
    
    // Create config file
    $config_content = "<?php
define('DB_HOST', '$dbhost');
define('DB_NAME', '$dbname');
define('DB_USER', '$dbuser');
define('DB_PASS', '$dbpass');
define('SITE_NAME', '$site_name');
define('BASE_URL', 'https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,txt');
?>";
    
    if (!file_put_contents('config.php', $config_content)) {
        die('Error creating config file. Please check file permissions.');
    }
    
    // Create uploads directory
    if (!is_dir('uploads')) {
        if (!mkdir('uploads', 0755, true)) {
            die('Error creating uploads directory. Please check file permissions.');
        }
    }
    
    mysqli_close($conn);
    
    echo "<div style='background: #43b581; color: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <h2>✅ Installation Complete!</h2>
        <p>Your PrivateCircle is now ready to use.</p>
        <p><strong>Admin Login:</strong> $admin_email</p>
        <p><strong>Password:</strong> [Your chosen password]</p>
        <p><a href='index.php' style='color: white; text-decoration: underline;'>Go to your PrivateCircle</a></p>
        <p style='margin-top: 15px; font-size: 14px; opacity: 0.9;'>
            <strong>Important:</strong> For security, please delete the install.php file now.
        </p>
    </div>";
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrivateCircle Installation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #36393f;
            color: #dcddde;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #2f3136;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #7289da;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #b9bbbe;
            font-weight: 500;
        }
        
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            background: #40444b;
            border: 1px solid #4f545c;
            border-radius: 4px;
            color: #dcddde;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        input:focus {
            outline: none;
            border-color: #7289da;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background: #7289da;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #677bc4;
        }
        
        .info {
            background: #7289da;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .warning {
            background: #faa61a;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ PrivateCircle Installation</h1>
        
        <div class="info">
            <strong>Welcome!</strong> This installer will set up your private group communication platform with Discord-style interface, real-time chat, file sharing, and encrypted private messages.
        </div>
        
        <div class="warning">
            <strong>Requirements:</strong> Make sure you have created a MySQL database and have your connection details ready.
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="install">
            
            <div class="form-group">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="site_name" value="My Private Circle" required>
            </div>
            
            <div class="form-group">
                <label for="dbhost">Database Host</label>
                <input type="text" id="dbhost" name="dbhost" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label for="dbname">Database Name</label>
                <input type="text" id="dbname" name="dbname" placeholder="e.g., baldoybp_bondr" required>
            </div>
            
            <div class="form-group">
                <label for="dbuser">Database Username</label>
                <input type="text" id="dbuser" name="dbuser" placeholder="e.g., baldoybp_bondr" required>
            </div>
            
            <div class="form-group">
                <label for="dbpass">Database Password</label>
                <input type="password" id="dbpass" name="dbpass">
            </div>
            
            <div class="form-group">
                <label for="admin_email">Admin Email</label>
                <input type="email" id="admin_email" name="admin_email" required>
            </div>
            
            <div class="form-group">
                <label for="admin_pass">Admin Password</label>
                <input type="password" id="admin_pass" name="admin_pass" required>
            </div>
            
            <button type="submit">🚀 Install PrivateCircle</button>
        </form>
    </div>
</body>
</html>
