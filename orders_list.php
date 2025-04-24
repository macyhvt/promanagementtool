<?php
require_once 'db_connect.php'; // Auth, DB, Session, Theme

// --- Auth Check ---
if (!isset($_SESSION['user_id'])) { header("Location: login.php?auth_required=1"); exit(); }

// --- Fetch Orders (Separated by Active Status) ---
$active_orders = [];
$inactive_orders = [];
$fetch_error = '';
try {
    // Fetch Active Orders
    $sql_active = "SELECT orderID, order_type, customer_name, project_no, status, request_date
                   FROM orders WHERE is_active = 1 ORDER BY orderID DESC LIMIT 200"; // Add LIMIT
    $stmt_active = $pdo->query($sql_active);
    $active_orders = $stmt_active->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Inactive Orders
    $sql_inactive = "SELECT orderID, order_type, customer_name, project_no, status, request_date
                     FROM orders WHERE is_active = 0 ORDER BY orderID DESC LIMIT 100"; // Add LIMIT
    $stmt_inactive = $pdo->query($sql_inactive);
    $inactive_orders = $stmt_inactive->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $fetch_error = "Error fetching orders: " . $e->getMessage();
    error_log("Order List Fetch Error: " . $e->getMessage());
}

$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';

// Helper to format dates nicely, handling potential NULLs
function formatDate($dateString) {
    if (empty($dateString) || $dateString === '0000-00-00 00:00:00') return 'N/A';
    try {
        return date('Y-m-d', strtotime($dateString)); // Format as YYYY-MM-DD
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <!-- Font Awesome if needed -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top;}
        th { background-color: #f2f2f2; font-weight: bold; }
        .action-links a { margin-right: 10px; text-decoration: none; }
        .action-links a.edit { color: #5cb85c; }
        .action-links a.deactivate { color: #f0ad4e; }
        .action-links a.activate { color: #337ab7; }
        .section-title { margin-top: 30px; border-bottom: 2px solid #eee; padding-bottom: 5px; font-size: 1.2em; }
        .no-orders { text-align: center; color: #888; padding: 15px; }
         /* Basic Dark Theme Styles */
        body.dark-theme th { background-color: #404040; border-color: #555; }
        body.dark-theme td { border-color: #555; }
        body.dark-theme tr:nth-child(even) { background-color: #3a3a3a; }
        body.dark-theme .section-title { border-bottom-color: #555; }
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
<div class="container" style="max-width: 1200px;">
    <!-- Navigation -->
    <div class="nav-links" style=" margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
       <a href="dashboard.php">Dashboard</a> | <a href="articles_list.php">Articles</a> | <a href="orders_list.php">Orders</a> | <a href="preferences.php">Preferences</a> | <a href="logout.php" style="float: right;">Logout</a>
    </div>

    <h2>Manage Orders</h2>

    <!-- Messages -->
    <div id="global-message-area" style="margin-bottom: 15px;">
        <?php if (isset($_GET['status'])): ?>
             <div class="message <?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($_GET['msg'] ?? ''); ?></div>
        <?php endif; ?>
        <?php if ($fetch_error): ?>
            <div class="message error"><p><?php echo htmlspecialchars($fetch_error); ?></p></div>
        <?php endif; ?>
    </div>

    <a href="order_add.php" class="button-like" style="display: inline-block; margin-bottom: 15px;"> <i class="fa fa-plus"></i> Add New Order</a>

    <!-- Active Orders -->
    <h3 class="section-title">Active Orders</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Type</th><th>Customer</th><th>Project</th><th>Status</th><th>Request Date</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($active_orders) && !$fetch_error): ?>
                <tr><td colspan="7" class="no-orders">No active orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($active_orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['orderID']); ?></td>
                    <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['project_no']); ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                    <td><?php echo formatDate($order['request_date']); ?></td>
                    <td class="action-links">
                        <a href="order_edit.php?id=<?php echo $order['orderID']; ?>" class="edit" title="Edit"><i class="fa fa-edit"></i> Edit</a>
                        <a href="order_set_active.php?id=<?php echo $order['orderID']; ?>&action=deactivate" class="deactivate" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this order?');"><i class="fa fa-toggle-off"></i> Deactivate</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

     <!-- Inactive Orders -->
    <h3 class="section-title">Inactive Orders</h3>
    <table>
        <thead>
             <tr>
                 <th>ID</th><th>Type</th><th>Customer</th><th>Project</th><th>Status</th><th>Request Date</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inactive_orders) && !$fetch_error): ?>
                <tr><td colspan="7" class="no-orders">No inactive orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($inactive_orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['orderID']); ?></td>
                    <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['project_no']); ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                     <td><?php echo formatDate($order['request_date']); ?></td>
                    <td class="action-links">
                        <a href="order_edit.php?id=<?php echo $order['orderID']; ?>" class="edit" title="Edit"><i class="fa fa-edit"></i> Edit</a>
                        <a href="order_set_active.php?id=<?php echo $order['orderID']; ?>&action=activate" class="activate" title="Activate" onclick="return confirm('Are you sure you want to reactivate this order?');"><i class="fa fa-toggle-on"></i> Activate</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>