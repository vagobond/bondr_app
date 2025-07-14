<?php
// PrivateCircle Login System - PHP 8.1 Compatible
// login.php

session_start();

// Check if config file exists
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';
require_once 'functions.php';

$error = '';
$success = '';

// Handle login
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    $user = authenticate_user($email, $password);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        update_last_seen($user['id']);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password, or account not approved yet.';
    }
}

// Handle registration
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $user_id = create_user($username, $email, $password);
        if ($user_id) {
            $success = 'Account created successfully! Please wait for admin approval.';
        } else {
            $error = 'Username or email already exists.';
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $user = get_user($_SESSION['user_id']);
    if ($user && $user['status'] === 'approved') {
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: #2f3136;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: #7289da;
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .tab-buttons {
            display: flex;
            margin-bottom: 20px;
            background: #40444b;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .tab-button {
            flex: 1;
            padding: 12px;
            background: transparent;
            border: none;
            color: #b9bbbe;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .tab-button.active {
            background: #7289da;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #b9bbbe;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            background: #40444b;
            border: 1px solid #4f545c;
            border-radius: 4px;
            color: #dcddde;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #7289da;
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: #7289da;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
            font-weight: 600;
        }
        
        button[type="submit"]:hover {
            background: #677bc4;
        }
        
        .error {
            background: #f04747;
            color: white;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #43b581;
            color: white;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
        
        .forgot-password a {
            color: #7289da;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .registration-note {
            background: #faa61a;
            color: white;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo SITE_NAME; ?></h1>
            <p>Your private community space</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('login')">Login</button>
                <button class="tab-button" onclick="showTab('register')">Register</button>
            </div>
            
            <!-- Login Tab -->
            <div id="login-tab" class="tab-content active">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    
                    <button type="submit">ЁЯЪА Login</button>
                </form>
                
                <div class="forgot-password">
                    <a href="mailto:<?php echo htmlspecialchars($_SERVER['SERVER_ADMIN'] ?? 'admin@' . $_SERVER['HTTP_HOST']); ?>">
                        Forgot your password?
                    </a>
                </div>
            </div>
            
            <!-- Register Tab -->
            <div id="register-tab" class="tab-content">
                <div class="registration-note">
                    тЪая╕П New accounts require admin approval before you can participate.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="register-username">Username</label>
                        <input type="text" id="register-username" name="username" required minlength="3" maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Email Address</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-confirm">Confirm Password</label>
                        <input type="password" id="register-confirm" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit">ЁЯУЭ Create Account</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Password confirmation validation
        document.getElementById('register-confirm').addEventListener('input', function() {
            const password = document.getElementById('register-password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.style.borderColor = '#f04747';
            } else {
                this.style.borderColor = '#43b581';
            }
        });
        
        // Username validation
        document.getElementById('register-username').addEventListener('input', function() {
            const username = this.value;
            const isValid = /^[a-zA-Z0-9_-]{3,50}$/.test(username);
            
            if (!isValid && username.length > 0) {
                this.style.borderColor = '#f04747';
            } else {
                this.style.borderColor = '#4f545c';
            }
        });
    </script>
</body>
</html>
