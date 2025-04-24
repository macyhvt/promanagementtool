<?php
// article_edit.php
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get article ID from URL
$article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$article_id) {
    $_SESSION['message'] = 'Invalid article ID.';
    $_SESSION['message_type'] = 'alert-danger';
    header('Location: articles_list.php');
    exit();
}

// Fetch article details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, u_add.name as added_by_name, u_edit.name as edited_by_name
        FROM articles a
        LEFT JOIN users u_add ON a.added_by = u_add.userID
        LEFT JOIN users u_edit ON a.edit_by = u_edit.userID
        WHERE a.articleID = ?
    ");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        $_SESSION['message'] = 'Article not found.';
        $_SESSION['message_type'] = 'alert-danger';
        header('Location: articles_list.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching article: " . $e->getMessage());
    $_SESSION['message'] = 'Error fetching article details.';
    $_SESSION['message_type'] = 'alert-danger';
    header('Location: articles_list.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_STRING);
    $customer_article_no = filter_input(INPUT_POST, 'customer_article_no', FILTER_SANITIZE_STRING);
    $system_article_no = filter_input(INPUT_POST, 'system_article_no', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
    $parent_article_id = filter_input(INPUT_POST, 'parent_article_id', FILTER_VALIDATE_INT);

    //print_r($_POST);exit;
    try {
        $stmt = $pdo->prepare("
            UPDATE articles 
            SET customer_name = ?, 
                customer_article_no = ?, 
                system_article_no = ?, 
                price = ?, 
                status = ?, 
                parent_article_id = ?,
                edit_by = ?,
                edit_date = NOW()
            WHERE articleID = ?
        ");
        
        $result = $stmt->execute([
            $customer_name,
            $customer_article_no,
            $system_article_no,
            $price,
            $status,
            $parent_article_id,
            $_SESSION['user_id'],
            $article_id
        ]);

        if ($result) {
            $_SESSION['message'] = 'Article updated successfully!';
            $_SESSION['message_type'] = 'alert-success';
            header('Location: articles_list.php');
            exit();
        } else {
            throw new Exception('Update failed');
        }
    } catch (Exception $e) {
        error_log("Error updating article: " . $e->getMessage());
        $_SESSION['message'] = 'Error updating article.';
        $_SESSION['message_type'] = 'alert-danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Article</title>
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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

        .article-info {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--card-bg);
            border-radius: 4px;
            font-size: 0.9em;
            color: var(--text-muted);
        }

        .article-info p {
            margin: 5px 0;
        }
        .link-white{
            color: #fff;
            text-decoration:unset;
        }
    </style>
</head>
<body>
    <nav class="top-menu">
        <div class="menu-items">
            <div class="menu-left">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="../orders/index.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="articles_list.php" class="menu-item active">
                    <i class="fas fa-box"></i> Articles
                </a>
            </div>
            <div class="menu-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <a class="link-white" href="preferences.php"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></a>
                    </div>
                        <a class="link-white" href="preferences.php"><span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span></a>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Edit Article</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" 
                           value="<?php echo htmlspecialchars($article['customer_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="customer_article_no">Customer Article No</label>
                    <input type="text" id="customer_article_no" name="customer_article_no" 
                           value="<?php echo htmlspecialchars($article['customer_article_no']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="system_article_no">System Article No</label>
                    <input type="text" id="system_article_no" name="system_article_no" 
                           value="<?php echo htmlspecialchars($article['system_article_no']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="text" id="price" name="price" 
                           value="<?php echo htmlspecialchars($article['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="1" <?php echo $article['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $article['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <!-- <div class="form-group">
                    <label for="parent_article_id">Parent Article ID (Optional)</label>
                    <input type="text" id="parent_article_id" name="parent_article_id" 
                           value="<?php echo htmlspecialchars($article['parent_article_id'] ?? ''); ?>">
                </div> -->

                <div class="btn-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="articles_list.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>

            <div class="article-info">
                <p><strong>Added By:</strong> <?php echo htmlspecialchars($article['added_by_name'] ?? 'Unknown'); ?></p>
                <p><strong>Added Date:</strong> <?php echo date('Y-m-d H:i', strtotime($article['added_date'])); ?></p>
                <p><strong>Last Edited By:</strong> <?php echo htmlspecialchars($article['edited_by_name'] ?? 'Unknown'); ?></p>
                <p><strong>Last Edit Date:</strong> <?php echo $article['edit_date'] ? date('Y-m-d H:i', strtotime($article['edit_date'])) : 'Never'; ?></p>
            </div>
        </div>
    </div>
</body>
</html>