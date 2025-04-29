<?php
session_start();
require '../db_connect.php'; // db_connect.php should call session_start() if needed, but having it here is safe.

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// --- Pagination Settings ---
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// --- Calculate Total Records for Pagination ---
// Only count active orders as per the original query's WHERE clause
$countStmt = $pdo->query("SELECT COUNT(*) FROM orders_initial WHERE is_active = 1");
$total_records = $countStmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// --- Adjust current page if it's out of bounds ---
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
} elseif ($current_page < 1) {
    $current_page = 1;
}

// --- Calculate Offset ---
$offset = ($current_page - 1) * $records_per_page;

// --- Fetch Orders for the Current Page with Parent-Child Relationship ---
$sql = "
    SELECT o.*, 
           a.customer_article_no as article_customer_article_no,
           CASE
               WHEN o.order_type = 'F' THEN 'F'
               WHEN o.order_type = 'C' THEN 'C'
               WHEN o.order_type = 'N' THEN 'N'
           END as order_type_name,
           CASE
               WHEN o.is_active = 1 THEN 'Active'
               WHEN o.is_active = 0 THEN 'Inactive'
           END as status_name
    FROM orders_initial o
    LEFT JOIN articles a ON o.system_article_no = a.system_article_no
    WHERE o.is_active = 1 
    AND (o.order_type IN ('F', 'N') OR (o.order_type = 'C' AND o.parent_order_id IS NULL))
    ORDER BY COALESCE(o.parent_order_id, o.orderID), o.order_type, o.orderID
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all Type C orders separately to be displayed under their parent F orders
$sql_c_orders = "
    SELECT o.*, 
           a.customer_article_no as article_customer_article_no,
           CASE
               WHEN o.order_type = 'C' THEN 'C'
           END as order_type_name,
           CASE
               WHEN o.is_active = 1 THEN 'Active'
               WHEN o.is_active = 0 THEN 'Inactive'
           END as status_name
    FROM orders_initial o
    LEFT JOIN articles a ON o.system_article_no = a.system_article_no
    WHERE o.is_active = 1 
    AND o.order_type = 'C'
    AND o.parent_order_id IS NOT NULL
    ORDER BY o.orderID
";

$stmt_c = $pdo->prepare($sql_c_orders);
$stmt_c->execute();
$c_orders = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

// Create a lookup array for Type C orders by parent_order_id
$c_orders_by_parent = [];
foreach ($c_orders as $c_order) {
    if (!isset($c_orders_by_parent[$c_order['parent_order_id']])) {
        $c_orders_by_parent[$c_order['parent_order_id']] = [];
    }
    $c_orders_by_parent[$c_order['parent_order_id']][] = $c_order;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
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
            display: flex; /* Added for footer potential */
            flex-direction: column; /* Added for footer potential */
            min-height: 100vh; /* Added for footer potential */
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
            margin: 80px auto 20px; /* Adjusted top margin for fixed menu */
            padding: 0 20px;
            flex-grow: 1; /* Added for footer potential */
        }

        .card {
            background-color: var(--secondary-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-header { /* Style for header section within card */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.5rem; /* Slightly larger title */
            color: var(--text-color);
            margin: 0; /* Remove default h1 margin */
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
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        /* ... other button styles ... */
        .btn-danger { background-color: var(--danger-color); }
        .btn-danger:hover { background-color: #c0392b; }
        .btn-warning { background-color: var(--warning-color); color: #000; }
        .btn-warning:hover { background-color: #f39c12; }

        .table-container {
            overflow-x: auto; /* Allows horizontal scrolling on small screens */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap; /* Prevent text wrapping, rely on scroll */
        }

        th {
            background-color: var(--card-bg);
            font-weight: 600;
        }

        tr:hover {
            background-color: var(--card-bg);
        }

        .status-active { color: var(--success-color); font-weight: bold; }
        .status-inactive { color: var(--danger-color); font-weight: bold; }

        /* ... Editable, Saving, Error styles ... */
        .editable { cursor: pointer; transition: background-color 0.3s; }
        .editable:hover { background-color: var(--card-bg); }
        .editing { background-color: var(--card-bg); }
        .editing input { width: 100%; padding: 5px; background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-color); }
        .saving { opacity: 0.7; }
        .error { color: var(--danger-color); }

        /* ... Message styles ... */
        .message { padding: 10px 15px; margin: 10px 0; border-radius: 4px; }
        .message.success { background-color: var(--success-color); color: white; }
        .message.error { background-color: var(--danger-color); color: white; }

        .search-container {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
            font-size: 16px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .action-links {
            display: flex;
            gap: 5px; /* Smaller gap */
        }

        .action-links a {
            text-decoration: none;
            padding: 4px 8px; /* Smaller buttons */
            border-radius: 4px;
            font-size: 13px; /* Smaller font */
            display: inline-flex; /* Align icon and text */
            align-items: center;
            gap: 4px;
            white-space: nowrap; /* Prevent wrapping */
        }

        .action-links a i {
             margin-right: 3px; /* Space between icon and text */
        }

        .action-links a.edit { background-color: var(--accent-color); color: white; }
        .action-links a.split { background-color: var(--warning-color); color: #000; }
        .action-links a.deactivate { background-color: var(--danger-color); color: white; }
        .action-links a.activate { background-color: var(--success-color); color: white; }

        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .logout-btn { background-color: var(--danger-color); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .logout-btn:hover { background-color: #c0392b; }
        .link-white { color: #fff; text-decoration: none; }
        .link-white:hover { text-decoration: underline; }

        /* --- Pagination Styles --- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 25px;
            padding: 10px 0;
            list-style: none;
        }

        .pagination li {
            margin: 0 5px;
        }

        .pagination li a, .pagination li span {
            display: block;
            padding: 8px 15px;
            color: var(--text-color);
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
        }

        .pagination li a:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        .pagination li.active span {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            font-weight: bold;
            cursor: default;
        }

        .pagination li.disabled span {
            background-color: var(--card-bg);
            color: var(--text-muted);
            border-color: var(--border-color);
            cursor: not-allowed;
            opacity: 0.6;
        }
        /* --- End Pagination Styles --- */

        /* Footer placeholder style */
        /* footer { background-color: var(--secondary-color); color: var(--text-muted); text-align: center; padding: 1rem 0; margin-top: auto; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); width: 100%; } */
        /* footer p { margin: 0; font-size: 0.9rem; } */

    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editableCells = document.querySelectorAll('td:not(.action-links)');
            let currentCell = null;

            editableCells.forEach(cell => {
                cell.classList.add('editable');

                cell.addEventListener('dblclick', function() {
                    if (currentCell) {
                        saveCell(currentCell);
                    }

                    const originalValue = this.textContent.trim();
                    const input = document.createElement('input');
                    input.value = originalValue;
                    input.className = 'edit-input';

                    this.innerHTML = '';
                    this.appendChild(input);
                    this.classList.add('editing');
                    input.focus();

                    currentCell = this;

                    input.addEventListener('blur', function() {
                        saveCell(cell);
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            saveCell(cell);
                        } else if (e.key === 'Escape') {
                            cell.textContent = originalValue;
                            cell.classList.remove('editing');
                            currentCell = null;
                        }
                    });
                });
            });

            function saveCell(cell) {
                if (!cell.classList.contains('editing')) return;

                const input = cell.querySelector('input');
                const newValue = input.value.trim();
                const orderId = cell.closest('tr').querySelector('td:first-child').textContent;
                const columnName = cell.getAttribute('data-column');

                cell.classList.remove('editing');
                cell.classList.add('saving');

                fetch('actions.php?action=update_cell', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&column=${columnName}&value=${encodeURIComponent(newValue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cell.textContent = newValue;
                        cell.classList.remove('saving');
                    } else {
                        cell.textContent = input.value;
                        cell.classList.remove('saving');
                        cell.classList.add('error');
                        setTimeout(() => cell.classList.remove('error'), 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    cell.textContent = input.value;
                    cell.classList.remove('saving');
                    cell.classList.add('error');
                    setTimeout(() => cell.classList.remove('error'), 2000);
                });

                currentCell = null;
            }
        });
    </script>
</head>
<body>
    <nav class="top-menu">
        <div class="menu-items">
            <div class="menu-left">
                <a href="../dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="index.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="../articles_list.php" class="menu-item">
                    <i class="fas fa-box"></i> Articles
                </a>
            </div>
            <div class="menu-right">
                <div class="user-info">
                    <a class="link-white" href="../preferences.php" title="Go to Preferences">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    </a>
                     <a class="link-white" href="../preferences.php">
                         <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                     </a>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width:100%">
        <div class="card">
            <div class="card-header">
                 <h1 class="card-title">Orders Management</h1>
                 <a href="create.php" class="btn btn-success">
                     <i class="fas fa-plus"></i> Create New Order
                 </a>
             </div>


            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search orders within this page...">
            </div>
             <!-- Optional: Display record count -->
             <p style="margin-bottom: 15px; color: var(--text-muted);">
                Showing records <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?>
            </p>


            <div class="table-container">
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Order No</th>
                            <th>Pos</th> <!-- Shortened -->
                            <th>Project No</th>
                            <th>Cust. Art. No</th> <!-- Shortened -->
                            <th>Sys. Art. No</th> <!-- Shortened -->
                            <th>Qty</th> <!-- Shortened -->
                            <th>Price</th>
                            <th>Req. Date</th> <!-- Shortened -->
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="12" style="text-align: center; color: var(--text-muted);">No active orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php if ($order['order_type'] === 'F'): ?>
                                    <tr style="background-color: <?php echo isset($c_orders_by_parent[$order['orderID']]) ? '#027e20' : 'inherit'; ?>;">
                                        <td data-column="id"><?php echo htmlspecialchars($order['orderID']); ?></td>
                                        <td data-column="type"><?php echo htmlspecialchars($order['order_type_name']); ?></td>
                                        <td data-column="order_no"><?php echo htmlspecialchars($order['framework_order_no']); ?></td>
                                        <td data-column="position"><?php echo htmlspecialchars($order['framework_order_position']); ?></td>
                                        <td data-column="project_no"><?php echo htmlspecialchars($order['project_no']); ?></td>
                                        <td data-column="customer_article"><?php echo htmlspecialchars($order['article_customer_article_no']); ?></td>
                                        <td data-column="system_article"><?php echo htmlspecialchars($order['system_article_no']); ?></td>
                                        <td data-column="quantity"><?php echo htmlspecialchars($order['framework_quantity']); ?></td>
                                        <td data-column="price"><?php echo htmlspecialchars(number_format((float)$order['price_article'], 2, '.', '')); ?></td>
                                        <td data-column="request_date"><?php echo htmlspecialchars($order['request_date']); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($order['status_name']); ?>">
                                                <?php echo htmlspecialchars($order['status_name']); ?>
                                            </span>
                                        </td>
                                        <td class="action-links">
                                            <a href="actions.php?action=deactivate&id=<?php echo $order['orderID']; ?>" class="deactivate" onclick="return confirm('Are you sure you want to deactivate this order?');" title="Deactivate Order">
                                                <i class="fas fa-toggle-off"></i> Deactivate
                                            </a>
                                        </td>
                                    </tr>
                                    <?php if (isset($c_orders_by_parent[$order['orderID']])): ?>
                                        <?php foreach ($c_orders_by_parent[$order['orderID']] as $c_order): ?>
                                            <tr style="background-color: #e98929;">
                                                <td style="padding-left: 20px;" data-column="id">&mdash; <?php echo htmlspecialchars($c_order['orderID']); ?></td>
                                                <td data-column="type"><?php echo htmlspecialchars($c_order['order_type_name']); ?></td>
                                                <td data-column="order_no"><?php echo htmlspecialchars($c_order['framework_order_no']); ?></td>
                                                <td data-column="position"><?php echo htmlspecialchars($c_order['framework_order_position']); ?></td>
                                                <td data-column="project_no"><?php echo htmlspecialchars($c_order['project_no']); ?></td>
                                                <td data-column="customer_article"><?php echo htmlspecialchars($c_order['article_customer_article_no']); ?></td>
                                                <td data-column="system_article"><?php echo htmlspecialchars($c_order['system_article_no']); ?></td>
                                                <td data-column="quantity"><?php echo htmlspecialchars($c_order['framework_quantity']); ?></td>
                                                <td data-column="price"><?php echo htmlspecialchars(number_format((float)$c_order['price_article'], 2, '.', '')); ?></td>
                                                <td data-column="request_date"><?php echo htmlspecialchars($c_order['request_date']); ?></td>
                                                <td>
                                                    <span class="status-<?php echo strtolower($c_order['status_name']); ?>">
                                                        <?php echo htmlspecialchars($c_order['status_name']); ?>
                                                    </span>
                                                </td>
                                                <td class="action-links">
                                                    <a href="actions.php?action=deactivate&id=<?php echo $c_order['orderID']; ?>" class="deactivate" onclick="return confirm('Are you sure you want to deactivate this order?');" title="Deactivate Order">
                                                        <i class="fas fa-toggle-off"></i> Deactivate
                                                    </a>
                                                    <a href="actions.php?action=split&id=<?php echo $c_order['orderID']; ?>" class="split" onclick="return confirm('Are you sure you want to split this order?');" title="Split Order">
                                                        <i class="fas fa-code-branch"></i> Split
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php elseif ($order['order_type'] === 'N'): ?>
                                    <tr>
                                        <td data-column="id"><?php echo htmlspecialchars($order['orderID']); ?></td>
                                        <td data-column="type"><?php echo htmlspecialchars($order['order_type_name']); ?></td>
                                        <td data-column="order_no"><?php echo htmlspecialchars($order['framework_order_no']); ?></td>
                                        <td data-column="position"><?php echo htmlspecialchars($order['framework_order_position']); ?></td>
                                        <td data-column="project_no"><?php echo htmlspecialchars($order['project_no']); ?></td>
                                        <td data-column="customer_article"><?php echo htmlspecialchars($order['article_customer_article_no']); ?></td>
                                        <td data-column="system_article"><?php echo htmlspecialchars($order['system_article_no']); ?></td>
                                        <td data-column="quantity"><?php echo htmlspecialchars($order['framework_quantity']); ?></td>
                                        <td data-column="price"><?php echo htmlspecialchars(number_format((float)$order['price_article'], 2, '.', '')); ?></td>
                                        <td data-column="request_date"><?php echo htmlspecialchars($order['request_date']); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($order['status_name']); ?>">
                                                <?php echo htmlspecialchars($order['status_name']); ?>
                                            </span>
                                        </td>
                                        <td class="action-links">
                                            <a href="actions.php?action=deactivate&id=<?php echo $order['orderID']; ?>" class="deactivate" onclick="return confirm('Are you sure you want to deactivate this order?');" title="Deactivate Order">
                                                <i class="fas fa-toggle-off"></i> Deactivate
                                            </a>
                                            <a href="actions.php?action=split&id=<?php echo $order['orderID']; ?>" class="split" onclick="return confirm('Are you sure you want to split this order?');" title="Split Order">
                                                <i class="fas fa-code-branch"></i> Split
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <?php if ($current_page > 1): ?>
                            <a class="page-link" href="index.php?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">«</span> 
                            </a>
                        <?php else: ?>
                            <span class="page-link" aria-hidden="true">«</span> 
                        <?php endif; ?>
                    </li>

                    <!-- Page Number Links -->
                    <?php
                        // Determine pagination range (optional: limit number of page links shown)
                        $link_limit = 5; // Max number of page links to show around current page
                        $start_page = max(1, $current_page - floor($link_limit / 2));
                        $end_page = min($total_pages, $start_page + $link_limit - 1);
                         // Adjust if we are near the end
                        if ($end_page - $start_page + 1 < $link_limit && $start_page > 1) {
                            $start_page = max(1, $end_page - $link_limit + 1);
                        }

                        // Ellipsis at the beginning
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="index.php?page=1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Actual page numbers
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php if ($i == $current_page): ?>
                                    <span class="page-link"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="index.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            </li>
                    <?php endfor;

                        // Ellipsis at the end
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                             echo '<li class="page-item"><a class="page-link" href="index.php?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                    ?>

                    <!-- Next Button -->
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <?php if ($current_page < $total_pages): ?>
                            <a class="page-link" href="index.php?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                 <span aria-hidden="true">»</span>
                            </a>
                        <?php else: ?>
                            <span class="page-link" aria-hidden="true"> »</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <!-- End Pagination -->

        </div> <!-- End Card -->
    </div> <!-- End Container -->

    <!-- Optional: Footer could go here -->
    <!--
    <footer>
        <p>© <?php echo date('Y'); ?> Your Company Name. All Rights Reserved.</p>
    </footer>
    -->

    <script>
        // Search functionality - NOTE: This only searches the *current page*
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tbody tr');

            rows.forEach(row => {
                // Check if it's the 'no orders found' row
                if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                    return; // Skip the 'no results' row
                }
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>
</body>
</html>