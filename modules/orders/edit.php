<?php
require '../db_connect.php';


$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$order = null;
$error_message = '';

if (!$order_id) {
    $_SESSION['message'] = 'Invalid Order ID provided.';
    $_SESSION['message_type'] = 'alert-danger';
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders_initial WHERE orderID = ? AND is_active = 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['message'] = 'Order not found or is inactive.';
        $_SESSION['message_type'] = 'alert-danger';
        header('Location: index.php');
        exit;
    }
} catch (\PDOException $e) {
    $error_message = "Error fetching order details: " . $e->getMessage();
    // Log error
}

// Format datetime for datetime-local input
$request_date_formatted = '';
if (!empty($order['request_date'])) {
    try {
        $date = new DateTime($order['request_date']);
        $request_date_formatted = $date->format('Y-m-d\TH:i');
    } catch (Exception $ex) {
        // Handle invalid date format if necessary
        $request_date_formatted = '';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Framework Order #<?= htmlspecialchars($order['orderID']); ?></h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($order): // Only show form if order was found ?>
        <form action="actions.php?action=update&id=<?= htmlspecialchars($order['orderID']); ?>" method="POST">

            <label for="order_type">Order Type:</label>
            <select id="order_type" name="order_type" required>
                <option value="F" <?= ($order['order_type'] ?? '') == 'F' ? 'selected' : ''; ?>>F</option>
                <option value="C" <?= ($order['order_type'] ?? '') == 'C' ? 'selected' : ''; ?>>C</option>
                <option value="N" <?= ($order['order_type'] ?? '') == 'N' ? 'selected' : ''; ?>>N</option>
            </select>

            <label for="project_no">Project No:</label>
            <input type="text" id="project_no" name="project_no" maxlength="200" value="<?= htmlspecialchars($order['project_no'] ?? ''); ?>">

            <label for="framework_order_no">Framework Order No:</label>
            <input type="text" id="framework_order_no" name="framework_order_no" maxlength="100" value="<?= htmlspecialchars($order['framework_order_no'] ?? ''); ?>">

            <label for="framework_order_position">Framework Order Position:</label>
            <input type="text" id="framework_order_position" name="framework_order_position" maxlength="30" value="<?= htmlspecialchars($order['framework_order_position'] ?? ''); ?>">

            <label for="customer_article_no">Customer Article No:</label>
            <input type="text" id="customer_article_no" name="customer_article_no" maxlength="100" value="<?= htmlspecialchars($order['customer_article_no'] ?? ''); ?>">

            <label for="system_article_no">System Article No:</label>
            <input type="text" id="system_article_no" name="system_article_no" maxlength="100" value="<?= htmlspecialchars($order['system_article_no'] ?? ''); ?>">

            <label for="price_article">Price Article:</label>
            <input type="text" id="price_article" name="price_article" maxlength="30" value="<?= htmlspecialchars($order['price_article'] ?? ''); ?>">

            <label for="request_date">Request Date:</label>
            <input type="datetime-local" id="request_date" name="request_date" value="<?= htmlspecialchars($request_date_formatted); ?>">

            <label for="framework_quantity">Framework Quantity:</label>
            <input type="text" id="framework_quantity" name="framework_quantity" maxlength="30" value="<?= htmlspecialchars($order['framework_quantity'] ?? ''); ?>">

            <label for="status">Status:</label>
            <select id="status" name="status">
                <!-- Add all your enum values here -->
                <option value="Framework" <?= ($order['status'] ?? '') == 'Framework' ? 'selected' : ''; ?>>Framework</option>
                <option value="Scheduled" <?= ($order['status'] ?? '') == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="Delivered" <?= ($order['status'] ?? '') == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="Delay" <?= ($order['status'] ?? '') == 'Delay' ? 'selected' : ''; ?>>Delay</option>
                <!-- Add others if they exist... -->
            </select>

            <label for="total_price">Total Price:</label>
            <input type="text" id="total_price" name="total_price" maxlength="30" value="<?= htmlspecialchars($order['total_price'] ?? ''); ?>">

            <label for="note">Note:</label>
            <textarea id="note" name="note"><?= htmlspecialchars($order['note'] ?? ''); ?></textarea>

            <label for="delivered_quantity">Delivered Quantity:</label>
            <input type="text" id="delivered_quantity" name="delivered_quantity" maxlength="30" value="<?= htmlspecialchars($order['delivered_quantity'] ?? ''); ?>">

            <label for="remaining_quantity">Remaining Quantity:</label>
            <input type="text" id="remaining_quantity" name="remaining_quantity" maxlength="30" value="<?= htmlspecialchars($order['remaining_quantity'] ?? ''); ?>">

            <label for="need_to_pro_quantity">Need to Pro Quantity:</label>
            <input type="text" id="need_to_pro_quantity" name="need_to_pro_quantity" maxlength="30" value="<?= htmlspecialchars($order['need_to_pro_quantity'] ?? ''); ?>">

            <label for="called_quantity">Called Quantity:</label>
            <input type="text" id="called_quantity" name="called_quantity" maxlength="30" value="<?= htmlspecialchars($order['called_quantity'] ?? ''); ?>">

            <label for="uncalled_quantity">Uncalled Quantity:</label>
            <input type="text" id="uncalled_quantity" name="uncalled_quantity" maxlength="30" value="<?= htmlspecialchars($order['uncalled_quantity'] ?? ''); ?>">

            <label for="stock_price">Stock Price:</label>
            <input type="text" id="stock_price" name="stock_price" maxlength="30" value="<?= htmlspecialchars($order['stock_price'] ?? ''); ?>">

            <label for="uncalled_quantity_price">Uncalled Quantity Price:</label>
            <input type="text" id="uncalled_quantity_price" name="uncalled_quantity_price" maxlength="30" value="<?= htmlspecialchars($order['uncalled_quantity_price'] ?? ''); ?>">

            <label for="remaining">Remaining:</label>
            <input type="text" id="remaining" name="remaining" maxlength="20" value="<?= htmlspecialchars($order['remaining'] ?? ''); ?>">

            <button type="submit" class="btn btn-edit">Update Order</button>
            <a href="index.php" class="btn">Cancel</a>
        </form>
        <?php else: ?>
            <p>Could not load order details.</p>
            <a href="index.php" class="btn">Back to List</a>
        <?php endif; ?>
    </div>
</body>
</html>