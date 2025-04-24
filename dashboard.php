<?php
// dashboard.php
require_once 'db_connect.php'; // Ensures session_start() is called

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? 'No email';
// Get theme class for the body tag
$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
            max-width: 1200px;
            margin: 80px auto 20px;
            padding: 0 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-icon {
            font-size: 1.5rem;
            color: var(--accent-color);
        }

        .card-content {
            color: var(--text-muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background-color: var(--secondary-color);
            border-radius: 6px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
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
        .link-white{
            color: #fff;
            text-decoration:unset;
        }


        /* --- Footer Styles --- */
        footer {
            background-color: var(--secondary-color); /* Match top menu */
            color: var(--text-muted);
            text-align: center;
            padding: 1rem 0;
            margin-top: auto; /* Pushes footer to bottom when content is short */
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1); /* Subtle shadow on top */
            width: 100%; /* Ensure footer spans full width */
        }

        footer p {
            margin: 0;
            font-size: 0.9rem;
        }
        /* --- End Footer Styles --- */
    </style>
</head>
<body>
    <nav class="top-menu">
        <div class="menu-items">
            <div class="menu-left">
                <a href="dashboard.php" class="menu-item active">
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
                        <a class="link-white" href="preferences.php"><?php echo strtoupper(substr($user_name, 0, 1)); ?></a>
                        </div>
                        <a class="link-white" href="preferences.php"><span><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                    <i class="fas fa-bolt card-icon"></i>
                </div>
                <div class="card-content">
                    <div class="stats-grid">
                        <a href="orders/create.php" class="stat-item">
                            <div class="stat-value"><i class="fas fa-plus"></i></div>
                            <div class="stat-label">New Order</div>
                        </a>
                        <a href="articles_list.php" class="stat-item">
                            <div class="stat-value"><i class="fas fa-search"></i></div>
                            <div class="stat-label">Search Articles</div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Activity</h2>
                    <i class="fas fa-history card-icon"></i>
                </div>
                <div class="card-content">
                    <p>No recent activity to display.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">System Status</h2>
                    <i class="fas fa-server card-icon"></i>
                </div>
                <div class="card-content">
                    <p>All systems operational.</p>
                </div>
            </div>
        </div>
    </div>
     <!-- Footer -->
    <footer>
        <p>© <?php echo date('Y'); ?> Your Company Name. All Rights Reserved.</p>
    </footer>
    <!-- End Footer -->
</body>
</html>