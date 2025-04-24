<?php
// order_set_active.php
require_once 'db_connect.php'; // Auth, DB, Session

// --- Auth Check ---
if (!isset($_SESSION['user_id'])) {
    // Optional: Return JSON error if called via AJAX in future
    header("Location: login.php?auth_required=1");
    exit();
}

// --- Get Input ---
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS); // 'activate' or 'deactivate'

// --- Validate Input ---
if (!$order_id || $order_id <= 0) {
    header("Location: orders_list.php?status=error&msg=Invalid Order ID.");
    exit();
}
if ($action !== 'activate' && $action !== 'deactivate') {
    header("Location: orders_list.php?status=error&msg=Invalid action specified.");
    exit();
}

// Determine the new status (1 for active, 0 for inactive)
$new_active_status = ($action === 'activate') ? 1 : 0;
$action_past_tense = ($action === 'activate') ? 'activated' : 'deactivated';

// --- Update Database ---
try {
    // Check if order exists first (optional but good practice)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE orderID = :id");
    $stmtCheck->bindParam(':id', $order_id, PDO::PARAM_INT);
    $stmtCheck->execute();
    if ($stmtCheck->fetchColumn() == 0) {
        header("Location: orders_list.php?status=error&msg=Order not found.");
        exit();
    }

    // Perform the update
    $sql = "UPDATE orders SET is_active = :is_active WHERE orderID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':is_active', $new_active_status, PDO::PARAM_INT);
    $stmt->bindParam(':id', $order_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: orders_list.php?status=success&msg=Order successfully " . $action_past_tense . ".");
        exit();
    } else {
        header("Location: orders_list.php?status=error&msg=Failed to update order status.");
        exit();
    }

} catch (PDOException $e) {
    error_log("Order Set Active Error: " . $e->getMessage());
    header("Location: orders_list.php?status=error&msg=Database error updating status.");
    exit();
}
?>