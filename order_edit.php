<?php
// order_edit.php
require_once 'db_connect.php'; // Auth, DB, Session, Theme

// --- Auth Check ---
if (!isset($_SESSION['user_id'])) { header("Location: login.php?auth_required=1"); exit(); }

$errors = [];
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$order_data = null; // Holds existing order data

if (!$order_id) {
    header("Location: orders_list.php?status=error&msg=Invalid Order ID.");
    exit();
}

// --- Fetch Existing Order ---
try {
    $stmtFetch = $pdo->prepare("SELECT * FROM orders WHERE orderID = :id AND is_active IN (0, 1)"); // Fetch even if inactive
    $stmtFetch->bindParam(':id', $order_id, PDO::PARAM_INT);
    $stmtFetch->execute();
    $order_data = $stmtFetch->fetch(PDO::FETCH_ASSOC);
    if (!$order_data) {
         header("Location: orders_list.php?status=error&msg=Order not found.");
         exit();
    }
} catch (PDOException $e) {
     error_log("Order Edit - Fetch Error: " . $e->getMessage());
     die("Error fetching order data."); // Simple error for now
}

// --- Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Repopulate $order_data with submitted values over fetched ones
    $submitted_data = $_POST;
    // Ensure non-editable fields from original data are kept if not submitted
    $submitted_data['orderID'] = $order_data['orderID']; // Keep original ID
    $submitted_data['order_type'] = $order_data['order_type']; // Type generally shouldn't change easily
    $order_data = array_merge($order_data, $submitted_data);

    // --- Retrieve & Trim ALL potential fields ---
    $customer_name = trim($_POST['customer_name'] ?? '');
    $order_type = $order_data['order_type']; // Use existing type
    $project_no = trim($_POST['project_no'] ?? '');
    $framework_order_no = trim($_POST['framework_order_no'] ?? '');
    $framework_order_position = trim($_POST['framework_order_position'] ?? '');
    $customer_article_no = trim($_POST['customer_article_no'] ?? '');
    $system_article_no = trim($_POST['system_article_no'] ?? '');
    $price_article = trim($_POST['price_article'] ?? '');
    $request_date_str = trim($_POST['request_date'] ?? '');
    $framework_quantity = trim($_POST['framework_quantity'] ?? '');
    $confirmed_date_str = trim($_POST['confirmed_date'] ?? '');
    $delivery_date_str = trim($_POST['delivery_date'] ?? '');
    $status = $_POST['status'] ?? null;
    $total_price = trim($_POST['total_price'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $delivered_quantity = trim($_POST['delivered_quantity'] ?? '');
    $remaining_quantity = trim($_POST['remaining_quantity'] ?? '');
    $need_to_pro_quantity = trim($_POST['need_to_pro_quantity'] ?? '');
    $called_quantity = trim($_POST['called_quantity'] ?? '');
    $uncalled_quantity = trim($_POST['uncalled_quantity'] ?? '');
    $delivery_year_str = trim($_POST['delivery_year'] ?? '');
    $delivery_month_str = trim($_POST['delivery_month'] ?? '');
    $stock_price = trim($_POST['stock_price'] ?? '');
    $uncalled_quantity_price = trim($_POST['uncalled_quantity_price'] ?? '');
    $remaining = trim($_POST['remaining'] ?? '');

    // --- Basic Validation (Similar to Add) ---
    if (empty($customer_name)) { $errors[] = "Customer Name is required."; }
    if (empty($status) || !in_array($status, ['Framework', 'Scheduled', 'Delivered', 'Delayed'])) { /* Add all valid statuses */ $errors[] = "Valid Status is required."; }
    if (empty($customer_article_no)) { $errors[] = "Customer Article Number is required."; }

    // Validate Dates
    $request_date = (!empty($request_date_str) && strtotime($request_date_str)) ? date('Y-m-d H:i:s', strtotime($request_date_str)) : null;
    $confirmed_date = (!empty($confirmed_date_str) && strtotime($confirmed_date_str)) ? date('Y-m-d H:i:s', strtotime($confirmed_date_str)) : null;
    $delivery_date = (!empty($delivery_date_str) && strtotime($delivery_date_str)) ? date('Y-m-d H:i:s', strtotime($delivery_date_str)) : null;
    $delivery_year = (!empty($delivery_year_str) && strtotime($delivery_year_str)) ? date('Y-m-d H:i:s', strtotime($delivery_year_str)) : null;
    $delivery_month = (!empty($delivery_month_str) && strtotime($delivery_month_str)) ? date('Y-m-d H:i:s', strtotime($delivery_month_str)) : null;

    // Add more validation as needed...

    // --- Update Database if No Errors ---
    if (empty($errors)) {
        try {
            // Build UPDATE SQL - Include ALL editable fields
            $sql = "UPDATE orders SET
                        customer_name = :customer_name, project_no = :project_no,
                        framework_order_no = :framework_order_no, framework_order_position = :framework_order_position,
                        customer_article_no = :customer_article_no, system_article_no = :system_article_no,
                        price_article = :price_article, request_date = :request_date,
                        framework_quantity = :framework_quantity, confirmed_date = :confirmed_date,
                        delivery_date = :delivery_date, status = :status, total_price = :total_price, note = :note,
                        delivered_quantity = :delivered_quantity, remaining_quantity = :remaining_quantity,
                        need_to_pro_quantity = :need_to_pro_quantity, called_quantity = :called_quantity,
                        uncalled_quantity = :uncalled_quantity, delivery_year = :delivery_year,
                        delivery_month = :delivery_month, stock_price = :stock_price,
                        uncalled_quantity_price = :uncalled_quantity_price, remaining = :remaining
                        -- Do NOT update order_type or is_active here (use order_set_active.php for that)
                    WHERE orderID = :orderID";
            $stmt = $pdo->prepare($sql);

            // Bind All Parameters (handle NULLs)
            $stmt->bindParam(':customer_name', $customer_name, PDO::PARAM_STR);
            $stmt->bindValue(':project_no', empty($project_no) ? null : $project_no, PDO::PARAM_STR);
            $stmt->bindValue(':framework_order_no', empty($framework_order_no) ? null : $framework_order_no, PDO::PARAM_STR);
            $stmt->bindValue(':framework_order_position', empty($framework_order_position) ? null : $framework_order_position, PDO::PARAM_STR);
            $stmt->bindValue(':customer_article_no', empty($customer_article_no) ? null : $customer_article_no, PDO::PARAM_STR);
            $stmt->bindValue(':system_article_no', empty($system_article_no) ? null : $system_article_no, PDO::PARAM_STR);
            $stmt->bindValue(':price_article', empty($price_article) ? null : $price_article, PDO::PARAM_STR);
            $stmt->bindValue(':request_date', $request_date, PDO::PARAM_STR);
            $stmt->bindValue(':framework_quantity', empty($framework_quantity) ? null : $framework_quantity, PDO::PARAM_STR);
            $stmt->bindValue(':confirmed_date', $confirmed_date, PDO::PARAM_STR);
            $stmt->bindValue(':delivery_date', $delivery_date, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':total_price', empty($total_price) ? null : $total_price, PDO::PARAM_STR);
            $stmt->bindValue(':note', empty($note) ? null : $note, PDO::PARAM_STR);
            $stmt->bindValue(':delivered_quantity', empty($delivered_quantity) ? null : $delivered_quantity, PDO::PARAM_STR);
            $stmt->bindValue(':remaining_quantity', empty($remaining_quantity) ? null : $remaining_quantity, PDO::PARAM_STR);
            $stmt->bindValue(':need_to_pro_quantity', empty($need_to_pro_quantity) ? null : $need_to_pro_quantity, PDO::PARAM_STR);
            $stmt->bindValue(':called_quantity', empty($called_quantity) ? null : $called_quantity, PDO::PARAM_STR);
            $stmt->bindValue(':uncalled_quantity', empty($uncalled_quantity) ? null : $uncalled_quantity, PDO::PARAM_STR);
            $stmt->bindValue(':delivery_year', $delivery_year, PDO::PARAM_STR);
            $stmt->bindValue(':delivery_month', $delivery_month, PDO::PARAM_STR);
            $stmt->bindValue(':stock_price', empty($stock_price) ? null : $stock_price, PDO::PARAM_STR);
            $stmt->bindValue(':uncalled_quantity_price', empty($uncalled_quantity_price) ? null : $uncalled_quantity_price, PDO::PARAM_STR);
            $stmt->bindValue(':remaining', empty($remaining) ? null : $remaining, PDO::PARAM_STR);

            // Bind the WHERE clause ID
            $stmt->bindParam(':orderID', $order_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                header("Location: orders_list.php?status=success&msg=Order updated successfully.");
                exit();
            } else {
                 $errors[] = "Failed to update order. Error: " . ($stmt->errorInfo()[2] ?? 'Unknown DB error');
            }

        } catch (PDOException $e) {
            $errors[] = "Database error during update: " . $e->getMessage();
            error_log("Order Edit Error: " . $e->getMessage());
        }
    }
    // If errors, $order_data contains merged data for form repopulation
}

// --- Prepare for Form Display ---
$page_title = "Edit Order (ID: ".htmlspecialchars($order_id).")";
$form_action = "order_edit.php?id=".$order_id;
$submit_text = "Update Order";
$order = $order_data; // Use fetched/merged data
$is_edit = true;

$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <style> /* Add basic form styling */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="date"], select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { min-height: 80px; }
        fieldset { margin-bottom: 20px; border: 1px solid #ccc; padding: 15px; border-radius: 4px; }
        legend { font-weight: bold; padding: 0 10px; }
        small { color: #777; }
        /* Dark theme form adjustments */
        body.dark-theme fieldset { border-color: #555;} body.dark-theme legend { color: #eee; }
        body.dark-theme label { color: #ccc; }
        body.dark-theme input, body.dark-theme select, body.dark-theme textarea { background-color: #444; border-color: #666; color: #eee; }
        body.dark-theme small { color: #aaa; }
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
<div class="container">
     <!-- Navigation -->
     <div class="nav-links" style=" margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
       <a href="dashboard.php">Dashboard</a> | <a href="articles_list.php">Articles</a> | <a href="orders_list.php">Orders</a> | <a href="preferences.php">Preferences</a> | <a href="logout.php" style="float: right;">Logout</a>
    </div>
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message error">
             <strong>Please fix the following errors:</strong>
            <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php include 'order_form_partial.php'; ?>

</div>
<!-- No jQuery needed for this basic version -->
</body>
</html>