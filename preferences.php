<?php
// preferences.php
require_once 'db_connect.php'; // Connects to DB, starts session, gets theme

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_name = $_SESSION['user_name'] ?? ''; // Get current name from session
$current_theme = $_SESSION['user_theme'] ?? 'light'; // Get current theme

$name_errors = [];
$name_success = '';
$pass_errors = [];
$pass_success = '';
$theme_errors = [];
$theme_success = '';

// --- Process Name Change ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_name'])) {
    $new_name = trim($_POST['name'] ?? '');

    if (empty($new_name)) {
        $name_errors[] = "Name cannot be empty.";
    } elseif (strlen($new_name) > 50) {
        $name_errors[] = "Name is too long (max 50 characters).";
    }

    if (empty($name_errors)) {
        try {
            $sql = "UPDATE users SET name = :name WHERE userID = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $new_name, PDO::PARAM_STR);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['user_name'] = $new_name; // Update session immediately
                $name_success = "Name updated successfully!";
                $current_name = $new_name; // Update displayed name
            } else {
                $name_errors[] = "Failed to update name. Please try again.";
            }
        } catch (PDOException $e) {
            $name_errors[] = "Database error updating name.";
            error_log("Name Update Error: " . $e->getMessage());
        }
    }
}

// --- Process Password Change ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $pass_errors[] = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $pass_errors[] = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) { // Example minimum length
        $pass_errors[] = "New password must be at least 6 characters long.";
    }

    if (empty($pass_errors)) {
        try {
            // 1. Fetch current hashed password
            $sql_fetch = "SELECT password FROM users WHERE userID = :id";
            $stmt_fetch = $pdo->prepare($sql_fetch);
            $stmt_fetch->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt_fetch->execute();
            $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current_password, $user['password'])) {
                // 2. Current password is correct, hash the new one
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT); // Or PASSWORD_DEFAULT

                // 3. Update the password in the database
                $sql_update = "UPDATE users SET password = :new_password WHERE userID = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':new_password', $new_hashed_password, PDO::PARAM_STR);
                $stmt_update->bindParam(':id', $user_id, PDO::PARAM_INT);

                if ($stmt_update->execute()) {
                    $pass_success = "Password updated successfully!";
                    // Optional: Force re-login after password change for security
                    // session_destroy(); header("Location: login.php?password_changed=1"); exit();
                } else {
                    $pass_errors[] = "Failed to update password. Please try again.";
                }
            } else {
                $pass_errors[] = "Incorrect current password.";
            }
        } catch (PDOException $e) {
            $pass_errors[] = "Database error changing password.";
            error_log("Password Update Error: " . $e->getMessage());
        }
    }
}

// --- Process Theme Change ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_theme'])) {
    $new_theme = $_POST['theme_preference'] ?? 'light'; // Default to light

    if ($new_theme !== 'light' && $new_theme !== 'dark') {
        $theme_errors[] = "Invalid theme selection.";
    }

    if (empty($theme_errors)) {
         try {
            $sql = "UPDATE users SET theme_preference = :theme WHERE userID = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':theme', $new_theme, PDO::PARAM_STR);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['user_theme'] = $new_theme; // Update session immediately
                $theme_success = "Theme updated successfully!";
                $current_theme = $new_theme; // Update displayed selection
            } else {
                $theme_errors[] = "Failed to update theme. Please try again.";
            }
        } catch (PDOException $e) {
            $theme_errors[] = "Database error updating theme.";
            error_log("Theme Update Error: " . $e->getMessage());
        }
    }
}

// Get theme class for the body tag
$theme_class = ($current_theme === 'dark') ? 'dark-theme' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Preferences</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-color: #ecf0f1;
            --text-muted: #bdc3c7;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --card-bg: #2c3e50;
            --border-color: #34495e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--primary-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .top-menu {
            background-color: var(--secondary-color);
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .menu-items {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .menu-left, .menu-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-item {
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .menu-item:hover {
            background-color: var(--accent-color);
        }

        .menu-item.active {
            background-color: var(--accent-color);
        }

        .container {
            max-width: 800px;
            margin: 80px auto 20px;
            padding: 0 20px;
        }

        .card {
            background-color: var(--secondary-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
            font-size: 16px;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .message.success {
            background-color: var(--success-color);
            color: white;
        }

        .message.error {
            background-color: var(--danger-color);
            color: white;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        .theme-options {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .theme-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .theme-option input[type="radio"] {
            margin: 0;
        }

        .theme-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: 2px solid var(--border-color);
        }

        .theme-preview.light {
            background-color: #f5f5f5;
        }

        .theme-preview.dark {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
    <nav class="top-menu">
        <div class="menu-items">
            <div class="menu-left">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="orders/index.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="articles_list.php" class="menu-item">
                    <i class="fas fa-box"></i> Articles
                </a>
            </div>
            <div class="menu-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_name, 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($current_name); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>User Preferences</h1>

        <!-- Change Name Section -->
        <div class="card">
            <h2 class="card-title">Change Display Name</h2>
            <?php if (!empty($name_errors)): ?>
                <div class="message error">
                    <?php foreach ($name_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($name_success): ?>
                <div class="message success">
                    <p><?php echo htmlspecialchars($name_success); ?></p>
                </div>
            <?php endif; ?>
            <form action="preferences.php" method="post">
                <div class="form-group">
                    <label for="name">New Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_name); ?>" required>
                </div>
                <button type="submit" name="update_name" class="btn">
                    <i class="fas fa-save"></i> Update Name
                </button>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="card">
            <h2 class="card-title">Change Password</h2>
            <?php if (!empty($pass_errors)): ?>
                <div class="message error">
                    <?php foreach ($pass_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($pass_success): ?>
                <div class="message success">
                    <p><?php echo htmlspecialchars($pass_success); ?></p>
                </div>
            <?php endif; ?>
            <form action="preferences.php" method="post">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="update_password" class="btn">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
        </div>

        <!-- Change Theme Section -->
        <div class="card">
            <h2 class="card-title">Change Theme</h2>
            <?php if (!empty($theme_errors)): ?>
                <div class="message error">
                    <?php foreach ($theme_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($theme_success): ?>
                <div class="message success">
                    <p><?php echo htmlspecialchars($theme_success); ?></p>
                </div>
            <?php endif; ?>
            <form action="preferences.php" method="post">
                <div class="form-group">
                    <label>Select Theme:</label>
                    <div class="theme-options">
                        <label class="theme-option">
                            <input type="radio" name="theme_preference" value="light" <?php echo ($current_theme === 'light') ? 'checked' : ''; ?>>
                            <div class="theme-preview light"></div>
                            <span>Light</span>
                        </label>
                        <label class="theme-option">
                            <input type="radio" name="theme_preference" value="dark" <?php echo ($current_theme === 'dark') ? 'checked' : ''; ?>>
                            <div class="theme-preview dark"></div>
                            <span>Dark</span>
                        </label>
                    </div>
                </div>
                <button type="submit" name="update_theme" class="btn">
                    <i class="fas fa-palette"></i> Update Theme
                </button>
            </form>
        </div>
    </div>
</body>
</html>